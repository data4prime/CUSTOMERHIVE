<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AdminController::postForgot() non genera piu' una password casuale da
 * mandare in chiaro via email (placeholder [password]): manda un link di
 * reset con token (placeholder [reset_url]), scaduto dopo 60 minuti
 * (config/auth.php: passwords.users.expire), che porta alla nuova pagina
 * reset-password dove l'utente sceglie lui la nuova password (soggetta alla
 * stessa policy del form utenti - vedi AdminCmsUsersController::cbInit()).
 *
 * L'UPDATE e' condizionato al contenuto esatto seedato di default: se
 * l'admin ha gia' personalizzato il template dal pannello Settings, non
 * viene toccato (stesso criterio di 2026_08_27_030200_update_default_email_sender).
 */
class UpdateForgotPasswordEmailTemplate extends Migration
{
    private const OLD_CONTENT = '<p>Hi,</p><p>Someone requested forgot password, here is your new password : </p><p>[password]</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';
    private const NEW_CONTENT = '<p>Hi,</p><p>Someone requested a password reset for this account. Click the link below to choose a new password:</p><p><a href="[reset_url]">[reset_url]</a></p><p>If you did not request this, you can safely ignore this email - your password will not change.</p><p>This link expires in 60 minutes.</p><p><br></p><p>--</p><p>Regards,</p><p>Admin</p>';

    public function up()
    {
        DB::table('cms_email_templates')
            ->where('slug', 'forgot_password_backend')
            ->where('content', self::OLD_CONTENT)
            ->update([
                'content' => self::NEW_CONTENT,
                'description' => '[reset_url]',
            ]);
    }

    public function down()
    {
        DB::table('cms_email_templates')
            ->where('slug', 'forgot_password_backend')
            ->where('content', self::NEW_CONTENT)
            ->update([
                'content' => self::OLD_CONTENT,
                'description' => '[password]',
            ]);
    }
}
