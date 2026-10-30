<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 329 (docs/refactoring/piani/329-chatai-send-message-agent-controllo-
 * autorizzazione.md): POST admin/chat_ai/send_message_agent deve controllare
 * ChatAIHelper::can_see_item($agent_id) prima di toccare sessione, conf e
 * storico. I casi 1-4 e 8 sono rossi sul codice precedente al piano; 5-7 sono
 * i positivi che provano che i negativi non passano "a vuoto".
 *
 * Le conf puntano a un URL che rifiuta subito la connessione, cosi' una
 * richiesta che supera il controllo risponde con un errore di rete (e scrive lo
 * storico) senza attese.
 */
class ChatAISendMessageAgentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private string $echoed = '';

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

    /** Conf con passphrase di 32+ caratteri (getToken() non fallisce) e URL che rifiuta la connessione. */
    private function seedConf(): int
    {
        return DB::table('chatai_confs')->insertGetId([
            'title' => 'Agente Test',
            'url' => 'http://127.0.0.1:9/',
            'token' => str_repeat('k', 40),
            'created_at' => now(),
        ]);
    }

    private function allowTenant(int $confId, int $tenantId): void
    {
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);
    }

    private function allowGroup(int $confId, int $groupId): void
    {
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);
    }

    /**
     * Il metodo scrive con echo (si cattura il buffer, come ApiTokensAddSaveTest),
     * ma il rifiuto denied_access esce dal Form Request come Response JSON (doc 332,
     * batch 2): con il buffer vuoto si legge il corpo del TestResponse. Il JSON e lo
     * stato 200 sono gli stessi di prima.
     *
     * @return array<string,mixed>
     */
    private function postSendMessageAgent(mixed $agentId): array
    {
        ob_start();
        try {
            $response = $this->post('http://localhost/admin/chat_ai/send_message_agent', [
                'message' => 'ciao',
                'agent_id' => $agentId,
            ]);
        } finally {
            $this->echoed = (string) ob_get_clean();
        }

        $body = $this->echoed !== '' ? $this->echoed : (string) $response->getContent();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, 'risposta non JSON: ' . $body);

        return $decoded;
    }

    private function assertDenied(array $json, mixed $agentId): void
    {
        $this->assertSame(trans('crudbooster.denied_access'), $json['message'] ?? null);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
        $this->assertFalse(session()->has('chat_messages_' . $agentId));
    }

    private function assertPassedAuthorization(array $json): void
    {
        $this->assertNotSame(trans('crudbooster.denied_access'), $json['message'] ?? null);
        $this->assertNotSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        // prova che la richiesta e' arrivata oltre il controllo, fino allo storico
        $this->assertSame(1, DB::table('chat_ai_history')->count());
    }

    // 1
    public function test_tenantadmin_con_conf_abilitata_solo_a_un_altro_tenant_riceve_accesso_negato(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $this->seedTenant());

        $this->assertDenied($this->postSendMessageAgent($confId), $confId);
    }

    // 2
    public function test_utente_standard_con_tenant_abilitato_ma_non_il_suo_gruppo_riceve_accesso_negato(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $ownGroupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $ownGroupId]);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);
        $this->allowGroup($confId, $this->seedGroup());

        $this->assertDenied($this->postSendMessageAgent($confId), $confId);
    }

    // 3
    public function test_agent_id_non_intero_da_non_superadmin_riceve_accesso_negato(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);

        $this->assertDenied($this->postSendMessageAgent('abc'), 'abc');
    }

    // 4
    public function test_agent_id_inesistente_da_non_superadmin_riceve_accesso_negato(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);

        $this->assertDenied($this->postSendMessageAgent(999999), 999999);
    }

    // 5
    public function test_tenantadmin_con_tenant_abilitato_supera_il_controllo(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);

        $this->assertPassedAuthorization($this->postSendMessageAgent($confId));
    }

    // 6
    public function test_utente_standard_con_tenant_e_gruppo_abilitati_supera_il_controllo(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $groupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $groupId]);
        $confId = $this->seedConf();
        $this->allowTenant($confId, $tenantId);
        $this->allowGroup($confId, $groupId);

        $this->assertPassedAuthorization($this->postSendMessageAgent($confId));
    }

    // 7
    public function test_superadmin_con_conf_esistente_non_e_bloccato(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();

        $this->assertPassedAuthorization($this->postSendMessageAgent($confId));
    }

    // 8
    public function test_superadmin_con_agent_id_inesistente_riceve_missing_item_senza_errore_500(): void
    {
        $this->actingAsSuperadmin();

        $json = $this->postSendMessageAgent(999999);

        $this->assertSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
    }
}
