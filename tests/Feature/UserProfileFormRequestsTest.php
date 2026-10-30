<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 334 (docs/refactoring/piani/334-profilo-utente-form-request.md):
 * caratterizzazione di permesso e validazione dei quattro endpoint del profilo
 * che passano a un Form Request (postProfileQlik, postProfileGeneral,
 * postProfileSystem, postProfilePreferences). Vanno verdi PRIMA e DOPO il
 * refactoring: fissano stato HTTP, forma del JSON e che nulla venga scritto
 * quando il permesso o la validazione rifiutano.
 *
 * Completa UserProfileSectionsTest (casi felici); postProfileQlik non aveva
 * nessun test. Stesso setUp (chiavi $_SERVER lette da cbInit()).
 */
class UserProfileFormRequestsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private $previousServerValues = [];

    private const FAKE_SERVER_VALUES = [
        'REQUEST_URI' => '/admin/users/profile',
        'HTTP_HOST' => 'localhost',
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'phpunit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();

        foreach (self::FAKE_SERVER_VALUES as $key => $value) {
            $this->previousServerValues[$key] = $_SERVER[$key] ?? null;
            $_SERVER[$key] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousServerValues as $key => $previousValue) {
            if ($previousValue === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $previousValue;
            }
        }

        parent::tearDown();
    }

    private function url(string $path): string
    {
        return 'http://localhost/admin/users/' . $path;
    }

    /** Licenza con il modulo Qlik attivo (stessa simulazione dei test ChatAI, con un altro modulo). */
    private function fakeActiveQlikLicense(): void
    {
        Storage::fake('license');
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'Qlik']],
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    private function seedQlikConf(string $type = 'ONPREM'): int
    {
        return DB::table('qlik_confs')->insertGetId([
            'confname' => 'Conf ' . $type,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertForbiddenJson($response): void
    {
        $response->assertStatus(403)->assertExactJson([
            'ok' => false,
            'message' => trans('crudbooster.denied_access'),
        ]);
    }

    /** Un 422 reale: ok=false, message = prima riga di errors, errors per campo. */
    private function assertValidationJson($response, string $field): void
    {
        $response->assertStatus(422)->assertJson(['ok' => false])->assertJsonValidationErrors($field);

        $json = $response->json();
        $this->assertIsArray($json['errors']);
        $this->assertSame(collect($json['errors'])->first()[0], $json['message']);
    }

    // ---------------------------------------------------------------- Qlik

    public function test_qlik_superadmin_con_licenza_salva_le_associazioni(): void
    {
        $this->fakeActiveQlikLicense();
        $actor = $this->actingAsSuperadmin();
        $confId = $this->seedQlikConf();

        $this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => 'mario', 'user_directory' => 'CORP']],
        ])->assertStatus(200)->assertJson(['ok' => true, 'reload' => true]);

        $row = DB::table('qlik_users')->where('user_id', $actor['userId'])->first();
        $this->assertNotNull($row);
        $this->assertSame($confId, (int) $row->qlik_conf_id);
        $this->assertSame('mario', $row->qlik_login);
        $this->assertSame('CORP', $row->user_directory);
    }

    public function test_qlik_conf_inesistente_da_422_e_non_cancella_le_righe_esistenti(): void
    {
        $this->fakeActiveQlikLicense();
        $actor = $this->actingAsSuperadmin();
        $confId = $this->seedQlikConf();

        $this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => 'mario']],
        ])->assertStatus(200);

        $response = $this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => 999999, 'qlik_login' => 'x']],
        ]);

        $this->assertValidationJson($response, 'rows.0.qlik_conf_id');
        $this->assertSame(1, DB::table('qlik_users')->where('user_id', $actor['userId'])->count());
        $this->assertSame('mario', DB::table('qlik_users')->where('user_id', $actor['userId'])->value('qlik_login'));
    }

    public function test_qlik_stessa_conf_due_volte_da_422(): void
    {
        $this->fakeActiveQlikLicense();
        $actor = $this->actingAsSuperadmin();
        $confId = $this->seedQlikConf();

        $response = $this->postJson($this->url('profile-qlik'), [
            'rows' => [
                ['qlik_conf_id' => $confId, 'qlik_login' => 'a'],
                ['qlik_conf_id' => $confId, 'qlik_login' => 'b'],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertSame(0, DB::table('qlik_users')->where('user_id', $actor['userId'])->count());
    }

    public function test_qlik_login_troppo_lungo_e_rows_non_array_danno_422(): void
    {
        $this->fakeActiveQlikLicense();
        $this->actingAsSuperadmin();
        $confId = $this->seedQlikConf();

        $this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => str_repeat('a', 256)]],
        ])->assertStatus(422)->assertJson(['ok' => false]);

        $this->postJson($this->url('profile-qlik'), ['rows' => 'non-un-array'])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_qlik_utente_base_riceve_403_json(): void
    {
        $this->fakeActiveQlikLicense();
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, false, ['users']);
        $confId = $this->seedQlikConf();

        $this->assertForbiddenJson($this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => 'x']],
        ]));
        $this->assertSame(0, DB::table('qlik_users')->where('user_id', $actor['userId'])->count());
    }

    public function test_qlik_utente_base_con_dati_non_validi_riceve_403_e_non_422(): void
    {
        $this->fakeActiveQlikLicense();
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, false, ['users']);

        $this->assertForbiddenJson($this->postJson($this->url('profile-qlik'), ['rows' => 'non-un-array']));
    }

    public function test_qlik_tenant_admin_senza_licenza_riceve_403_json(): void
    {
        // Nessuna licenza simulata: isActiveQlik() e' falso.
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, true, ['users']);
        $confId = $this->seedQlikConf();

        $this->assertForbiddenJson($this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => 'x']],
        ]));
        $this->assertSame(0, DB::table('qlik_users')->where('user_id', $actor['userId'])->count());
    }

    public function test_qlik_tenant_admin_con_licenza_puo_salvare(): void
    {
        // Caso positivo del precedente: stesso setup, con la licenza.
        $this->fakeActiveQlikLicense();
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, true, ['users']);
        $confId = $this->seedQlikConf();

        $this->postJson($this->url('profile-qlik'), [
            'rows' => [['qlik_conf_id' => $confId, 'qlik_login' => 'x']],
        ])->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertSame(1, DB::table('qlik_users')->where('user_id', $actor['userId'])->count());
    }

    // ------------------------------------------------------------- Generale

    public function test_generale_nome_non_valido_da_422_e_non_cambia_il_db(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->value('name');

        $this->assertValidationJson($this->postJson($this->url('profile-general'), [
            'name' => '12345', 'status' => 'Active',
        ]), 'name');

        $this->assertValidationJson($this->postJson($this->url('profile-general'), [
            'name' => 'Ab', 'status' => 'Active',
        ]), 'name');

        $this->assertSame($before, DB::table('cms_users')->where('id', $actor['userId'])->value('name'));
    }

    public function test_generale_foto_non_immagine_da_422(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->value('name');

        $response = $this->post($this->url('profile-general'), [
            'name' => 'Nome Valido',
            'status' => 'Active',
            'photo' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $this->assertValidationJson($response, 'photo');
        $this->assertSame($before, DB::table('cms_users')->where('id', $actor['userId'])->value('name'));
    }

    public function test_generale_superadmin_stato_non_valido_o_data_non_valida_da_422(): void
    {
        $this->actingAsSuperadmin();

        $this->assertValidationJson($this->postJson($this->url('profile-general'), [
            'name' => 'Nome Valido', 'status' => 'Sospeso',
        ]), 'status');

        $this->assertValidationJson($this->postJson($this->url('profile-general'), [
            'name' => 'Nome Valido', 'status' => 'Active', 'data_scadenza' => 'non-una-data',
        ]), 'data_scadenza');

        // Caso positivo con lo stesso setup.
        $this->postJson($this->url('profile-general'), [
            'name' => 'Nome Valido', 'status' => 'Active', 'data_scadenza' => '2031-02-03',
        ])->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_generale_utente_base_con_stato_non_valido_ottiene_200(): void
    {
        // Le regole su status/data_scadenza non si applicano a chi non puo' gestire.
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, false, ['users'], ['status' => 'Active']);

        $this->postJson($this->url('profile-general'), [
            'name' => 'Utente Base', 'status' => 'Sospeso', 'data_scadenza' => 'non-una-data',
        ])->assertStatus(200)->assertJson(['ok' => true]);
    }

    // ----------------------------------------------------------- Preferenze

    public function test_preferenze_ogni_campo_assente_o_non_ammesso_da_422_e_il_db_resta_invariato(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->first();

        $valid = ['lang' => 'en', 'decimal_separator' => 'dot', 'date_format' => 'Y-m-d'];

        foreach (['lang', 'decimal_separator', 'date_format'] as $field) {
            $payload = $valid;
            unset($payload[$field]);
            $this->assertValidationJson($this->postJson($this->url('profile-preferences'), $payload), $field);
        }

        $this->assertValidationJson($this->postJson($this->url('profile-preferences'), array_merge($valid, ['decimal_separator' => 'virgola'])), 'decimal_separator');
        $this->assertValidationJson($this->postJson($this->url('profile-preferences'), array_merge($valid, ['date_format' => 'zz-zz'])), 'date_format');

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame($before->lang, $after->lang);
        $this->assertSame($before->decimal_separator, $after->decimal_separator);
        $this->assertSame($before->date_format, $after->date_format);

        // Caso positivo con lo stesso setup.
        $this->postJson($this->url('profile-preferences'), $valid)->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertSame('Y-m-d', DB::table('cms_users')->where('id', $actor['userId'])->value('date_format'));
    }

    // -------------------------------------------------------------- Sistema

    public function test_sistema_primary_group_assente_o_inesistente_e_tenant_inesistente_danno_422(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->first();

        $this->assertValidationJson($this->postJson($this->url('profile-system'), ['tenant' => $before->tenant]), 'primary_group');
        $this->assertValidationJson($this->postJson($this->url('profile-system'), ['primary_group' => 999999]), 'primary_group');
        $this->assertValidationJson($this->postJson($this->url('profile-system'), ['tenant' => 999999, 'primary_group' => $before->primary_group]), 'tenant');

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame($before->tenant, $after->tenant);
        $this->assertSame($before->primary_group, $after->primary_group);
    }

    public function test_sistema_utente_base_con_dati_non_validi_riceve_403_e_non_422(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, false, ['users']);

        $this->assertForbiddenJson($this->postJson($this->url('profile-system'), ['primary_group' => 999999]));
    }

    public function test_sistema_tenant_admin_con_dati_validi_non_riceve_403(): void
    {
        // Caso positivo del precedente: lo stesso endpoint, con permesso e dati validi.
        $tenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['tenant_id' => $tenantId, 'group_id' => $groupId]);
        $actor = $this->actingAsTenantUser($tenantId, true, ['users'], ['primary_group' => $groupId]);

        $this->postJson($this->url('profile-system'), ['primary_group' => $groupId])
            ->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertSame($groupId, (int) DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group'));
    }
}
