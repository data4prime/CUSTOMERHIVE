<?php

namespace Tests\Feature;

use App\Helpers\ChatAIHelper;
use App\Helpers\QlikHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Comportamento OBIETTIVO del piano 318 (docs/refactoring/piani/318-chatai-
 * autorizzazioni-come-qlik.md): le autorizzazioni delle conf ChatAI vivono
 * nelle tabelle dedicate chatai_groups_allowed / chatai_tenants_allowed e non
 * hanno piu' nulla in comune con items_allowed / tenants_allowed di Qlik.
 *
 * Sezioni: 1) ChatAIHelper::can_see_item (stessa matrice di
 * QlikItemVisibilityTest); 2) isolamento da Qlik a parita' di id; 3) gestione
 * autorizzazioni via HTTP (solo superadmin, redirect filtrato, nessun
 * duplicato); 4) cancellazione conf con cascata solo sulle tabelle ChatAI;
 * 5) selettore "Agent AI" del menu.
 */
class ChatAIAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
        $this->registerAdminModule('chat_ai', 'AdminChatAIController', 'chatai_confs');
        $this->fakeActiveChatAILicense();
    }

    /**
     * Simula una licenza con il modulo "ChatAI" attivo (oggi il modulo non
     * esiste sul license server: docs/refactoring/300). Le route custom
     * admin/chat_ai/* passano da EnforceModuleLicense, che altrimenti rimanda
     * ogni richiesta alla dashboard, e il selettore "Agent AI" del menu e'
     * mostrato solo con la licenza attiva (MenusController::cbInit).
     * Storage::fake() evita di toccare il license.json reale.
     */
    private function fakeActiveChatAILicense(): void
    {
        Storage::fake('license');
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'ChatAI']],
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        // token gia' in cache: nessuna chiamata HTTP al license server
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    private function seedConf(array $overrides = []): int
    {
        return DB::table('chatai_confs')->insertGetId(array_merge([
            'title' => 'Agente Test',
            'created_at' => now(),
        ], $overrides));
    }

    private function seedQlikItem(): int
    {
        return DB::table('qlik_items')->insertGetId(['title' => 'Item Qlik Test', 'created_at' => now()]);
    }

    private function allowTenant(int $confId, int $tenantId): void
    {
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);
    }

    private function allowGroup(int $confId, int $groupId): void
    {
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);
    }

    // ---------------------------------------------------------------
    // 1. ChatAIHelper::can_see_item
    // ---------------------------------------------------------------

    public function test_superadmin_vede_sempre_qualunque_conf(): void
    {
        $this->actingAsSuperadmin();

        $this->assertTrue(ChatAIHelper::can_see_item($this->seedConf()));
    }

    public function test_id_non_intero_o_inesistente_non_e_visibile_a_un_utente_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);

        $this->assertFalse(ChatAIHelper::can_see_item('non-un-intero'));
        $this->assertFalse(ChatAIHelper::can_see_item(999999));
    }

    public function test_tenantadmin_vede_una_conf_il_cui_tenant_e_abilitato(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);

        $this->assertTrue(ChatAIHelper::can_see_item($confId));
    }

    public function test_tenantadmin_non_vede_una_conf_abilitata_solo_a_un_altro_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $otherTenantId);

        $this->assertFalse(ChatAIHelper::can_see_item($confId));
    }

    public function test_utente_standard_vede_una_conf_con_tenant_e_gruppo_abilitati(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $groupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $groupId]);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);
        $this->allowGroup($confId, $groupId);

        $this->assertTrue(ChatAIHelper::can_see_item($confId));
    }

    public function test_utente_standard_non_vede_una_conf_se_il_gruppo_abilitato_non_e_il_suo(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $ownGroupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $ownGroupId]);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);
        $this->allowGroup($confId, $otherGroupId);

        $this->assertFalse(ChatAIHelper::can_see_item($confId));
    }

    public function test_utente_standard_non_vede_una_conf_se_il_tenant_non_e_abilitato_anche_con_gruppo_ok(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $groupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $groupId]);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $otherTenantId);
        $this->allowGroup($confId, $groupId);

        $this->assertFalse(ChatAIHelper::can_see_item($confId));
    }

    // ---------------------------------------------------------------
    // 2. Isolamento da Qlik (stesso id, tabelle diverse)
    // ---------------------------------------------------------------

    public function test_item_qlik_abilitato_non_rende_visibile_la_conf_chatai_con_lo_stesso_id(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $qlikId = $this->seedQlikItem();
        $confId = $this->seedConf(['id' => $qlikId]);
        DB::table('tenants_allowed')->insert(['item_id' => $qlikId, 'tenant_id' => $tenantId]);

        $this->assertSame($qlikId, $confId);
        $this->assertTrue(QlikHelper::can_see_item($qlikId));
        $this->assertFalse(ChatAIHelper::can_see_item($confId));
    }

    public function test_conf_chatai_abilitata_non_rende_visibile_l_item_qlik_con_lo_stesso_id(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $qlikId = $this->seedQlikItem();
        $confId = $this->seedConf(['id' => $qlikId]);
        $this->allowTenant($confId, $tenantId);

        $this->assertTrue(ChatAIHelper::can_see_item($confId));
        $this->assertFalse(QlikHelper::can_see_item($qlikId));
    }

    // ---------------------------------------------------------------
    // 3. Gestione autorizzazioni via HTTP
    // ---------------------------------------------------------------

    public function test_add_authorization_crea_la_riga_una_sola_volta(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $payload = ['name' => $groupId, 'return_url' => '/admin/chat_ai/access/' . $confId];

        $this->post("http://localhost/admin/chat_ai/{$confId}/auth", $payload);
        $this->post("http://localhost/admin/chat_ai/{$confId}/auth", $payload);

        $this->assertSame(1, DB::table('chatai_groups_allowed')
            ->where('chatai_conf_id', $confId)->where('group_id', $groupId)->count());
        $this->assertSame(0, DB::table('items_allowed')->where('group_id', $groupId)->count());
    }

    public function test_add_tenant_crea_la_riga_una_sola_volta(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $tenantId = $this->seedTenant();
        $payload = ['name' => $tenantId, 'return_url' => '/admin/chat_ai/tenant/' . $confId];

        $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", $payload);
        $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", $payload);

        $this->assertSame(1, DB::table('chatai_tenants_allowed')
            ->where('chatai_conf_id', $confId)->where('tenant_id', $tenantId)->count());
        $this->assertSame(0, DB::table('tenants_allowed')->where('tenant_id', $tenantId)->count());
    }

    public function test_add_con_id_non_validi_non_crea_righe(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $tenantId = $this->seedTenant();

        // gruppo/tenant inesistente o non intero
        $this->post("http://localhost/admin/chat_ai/{$confId}/auth", ['name' => 999999, 'return_url' => '/admin/chat_ai/access/' . $confId]);
        $this->post("http://localhost/admin/chat_ai/{$confId}/auth", ['name' => 'abc', 'return_url' => '/admin/chat_ai/access/' . $confId]);
        $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", ['name' => 999999, 'return_url' => '/admin/chat_ai/tenant/' . $confId]);
        $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", ['name' => 'abc', 'return_url' => '/admin/chat_ai/tenant/' . $confId]);
        // conf inesistente o non intera, con gruppo/tenant validi
        $this->post('http://localhost/admin/chat_ai/999999/auth', ['name' => $groupId]);
        $this->post('http://localhost/admin/chat_ai/abc/auth', ['name' => $groupId]);
        $this->post('http://localhost/admin/chat_ai/999999/add_tenant', ['name' => $tenantId]);
        $this->post('http://localhost/admin/chat_ai/abc/add_tenant', ['name' => $tenantId]);

        $this->assertSame(0, DB::table('chatai_groups_allowed')->count());
        $this->assertSame(0, DB::table('chatai_tenants_allowed')->count());
    }

    public function test_return_url_verso_host_esterno_riporta_al_fallback(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();

        $response = $this->post("http://localhost/admin/chat_ai/{$confId}/auth", [
            'name' => $groupId,
            'return_url' => 'https://evil.example.com/phish',
            'ref_mainpath' => 'https://evil.example.com/other',
        ]);

        $response->assertRedirect();
        $this->assertStringNotContainsString('evil.example.com', $response->headers->get('Location'));
    }

    public function test_add_tenant_con_return_url_verso_host_esterno_riporta_al_fallback(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $tenantId = $this->seedTenant();

        $response = $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", [
            'name' => $tenantId,
            'return_url' => 'https://evil.example.com/phish',
            'ref_mainpath' => 'https://evil.example.com/other',
        ]);

        $response->assertRedirect();
        $this->assertStringNotContainsString('evil.example.com', $response->headers->get('Location'));
    }

    // ---------------------------------------------------------------
    // 3b. Popup item_access_datamodal / item_tenant_datamodal
    //     (CBController::getModalData + browser.blade.php del tipo)
    // ---------------------------------------------------------------

    private function popupHtml(string $type, int $selectTo, string $extraQuery): string
    {
        $table = $type === 'item_access_datamodal' ? 'groups' : 'tenants';
        $url = 'http://localhost/admin/chat_ai/modal-data?table=' . $table
            . '&columns=id,name&name_column=name&columns_name_alias=Name&where=&select_to=' . $selectTo
            . '&type=' . $type . $extraQuery;

        $response = $this->get($url);
        $response->assertStatus(200);

        return $response->getContent();
    }

    /**
     * Semina tre righe (gruppi o tenant): "Qlik" presente solo nella tabella
     * Qlik per $id, "ChatAI" presente solo nella tabella ChatAI per $id,
     * "Libero" in nessuna. I nomi sono unici per poterli cercare nell'HTML.
     *
     * @return array{id:int,qlik:string,chatai:string,libero:string}
     */
    private function seedPopupScenario(bool $groups): array
    {
        $confId = $this->seedConf();
        $names = ['qlik' => 'ZzQlikSolo', 'chatai' => 'ZzChatAiSolo', 'libero' => 'ZzLibero'];
        $ids = [];
        foreach ($names as $key => $name) {
            $ids[$key] = $groups ? $this->seedGroup() : $this->seedTenant();
            DB::table($groups ? 'groups' : 'tenants')->where('id', $ids[$key])->update(['name' => $name]);
        }

        if ($groups) {
            DB::table('items_allowed')->insert(['item_id' => $confId, 'group_id' => $ids['qlik']]);
            $this->allowGroup($confId, $ids['chatai']);
        } else {
            DB::table('tenants_allowed')->insert(['item_id' => $confId, 'tenant_id' => $ids['qlik']]);
            $this->allowTenant($confId, $ids['chatai']);
        }

        return ['id' => $confId] + $names;
    }

    /**
     * @return array<string,array{0:string,1:string,2:string}> etichetta => [query extra, nome escluso, nome visibile]
     */
    public static function popupScopeProvider(): array
    {
        return [
            'senza allowed_scope = qlik' => ['', 'ZzQlikSolo', 'ZzChatAiSolo'],
            'allowed_scope=qlik' => ['&allowed_scope=qlik', 'ZzQlikSolo', 'ZzChatAiSolo'],
            'allowed_scope=chatai' => ['&allowed_scope=chatai', 'ZzChatAiSolo', 'ZzQlikSolo'],
            'allowed_scope sconosciuto = qlik' => ['&allowed_scope=foo', 'ZzQlikSolo', 'ZzChatAiSolo'],
            'allowed_scope array = qlik' => ['&allowed_scope[]=x', 'ZzQlikSolo', 'ZzChatAiSolo'],
        ];
    }

    #[DataProvider('popupScopeProvider')]
    public function test_popup_gruppi_esclude_i_gia_autorizzati_in_base_allo_scope(string $extra, string $excluded, string $visible): void
    {
        $this->actingAsSuperadmin();
        $scenario = $this->seedPopupScenario(true);

        $html = $this->popupHtml('item_access_datamodal', $scenario['id'], $extra);

        $this->assertStringNotContainsString($excluded, $html);
        $this->assertStringContainsString($visible, $html);
        $this->assertStringContainsString('ZzLibero', $html);
    }

    #[DataProvider('popupScopeProvider')]
    public function test_popup_tenant_esclude_i_gia_autorizzati_in_base_allo_scope(string $extra, string $excluded, string $visible): void
    {
        $this->actingAsSuperadmin();
        $scenario = $this->seedPopupScenario(false);

        $html = $this->popupHtml('item_tenant_datamodal', $scenario['id'], $extra);

        $this->assertStringNotContainsString($excluded, $html);
        $this->assertStringContainsString($visible, $html);
        $this->assertStringContainsString('ZzLibero', $html);
    }

    public function test_remove_cancella_solo_le_righe_chatai(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        $this->allowGroup($confId, $groupId);
        $this->allowTenant($confId, $tenantId);
        // riga Qlik con lo stesso item_id: non deve essere toccata
        DB::table('items_allowed')->insert(['item_id' => $confId, 'group_id' => $groupId]);
        DB::table('tenants_allowed')->insert(['item_id' => $confId, 'tenant_id' => $tenantId]);

        $this->get("http://localhost/admin/chat_ai/{$confId}/deauth/{$groupId}");
        $this->get("http://localhost/admin/chat_ai/{$confId}/remove_tenant/{$tenantId}");

        $this->assertSame(0, DB::table('chatai_groups_allowed')->count());
        $this->assertSame(0, DB::table('chatai_tenants_allowed')->count());
        $this->assertSame(1, DB::table('items_allowed')->where('item_id', $confId)->count());
        $this->assertSame(1, DB::table('tenants_allowed')->where('item_id', $confId)->count());
    }

    public function test_un_non_superadmin_non_puo_gestire_le_autorizzazioni(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['chat_ai']);
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $this->allowTenant($confId, $tenantId);

        $this->post("http://localhost/admin/chat_ai/{$confId}/auth", ['name' => $groupId]);
        $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", ['name' => $this->seedTenant()]);
        $this->get("http://localhost/admin/chat_ai/{$confId}/remove_tenant/{$tenantId}");

        $this->assertSame(0, DB::table('chatai_groups_allowed')->count());
        $this->assertSame(1, DB::table('chatai_tenants_allowed')->count());
    }

    // ---------------------------------------------------------------
    // 4. Cancellazione della conf
    // ---------------------------------------------------------------

    public function test_cancellare_una_conf_rimuove_le_sue_autorizzazioni_ma_non_quelle_qlik(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        $this->allowGroup($confId, $groupId);
        $this->allowTenant($confId, $tenantId);
        DB::table('items_allowed')->insert(['item_id' => $confId, 'group_id' => $groupId]);
        DB::table('tenants_allowed')->insert(['item_id' => $confId, 'tenant_id' => $tenantId]);

        $this->get("http://localhost/admin/chat_ai/delete/{$confId}");

        $this->assertSame(0, DB::table('chatai_groups_allowed')->where('chatai_conf_id', $confId)->count());
        $this->assertSame(0, DB::table('chatai_tenants_allowed')->where('chatai_conf_id', $confId)->count());
        $this->assertSame(1, DB::table('items_allowed')->where('item_id', $confId)->count());
        $this->assertSame(1, DB::table('tenants_allowed')->where('item_id', $confId)->count());
    }

    // ---------------------------------------------------------------
    // 5. Selettore "Agent AI" del Menu Management
    // ---------------------------------------------------------------

    public function test_il_selettore_agent_ai_del_menu_mostra_solo_le_conf_del_tenant_dell_utente(): void
    {
        $actor = $this->actingAsSuperadmin();
        $visible = $this->seedConf(['title' => 'Agente Visibile']);
        $hidden = $this->seedConf(['title' => 'Agente Di Altri']);
        $this->allowTenant($visible, $actor['tenantId']);
        $this->allowTenant($hidden, $this->seedTenant());

        // Si esegue la dataquery che MenusController::cbInit() costruisce per il
        // campo "chat_ai", senza renderizzare la pagina: GET .../add e' sempre
        // rimandata (button_add = false) e il form di modifica
        // (menus/form_body.blade.php) oggi non compila in PHP 8.
        $controller = new \App\Http\Controllers\System\MenusController();
        $controller->cbInit();
        $field = collect($controller->form)->firstWhere('name', 'chat_ai');

        $this->assertNotNull($field, 'il campo "chat_ai" non e\' nel form del menu (licenza ChatAI non attiva?)');
        $labels = array_column(DB::select($field['dataquery']), 'label');

        $this->assertContains('Agente Visibile', $labels);
        $this->assertNotContains('Agente Di Altri', $labels);
    }
}
