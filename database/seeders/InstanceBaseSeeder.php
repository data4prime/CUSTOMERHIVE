<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dati di base di un'istanza NUOVA, usato da "php artisan instance:bootstrap"
 * (vedi docs/refactoring/299-*). NON sostituisce DatabaseSeeder (che resta
 * quello dei clienti esistenti e dell'ambiente di sviluppo): chiama soltanto i
 * seeder necessari, nello stesso ordine relativo di DatabaseSeeder.
 *
 * Esclusi di proposito:
 *  - Cms_usersSeeder: chiede l'email in modo interattivo e crea l'utente con
 *    password 123456. L'utente lo crea il comando, con password casuale.
 *  - QlikConf: inserisce il modulo "Qlik Configuration", che Cms_modulsSeeder
 *    contiene gia' (duplicato, e privo di riga in cms_privileges_roles).
 *  - CBSeeder (guscio vuoto), GroupTenantSeeder e MenuParentDefaultSeeder
 *    (riparazioni di dati legacy), ModuleHelperSeeder (link di aiuto
 *    facoltativi, senza namespace e fuori da DatabaseSeeder).
 *
 * Tenant e gruppo ("Tenant 1", "IT") sono segnaposto, ma servono: l'utente ha
 * tenant e primary_group e le tabelle ponte li usano. Sono gli stessi che
 * crea DatabaseSeeder.
 *
 * Il legame utenti-gruppi (Cms_usersGroups) va dopo la creazione dell'admin:
 * lo fa linkUsersToGroups(), chiamato dal comando.
 */
class InstanceBaseSeeder extends Seeder
{
    public function run()
    {
        // silent=true: Seeder::call() altrimenti scrive su stdout, che per i
        // comandi instance:* deve contenere solo la riga LINK=.
        $this->call(GroupSeeder::class, true);
        $this->call(TenantSeeder::class, true);
        $this->call(Cms_statistics::class, true);
        $this->call(Cms_menus::class, true);
        $this->call(Cms_menusGroups::class, true);
        $this->call(Cms_menusTenants::class, true);
        $this->call(Cms_groupTenants::class, true);
        $this->call(Cms_modulsSeeder::class, true);
        $this->call(Cms_privilegesSeeder::class, true);
        $this->call(Cms_privileges_rolesSeeder::class, true);
        $this->call(Cms_settingsSeeder::class, true);
        // Le migrazioni hanno gia' creato i due template MFA (000003 + restyle
        // 000007) e CmsEmailTemplates li reinserisce senza controllare lo slug
        // (su DatabaseSeeder + migrate darebbe duplicati). In un'istanza vuota
        // si tolgono quelli delle migrazioni e si lascia il seeder, che ha lo
        // stesso HTML e in piu' forgot_password_backend.
        DB::table('cms_email_templates')->whereIn('slug', ['mfa_email_otp', 'mfa_recovery_request'])->delete();
        $this->call(CmsEmailTemplates::class, true);
        $this->call(Cms_menusPrivileges::class, true);
    }

    /** Da chiamare dopo aver creato l'utente admin. */
    public function linkUsersToGroups(): void
    {
        $this->call(Cms_usersGroups::class, true);
    }
}
