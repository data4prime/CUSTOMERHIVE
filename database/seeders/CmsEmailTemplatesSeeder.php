<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CmsEmailTemplates extends Seeder
{
    public function run()
    {
        // Contenuto in stile app (bottone indigo, vedi
        // docs/refactoring/106-*) - stesso identico HTML della migration
        // 2026_09_25_000007_restyle_auth_email_templates.php, qui solo
        // per le installazioni nuove che partono dai seeder invece che
        // dallo storico delle migration.
        DB::table('cms_email_templates')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'Email Template Forgot Password Backend',
            'slug' => 'forgot_password_backend',
            'subject' => 'Reset your password',
            'content' => '<p>Hi,</p><p>Someone requested a password reset for this account. Click the button below to choose a new password:</p><p style="text-align:center;margin:28px 0;"><a href="[reset_url]" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;">Reset password</a></p><p style="font-size:12px;color:#8b8b96;">If the button doesn\'t work, copy and paste this link into your browser: [reset_url]</p><p>If you did not request this, you can safely ignore this email - your password will not change.</p><p>This link expires in 60 minutes.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
            'description' => '[reset_url]',
            'from_name' => 'System',
            'from_email' => 'system@crudbooster.com',
            'cc_email' => null,
        ]);

        DB::table('cms_email_templates')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'Email Template MFA - Email OTP',
            'slug' => 'mfa_email_otp',
            'subject' => 'Your verification code',
            'content' => '<p>Hi,</p><p>Here is your verification code to complete sign-in:</p><p style="text-align:center;margin:28px 0;"><span style="display:inline-block;background:#f7f7f9;border:1px solid #e6e6ea;border-radius:8px;padding:16px 28px;font-size:28px;font-weight:700;letter-spacing:6px;font-family:\'Courier New\',monospace;color:#16161d;">[otp_code]</span></p><p>This code expires in 10 minutes. If you did not try to sign in, you can safely ignore this email.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
            'description' => '[otp_code]',
            'from_name' => 'System',
            'from_email' => 'system@crudbooster.com',
            'cc_email' => null,
        ]);

        DB::table('cms_email_templates')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'Email Template MFA - Recovery Request',
            'slug' => 'mfa_recovery_request',
            'subject' => 'Two-factor authentication recovery request',
            'content' => '<p>Hi,</p><p>Someone requested to disable two-factor authentication on this account because access to the authenticator app and the backup codes was lost. Click the button below to review the request:</p><p style="text-align:center;margin:28px 0;"><a href="[recovery_url]" style="display:inline-block;background:#d1373f;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;">Review recovery request</a></p><p style="font-size:12px;color:#8b8b96;">If the button doesn\'t work, copy and paste this link into your browser: [recovery_url]</p><p>Two-factor authentication will only be disabled 30 minutes after this link is opened, and you will be able to cancel the request in that window. If you did not request this, open the link and cancel it as soon as possible.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
            'description' => '[recovery_url]',
            'from_name' => 'System',
            'from_email' => 'system@crudbooster.com',
            'cc_email' => null,
        ]);
    }
}
