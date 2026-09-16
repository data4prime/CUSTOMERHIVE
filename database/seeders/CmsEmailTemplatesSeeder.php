<?php 

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CmsEmailTemplates extends Seeder
{
    public function run()
    {
        DB::table('cms_email_templates')->insert([
            'created_at' => date('Y-m-d H:i:s'),
            'name' => 'Email Template Forgot Password Backend',
            'slug' => 'forgot_password_backend',
            'content' => '<p>Hi,</p><p>Someone requested a password reset for this account. Click the link below to choose a new password:</p><p><a href="[reset_url]">[reset_url]</a></p><p>If you did not request this, you can safely ignore this email - your password will not change.</p><p>This link expires in 60 minutes.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>',
            'description' => '[reset_url]',
            'from_name' => 'System',
            'from_email' => 'system@crudbooster.com',
            'cc_email' => null,
        ]);
    }
}
