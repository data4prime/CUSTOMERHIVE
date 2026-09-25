<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Riscrive contenuto e oggetto (mai valorizzato finora, vedi
 * docs/refactoring/106-*) dei 3 template email coinvolti nell'auth: il
 * link nudo diventa un bottone in stile app (stesso indigo #4f46e5 usato
 * per i pulsanti primari, rosso #d1373f per l'azione sensibile di
 * recovery), il codice OTP diventa un blocco grande/monospace. Il wrapper
 * condiviso (resources/views/crudbooster/emails/{header,footer}.blade.php)
 * e' stato ristilizzato a parte, non serve toccarlo qui.
 *
 * Guardia su whereNotExists/where(content=OLD): se il contenuto attuale
 * non corrisponde esattamente a quello seedato in 072/095 (es. un admin
 * lo ha gia' personalizzato a mano), non tocca nulla - stesso criterio
 * gia' usato in 2026_09_16_000001_update_forgot_password_email_template.php.
 */
class RestyleAuthEmailTemplates extends Migration
{
    private const FORGOT_OLD = '<p>Hi,</p><p>Someone requested a password reset for this account. Click the link below to choose a new password:</p><p><a href="[reset_url]">[reset_url]</a></p><p>If you did not request this, you can safely ignore this email - your password will not change.</p><p>This link expires in 60 minutes.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';
    private const FORGOT_NEW = '<p>Hi,</p><p>Someone requested a password reset for this account. Click the button below to choose a new password:</p><p style="text-align:center;margin:28px 0;"><a href="[reset_url]" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;">Reset password</a></p><p style="font-size:12px;color:#8b8b96;">If the button doesn\'t work, copy and paste this link into your browser: [reset_url]</p><p>If you did not request this, you can safely ignore this email - your password will not change.</p><p>This link expires in 60 minutes.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';

    private const OTP_OLD = '<p>Hi,</p><p>Here is your verification code to complete sign-in:</p><p><strong>[otp_code]</strong></p><p>This code expires in 10 minutes. If you did not try to sign in, you can safely ignore this email.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';
    private const OTP_NEW = '<p>Hi,</p><p>Here is your verification code to complete sign-in:</p><p style="text-align:center;margin:28px 0;"><span style="display:inline-block;background:#f7f7f9;border:1px solid #e6e6ea;border-radius:8px;padding:16px 28px;font-size:28px;font-weight:700;letter-spacing:6px;font-family:\'Courier New\',monospace;color:#16161d;">[otp_code]</span></p><p>This code expires in 10 minutes. If you did not try to sign in, you can safely ignore this email.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';

    private const RECOVERY_OLD = '<p>Hi,</p><p>Someone requested to disable two-factor authentication on this account because access to the authenticator app and the backup codes was lost. Click the link below to review the request:</p><p><a href="[recovery_url]">[recovery_url]</a></p><p>Two-factor authentication will only be disabled 30 minutes after this link is opened, and you will be able to cancel the request in that window. If you did not request this, open the link and cancel it as soon as possible.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';
    private const RECOVERY_NEW = '<p>Hi,</p><p>Someone requested to disable two-factor authentication on this account because access to the authenticator app and the backup codes was lost. Click the button below to review the request:</p><p style="text-align:center;margin:28px 0;"><a href="[recovery_url]" style="display:inline-block;background:#d1373f;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;">Review recovery request</a></p><p style="font-size:12px;color:#8b8b96;">If the button doesn\'t work, copy and paste this link into your browser: [recovery_url]</p><p>Two-factor authentication will only be disabled 30 minutes after this link is opened, and you will be able to cancel the request in that window. If you did not request this, open the link and cancel it as soon as possible.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';

    public function up()
    {
        DB::table('cms_email_templates')
            ->where('slug', 'forgot_password_backend')
            ->where('content', self::FORGOT_OLD)
            ->update(['content' => self::FORGOT_NEW, 'subject' => 'Reset your password']);

        DB::table('cms_email_templates')
            ->where('slug', 'mfa_email_otp')
            ->where('content', self::OTP_OLD)
            ->update(['content' => self::OTP_NEW, 'subject' => 'Your verification code']);

        DB::table('cms_email_templates')
            ->where('slug', 'mfa_recovery_request')
            ->where('content', self::RECOVERY_OLD)
            ->update(['content' => self::RECOVERY_NEW, 'subject' => 'Two-factor authentication recovery request']);
    }

    public function down()
    {
        DB::table('cms_email_templates')
            ->where('slug', 'forgot_password_backend')
            ->where('content', self::FORGOT_NEW)
            ->update(['content' => self::FORGOT_OLD, 'subject' => null]);

        DB::table('cms_email_templates')
            ->where('slug', 'mfa_email_otp')
            ->where('content', self::OTP_NEW)
            ->update(['content' => self::OTP_OLD, 'subject' => null]);

        DB::table('cms_email_templates')
            ->where('slug', 'mfa_recovery_request')
            ->where('content', self::RECOVERY_NEW)
            ->update(['content' => self::RECOVERY_OLD, 'subject' => null]);
    }
}
