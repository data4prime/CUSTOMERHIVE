<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione di ApiTokensController::postAddSave() (B5, doc
 * 327): fissano il comportamento PRIMA di spostare la validazione in un Form
 * Request (StoreApiTokenRequest) e devono restare verdi prima e dopo.
 *
 * Ordine da preservare: permesso `create` -> validazione -> creazione del
 * token. Permesso negato = redirect al mainpath con denied_access (non 403);
 * validazione fallita = redirect indietro (Referer) con errori e input.
 */
class ApiTokensAddSaveTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const REFERER = 'http://localhost/admin/api_tokens/add';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules(['api_tokens' => 'ApiTokensController']);
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function tokenCount(): int
    {
        return DB::table('personal_access_tokens')->count();
    }

    private function payload(int $userId, array $overrides = []): array
    {
        return array_merge([
            'tokenable_id' => $userId,
            'name' => 'Token di prova',
            'expiry_choice' => 'never',
            'expiry_custom_date' => '',
        ], $overrides);
    }

    /**
     * HTML scritto da cbView() con echo (non e' nel corpo della risposta):
     * lo cattura postAddSave() per le pagine che renderizzano una vista.
     */
    private string $echoedHtml = '';

    private function postAddSave(array $data)
    {
        ob_start();
        try {
            return $this->withHeader('Referer', self::REFERER)
                ->post('http://localhost/admin/api_tokens/add-save', $data);
        } finally {
            $this->echoedHtml = (string) ob_get_clean();
        }
    }

    // ---------------------------------------------------------------
    // Permesso `create`
    // ---------------------------------------------------------------

    public function test_senza_permesso_create_redirect_con_denied_access_e_nessun_token(): void
    {
        $target = $this->seedUser();
        // ruolo con visibilita' solo su 'users': nessuna riga per api_tokens
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: true, visibleModulePaths: ['users']);

        $response = $this->postAddSave($this->payload($target['id']));

        $response->assertStatus(302);
        $response->assertRedirect(url('admin/api_tokens'));
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertSame(0, $this->tokenCount());
    }

    /**
     * Il permesso si controlla PRIMA della validazione: con dati non validi e
     * senza permesso la risposta e' denied_access, non una lista di errori.
     */
    public function test_senza_permesso_create_e_dati_non_validi_risponde_denied_access(): void
    {
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: true, visibleModulePaths: ['users']);

        $response = $this->postAddSave(['tokenable_id' => 999999, 'name' => '', 'expiry_choice' => 'boh']);

        $response->assertRedirect(url('admin/api_tokens'));
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $response->assertSessionHasNoErrors();
        $this->assertSame(0, $this->tokenCount());
    }

    // ---------------------------------------------------------------
    // Validazione
    // ---------------------------------------------------------------

    public static function invalidPayloads(): array
    {
        return [
            'utente inesistente' => [['tokenable_id' => 999999], 'tokenable_id'],
            'utente non numerico' => [['tokenable_id' => 'abc'], 'tokenable_id'],
            'utente mancante' => [['tokenable_id' => ''], 'tokenable_id'],
            'nome vuoto' => [['name' => ''], 'name'],
            'nome troppo lungo' => [['name' => str_repeat('a', 256)], 'name'],
            'scadenza fuori elenco' => [['expiry_choice' => '7'], 'expiry_choice'],
            'scadenza mancante' => [['expiry_choice' => ''], 'expiry_choice'],
            'data personalizzata mancante' => [['expiry_choice' => 'custom', 'expiry_custom_date' => ''], 'expiry_custom_date'],
            'data personalizzata non valida' => [['expiry_choice' => 'custom', 'expiry_custom_date' => 'non-una-data'], 'expiry_custom_date'],
            'data personalizzata nel passato' => [['expiry_choice' => 'custom', 'expiry_custom_date' => '2000-01-01'], 'expiry_custom_date'],
            'data personalizzata oggi' => [['expiry_choice' => 'custom', 'expiry_custom_date' => '__oggi__'], 'expiry_custom_date'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_dati_non_validi_tornano_indietro_con_errori_e_input_e_nessun_token(array $overrides, string $errorField): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();
        if (($overrides['expiry_custom_date'] ?? null) === '__oggi__') {
            $overrides['expiry_custom_date'] = now()->toDateString();
        }

        $response = $this->postAddSave($this->payload($target['id'], $overrides));

        $response->assertStatus(302);
        $response->assertRedirect(self::REFERER);
        $response->assertSessionHasErrors($errorField);
        $this->assertSame(0, $this->tokenCount());
    }

    public function test_dati_non_validi_rimandano_indietro_anche_l_input(): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();

        $response = $this->postAddSave($this->payload($target['id'], ['name' => '', 'expiry_choice' => '90']));

        $response->assertRedirect(self::REFERER);
        $this->assertSame('90', session()->getOldInput('expiry_choice'));
        $this->assertSame((string) $target['id'], (string) session()->getOldInput('tokenable_id'));
    }

    // ---------------------------------------------------------------
    // Creazione
    // ---------------------------------------------------------------

    public static function expiries(): array
    {
        return [
            '30 giorni' => ['30', 30],
            '90 giorni' => ['90', 90],
            '365 giorni' => ['365', 365],
        ];
    }

    #[DataProvider('expiries')]
    public function test_scadenza_a_giorni_crea_il_token_con_expires_at_corretto(string $choice, int $days): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();

        $response = $this->postAddSave($this->payload($target['id'], ['expiry_choice' => $choice]));

        $response->assertStatus(200);
        $row = DB::table('personal_access_tokens')->first();
        $this->assertNotNull($row);
        $this->assertSame($target['id'], (int) $row->tokenable_id);
        $this->assertSame('Token di prova', $row->name);
        $this->assertEqualsWithDelta(
            now()->addDays($days)->timestamp,
            Carbon::parse($row->expires_at)->timestamp,
            300
        );
    }

    public function test_scadenza_mai_crea_il_token_senza_expires_at(): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();

        $response = $this->postAddSave($this->payload($target['id'], ['expiry_choice' => 'never']));

        $response->assertStatus(200);
        $this->assertNull(DB::table('personal_access_tokens')->value('expires_at'));
    }

    public function test_scadenza_personalizzata_scade_a_fine_giornata(): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();
        $date = now()->addDays(10)->toDateString();

        $response = $this->postAddSave($this->payload($target['id'], [
            'expiry_choice' => 'custom',
            'expiry_custom_date' => $date,
        ]));

        $response->assertStatus(200);
        $expiresAt = Carbon::parse(DB::table('personal_access_tokens')->value('expires_at'));
        $this->assertSame($date, $expiresAt->toDateString());
        $this->assertSame('23:59:59', $expiresAt->format('H:i:s'));
    }

    public function test_pagina_di_rivelazione_mostra_il_token_in_chiaro_una_volta(): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();

        $response = $this->postAddSave($this->payload($target['id'], ['name' => 'Token Rivelato']));

        $response->assertStatus(200);
        // formato Sanctum "<id>|<token>" (almeno 40 caratteri): il valore in chiaro e' nella pagina
        $this->assertSame(1, preg_match('/\d+\|[A-Za-z0-9]{40,}/', $this->echoedHtml, $m));
        $plain = $m[0];
        // a DB resta solo l'hash (sha256 della parte dopo la "|"), non il valore in chiaro
        $row = DB::table('personal_access_tokens')->first();
        $this->assertNotSame($plain, $row->token);
        $this->assertSame(hash('sha256', explode('|', $plain, 2)[1]), $row->token);
    }

    /**
     * CRUDBooster::insertLog() scrive in cms_logs solo con l'impostazione
     * api_debug_mode attiva (e legge REMOTE_ADDR/HTTP_USER_AGENT da $_SERVER,
     * che il client di test non popola).
     */
    public function test_con_api_debug_mode_attivo_la_creazione_scrive_una_riga_di_log(): void
    {
        $this->seedSetting(['name' => 'api_debug_mode', 'content' => '1']);
        $target = $this->seedUser(['name' => 'Destinatario Log']);
        $this->actingAsSuperadmin();
        $previousServer = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $before = DB::table('cms_logs')->count();

        try {
            $this->postAddSave($this->payload($target['id'], ['name' => 'Token Loggato']))->assertStatus(200);

            $this->assertSame($before + 1, DB::table('cms_logs')->count());
            $this->assertStringContainsString('Token Loggato', (string) DB::table('cms_logs')->orderByDesc('id')->value('description'));
        } finally {
            $_SERVER = $previousServer;
        }
    }

    public function test_senza_api_debug_mode_la_creazione_non_scrive_log(): void
    {
        $target = $this->seedUser();
        $this->actingAsSuperadmin();
        $before = DB::table('cms_logs')->count();

        $this->postAddSave($this->payload($target['id']))->assertStatus(200);

        $this->assertSame($before, DB::table('cms_logs')->count());
    }
}
