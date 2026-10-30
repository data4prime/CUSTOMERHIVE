<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Comportamento OBIETTIVO del piano 324 (docs/refactoring/piani/324-rotte-
 * custom-sotto-login.md, B1): le rotte custom di sistema di routes/web.php
 * (da admin/menu_management/edit/{id} a /mashup-objects/...) e le rotte
 * api-documentation / download-documentation-postman di routes/crudbooster.php
 * passano da CBBackend, quindi un ospite viene rimandato a admin/login.
 *
 * Scritto PRIMA della modifica delle rotte: finche' il passo 2 del piano non e'
 * applicato i casi "ospite respinto" sono ROSSI di proposito (oggi un ospite
 * raggiunge il controller). Lezione del doc 318: ogni caso negativo ha il suo
 * positivo con lo stesso setup (superadmin/utente loggato sulle stesse URL,
 * stesse entita' seminate), cosi' "nessuna riga creata" non puo' essere un
 * falso verde dovuto a una richiesta che non arriva mai al controller.
 *
 * I metodi add_member/add_group/... leggono $_POST direttamente (non
 * Request::input) e il client di test di Laravel non popola quella
 * superglobale: postAsForm() la imposta a mano e la ripristina.
 *
 * Il caso save_enable (che fa TRUNCATE, commit implicito in MySQL) sta in
 * PublicSystemRoutesSaveEnableTest per poter ripulire i dati senza sporcare
 * gli altri test.
 */
class PublicSystemRoutesAuthTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    /**
     * Entita' reali su cui costruire gli URL (id inesistenti non dimostrano
     * nulla: per il superadmin la pagina deve poter rispondere 200).
     *
     * @return array{menu:int,tenant:int,group:int,user:int}
     */
    private function seedRouteEntities(): array
    {
        return [
            'menu' => $this->seedMenu(['name' => 'Voce Test Rotte'])['id'],
            'tenant' => $this->seedTenant(),
            'group' => $this->seedGroup(),
            'user' => $this->seedUser()['id'],
        ];
    }

    /**
     * @param array{menu:int,tenant:int,group:int,user:int} $ids
     */
    private function resolveUri(string $uri, array $ids): string
    {
        $search = [];
        $replace = [];
        foreach ($ids as $placeholder => $id) {
            $search[] = '{' . $placeholder . '}';
            $replace[] = (string) $id;
        }

        return 'http://localhost/' . str_replace($search, $replace, $uri);
    }

    private function postAsForm(string $uri, array $data): TestResponse
    {
        $previous = $_POST;
        $_POST = $data;

        try {
            return $this->post($uri, $data);
        } finally {
            $_POST = $previous;
        }
    }

    private function assertRedirectToLogin(TestResponse $response): void
    {
        $response->assertStatus(302);
        $response->assertRedirect(url('admin/login'));
    }

    private function assertNotRedirectToLogin(TestResponse $response): void
    {
        $this->assertNotSame(url('admin/login'), $response->headers->get('Location'));
    }

    private function membershipCount(int $groupId, int $userId): int
    {
        return DB::table('users_groups')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();
    }

    // ---------------------------------------------------------------
    // 2. GET admin/module_generator/enable
    // ---------------------------------------------------------------

    public function test_enable_superadmin_vede_la_pagina(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get('http://localhost/admin/module_generator/enable');

        $response->assertStatus(200);
    }

    /**
     * Oggi 500: nel ramo di rifiuto di ModulsController::enable() $row non e'
     * definita (passo 3 del piano). Obiettivo: redirect con denied_access.
     */
    public function test_enable_non_superadmin_riceve_denied_access(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['module_generator']);

        $response = $this->get('http://localhost/admin/module_generator/enable');

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_enable_ospite_va_al_login(): void
    {
        $response = $this->get('http://localhost/admin/module_generator/enable');

        $this->assertRedirectToLogin($response);
    }

    // ---------------------------------------------------------------
    // 3. Rotte custom: GET (ospite respinto / superadmin raggiunge il controller)
    // ---------------------------------------------------------------

    /**
     * @return array<string,array{0:string,1:bool}> uri con segnaposto, e se
     *   il controller richiede anche la licenza Qlik (groups/items risponde
     *   con un redirect alla dashboard senza licenza: resta "raggiunto il
     *   controller", purche' non sia il redirect al login).
     */
    public static function customGetRoutes(): array
    {
        return [
            'menu edit' => ['admin/menu_management/edit/{menu}', false],
            'tenants members' => ['admin/tenants/members/{tenant}', false],
            'tenants group' => ['admin/tenants/group/{tenant}', false],
            'groups members' => ['admin/groups/members/{group}', false],
            'groups items' => ['admin/groups/items/{group}', true],
            'groups tenant' => ['admin/groups/tenant/{group}', false],
            'users groups' => ['admin/users/groups/{user}', false],
        ];
    }

    #[DataProvider('customGetRoutes')]
    public function test_ospite_su_rotta_custom_get_va_al_login(string $uri, bool $needsQlik): void
    {
        $ids = $this->seedRouteEntities();

        $response = $this->get($this->resolveUri($uri, $ids));

        $this->assertRedirectToLogin($response);
    }

    #[DataProvider('customGetRoutes')]
    public function test_superadmin_su_rotta_custom_get_raggiunge_il_controller(string $uri, bool $needsQlik): void
    {
        $ids = $this->seedRouteEntities();
        $this->actingAsSuperadmin();
        $resolved = $this->resolveUri($uri, $ids);

        // AdminCmsUsersController::cbInit() legge $_SERVER['REQUEST_URI'] (non
        // la Request): il client di test non lo imposta, quindi lo si simula.
        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/' . ltrim($resolved, '/');
        try {
            $response = $this->get($resolved);
        } finally {
            if ($previousUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousUri;
            }
        }

        if ($needsQlik) {
            $this->assertNotRedirectToLogin($response);
            $this->assertContains($response->getStatusCode(), [200, 302]);

            return;
        }
        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // 3. Rotte custom: mutazioni (ospite = nessuna riga; superadmin = riga)
    // ---------------------------------------------------------------

    public function test_ospite_add_member_non_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $payload = ['name' => $ids['user'], 'return_url' => '/admin/groups/members/' . $ids['group'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/groups/{$ids['group']}/add_member", $payload);

        $this->assertRedirectToLogin($response);
        $this->assertSame(0, $this->membershipCount($ids['group'], $ids['user']));
    }

    public function test_superadmin_add_member_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $this->actingAsSuperadmin();
        $payload = ['name' => $ids['user'], 'return_url' => '/admin/groups/members/' . $ids['group'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/groups/{$ids['group']}/add_member", $payload);

        $response->assertStatus(302);
        $this->assertNotRedirectToLogin($response);
        $this->assertSame(1, $this->membershipCount($ids['group'], $ids['user']));
    }

    /**
     * Il gruppo del membro NON e' il suo gruppo primario (seedUser ne crea uno
     * suo): remove_member non passa dal ramo con CRUDBooster::redirectBack(),
     * che fa exit e ucciderebbe PHPUnit.
     */
    public function test_ospite_remove_member_non_toglie_il_membro(): void
    {
        $ids = $this->seedRouteEntities();
        DB::table('users_groups')->insert(['user_id' => $ids['user'], 'group_id' => $ids['group']]);

        $response = $this->get("http://localhost/admin/groups/{$ids['group']}/remove_member/{$ids['user']}");

        $this->assertRedirectToLogin($response);
        $this->assertSame(1, $this->membershipCount($ids['group'], $ids['user']));
    }

    public function test_superadmin_remove_member_toglie_il_membro(): void
    {
        $ids = $this->seedRouteEntities();
        DB::table('users_groups')->insert(['user_id' => $ids['user'], 'group_id' => $ids['group']]);
        $this->actingAsSuperadmin();

        $response = $this->get("http://localhost/admin/groups/{$ids['group']}/remove_member/{$ids['user']}");

        $response->assertStatus(302);
        $this->assertNotRedirectToLogin($response);
        $this->assertSame(0, $this->membershipCount($ids['group'], $ids['user']));
    }

    public function test_ospite_users_add_group_non_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $payload = ['name' => $ids['group'], 'return_url' => '/admin/users/groups/' . $ids['user'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/users/{$ids['user']}/add_group", $payload);

        $this->assertRedirectToLogin($response);
        $this->assertSame(0, $this->membershipCount($ids['group'], $ids['user']));
    }

    public function test_superadmin_users_add_group_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $this->actingAsSuperadmin();
        $payload = ['name' => $ids['group'], 'return_url' => '/admin/users/groups/' . $ids['user'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/users/{$ids['user']}/add_group", $payload);

        $response->assertStatus(302);
        $this->assertNotRedirectToLogin($response);
        $this->assertSame(1, $this->membershipCount($ids['group'], $ids['user']));
    }

    public function test_ospite_tenants_add_group_non_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $payload = ['name' => $ids['group'], 'return_url' => '/admin/tenants/group/' . $ids['tenant'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/tenants/{$ids['tenant']}/add_group", $payload);

        $this->assertRedirectToLogin($response);
        $this->assertSame(0, DB::table('group_tenants')->where('group_id', $ids['group'])->where('tenant_id', $ids['tenant'])->count());
    }

    public function test_superadmin_tenants_add_group_crea_la_riga(): void
    {
        $ids = $this->seedRouteEntities();
        $this->actingAsSuperadmin();
        $payload = ['name' => $ids['group'], 'return_url' => '/admin/tenants/group/' . $ids['tenant'], 'ref_mainpath' => ''];

        $response = $this->postAsForm("http://localhost/admin/tenants/{$ids['tenant']}/add_group", $payload);

        $response->assertStatus(302);
        $this->assertNotRedirectToLogin($response);
        $this->assertSame(1, DB::table('group_tenants')->where('group_id', $ids['group'])->where('tenant_id', $ids['tenant'])->count());
    }

    // ---------------------------------------------------------------
    // 4. /mashup/{componentID} e /mashup-objects/{mashup}/{componentID}/{objectid}
    //    (senza licenza Qlik rispondono 200 con la vista mashup_noqlik, con
    //    licenza 200 vuoto: in entrambi i casi "raggiunge il controller")
    // ---------------------------------------------------------------

    public function test_ospite_su_mashup_va_al_login(): void
    {
        $this->assertRedirectToLogin($this->get('http://localhost/mashup/componente-test'));
    }

    public function test_utente_loggato_su_mashup_raggiunge_il_controller(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);

        $this->get('http://localhost/mashup/componente-test')->assertStatus(200);
    }

    public function test_ospite_su_mashup_objects_va_al_login(): void
    {
        $this->assertRedirectToLogin($this->get('http://localhost/mashup-objects/1/componente-test/oggetto-test'));
    }

    public function test_utente_loggato_su_mashup_objects_raggiunge_il_controller(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);

        $this->get('http://localhost/mashup-objects/1/componente-test/oggetto-test')->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // 5. api-documentation e download-documentation-postman
    // ---------------------------------------------------------------

    public function test_ospite_su_api_documentation_va_al_login(): void
    {
        $this->assertRedirectToLogin($this->get('http://localhost/api-documentation'));
    }

    public function test_utente_loggato_non_superadmin_vede_api_documentation(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);

        $this->get('http://localhost/api-documentation')->assertStatus(200);
    }

    public function test_ospite_su_download_postman_va_al_login(): void
    {
        $this->assertRedirectToLogin($this->get('http://localhost/download-documentation-postman'));
    }

    public function test_superadmin_scarica_la_collection_postman(): void
    {
        $this->actingAsSuperadmin();

        $this->get('http://localhost/download-documentation-postman')->assertStatus(200);
    }

    /**
     * Invariato rispetto a oggi: il controllo e' gia' dentro getDownloadPostman()
     * (denyUnlessSuperadmin), B1 non lo tocca.
     */
    public function test_non_superadmin_su_download_postman_riceve_denied_access(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);

        $response = $this->get('http://localhost/download-documentation-postman');

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    // ---------------------------------------------------------------
    // 6. Sessione invalidata su una rotta nuova
    // ---------------------------------------------------------------

    public function test_sessione_invalidata_su_rotta_custom_va_al_login_con_messaggio(): void
    {
        $ids = $this->seedRouteEntities();
        $this->actingAsSuperadmin();
        // il contatore in sessione resta indietro rispetto a cms_users.session_version (0)
        $this->withSession(['admin_session_version' => 99]);

        $response = $this->get("http://localhost/admin/groups/members/{$ids['group']}");

        $this->assertRedirectToLogin($response);
        $response->assertSessionHas('message', trans('crudbooster.session_invalidated'));
    }

    // ---------------------------------------------------------------
    // 7. Non regressione: cio' che deve restare com'e'
    // ---------------------------------------------------------------

    /**
     * File inesistente: 404 da FileController::getPreview(), non redirect al
     * login (uploads/* e' fuori da B1, resta solo 'web').
     */
    public function test_uploads_resta_raggiungibile_da_ospite(): void
    {
        $this->get('http://localhost/uploads/phpunit-file-inesistente.txt')->assertStatus(404);
    }

    /**
     * Token inesistente: 404 da QlikItemsController::show(); il link pubblico
     * di condivisione non deve finire dietro il login. Il caso con item reale
     * e' coperto da QlikItemPublicAccessTest.
     */
    public function test_link_pubblico_qi_resta_raggiungibile_da_ospite(): void
    {
        $this->get('http://localhost/qi/token-inesistente')->assertStatus(404);
    }

    public function test_i_nomi_di_rotta_restano_registrati(): void
    {
        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();

        foreach (['MenusControllerGetEdit', 'apiDocumentation', 'downloadDocumentationPostman', 'fileControllerPreview'] as $name) {
            $this->assertNotNull($routes->getByName($name), "Rotta '{$name}' non registrata.");
        }
    }

    /**
     * File presente solo in storage/app/public/uploads (dove li salva l'app e
     * da dove escono via /storage/uploads/...): la rotta uploads/* non lo
     * serve e risponde 404, prima andava in 500 perche' lo apriva da
     * storage/app/uploads.
     */
    public function test_uploads_con_file_solo_nella_cartella_pubblica_risponde_404(): void
    {
        $relative = 'uploads/phpunit-b1b/file.txt';
        Storage::put($relative, 'contenuto di prova');

        try {
            $this->get('http://localhost/uploads/phpunit-b1b/file.txt')->assertStatus(404);
        } finally {
            Storage::deleteDirectory('uploads/phpunit-b1b');
        }
    }

    /**
     * Non regressione: con il file in entrambe le cartelle (public/uploads e
     * storage/app/uploads) la rotta continua a servirlo come prima.
     */
    public function test_uploads_con_file_in_entrambe_le_cartelle_viene_servito(): void
    {
        Storage::put('uploads/phpunit-b1b/doppio.txt', 'contenuto di prova');
        $legacyDir = storage_path('app/uploads/phpunit-b1b');
        mkdir($legacyDir, 0777, true);
        file_put_contents($legacyDir.'/doppio.txt', 'contenuto di prova');

        try {
            $this->get('http://localhost/uploads/phpunit-b1b/doppio.txt')->assertOk();
        } finally {
            Storage::deleteDirectory('uploads/phpunit-b1b');
            @unlink($legacyDir.'/doppio.txt');
            @rmdir($legacyDir);
            @rmdir(storage_path('app/uploads'));
        }
    }

    public function test_uploads_ha_ancora_solo_il_middleware_web(): void
    {
        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();

        $this->assertSame(['web'], $routes->getByName('fileControllerPreview')->gatherMiddleware());
    }
}
