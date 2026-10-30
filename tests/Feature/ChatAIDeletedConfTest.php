<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 330, parte 2 (correzione con cambio visibile): la chat ignora le conf
 * ChatAI soft-deleted. send_message() e send_message_agent() leggevano la conf
 * con DB::table() senza filtro su deleted_at, mentre ChatAIHelper::getToken()
 * usa find() (che le esclude): una conf cancellata dava "token error" o, se era
 * ancora primary, veniva scelta al posto di quella nuova.
 *
 * I casi 1-3 sono rossi sul codice precedente alla parte 2; il 4 e' il positivo
 * che prova che i negativi non passano "a vuoto". Le conf attive puntano a un
 * URL che rifiuta subito la connessione, come in ChatAISendMessageAgentTest.
 */
class ChatAIDeletedConfTest extends TestCase
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

    /**
     * Conf con passphrase di 40 caratteri (getToken() produce un JWT) e URL che
     * rifiuta la connessione. Con deleted_at valorizzato getToken() restituisce
     * comunque false (find() esclude i soft-deleted).
     */
    private function seedConf(array $overrides = []): int
    {
        return DB::table('chatai_confs')->insertGetId(array_merge([
            'title' => 'Agente Test',
            'url' => 'http://127.0.0.1:9/',
            'token' => str_repeat('k', 40),
            'created_at' => now(),
        ], $overrides));
    }

    /**
     * @return array<string,mixed>
     */
    private function postChat(string $endpoint, array $payload): array
    {
        ob_start();
        try {
            $this->post('http://localhost/admin/chat_ai/' . $endpoint, $payload + ['message' => 'ciao']);
        } finally {
            $echoed = (string) ob_get_clean();
        }

        $decoded = json_decode($echoed, true);
        $this->assertIsArray($decoded, 'risposta non JSON: ' . $echoed);

        return $decoded;
    }

    // 1
    public function test_send_message_con_la_sola_primary_cancellata_risponde_missing_item(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf(['primary' => 1, 'deleted_at' => now()]);

        $json = $this->postChat('send_message', []);

        $this->assertSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
    }

    // 2
    public function test_send_message_con_due_primary_usa_quella_attiva_e_non_la_cancellata(): void
    {
        $this->actingAsSuperadmin();
        // la cancellata ha l'id minore: first() senza filtro la sceglierebbe
        $this->seedConf(['primary' => 1, 'deleted_at' => now()]);
        $this->seedConf(['primary' => 1]);

        $json = $this->postChat('send_message', []);

        $this->assertNotSame(trans('crudbooster.chatai_token_error'), $json['message'] ?? null);
        $this->assertNotSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        // arrivata fino a saveChatHistory con la conf attiva
        $this->assertSame(1, DB::table('chat_ai_history')->count());
    }

    // 3
    public function test_superadmin_con_agent_id_di_una_conf_cancellata_riceve_missing_item(): void
    {
        $this->actingAsSuperadmin();
        $deleted = $this->seedConf(['deleted_at' => now()]);

        $json = $this->postChat('send_message_agent', ['agent_id' => $deleted]);

        $this->assertSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
    }

    // 4
    public function test_positivi_primary_attiva_e_agent_id_attivo_arrivano_allo_storico(): void
    {
        $this->actingAsSuperadmin();
        $primary = $this->seedConf(['primary' => 1]);

        $json = $this->postChat('send_message', []);
        $this->assertNotSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        $this->assertNotSame(trans('crudbooster.chatai_token_error'), $json['message'] ?? null);
        $this->assertSame(1, DB::table('chat_ai_history')->count());

        $json = $this->postChat('send_message_agent', ['agent_id' => $primary]);
        $this->assertNotSame(trans('crudbooster.missing_item'), $json['message'] ?? null);
        $this->assertNotSame(trans('crudbooster.chatai_token_error'), $json['message'] ?? null);
        // send_message salva con chat_ai_id null, l'agente con l'id della conf
        $this->assertSame(1, DB::table('chat_ai_history')->where('chat_ai_id', $primary)->count());
    }
}
