<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 331 (docs/refactoring/piani/331-chatai-client-service.md): test di
 * caratterizzazione della parte "parla con l'agente" di send_message() e
 * send_message_agent(), scritti prima dell'estrazione in ChatAIClient. Devono
 * essere verdi prima e dopo: fissano errore di rete, errore token, accumulo in
 * sessione e contenuto dello storico per ENTRAMBI gli endpoint.
 *
 * Le conf puntano a un URL che rifiuta subito la connessione (cURL fallisce),
 * cosi' si raggiunge il ramo "errore di rete" senza server.
 */
class ChatAIRelayCharacterizationTest extends TestCase
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
     * Gli endpoint da provare: nome e se e' quello dell'agente (agent_id,
     * chiave di sessione per id, chat_ai_id valorizzato nello storico).
     *
     * @return array<string,array{0:string,1:bool}>
     */
    public static function endpoints(): array
    {
        return [
            'send_message' => ['send_message', false],
            'send_message_agent' => ['send_message_agent', true],
        ];
    }

    /** Conf primary (serve a send_message) con passphrase scelta; URL che rifiuta la connessione. */
    private function seedConf(string $passphrase): int
    {
        return DB::table('chatai_confs')->insertGetId([
            'title' => 'Agente Test',
            'url' => 'http://127.0.0.1:9/',
            'token' => $passphrase,
            'primary' => 1,
            'created_at' => now(),
        ]);
    }

    /** @return array<string,mixed> */
    private function relay(string $endpoint, bool $isAgent, int $confId, string $message = 'ciao'): array
    {
        $payload = ['message' => $message];
        if ($isAgent) {
            $payload['agent_id'] = $confId;
        }

        ob_start();
        try {
            $this->call('POST', 'http://localhost/admin/chat_ai/' . $endpoint, $payload);
        } finally {
            $echoed = (string) ob_get_clean();
        }

        $decoded = json_decode($echoed, true);
        $this->assertIsArray($decoded, 'risposta non JSON: ' . $echoed);

        return $decoded;
    }

    private function sessionKey(bool $isAgent, int $confId): string
    {
        return $isAgent ? 'chat_messages_' . $confId : 'chat_messages';
    }

    // 1: errore di rete
    #[DataProvider('endpoints')]
    public function test_errore_di_rete_risponde_con_il_messaggio_e_lo_registra_in_sessione_e_storico(string $endpoint, bool $isAgent): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf(str_repeat('k', 40));

        $json = $this->relay($endpoint, $isAgent, $confId);

        // Scritto prima di D3 con il testo italiano hardcoded; dopo D3 usa la chiave
        // (it identica al testo di prima, en nuova)
        $expected = trans('crudbooster.chatai_request_error');
        $this->assertNotSame('crudbooster.chatai_request_error', $expected, 'chiave i18n mancante');
        $this->assertSame($expected, $json['message'] ?? null);
        $this->assertSame(1, DB::table('chat_ai_history')->count());
        $this->assertSame(
            [['message' => 'ciao', 'response' => $expected]],
            session()->get($this->sessionKey($isAgent, $confId))
        );
    }

    // 2: errore token, con il positivo affiancato (stesso setup, passphrase valida)
    #[DataProvider('endpoints')]
    public function test_errore_token_non_scrive_ne_sessione_ne_storico(string $endpoint, bool $isAgent): void
    {
        $this->actingAsSuperadmin();

        // positivo: con passphrase valida la richiesta arriva fino allo storico
        $okId = $this->seedConf(str_repeat('k', 40));
        $this->relay($endpoint, $isAgent, $okId);
        $this->assertSame(1, DB::table('chat_ai_history')->count());

        // negativo: passphrase di 10 caratteri, conf al posto della precedente
        DB::table('chatai_confs')->where('id', $okId)->update(['primary' => 0, 'deleted_at' => now()]);
        DB::table('chat_ai_history')->delete();
        session()->forget($this->sessionKey($isAgent, $okId));
        $badId = $this->seedConf(str_repeat('k', 10));

        $json = $this->relay($endpoint, $isAgent, $badId);

        $this->assertSame(trans('crudbooster.chatai_token_error'), $json['message'] ?? null);
        $this->assertSame(0, DB::table('chat_ai_history')->count());
        $key = $this->sessionKey($isAgent, $badId);
        // send_message inizializza 'chat_messages' a [] prima del token: nessuna coppia aggiunta
        $this->assertSame([], session()->get($key, []));
    }

    // 3: accumulo
    #[DataProvider('endpoints')]
    public function test_due_messaggi_accumulano_due_coppie_in_sessione_e_una_sola_riga_di_storico(string $endpoint, bool $isAgent): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf(str_repeat('k', 40));

        $this->relay($endpoint, $isAgent, $confId, 'primo');
        $this->relay($endpoint, $isAgent, $confId, 'secondo');

        $session = session()->get($this->sessionKey($isAgent, $confId));
        $this->assertCount(2, $session);
        $this->assertSame('primo', $session[0]['message']);
        $this->assertSame('secondo', $session[1]['message']);
        // la riga viene aggiornata, non duplicata
        $this->assertSame(1, DB::table('chat_ai_history')->count());
    }

    // 4: contenuto dello storico
    #[DataProvider('endpoints')]
    public function test_la_riga_di_storico_porta_chat_ai_id_e_il_messaggio_inviato(string $endpoint, bool $isAgent): void
    {
        $this->actingAsSuperadmin();
        $confId = $this->seedConf(str_repeat('k', 40));

        $this->relay($endpoint, $isAgent, $confId, 'messaggio di prova');

        $row = DB::table('chat_ai_history')->first();
        $this->assertNotNull($row);
        $this->assertSame($isAgent ? $confId : null, $row->chat_ai_id === null ? null : (int) $row->chat_ai_id);

        // messages = lista di {messages: <json della coppia>, created_by, created_at}
        $entries = json_decode($row->messages, true);
        $this->assertCount(1, $entries);
        $pair = json_decode($entries[0]['messages'], true);
        $this->assertSame('messaggio di prova', $pair['message']);
    }
}
