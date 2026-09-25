<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Template email per il piano MFA (Fase 0), vedi docs/refactoring/095-*.
 * Stesso meccanismo gia' usato per forgot_password_backend: placeholder tra
 * [ ] sostituiti da CRUDBooster::sendEmail(), invio tramite
 * cms_email_templates + CRUDBooster::sendEmail() (Fase 2/3 del piano, non
 * ancora scritte).
 *
 * - mfa_email_otp: step-up email per chi non ha il TOTP attivo (Fase 2).
 *   Scadenza 10 minuti (decisione utente).
 * - mfa_recovery_request: "ho perso il dispositivo e i backup codes"
 *   (Fase 3). Il link non disattiva subito l'MFA: programma la
 *   disattivazione fra 30 minuti, con possibilita' di annullare nella
 *   stessa pagina di atterraggio (decisione utente).
 *
 * Guardia su whereNotExists: idempotente se rieseguita per errore, non
 * duplica le righe.
 */
class AddMfaEmailTemplates extends Migration
{
    public function up()
    {
        if (! DB::table('cms_email_templates')->where('slug', 'mfa_email_otp')->exists()) {
            DB::table('cms_email_templates')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Email Template MFA - Email OTP',
                'slug' => 'mfa_email_otp',
                'content' => '<p>Hi,</p><p>Here is your verification code to complete sign-in:</p><p><strong>[otp_code]</strong></p><p>This code expires in 10 minutes. If you did not try to sign in, you can safely ignore this email.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
                'description' => '[otp_code]',
                'from_name' => 'System',
                'from_email' => 'system@crudbooster.com',
                'cc_email' => null,
            ]);
        }

        if (! DB::table('cms_email_templates')->where('slug', 'mfa_recovery_request')->exists()) {
            DB::table('cms_email_templates')->insert([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => 'Email Template MFA - Recovery Request',
                'slug' => 'mfa_recovery_request',
                'content' => '<p>Hi,</p><p>Someone requested to disable two-factor authentication on this account because access to the authenticator app and the backup codes was lost. Click the link below to review the request:</p><p><a href="[recovery_url]">[recovery_url]</a></p><p>Two-factor authentication will only be disabled 30 minutes after this link is opened, and you will be able to cancel the request in that window. If you did not request this, open the link and cancel it as soon as possible.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
                'description' => '[recovery_url]',
                'from_name' => 'System',
                'from_email' => 'system@crudbooster.com',
                'cc_email' => null,
            ]);
        }
    }

    public function down()
    {
        DB::table('cms_email_templates')->whereIn('slug', ['mfa_email_otp', 'mfa_recovery_request'])->delete();
    }
}
