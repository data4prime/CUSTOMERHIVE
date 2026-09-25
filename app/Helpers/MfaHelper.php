<?php

namespace App\Helpers;

use App\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Logica MFA (TOTP + Email OTP + dispositivi fidati + recovery),
 * centralizzata qui perche' riusata sia da AdminController (login/verify/
 * recovery) sia da AdminCmsUsersController (enrollment self-service). Vedi
 * il piano completo in docs/refactoring/095-* e successivi.
 */
class MfaHelper
{
    // Scorrevole: rinnovato ad ogni uso valido, non fisso dalla creazione -
    // decisione utente (30 giorni), vedi 095/096.
    const TRUSTED_DEVICE_DAYS = 30;
    const EMAIL_OTP_MINUTES = 10;
    const RECOVERY_DELAY_MINUTES = 30;
    const RECOVERY_CODES_COUNT = 10;
    const TRUSTED_DEVICE_COOKIE = 'ch_mfa_device';

    protected static function engine(): Google2FA
    {
        return new Google2FA();
    }

    public static function generateSecret(): string
    {
        return self::engine()->generateSecretKey();
    }

    public static function encryptSecret(string $secret): string
    {
        // Crypt/APP_KEY (non una chiave dedicata): decisione utente in 095,
        // per non impattare il processo di aggiornamento cliente.
        return Crypt::encryptString($secret);
    }

    public static function decryptSecret(?string $encrypted): ?string
    {
        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function qrCodeSvg(string $issuer, string $holder, string $secret): string
    {
        $url = self::engine()->getQRCodeUrl($issuer, $holder, $secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Verifica un codice TOTP contro un secret NON ancora persistito
     * (enrollment, prima conferma prima del salvataggio). Ritorna il
     * timeslice usato (da salvare come punto di partenza anti-replay) o
     * false se il codice non e' valido.
     *
     * @return int|false
     */
    public static function verifyNewSecret(string $secret, string $code)
    {
        return self::engine()->verifyKeyNewer($secret, $code, 0);
    }

    /**
     * Verifica il TOTP dell'utente gia' attivo e consuma il timeslice
     * (anti-replay: rifiuta un codice gia' usato in quella o in una
     * finestra precedente). Tolleranza +-1 finestra (default della
     * libreria).
     */
    public static function verifyAndConsume(User $user, string $code): bool
    {
        $secret = self::decryptSecret($user->two_factor_secret);
        if (! $secret) {
            return false;
        }

        $oldTimestamp = (int) ($user->two_factor_last_timeslice ?? 0);
        $newTimestamp = self::engine()->verifyKeyNewer($secret, $code, $oldTimestamp);

        if ($newTimestamp === false) {
            return false;
        }

        $user->two_factor_last_timeslice = $newTimestamp;
        $user->save();

        return true;
    }

    public static function generateRecoveryCodes(int $count = self::RECOVERY_CODES_COUNT): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    /**
     * Sostituisce tutti i backup codes esistenti dell'utente con quelli
     * appena generati (hashati, mai salvati in chiaro).
     */
    public static function storeRecoveryCodes(User $user, array $plaintextCodes): void
    {
        DB::table('mfa_recovery_codes')->where('user_id', $user->id)->delete();

        $now = now();
        $rows = array_map(function ($code) use ($user, $now) {
            return [
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $plaintextCodes);

        DB::table('mfa_recovery_codes')->insert($rows);
    }

    /**
     * Consuma un backup code se valido e non ancora usato (monouso).
     */
    public static function consumeRecoveryCode(User $user, string $code): bool
    {
        $rows = DB::table('mfa_recovery_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->get();

        foreach ($rows as $row) {
            if (Hash::check($code, $row->code_hash)) {
                DB::table('mfa_recovery_codes')->where('id', $row->id)->update(['used_at' => now()]);

                return true;
            }
        }

        return false;
    }

    public static function hasActiveTotp(User $user): bool
    {
        return ! empty($user->two_factor_confirmed_at);
    }

    /**
     * Disattiva l'MFA e ripulisce tutto cio' che dipende da un TOTP attivo
     * (backup codes, dispositivi fidati) - usato sia dal profilo
     * (disattivazione volontaria) sia dal recovery (Fase 3).
     */
    public static function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_last_timeslice = null;
        $user->save();

        DB::table('mfa_recovery_codes')->where('user_id', $user->id)->delete();
        self::revokeAllTrustedDevices($user);
    }

    // --- Dispositivi fidati: solo ramo email OTP, non bypassano mai un TOTP attivo ---

    public static function hasTrustedDevice(User $user, Request $request): bool
    {
        $cookie = $request->cookie(self::TRUSTED_DEVICE_COOKIE);
        if (! $cookie || strpos($cookie, ':') === false) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        $row = DB::table('mfa_trusted_devices')
            ->where('user_id', $user->id)
            ->where('selector', $selector)
            ->where('trusted_until', '>=', now())
            ->first();

        if (! $row || ! hash_equals($row->validator_hash, hash('sha256', $validator))) {
            return false;
        }

        // Scorrevole: ogni uso valido rinnova la scadenza di altri 30 giorni
        // dall'ultimo utilizzo, non fissa dalla creazione (decisione utente).
        DB::table('mfa_trusted_devices')->where('id', $row->id)->update([
            'trusted_until' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Crea la riga del dispositivo fidato e ritorna il cookie da accodare
     * alla response (Cookie::queue()/->withCookie()) - il chiamante decide
     * quando accodarlo, questo metodo non ha side effect sulla response.
     */
    public static function issueTrustedDeviceCookie(User $user, Request $request)
    {
        $selector = Str::random(16);
        $validator = Str::random(32);

        DB::table('mfa_trusted_devices')->insert([
            'user_id' => $user->id,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'trusted_until' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Durata del cookie: margine oltre la scadenza scorrevole lato DB,
        // che resta la fonte di verita' reale (il cookie da solo non basta,
        // serve anche la riga in mfa_trusted_devices non scaduta).
        return cookie(
            self::TRUSTED_DEVICE_COOKIE,
            $selector.':'.$validator,
            60 * 24 * (self::TRUSTED_DEVICE_DAYS + 30)
        );
    }

    public static function revokeAllTrustedDevices(User $user): void
    {
        DB::table('mfa_trusted_devices')->where('user_id', $user->id)->delete();
    }

    /**
     * Elenco dei dispositivi fidati dell'utente (solo quelli non ancora
     * scaduti), piu' recenti prima - per la sezione "dispositivi da cui hai
     * effettuato l'accesso" sul profilo.
     */
    public static function listTrustedDevices(User $user)
    {
        return DB::table('mfa_trusted_devices')
            ->where('user_id', $user->id)
            ->where('trusted_until', '>=', now())
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * Revoca un singolo dispositivo fidato. Scoped a user_id: chi chiama
     * deve passare l'utente corrente, cosi' non e' possibile revocare il
     * dispositivo di un altro utente indovinando l'id della riga.
     */
    public static function revokeTrustedDevice(User $user, int $deviceId): bool
    {
        return DB::table('mfa_trusted_devices')
            ->where('user_id', $user->id)
            ->where('id', $deviceId)
            ->delete() > 0;
    }

    // --- Email OTP: baseline/step-up solo per chi non ha il TOTP attivo ---

    /**
     * Ritorna true se l'email e' stata effettivamente inviata (o almeno
     * consegnata al transport senza errori), false se l'invio e' fallito
     * (es. SMTP non configurato/non raggiungibile) - in quel caso la riga
     * del codice viene rimossa (non sarebbe comunque mai consegnato) e il
     * chiamante decide il da farsi (vedi AdminController::postLogin(),
     * che in quel caso fa proseguire il login senza step-up invece di
     * bloccare l'utente fuori - decisione esplicita dell'utente).
     */
    public static function sendEmailOtp(User $user): bool
    {
        $code = (string) random_int(100000, 999999);

        $id = DB::table('mfa_email_otp_codes')->insertGetId([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EMAIL_OTP_MINUTES),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            CRUDBooster::sendEmail([
                'to' => $user->email,
                'data' => (object) ['otp_code' => $code],
                'template' => 'mfa_email_otp',
            ]);
        } catch (\Throwable $e) {
            DB::table('mfa_email_otp_codes')->where('id', $id)->delete();
            Log::warning('MFA: invio email OTP fallito (SMTP non configurato o non raggiungibile), login proseguito senza step-up.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public static function verifyEmailOtp(User $user, string $code): bool
    {
        $rows = DB::table('mfa_email_otp_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->get();

        foreach ($rows as $row) {
            if (Hash::check($code, $row->code_hash)) {
                DB::table('mfa_email_otp_codes')->where('id', $row->id)->update(['used_at' => now()]);

                return true;
            }
        }

        return false;
    }

    // --- Recovery: "ho perso il dispositivo e i backup codes" (Fase 3) ---

    /**
     * Crea la richiesta di recovery e ritorna il token da mettere nel link
     * email (selector:validator, stesso pattern dei dispositivi fidati).
     * Non invia l'email: il chiamante costruisce l'URL e la invia (stesso
     * criterio di postForgot()/CRUDBooster::sendEmail()).
     */
    public static function createRecoveryRequest(User $user): string
    {
        $selector = Str::random(16);
        $validator = Str::random(32);

        DB::table('mfa_recovery_requests')->insert([
            'user_id' => $user->id,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'requested_at' => now(),
            'effective_at' => now()->addMinutes(self::RECOVERY_DELAY_MINUTES),
            'cancelled_at' => null,
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $selector.':'.$validator;
    }

    public static function findRecoveryRequestByToken(string $token)
    {
        if (strpos($token, ':') === false) {
            return null;
        }

        [$selector, $validator] = explode(':', $token, 2);

        $row = DB::table('mfa_recovery_requests')->where('selector', $selector)->first();

        if (! $row || ! hash_equals($row->validator_hash, hash('sha256', $validator))) {
            return null;
        }

        return $row;
    }

    public static function cancelRecoveryRequest($row): void
    {
        DB::table('mfa_recovery_requests')->where('id', $row->id)->update([
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Applica la disattivazione MFA se il tempo di attesa e' passato e la
     * richiesta non e' stata ne' annullata ne' gia' completata. Valutato in
     * modo "lazy" (nessun cron/scheduler richiesto): chiamato sia dalla
     * pagina di stato del link email sia da postLogin() - qualunque dei due
     * tocchi arrivi prima rende effettiva la disattivazione.
     */
    public static function applyDueRecovery($row): bool
    {
        if ($row->cancelled_at || $row->completed_at) {
            return false;
        }

        if (now()->lt($row->effective_at)) {
            return false;
        }

        $user = User::find($row->user_id);
        if ($user) {
            self::disable($user);
        }

        DB::table('mfa_recovery_requests')->where('id', $row->id)->update([
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Da chiamare a inizio postLogin() (prima di controllare
     * two_factor_confirmed_at): applica una eventuale recovery gia' scaduta
     * anche se l'utente non ha mai riaperto il link email.
     */
    public static function applyDueRecoveryForUser(User $user): void
    {
        $rows = DB::table('mfa_recovery_requests')
            ->where('user_id', $user->id)
            ->whereNull('cancelled_at')
            ->whereNull('completed_at')
            ->where('effective_at', '<=', now())
            ->get();

        foreach ($rows as $row) {
            self::applyDueRecovery($row);
        }
    }
}
