<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il CRUD del modulo Menu Management
 * (MenusController - non usa la view standard di CBController::getIndex(),
 * ha una vista ad albero drag-and-drop dedicata; hook_before_add/edit/delete
 * personalizzati per costruire 'path' in base al 'type' della voce).
 *
 * Due bug reali trovati e corretti PRIMA di scrivere questi test (vedi
 * docs/refactoring/063):
 * - MenuHelper::menu_to_html(): route('MenusControllerGetEdit') senza
 *   l'id richiesto crashava con UrlGenerationException su OGNI voce di
 *   menu modificabile.
 * - MenusController::hook_before_add(): Menu::orderby('id','desc')
 *   ->first()->id crashava creando la primissima voce di menu su una
 *   tabella cms_menus vuota (Attempt to read property "id" on null).
 *
 * Fuori scope volutamente: i tipi Qlik/Agent AI (gated da licenza, stesso
 * trattamento gia' dato al resto dell'app) e CRUDBooster::redirectBack()
 * (ancora way exit()-based, usata anche da 2 Blade view - non toccata,
 * vedi hook_before_edit()).
 */
class MenusCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    public function test_lista_mostra_le_voci_di_menu_attive_e_inattive(): void
    {
        $this->actingAsSuperadmin();
        $this->seedMenu(['name' => 'Voce Attiva Da Trovare', 'is_active' => 1]);
        $this->seedMenu(['name' => 'Voce Inattiva Da Trovare', 'is_active' => 0]);

        $response = $this->get('http://localhost/admin/menu_management');

        $response->assertStatus(200);
        $response->assertSee('Voce Attiva Da Trovare');
        $response->assertSee('Voce Inattiva Da Trovare');
    }

    /**
     * Regressione del bug in hook_before_add() (vedi intestazione file):
     * su una cms_menus completamente vuota, Menu::orderby('id','desc')
     * ->first() torna null.
     */
    public function test_creazione_prima_voce_di_menu_su_tabella_vuota_non_va_in_crash(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();
        $this->assertSame(0, DB::table('cms_menus')->count(), 'Precondizione del test non valida: cms_menus deve essere vuota.');

        $response = $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Prima Voce Di Menu',
            'type' => 'URL',
            'path' => 'https://example.com',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('cms_menus', ['name' => 'Prima Voce Di Menu']);
    }

    public function test_creazione_menu_di_tipo_url_riesce_e_aggiunge_il_parametro_m_al_path(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();

        $response = $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Di Tipo Url',
            'type' => 'URL',
            'path' => 'https://esempio.test/pagina',
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_menus')->where('name', 'Voce Di Tipo Url')->first();
        $this->assertNotNull($row);
        // hook_before_add() aggiunge SEMPRE '?m=<id>' al path, qualunque
        // sia il type - serve al target layout fullpage/fillcontent.
        $this->assertMatchesRegularExpression('#^https://esempio\.test/pagina\?m=\d+$#', $row->path);
    }

    public function test_creazione_menu_di_tipo_module_costruisce_il_path_dal_modulo_selezionato(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();
        $usersModuleId = DB::table('cms_moduls')->where('path', 'users')->value('id');

        $response = $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Di Tipo Module',
            'type' => 'Module',
            'module_slug' => $usersModuleId,
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_menus')->where('name', 'Voce Di Tipo Module')->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('#^users\?m=\d+$#', $row->path);
    }

    public function test_creazione_menu_di_tipo_statistic_costruisce_il_path_dalla_statistic_selezionata(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();
        $statisticId = DB::table('cms_statistics')->insertGetId([
            'name' => 'Statistica Di Prova',
            'slug' => 'statistica-di-prova',
        ]);

        $response = $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Di Tipo Statistic',
            'type' => 'Statistic',
            'statistic_slug' => $statisticId,
        ]));

        $response->assertStatus(302);
        $row = DB::table('cms_menus')->where('name', 'Voce Di Tipo Statistic')->first();
        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('#^statistic_builder/show/statistica-di-prova\?m=\d+$#', $row->path);
    }

    /**
     * hook_after_add(): un tenantadmin che crea un menu non sceglie
     * esplicitamente il tenant (campo disabilitato nel form) - viene
     * assegnato in automatico il suo, via Menu::assign_default_tenant().
     */
    public function test_creazione_di_un_tenantadmin_assegna_automaticamente_il_suo_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);
        $groupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $groupId, 'tenant_id' => $tenantId]);

        $response = $this->post('http://localhost/admin/menu_management/add-save', [
            'name' => 'Voce Del Tenantadmin',
            'type' => 'URL',
            'path' => 'https://esempio.test',
            'cms_menus_privileges' => [$actor['privilegeId']],
            'menu_groups' => [$groupId],
            'is_custom' => 0,
            'color' => 'normal',
            'is_active' => 1,
            'is_dashboard' => 0,
            'new_tab' => 0,
            'target_layout' => 0,
            'frame_width' => 100,
            'frame_width_unit' => '%',
            'frame_height' => 100,
            'frame_height_unit' => '%',
        ]);

        $response->assertStatus(302);
        $menuId = DB::table('cms_menus')->where('name', 'Voce Del Tenantadmin')->value('id');
        $this->assertNotNull($menuId);
        $this->assertDatabaseHas('menu_tenants', ['menu_id' => $menuId, 'tenant_id' => $tenantId]);
    }

    public function test_modifica_menu_riesce_e_aggiorna_i_campi_base(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();
        $menu = $this->seedMenu(['name' => 'Voce Da Modificare']);
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $actor['tenantId']]);
        DB::table('menu_groups')->insert(['menu_id' => $menu['id'], 'group_id' => $groupId]);
        DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $menu['id'], 'id_cms_privileges' => $privilegeId]);

        $response = $this->post("http://localhost/admin/menu_management/edit-save/{$menu['id']}", $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Modificata',
            'type' => 'URL',
            'path' => 'https://esempio.test/modificato',
        ]));

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_menus', ['id' => $menu['id'], 'name' => 'Voce Modificata']);
    }

    /**
     * CARATTERIZZAZIONE di un comportamento sospetto (non corretto, solo
     * documentato): hook_before_edit() ricostruisce sempre 'path' dai
     * campi type-specifici per Module/Statistic/Qlik/Agent AI, ma la
     * ricostruzione non produce mai una stringa con '?', quindi il
     * controllo "$key == 'm'" pensato per preservare il '?m=<id>' aggiunto
     * alla creazione non scatta mai: il parametro sparisce ad ogni
     * modifica di una voce di questi tipi. Per Module: hook_before_add lo
     * aggiunge, hook_before_edit lo perde alla prima modifica successiva.
     */
    public function test_modifica_di_un_menu_di_tipo_module_perde_il_parametro_m(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();
        $usersModuleId = DB::table('cms_moduls')->where('path', 'users')->value('id');

        $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Module Da Modificare',
            'type' => 'Module',
            'module_slug' => $usersModuleId,
        ]));
        $menu = DB::table('cms_menus')->where('name', 'Voce Module Da Modificare')->first();
        $this->assertStringContainsString('?m=', $menu->path, 'Precondizione non valida: la creazione deve aggiungere ?m=.');

        $response = $this->post("http://localhost/admin/menu_management/edit-save/{$menu->id}", $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Module Da Modificare',
            'type' => 'Module',
            'module_slug' => $usersModuleId,
        ]));

        $response->assertStatus(302);
        $reloaded = DB::table('cms_menus')->where('id', $menu->id)->first();
        $this->assertSame('users', $reloaded->path);
    }

    public function test_non_si_puo_disattivare_lunica_voce_impostata_come_dashboard(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        // CRUDBooster::myPrivilegeId() (usato dall'hook per trovare "la
        // dashboard del mio privilegio") deve combaciare con la privilege
        // dell'attore autenticato, non una qualunque privilege superadmin.
        $privilegeId = DB::table('cms_users')->where('id', $actor['userId'])->value('id_cms_privileges');
        $dashboardMenu = $this->seedMenu(['name' => 'Voce Dashboard', 'is_dashboard' => 1, 'is_active' => 1]);
        DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $dashboardMenu['id'], 'id_cms_privileges' => $privilegeId]);

        $response = $this->post("http://localhost/admin/menu_management/edit-save/{$dashboardMenu['id']}", $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Dashboard',
            'type' => 'URL',
            'path' => 'https://esempio.test',
            'is_dashboard' => 0,
        ]));

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.cannot_disable_dashboard'));
        $this->assertDatabaseHas('cms_menus', ['id' => $dashboardMenu['id'], 'is_dashboard' => 1]);
    }

    public function test_cancellazione_menu_orfanizza_i_figli_invece_di_cancellarli(): void
    {
        $this->actingAsSuperadmin();
        $parent = $this->seedMenu(['name' => 'Voce Padre']);
        $child = $this->seedMenu(['name' => 'Voce Figlia', 'parent_id' => $parent['id']]);

        $response = $this->get("http://localhost/admin/menu_management/delete/{$parent['id']}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('cms_menus', ['id' => $parent['id']]);
        $this->assertDatabaseHas('cms_menus', ['id' => $child['id'], 'parent_id' => 0]);
    }

    public function test_cancellazione_menu_rimuove_le_associazioni_privilege(): void
    {
        $this->actingAsSuperadmin();
        $privilegeId = $this->seedPrivilege();
        $menu = $this->seedMenu(['name' => 'Voce Con Privilege']);
        DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $menu['id'], 'id_cms_privileges' => $privilegeId]);

        $response = $this->get("http://localhost/admin/menu_management/delete/{$menu['id']}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('cms_menus_privileges', ['id_cms_menus' => $menu['id']]);
    }

    /**
     * can_menu(): un tenantadmin puo' modificare/cancellare solo un menu
     * associato a UN SOLO tenant, e deve essere il proprio - non uno
     * condiviso tra piu' tenant (anche se il proprio e' tra questi).
     */
    public function test_tenantadmin_non_puo_modificare_un_menu_condiviso_tra_piu_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);
        $menu = $this->seedMenu(['name' => 'Voce Condivisa']);
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $otherTenantId]);

        $response = $this->get("http://localhost/admin/menu_management/delete/{$menu['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertDatabaseHas('cms_menus', ['id' => $menu['id']]);
    }

    public function test_creazione_sincronizza_privileges_tenants_e_gruppi_nelle_pivot_table(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $privilegeId = $this->seedPrivilege();

        $response = $this->post('http://localhost/admin/menu_management/add-save', $this->baseMenuPayload($actor, $privilegeId, [$groupId], [
            'name' => 'Voce Con Relazioni',
            'type' => 'URL',
            'path' => 'https://esempio.test',
        ]));

        $response->assertStatus(302);
        $menuId = DB::table('cms_menus')->where('name', 'Voce Con Relazioni')->value('id');
        $this->assertDatabaseHas('cms_menus_privileges', ['id_cms_menus' => $menuId, 'id_cms_privileges' => $privilegeId]);
        $this->assertDatabaseHas('menu_tenants', ['menu_id' => $menuId, 'tenant_id' => $actor['tenantId']]);
        $this->assertDatabaseHas('menu_groups', ['menu_id' => $menuId, 'group_id' => $groupId]);
    }

    public function test_post_save_menu_riordina_e_reimposta_il_genitore(): void
    {
        $this->actingAsSuperadmin();
        $first = $this->seedMenu(['name' => 'Prima Voce', 'sorting' => 1]);
        $second = $this->seedMenu(['name' => 'Seconda Voce', 'sorting' => 2]);

        // Struttura inviata dal drag-and-drop: 'second' diventa figlio di
        // 'first' (stesso formato prodotto dal JS lato client, vedi
        // menus_management.blade.php).
        $menuStructure = [[
            [
                'id' => $first['id'],
                'children' => [[
                    ['id' => $second['id'], 'children' => [[]]],
                ]],
            ],
        ]];

        $response = $this->post('http://localhost/admin/menu_management/save-menu', [
            'menus' => json_encode($menuStructure),
            'isActive' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('cms_menus', ['id' => $second['id'], 'parent_id' => $first['id'], 'sorting' => 1]);
        $this->assertDatabaseHas('cms_menus', ['id' => $first['id'], 'parent_id' => 0, 'sorting' => 1]);
    }

    /**
     * @param array{userId:int,tenantId:int} $actor
     * @param array<int,int> $groupIds
     */
    private function baseMenuPayload(array $actor, int $privilegeId, array $groupIds, array $overrides = []): array
    {
        return array_merge([
            'cms_menus_privileges' => [$privilegeId],
            'menu_tenants' => [$actor['tenantId']],
            'menu_groups' => $groupIds,
            'is_custom' => 0,
            'color' => 'normal',
            'is_active' => 1,
            'is_dashboard' => 0,
            'new_tab' => 0,
            'target_layout' => 0,
            'frame_width' => 100,
            'frame_width_unit' => '%',
            'frame_height' => 100,
            'frame_height_unit' => '%',
        ], $overrides);
    }
}
