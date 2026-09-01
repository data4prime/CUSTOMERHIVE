<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Helper di seeding condivisi tra i test che hanno bisogno di dati minimi
 * validi in tenants/groups/cms_privileges/cms_users (i campi NOT NULL senza
 * default in questo schema legacy vanno sempre passati esplicitamente:
 * tenants.domain_name, cms_users.tenant/primary_group, ecc.).
 */
trait SeedsCmsData
{
    protected function seedTenant(?string $domainName = null): int
    {
        return DB::table('tenants')->insertGetId([
            'name' => 'Tenant ' . ($domainName ?? uniqid()),
            'domain_name' => $domainName ?? ('tenant-' . uniqid()),
            'created_at' => now(),
        ]);
    }

    protected function seedPrivilege(bool $isSuperadmin = false): int
    {
        return DB::table('cms_privileges')->insertGetId([
            'name' => $isSuperadmin ? 'Superadmin' : 'Standard',
            'is_superadmin' => $isSuperadmin,
            'theme_color' => 'blue',
        ]);
    }

    protected function seedGroup(): int
    {
        return DB::table('groups')->insertGetId([
            'name' => 'Gruppo test',
            'created_at' => now(),
        ]);
    }

    /**
     * Crea una voce di menu (cms_menus) di primo livello, pronta per essere
     * mostrata da MenuHelper::get_menu()/menu_to_html() (parent_id=0,
     * sorting valorizzato). Nessuna associazione privilege/tenant/group
     * creata qui: aggiungerle esplicitamente nel test se servono per
     * l'isolamento per tenant o per i permessi di modifica/cancellazione.
     */
    protected function seedMenu(array $overrides = []): array
    {
        $data = array_merge([
            'name' => 'Voce Di Menu Test',
            'type' => 'URL',
            'path' => 'https://example.com',
            'parent_id' => 0,
            'sorting' => 1,
            'is_active' => 1,
            'is_dashboard' => 0,
            'icon' => 'fa fa-link',
            'created_at' => now(),
        ], $overrides);

        $menuId = DB::table('cms_menus')->insertGetId($data);

        return array_merge($data, ['id' => $menuId]);
    }

    /**
     * Le route dei moduli CRUD (Tenants, Users, ecc.) si registrano
     * leggendo cms_moduls quando l'applicazione boota (routes/crudbooster.php,
     * sezione "ROUTER FOR BACKEND CRUDBOOSTER") - PRIMA che un test possa
     * seminare quella riga (setUp() di un test gira sempre dopo il boot).
     * Seminare la riga non basta: va anche registrata la route stessa, a
     * mano, con lo stesso Route::group() (middleware CBBackend + prefix
     * "admin" + namespace System) e lo stesso helper che userebbe
     * routes/crudbooster.php - nessuno dei due da solo funziona.
     *
     * Il sidebar admin condiviso (sidebar.blade.php, usato da OGNI pagina
     * admin renderizzata) referenzia INCONDIZIONATAMENTE, per un utente
     * superadmin - a prescindere dal modulo che si sta testando - tutti i
     * moduli di sistema (Tenants, Privileges, Groups, Users, Logs, Menu
     * Management, Settings, Module Generator, Statistic Builder, Api
     * Generator, Email Templates) piu' Notifications (header/campanella):
     * route() lancia un'eccezione se anche uno solo di questi manca.
     * registerAdminModules() li registra tutti sempre, insieme a
     * qualunque modulo extra serva al test specifico.
     */
    private const BASELINE_TABLE_NAMES = [
        'users' => 'cms_users',
        'notifications' => 'cms_notifications',
        'settings' => 'cms_settings',
        'privileges' => 'cms_privileges',
        'menu_management' => 'cms_menus',
    ];

    protected function registerAdminModule(string $path, string $controller, ?string $tableName = null): void
    {
        $tableName ??= self::BASELINE_TABLE_NAMES[$path] ?? $path;

        DB::table('cms_moduls')->insert([
            'name' => ucfirst($path),
            'path' => $path,
            'table_name' => $tableName ?? $path,
            'controller' => $controller,
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ]);

        \Illuminate\Support\Facades\Route::group([
            'middleware' => ['web', '\App\Http\Middleware\CBBackend'],
            'prefix' => config('crudbooster.ADMIN_PATH'),
            'namespace' => 'App\Http\Controllers\System',
        ], function () use ($path, $controller) {
            \App\Helpers\CRUDBooster::routeController($path, $controller, 'App\Http\Controllers\System');
        });
    }

    /**
     * Registra il modulo/i moduli extra richiesti dal test PIU' i tre
     * sempre referenziati dal layout admin condiviso (vedi sopra). Da
     * chiamare in setUp(), dopo parent::setUp().
     *
     * @param array<string,string> $extra path => controller dei moduli
     *   specifici del test (es. ['tenants' => 'AdminTenantsController']).
     */
    protected function registerAdminModules(array $extra = []): void
    {
        $modules = array_merge([
            'users' => 'AdminCmsUsersController',
            'notifications' => 'NotificationsController',
            'settings' => 'SettingsController',
            'tenants' => 'AdminTenantsController',
            'privileges' => 'PrivilegesController',
            'groups' => 'AdminGroupsController',
            'logs' => 'LogsController',
            'menu_management' => 'MenusController',
            'module_generator' => 'ModulsController',
            'statistic_builder' => 'StatisticBuilderController',
            'api_generator' => 'ApiCustomController',
            'email_templates' => 'EmailTemplatesController',
        ], $extra);

        foreach ($modules as $path => $controller) {
            $this->registerAdminModule($path, $controller);
        }
    }

    /**
     * Crea un utente cms_users pronto per il login, con override opzionali.
     */
    protected function seedUser(array $overrides = []): array
    {
        $tenantId = $overrides['tenant'] ?? $this->seedTenant();
        $privilegeId = $overrides['id_cms_privileges'] ?? $this->seedPrivilege();
        $groupId = $this->seedGroup();

        $data = array_merge([
            'name' => 'Utente Test',
            'email' => 'utente.test+' . uniqid() . '@example.com',
            'password' => Hash::make('password-corretta-123'),
            'id_cms_privileges' => $privilegeId,
            'status' => 'Active',
            'primary_group' => $groupId,
            'tenant' => $tenantId,
            'created_at' => now(),
        ], $overrides);

        $userId = DB::table('cms_users')->insertGetId($data);

        return array_merge($data, ['id' => $userId]);
    }

    /**
     * Autentica il test come un utente superadmin fresco (tenant/privilegio/
     * utente seminati apposta) - bypassa ModuleHelper::can_edit()/
     * can_delete() e CRUDBooster::isCreate()/isUpdate()/isDelete(), non
     * serve seminare righe in cms_privileges_roles per il modulo specifico.
     * Ritorna l'id utente e l'id tenant, utile per gli hook che associano
     * dati creati al tenant dell'attore (es. AdminGroupsController::
     * hook_after_add()).
     *
     * @return array{userId:int,tenantId:int}
     */
    protected function actingAsSuperadmin(): array
    {
        $tenantId = $this->seedTenant();
        $superadminPrivilegeId = $this->seedPrivilege(isSuperadmin: true);
        $user = $this->seedUser(['tenant' => $tenantId, 'id_cms_privileges' => $superadminPrivilegeId]);

        $this->withSession([
            'admin_id' => $user['id'],
            'admin_lock' => 0,
            'admin_is_superadmin' => 1,
            // AdminController::postLogin() la popola sempre - alcuni hook
            // (es. MenusController) la leggono via CRUDBooster::
            // myPrivilegeId() per risalire alla privilege dell'attore.
            'admin_privileges' => $superadminPrivilegeId,
        ])->actingAs(\App\User::find($user['id']));

        return ['userId' => $user['id'], 'tenantId' => $tenantId];
    }

    /**
     * Autentica il test come un utente NON superadmin (tenantadmin o
     * standard, a seconda di $isTenantadmin), per verificare i filtri di
     * visibilita' per tenant (o l'assenza di tali filtri).
     *
     * A differenza di actingAsSuperadmin(), qui bisogna anche popolare
     * $_SESSION['admin_privileges_roles'] con la stessa forma prodotta da
     * AdminController::postLogin() (join cms_privileges_roles+cms_moduls),
     * perche' CRUDBooster::isView() per un non-superadmin legge da li' per
     * decidere se il modulo e' accessibile - senza una riga con
     * is_visible=1 per il path richiesto, getIndex() nega l'accesso.
     * registerAdminModules() deve gia' essere stata chiamata (serve
     * cms_moduls popolata per risolvere id_cms_moduls dal path).
     *
     * @param array<int,string> $visibleModulePaths path dei moduli (es.
     *   ['users','groups']) per cui l'attore ha visibilita' completa.
     * @return array{userId:int,tenantId:int,privilegeId:int}
     */
    protected function actingAsTenantUser(int $tenantId, bool $isTenantadmin, array $visibleModulePaths, array $userOverrides = []): array
    {
        $privilegeId = DB::table('cms_privileges')->insertGetId([
            'name' => $isTenantadmin ? 'Tenantadmin' : 'Standard',
            'is_superadmin' => 0,
            'is_tenantadmin' => $isTenantadmin,
            'theme_color' => 'blue',
        ]);

        $user = $this->seedUser(array_merge(
            ['tenant' => $tenantId, 'id_cms_privileges' => $privilegeId],
            $userOverrides
        ));

        foreach ($visibleModulePaths as $path) {
            $modulId = DB::table('cms_moduls')->where('path', $path)->value('id');
            DB::table('cms_privileges_roles')->insert([
                'id_cms_privileges' => $privilegeId,
                'id_cms_moduls' => $modulId,
                'is_visible' => 1,
                'is_create' => 1,
                'is_read' => 1,
                'is_edit' => 1,
                'is_delete' => 1,
                'created_at' => now(),
            ]);
        }

        $roles = DB::table('cms_privileges_roles')
            ->where('id_cms_privileges', $privilegeId)
            ->join('cms_moduls', 'cms_moduls.id', '=', 'id_cms_moduls')
            ->select('cms_moduls.name', 'cms_moduls.path', 'is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete')
            ->where('cms_moduls.deleted_at', null)
            ->get();

        $this->withSession([
            'admin_id' => $user['id'],
            'admin_lock' => 0,
            'admin_is_superadmin' => 0,
            'admin_privileges_roles' => $roles,
            'admin_privileges' => $privilegeId,
        ])->actingAs(\App\User::find($user['id']));

        return ['userId' => $user['id'], 'tenantId' => $tenantId, 'privilegeId' => $privilegeId];
    }
}
