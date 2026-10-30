<?php

namespace Tests\Feature;

use App\Services\ChatAI\ChatAIClient;
use Tests\TestCase;

/**
 * Piano 331: lettura della risposta dell'agente (semantica di
 * send_message_agent() per entrambi gli endpoint, decisione D1), senza rete.
 */
class ChatAIClientTest extends TestCase
{
    private function client(): ChatAIClient
    {
        return new ChatAIClient();
    }

    public function test_risposta_con_text_usa_text_e_lascia_raw_invariato(): void
    {
        $raw = '{"text":"ciao","message":"altro"}';

        $result = $this->client()->interpret($raw);

        $this->assertSame('ciao', $result['message']);
        $this->assertSame($raw, $result['raw']);
    }

    public function test_risposta_con_solo_message_usa_message_e_lascia_raw_invariato(): void
    {
        $raw = '{"message":"solo message"}';

        $result = $this->client()->interpret($raw);

        $this->assertSame('solo message', $result['message']);
        $this->assertSame($raw, $result['raw']);
    }

    public function test_risposta_senza_text_ne_message_restituisce_il_decodificato_dentro_message(): void
    {
        $result = $this->client()->interpret('{"altro":"x"}');

        $this->assertSame(['altro' => 'x'], $result['message']);
        $this->assertSame(json_encode(['message' => ['altro' => 'x']]), $result['raw']);
    }

    public function test_risposta_non_json_restituisce_message_null(): void
    {
        $result = $this->client()->interpret('<html>502</html>');

        $this->assertNull($result['message']);
        $this->assertSame('{"message":null}', $result['raw']);
    }

    public function test_curl_fallito_restituisce_il_messaggio_di_errore_di_rete(): void
    {
        $expected = trans('crudbooster.chatai_request_error');
        $this->assertNotSame('crudbooster.chatai_request_error', $expected, 'chiave i18n mancante');

        $result = $this->client()->interpret(false);

        $this->assertSame($expected, $result['message']);
        $this->assertSame(json_encode(['message' => $expected]), $result['raw']);
    }

    public function test_send_con_url_irraggiungibile_da_lo_stesso_risultato_dell_errore_di_rete(): void
    {
        $result = $this->client()->send('http://127.0.0.1:9/', 'token', 'ciao', 1);

        $this->assertSame($this->client()->interpret(false), $result);
    }
}
