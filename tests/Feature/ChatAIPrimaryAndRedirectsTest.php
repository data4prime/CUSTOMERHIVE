<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Caratterizzazione per il piano 330, parte 1 (docs/refactoring/piani/330-
 * chatai-controller-action-primary-e-autorizzazioni.md): fissa il comportamento
 * di AdminChatAIController PRIMA di spostare la logica nelle Action. Tutti i test
 * devono essere verdi sul codice precedente al refactoring e restare verdi dopo.
 *
 * Sezioni: 1) regola "una sola conf primary" via HTTP (add-save/edit-save, cosi'
 * passa dai veri hook di before e after); 2) redirect finale delle rotte remove;
 * 3) redirect /alert/1 delle rotte add.
 */
class ChatAIPrimaryAndRedirectsTest extends TestCase
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

    /** Stessa simulazione di ChatAIAuthorizationTest (modulo ChatAI non ancora sul license server). */
    private function fakeActiveChatAILicense(): void
    {
        Storage::fake('license');
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'ChatAI']],
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    private function seedConf(array $overrides = []): int
    {
        return DB::table('chatai_confs')->insertGetId(array_merge([
            'title' => 'Agente Test',
            'created_at' => now(),
        ], $overrides));
    }

    /** Campi obbligatori di cbInit(): passphrase di almeno 32 caratteri. */
    private function confPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Conf da HTTP',
            'method' => 'POST',
            'auth' => 'JWT',
            'url' => 'http://127.0.0.1:9/',
            'token' => str_repeat('k', 40),
        ], $overrides);
    }

    private function primaryOf(int $id): int
    {
        return (int) DB::table('chatai_confs')->where('id', $id)->value('primary');
    }

    private function addConfViaHttp(array $overrides = []): int
    {
        $title = 'Nuova ' . uniqid();
        $response = $this->post('http://localhost/admin/chat_ai/add-save', $this->confPayload(['title' => $title] + $overrides));
        $response->assertStatus(302);

        $id = DB::table('chatai_confs')->where('title', $title)->value('id');
        $this->assertNotNull($id, 'la conf non e\' stata creata: la richiesta non e\' arrivata al salvataggio');

        return (int) $id;
    }

    // ---------------------------------------------------------------
    // 1. Regola "una sola conf primary"
    // ---------------------------------------------------------------

    public function test_add_con_checkbox_primary_azzera_le_altre_conf(): void
    {
        $this->actingAsSuperadmin();
        $a = $this->seedConf(['primary' => 1]);

        $b = $this->addConfViaHttp(['primary' => 'primary']);

        $this->assertSame(0, $this->primaryOf($a));
        $this->assertSame(1, $this->primaryOf($b));
    }

    public function test_edit_con_checkbox_primary_azzera_le_altre_conf(): void
    {
        $this->actingAsSuperadmin();
        $a = $this->seedConf(['primary' => 1]);
        $b = $this->seedConf(['primary' => 0, 'url' => 'http://127.0.0.1:9/', 'token' => str_repeat('k', 40)]);

        $response = $this->post("http://localhost/admin/chat_ai/edit-save/{$b}", $this->confPayload(['primary' => 'primary']));
        $response->assertStatus(302);

        $this->assertSame(0, $this->primaryOf($a));
        $this->assertSame(1, $this->primaryOf($b));
    }

    public function test_add_senza_checkbox_non_tocca_le_altre_conf_e_la_nuova_non_e_primary(): void
    {
        $this->actingAsSuperadmin();
        $a = $this->seedConf(['primary' => 1]);

        // la chiave 'primary' manca del tutto dal POST: input_assignment() la
        // valorizza comunque (stringa vuota/null) e hook_before_add la porta a 0
        $b = $this->addConfViaHttp();

        $this->assertSame(1, $this->primaryOf($a));
        $this->assertSame(0, $this->primaryOf($b));
    }

    public function test_edit_senza_checkbox_non_tocca_le_altre_conf_e_la_conf_resta_non_primary(): void
    {
        $this->actingAsSuperadmin();
        $a = $this->seedConf(['primary' => 1]);
        $b = $this->seedConf(['primary' => 0]);

        $this->post("http://localhost/admin/chat_ai/edit-save/{$b}", $this->confPayload())->assertStatus(302);

        $this->assertSame(1, $this->primaryOf($a));
        $this->assertSame(0, $this->primaryOf($b));
    }

    public function test_edit_senza_checkbox_toglie_la_primary_alla_conf_modificata(): void
    {
        $this->actingAsSuperadmin();
        $a = $this->seedConf(['primary' => 1]);

        $this->post("http://localhost/admin/chat_ai/edit-save/{$a}", $this->confPayload())->assertStatus(302);

        $this->assertSame(0, $this->primaryOf($a));
    }

    public function test_una_conf_soft_deleted_primary_resta_primary_quando_un_altra_diventa_primary(): void
    {
        $this->actingAsSuperadmin();
        // la query Eloquent della regola esclude i soft-deleted: comportamento
        // attuale, fissato cosi' com'e' (la parte 2 del piano non lo cambia)
        $deleted = $this->seedConf(['primary' => 1, 'deleted_at' => now()]);
        $active = $this->seedConf(['primary' => 1]);

        $b = $this->addConfViaHttp(['primary' => 'primary']);

        $this->assertSame(1, $this->primaryOf($deleted));
        $this->assertSame(0, $this->primaryOf($active));
        $this->assertSame(1, $this->primaryOf($b));
    }

    // ---------------------------------------------------------------
    // 2. Redirect finale delle remove_*
    // ---------------------------------------------------------------

    public function test_remove_tenant_redirige_alla_pagina_tenant_della_conf(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $tenantId = $this->seedTenant();
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);

        $response = $this->get("http://localhost/admin/chat_ai/{$confId}/remove_tenant/{$tenantId}");

        $response->assertRedirect();
        $this->assertStringEndsWith("/admin/chat_ai/tenant/{$confId}", $response->headers->get('Location'));
        $this->assertSame(0, DB::table('chatai_tenants_allowed')->count());
    }

    public function test_remove_authorization_redirige_alla_pagina_access_della_conf(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);

        $response = $this->get("http://localhost/admin/chat_ai/{$confId}/deauth/{$groupId}");

        $response->assertRedirect();
        $this->assertStringEndsWith("/admin/chat_ai/access/{$confId}", $response->headers->get('Location'));
        $this->assertSame(0, DB::table('chatai_groups_allowed')->count());
    }

    public function test_remove_con_id_non_interi_nega_l_accesso_e_non_cancella(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);

        $responses = [
            $this->get("http://localhost/admin/chat_ai/{$confId}/remove_tenant/abc"),
            $this->get("http://localhost/admin/chat_ai/abc/remove_tenant/{$tenantId}"),
            $this->get("http://localhost/admin/chat_ai/{$confId}/deauth/abc"),
            $this->get("http://localhost/admin/chat_ai/abc/deauth/{$groupId}"),
        ];

        foreach ($responses as $response) {
            $response->assertRedirect();
            $location = (string) $response->headers->get('Location');
            $this->assertStringNotContainsString('chat_ai/tenant', $location);
            $this->assertStringNotContainsString('chat_ai/access', $location);
        }
        $this->assertSame(1, DB::table('chatai_groups_allowed')->count());
        $this->assertSame(1, DB::table('chatai_tenants_allowed')->count());
    }

    // ---------------------------------------------------------------
    // 3. Redirect /alert/1 delle add_*
    // ---------------------------------------------------------------

    public function test_add_tenant_con_tenant_non_valido_redirige_a_alert_1_senza_creare_righe(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $tenantId = $this->seedTenant();
        $returnUrl = "/admin/chat_ai/tenant/{$confId}";

        // caso positivo affiancato: prova che la richiesta arriva al controller
        $ok = $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", ['name' => $tenantId, 'return_url' => $returnUrl]);
        $this->assertStringEndsNotWith('/alert/1', (string) $ok->headers->get('Location'));
        $this->assertSame(1, DB::table('chatai_tenants_allowed')->count());

        foreach (['', 'abc', 999999] as $invalid) {
            $response = $this->post("http://localhost/admin/chat_ai/{$confId}/add_tenant", ['name' => $invalid, 'return_url' => $returnUrl]);
            $response->assertRedirect();
            $this->assertStringEndsWith($returnUrl . '/alert/1', (string) $response->headers->get('Location'));
        }
        $this->assertSame(1, DB::table('chatai_tenants_allowed')->count());
    }

    public function test_add_authorization_con_gruppo_non_valido_redirige_a_alert_1_senza_creare_righe(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $returnUrl = "/admin/chat_ai/access/{$confId}";

        $ok = $this->post("http://localhost/admin/chat_ai/{$confId}/auth", ['name' => $groupId, 'return_url' => $returnUrl]);
        $this->assertStringEndsNotWith('/alert/1', (string) $ok->headers->get('Location'));
        $this->assertSame(1, DB::table('chatai_groups_allowed')->count());

        foreach (['', 'abc', 999999] as $invalid) {
            $response = $this->post("http://localhost/admin/chat_ai/{$confId}/auth", ['name' => $invalid, 'return_url' => $returnUrl]);
            $response->assertRedirect();
            $this->assertStringEndsWith($returnUrl . '/alert/1', (string) $response->headers->get('Location'));
        }
        $this->assertSame(1, DB::table('chatai_groups_allowed')->count());
    }
}
