<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * B2 (open redirect): i cinque endpoint add_* di Groups/Users/Tenants
 * (add_member, add_item, add_tenant su Groups; add_group su Users e su
 * Tenants) leggevano return_url/ref_mainpath da $_POST e li passavano
 * direttamente a redirect(): un return_url verso un host esterno mandava un
 * admin autenticato su un sito arbitrario dopo il salvataggio.
 *
 * Scritto PRIMA della modifica: i casi "host esterno" sono ROSSI di proposito.
 * I casi con return_url interno (relativo o stesso host) e con ref_mainpath di
 * ripiego devono essere verdi prima e dopo (comportamento dei form reali, che
 * mandano sempre CRUDBooster::mainpath(...)).
 *
 * I metodi leggono anche $_POST['name'/'title'] direttamente e il client di
 * test non popola quella superglobale: postAsForm() la imposta a mano.
 */
class SafeRedirectAddActionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const EVIL = 'https://evil.example.com/phish';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
        $this->fakeActiveQlikLicense();
        $this->actingAsSuperadmin();
    }

    /**
     * add_item e' chiuso senza il modulo Qlik in licenza: stessa tecnica di
     * ChatAIAuthorizationTest, senza toccare il license.json reale.
     */
    private function fakeActiveQlikLicense(): void
    {
        Storage::fake('license');
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'Qlik']],
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    /**
     * @return array<string,array{0:string,1:string,2:string}> uri, campo
     *         "nome" del POST, return_url interno valido (con segnaposto)
     */
    public static function endpoints(): array
    {
        return [
            'groups add_member' => ['admin/groups/{group}/add_member', 'name', '/admin/groups/members/{group}'],
            'groups add_item' => ['admin/groups/{group}/add_item', 'title', '/admin/groups/items/{group}'],
            'groups add_tenant' => ['admin/groups/{group}/add_tenant', 'name', '/admin/groups/tenant/{group}'],
            'users add_group' => ['admin/users/{user}/add_group', 'name', '/admin/users/groups/{user}'],
            'tenants add_group' => ['admin/tenants/{tenant}/add_group', 'name', '/admin/tenants/group/{tenant}'],
        ];
    }

    /**
     * Entita' reali: il valore di "name"/"title" del POST dipende
     * dall'endpoint (utente, gruppo, tenant o item Qlik).
     *
     * @return array{group:int,user:int,tenant:int,target:array<string,int>}
     */
    private function ids(string $uri): array
    {
        $group = $this->seedGroup();
        $user = $this->seedUser()['id'];
        $tenant = $this->seedTenant();
        $item = DB::table('qlik_items')->insertGetId(['title' => 'Item Qlik Test', 'created_at' => now()]);

        $target = match (true) {
            str_contains($uri, 'add_member') => $user,
            str_contains($uri, 'add_item') => $item,
            str_contains($uri, 'groups/{group}/add_tenant') => $tenant,
            default => $group,
        };

        return compact('group', 'user', 'tenant', 'target');
    }

    private function resolve(string $template, array $ids): string
    {
        return str_replace(
            ['{group}', '{user}', '{tenant}'],
            [(string) $ids['group'], (string) $ids['user'], (string) $ids['tenant']],
            $template
        );
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

    private function location(TestResponse $response): string
    {
        $response->assertStatus(302);

        return (string) $response->headers->get('Location');
    }

    // ---------------------------------------------------------------
    // Host esterno: mai seguito (ROSSI prima della modifica)
    // ---------------------------------------------------------------

    #[DataProvider('endpoints')]
    public function test_return_url_e_ref_mainpath_esterni_non_vengono_seguiti(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => self::EVIL,
            'ref_mainpath' => self::EVIL,
        ]);

        $location = $this->location($response);
        $this->assertStringNotContainsString('evil.example.com', $location);
        $this->assertStringStartsWith('http://localhost/admin', $location);
    }

    /**
     * Ramo "campo vuoto": redirect($return_url . '/alert/1') era
     * raggiungibile con lo stesso return_url esterno.
     */
    #[DataProvider('endpoints')]
    public function test_return_url_esterno_non_viene_seguito_nemmeno_con_campo_vuoto(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => '',
            'return_url' => self::EVIL,
            'ref_mainpath' => '',
        ]);

        $location = $this->location($response);
        $this->assertStringNotContainsString('evil.example.com', $location);
        $this->assertStringStartsWith('http://localhost/admin', $location);
    }

    /**
     * return_url protocol-relative ("//host") e con backslash: stessi
     * trucchi che il trait SafeRedirectTarget gia' scarta per ChatAI/Qlik.
     */
    #[DataProvider('endpoints')]
    public function test_return_url_protocol_relative_non_viene_seguito(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => '//evil.example.com/phish',
            'ref_mainpath' => '',
        ]);

        $this->assertStringNotContainsString('evil.example.com', $this->location($response));
    }

    /**
     * Varianti che il primo filtro del trait lasciava passare (review del
     * doc 326): parse_url() restituisce false su "http:///host", e tab/a capo
     * dentro l'URL vengono tolti dal browser ("/\t/host" diventa "//host").
     * Anche scheme senza host (javascript:, data:) e backslash.
     */
    #[DataProvider('endpoints')]
    public function test_return_url_malformati_non_vengono_seguiti(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);
        $malformed = [
            'http:///evil.example.com/phish',
            'https:///evil.example.com/phish',
            "/\t/evil.example.com/phish",
            "/\n/evil.example.com/phish",
            "/\r/evil.example.com/phish",
            "/admin/ok /evil.example.com",
            'javascript:alert(1)',
            'data:text/html,evil.example.com',
            'https:\\\\evil.example.com\\phish',
        ];

        foreach ($malformed as $value) {
            $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
                $nameField => $ids['target'],
                'return_url' => $value,
                'ref_mainpath' => '',
            ]);

            $location = $this->location($response);
            $this->assertStringNotContainsString('evil.example.com', $location, 'return_url: ' . json_encode($value));
            $this->assertStringStartsWith('http://localhost/admin', $location, 'return_url: ' . json_encode($value));
        }
    }

    // ---------------------------------------------------------------
    // Comportamento dei form reali: invariato (VERDI prima e dopo)
    // ---------------------------------------------------------------

    #[DataProvider('endpoints')]
    public function test_return_url_relativo_interno_viene_seguito(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);
        $return = $this->resolve($internal, $ids);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => $return,
            'ref_mainpath' => '',
        ]);

        $this->assertSame('http://localhost' . $return, $this->location($response));
    }

    #[DataProvider('endpoints')]
    public function test_return_url_assoluto_dello_stesso_host_viene_seguito(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);
        $return = 'http://localhost' . $this->resolve($internal, $ids);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => $return,
            'ref_mainpath' => '',
        ]);

        $this->assertSame($return, $this->location($response));
    }

    #[DataProvider('endpoints')]
    public function test_senza_return_url_si_ripiega_su_ref_mainpath(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);
        $ref = $this->resolve($internal, $ids);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => '',
            'ref_mainpath' => $ref,
        ]);

        $this->assertSame('http://localhost' . $ref, $this->location($response));
    }

    /**
     * return_url esterno ma ref_mainpath valido: il valore non sicuro e'
     * trattato come vuoto e si usa il ripiego (ROSSO prima della modifica).
     */
    #[DataProvider('endpoints')]
    public function test_return_url_esterno_con_ref_mainpath_valido_usa_il_ref(string $uri, string $nameField, string $internal): void
    {
        $ids = $this->ids($uri);
        $ref = $this->resolve($internal, $ids);

        $response = $this->postAsForm('http://localhost/' . $this->resolve($uri, $ids), [
            $nameField => $ids['target'],
            'return_url' => self::EVIL,
            'ref_mainpath' => $ref,
        ]);

        $this->assertSame('http://localhost' . $ref, $this->location($response));
    }
}
