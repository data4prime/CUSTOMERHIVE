<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fase 4 del piano MFA (hardening), vedi docs/refactoring/099-*. Pulizia di
 * manutenzione, non di sicurezza: le righe scadute non vengono mai accettate
 * comunque (i controlli su expires_at/trusted_until/used_at sono gia' nelle
 * query di verifica in MfaHelper), questo comando serve solo a non far
 * crescere le tabelle all'infinito. Va schedulato (vedi
 * App\Console\Kernel::schedule(), stesso meccanismo gia' usato per
 * user:check-expiry) - se il cron non gira su una data installazione, le
 * righe scadute restano inerti ma innocue, non un rischio di sicurezza.
 */
class MfaCleanup extends Command
{
    protected $signature = 'mfa:cleanup';

    protected $description = 'Rimuove le righe MFA scadute/consumate (email OTP, dispositivi fidati, richieste di recovery concluse)';

    public function handle()
    {
        $emailOtpDeleted = DB::table('mfa_email_otp_codes')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $trustedDevicesDeleted = DB::table('mfa_trusted_devices')
            ->where('trusted_until', '<', now())
            ->delete();

        $recoveryRequestsDeleted = DB::table('mfa_recovery_requests')
            ->where(function ($query) {
                $query->whereNotNull('cancelled_at')->orWhereNotNull('completed_at');
            })
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        $this->comment("Email OTP scaduti rimossi: {$emailOtpDeleted}");
        $this->comment("Dispositivi fidati scaduti rimossi: {$trustedDevicesDeleted}");
        $this->comment("Richieste di recovery concluse (>30gg) rimosse: {$recoveryRequestsDeleted}");
    }
}
