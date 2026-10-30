<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 332, batch 2 (docs/refactoring/piani/332-chatai-controller-pulizia-i18n-
 * form-request.md): send_message e send_message_agent validano `message` (e
 * `agent_id`) con un Form Request. Dati non validi = JSON chatai_invalid_request
 * con HTTP 422, prima di toccare sessione, conf e storico; l'autorizzazione
 * dell'agente precede le regole (utente non autorizzato = denied_access, stato 200).
 *
 * Rossi sul codice precedente: i casi con message/agent_id non validi (500 o
 * inoltro all'agente) e il non autorizzato senza message (500). I positivi
 * provano che i dati validi arrivano allo storico.
 *
 * Le conf puntano a un URL che rifiuta subito la connessione: una richiesta
 * valida scrive lo storico con un errore di rete, senza attese.
 */
class ChatAISendMessageValidationTest extends TestCase
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

    /** Stessa simulazione di ChatAISendMessageAgentTest. */
    private function fakeActiveChatAILicense(): void
    {
        Storage::fake('license');
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'ChatAI']],
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    /** Conf con passphrase di 32+ caratteri, URL che rifiuta la connessione, primary per la chat flottante. */
    private function seedConf(): int
    {
        return DB::table('chatai_confs')->insertGetId([
            'title' => 'Agente Test',
            'url' => 'http://127.0.0.1:9/',
            'token' => str_repeat('k', 40),
            'primary' => 1,
            'created_at' => now(),
        ]);
    }

    /**
     * POST con il buffer catturato: i casi validi scrivono con echo (il buffer
     * va chiuso per non sporcare l'output di PHPUnit); i casi rifiutati dal Form
     * Request restituiscono una vera Response JSON.
     *
     * @param array<string,mixed> $payload
     */
    private function postTo(string $path, array $payload): TestResponse
    {
        ob_start();
        try {
            return $this->post('http://localhost/admin/chat_ai/' . $path, $payload);
        } finally {
            ob_end_clean();
        }
    }

    private function assertInvalidRequest(TestResponse $response): void
    {
        $response->assertStatus(422);
        $response->assertExactJson(['message' => trans('crudbooster.chatai_invalid_request')]);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
    }

    // 1 - send_message (chat flottante)

    public function test_send_message_senza_message_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message', []));
        $this->assertFalse(session()->has('chat_messages'));
    }

    public function test_send_message_con_message_vuoto_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message', ['message' => '']));
        $this->assertFalse(session()->has('chat_messages'));
    }

    public function test_send_message_con_message_array_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message', ['message' => ['a', 'b']]));
        $this->assertFalse(session()->has('chat_messages'));
    }

    // 1 - send_message_agent

    public function test_send_message_agent_senza_message_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message_agent', ['agent_id' => $confId]));
        $this->assertFalse(session()->has('chat_messages_' . $confId));
    }

    public function test_send_message_agent_con_message_vuoto_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message_agent', ['message' => '', 'agent_id' => $confId]));
        $this->assertFalse(session()->has('chat_messages_' . $confId));
    }

    public function test_send_message_agent_con_message_array_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message_agent', ['message' => ['a'], 'agent_id' => $confId]));
        $this->assertFalse(session()->has('chat_messages_' . $confId));
    }

    // 2

    public function test_send_message_agent_senza_agent_id_da_superadmin_riceve_422(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf();

        $this->assertInvalidRequest($this->postTo('send_message_agent', ['message' => 'ciao']));
    }

    // 3

    public function test_utente_non_autorizzato_senza_message_riceve_denied_access_e_non_422(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        // conf non abilitata a nessun tenant: l'utente non la vede
        $confId = $this->seedConf();

        $response = $this->postTo('send_message_agent', ['agent_id' => $confId]);

        $response->assertStatus(200);
        $response->assertExactJson(['message' => trans('crudbooster.denied_access')]);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
        $this->assertFalse(session()->has('chat_messages_' . $confId));
    }

    // 4 - positivi affiancati

    public function test_send_message_con_message_valido_arriva_allo_storico(): void
    {
        $this->actingAsSuperadmin();
        $this->seedConf();

        $response = $this->postTo('send_message', ['message' => 'ciao']);

        $this->assertNotSame(422, $response->getStatusCode());
        $this->assertSame(1, DB::table('chat_ai_history')->count());
    }

    public function test_send_message_agent_con_dati_validi_arriva_allo_storico(): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf();

        $response = $this->postTo('send_message_agent', ['message' => 'ciao', 'agent_id' => $confId]);

        $this->assertNotSame(422, $response->getStatusCode());
        $this->assertSame(1, DB::table('chat_ai_history')->count());
    }
}
