<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test per la pagina profilo a sezioni (Generale / MFA / Sistema / Password):
 * ogni sezione ha il suo endpoint JSON in AdminCmsUsersController
 * (postProfileGeneral, postProfileEmailStart/Confirm, postProfileSystem,
 * postProfilePasswordStart/Confirm), piu' il reset password dalla scheda di
 * un altro utente (postUserResetPassword) e la chiusura delle altre sessioni
 * (cms_users.session_version, controllata da CBBackend).
 *
 * Stesso setUp di UsersCrudTest (moduli admin registrati a mano, chiavi
 * $_SERVER lette direttamente da cbInit()). Il codice OTP e' hashato a DB:
 * i test lo sostituiscono con un valore noto (otpNoto()) invece di leggere
 * l'email inviata.
 */
class UserProfileSectionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const NEW_PASSWORD = 'nuvola-bianca-montagna-verde';

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

        foreach (['mfa_email_otp', 'forgot_password_backend'] as $slug) {
            if (! DB::table('cms_email_templates')->where('slug', $slug)->exists()) {
                DB::table('cms_email_templates')->insert([
                    'name' => $slug,
                    'slug' => $slug,
                    'subject' => $slug,
                    'content' => '[otp_code] [reset_url]',
                    'created_at' => now(),
                ]);
            }
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

    /** Sostituisce l'ultimo codice email OTP inviato con uno noto. */
    private function otpNoto(int $userId, string $code = '654321'): string
    {
        $id = DB::table('mfa_email_otp_codes')->where('user_id', $userId)->max('id');
        DB::table('mfa_email_otp_codes')->where('id', $id)->update(['code_hash' => Hash::make($code)]);

        return $code;
    }

    public function test_la_pagina_profilo_mostra_le_quattro_sezioni(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get($this->url('profile'));

        $response->assertStatus(200);
        $response->assertSee(trans('crudbooster.profile_section_general'));
        $response->assertSee(trans('crudbooster.profile_section_mfa'));
        $response->assertSee(trans('crudbooster.profile_section_system'));
        $response->assertSee(trans('crudbooster.profile_section_password'));
    }

    public function test_generale_salva_solo_i_campi_generali(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->first();

        $response = $this->postJson($this->url('profile-general'), [
            'name' => 'Nome Aggiornato',
            'lang' => 'it',
            'status' => 'Active',
            'data_scadenza' => '2030-01-31',
            // Campi che questa sezione NON deve mai toccare, anche se inviati.
            'email' => 'intruso@example.com',
            'password' => 'password-intrusa-123',
            'tenant' => $before->tenant + 99,
        ]);

        $response->assertStatus(200)->assertJson(['ok' => true]);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame('Nome Aggiornato', $after->name);
        $this->assertSame('it', $after->lang);
        $this->assertStringStartsWith('2030-01-31', (string) $after->data_scadenza);
        $this->assertSame($before->email, $after->email);
        $this->assertSame($before->password, $after->password);
        $this->assertSame($before->tenant, $after->tenant);
    }

    public function test_generale_un_utente_base_non_puo_cambiare_stato_e_scadenza(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, false, ['users'], ['status' => 'Active']);

        $response = $this->postJson($this->url('profile-general'), [
            'name' => 'Utente Base Rinominato',
            'lang' => 'en',
            'status' => 'Inactive',
            'data_scadenza' => '2020-01-01',
        ]);

        $response->assertStatus(200);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame('Utente Base Rinominato', $after->name);
        $this->assertSame('Active', $after->status);
        $this->assertNull($after->data_scadenza);
    }

    public function test_cambio_email_richiede_la_password_attuale(): void
    {
        $actor = $this->actingAsSuperadmin();

        $response = $this->postJson($this->url('profile-email-start'), [
            'new_email' => 'nuova@example.com',
            'current_password' => 'password-sbagliata',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertSame(0, DB::table('mfa_email_otp_codes')->where('user_id', $actor['userId'])->count());
    }

    public function test_cambio_email_rifiuta_un_indirizzo_gia_in_uso(): void
    {
        $this->actingAsSuperadmin();
        $this->seedUser(['email' => 'occupata@example.com']);

        $response = $this->postJson($this->url('profile-email-start'), [
            'new_email' => 'occupata@example.com',
            'current_password' => 'password-corretta-123',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_cambio_email_completo_con_codice_inviato_al_nuovo_indirizzo(): void
    {
        $actor = $this->actingAsSuperadmin();
        DB::table('mfa_trusted_devices')->insert([
            'user_id' => $actor['userId'],
            'selector' => 'sel-test',
            'validator_hash' => 'hash-test',
            'trusted_until' => now()->addDays(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson($this->url('profile-email-start'), [
            'new_email' => 'nuova.email@example.com',
            'current_password' => 'password-corretta-123',
        ])->assertStatus(200)->assertJson(['ok' => true]);

        // Nulla e' cambiato finche' il codice non e' confermato.
        $this->assertNotSame('nuova.email@example.com', DB::table('cms_users')->where('id', $actor['userId'])->value('email'));

        $code = $this->otpNoto($actor['userId']);

        $this->postJson($this->url('profile-email-confirm'), ['code' => $code])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'email' => 'nuova.email@example.com']);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame('nuova.email@example.com', $after->email);
        $this->assertSame(1, (int) $after->session_version);
        $this->assertSame(0, DB::table('mfa_trusted_devices')->where('user_id', $actor['userId'])->count());
    }

    public function test_cambio_email_con_codice_sbagliato_non_cambia_nulla(): void
    {
        $actor = $this->actingAsSuperadmin();
        $emailPrima = DB::table('cms_users')->where('id', $actor['userId'])->value('email');

        $this->postJson($this->url('profile-email-start'), [
            'new_email' => 'altra@example.com',
            'current_password' => 'password-corretta-123',
        ])->assertStatus(200);

        $this->otpNoto($actor['userId'], '654321');

        $this->postJson($this->url('profile-email-confirm'), ['code' => '000000'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame($emailPrima, DB::table('cms_users')->where('id', $actor['userId'])->value('email'));
    }

    public function test_cambio_password_richiede_attuale_e_rispetta_la_policy(): void
    {
        $actor = $this->actingAsSuperadmin();

        // Password attuale sbagliata.
        $this->postJson($this->url('profile-password-start'), [
            'current_password' => 'password-sbagliata',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(422);

        // Troppo corta.
        $this->postJson($this->url('profile-password-start'), [
            'current_password' => 'password-corretta-123',
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertStatus(422);

        // Conferma diversa.
        $this->postJson($this->url('profile-password-start'), [
            'current_password' => 'password-corretta-123',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'un-altra-password-diversa',
        ])->assertStatus(422);

        $this->assertSame(0, DB::table('mfa_email_otp_codes')->where('user_id', $actor['userId'])->count());
    }

    public function test_cambio_password_completo_salva_hash_e_chiude_le_altre_sessioni(): void
    {
        $actor = $this->actingAsSuperadmin();

        $this->postJson($this->url('profile-password-start'), [
            'current_password' => 'password-corretta-123',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(200)->assertJson(['ok' => true, 'method' => 'email']);

        // Finche' il codice non e' confermato la password e' quella vecchia.
        $hashPrima = DB::table('cms_users')->where('id', $actor['userId'])->value('password');
        $this->assertTrue(Hash::check('password-corretta-123', $hashPrima));

        $code = $this->otpNoto($actor['userId']);

        $this->postJson($this->url('profile-password-confirm'), ['code' => $code])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $after->password));
        $this->assertSame(1, (int) $after->session_version);
    }

    public function test_una_sessione_con_versione_vecchia_viene_chiusa(): void
    {
        $actor = $this->actingAsSuperadmin();
        DB::table('cms_users')->where('id', $actor['userId'])->update(['session_version' => 3]);

        // La sessione del test non ha 'admin_session_version' (= 0): e'
        // rimasta indietro rispetto al DB, come un'altra sessione aperta
        // prima del cambio password.
        $response = $this->get($this->url('profile'));

        $response->assertStatus(302);
        $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
    }

    public function test_sistema_il_tenant_admin_cambia_il_gruppo_ma_non_il_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, true, ['users']);
        $otherTenant = $this->seedTenant();

        $groupOwn = $this->seedGroup();
        $groupOther = $this->seedGroup();
        DB::table('group_tenants')->insert([
            ['group_id' => $groupOwn, 'tenant_id' => $tenantId],
            ['group_id' => $groupOther, 'tenant_id' => $otherTenant],
        ]);

        // Tenant "altro" ignorato (solo il superadmin lo cambia), gruppo del
        // proprio tenant accettato.
        $this->postJson($this->url('profile-system'), [
            'tenant' => $otherTenant,
            'primary_group' => $groupOwn,
        ])->assertStatus(200)->assertJson(['ok' => true]);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame($tenantId, (int) $after->tenant);
        $this->assertSame($groupOwn, (int) $after->primary_group);
        $this->assertDatabaseHas('users_groups', ['user_id' => $actor['userId'], 'group_id' => $groupOwn, 'deleted_at' => null]);

        // Gruppo di un altro tenant: rifiutato.
        $this->postJson($this->url('profile-system'), [
            'primary_group' => $groupOther,
        ])->assertStatus(422);

        $this->assertSame($groupOwn, (int) DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group'));
    }

    public function test_sistema_un_utente_base_non_puo_salvare(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, false, ['users']);
        $groupBefore = DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group');
        $newGroup = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $newGroup, 'tenant_id' => $tenantId]);

        $this->postJson($this->url('profile-system'), ['primary_group' => $newGroup])
            ->assertStatus(403);

        $this->assertSame($groupBefore, DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group'));
    }

    public function test_sistema_il_superadmin_cambia_tenant_e_gruppo_e_aggiorna_lappartenenza(): void
    {
        $actor = $this->actingAsSuperadmin();
        $oldGroup = DB::table('cms_users')->where('id', $actor['userId'])->value('primary_group');
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $oldGroup, 'created_at' => now()]);

        $newTenant = $this->seedTenant();
        $newGroup = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $newGroup, 'tenant_id' => $newTenant]);

        $this->postJson($this->url('profile-system'), [
            'tenant' => $newTenant,
            'primary_group' => $newGroup,
        ])->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertDatabaseHas('cms_users', ['id' => $actor['userId'], 'tenant' => $newTenant, 'primary_group' => $newGroup]);
        $this->assertDatabaseHas('users_groups', ['user_id' => $actor['userId'], 'group_id' => $newGroup, 'deleted_at' => null]);
        $this->assertDatabaseMissing('users_groups', ['user_id' => $actor['userId'], 'group_id' => $oldGroup, 'deleted_at' => null]);
    }

    public function test_il_superadmin_manda_il_link_di_reset_a_un_altro_utente(): void
    {
        $this->actingAsSuperadmin();
        $target = $this->seedUser(['email' => 'destinatario@example.com']);

        $this->postJson($this->url('user-reset-password/' . $target['id']))
            ->assertStatus(200)
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('password_resets', ['email' => 'destinatario@example.com']);
        // La password dell'utente non e' stata toccata.
        $this->assertSame($target['password'], DB::table('cms_users')->where('id', $target['id'])->value('password'));
    }

    public function test_il_reset_password_e_negato_a_un_utente_base_e_su_se_stessi(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, false, ['users']);
        $target = $this->seedUser(['tenant' => $tenantId]);

        $this->postJson($this->url('user-reset-password/' . $target['id']))->assertStatus(403);
        $this->postJson($this->url('user-reset-password/' . $actor['userId']))->assertStatus(403);
    }

    public function test_il_tenant_admin_resetta_solo_utenti_base_del_proprio_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, true, ['users']);

        $sameTenantBasic = $this->seedUser(['tenant' => $tenantId]);
        $otherTenantBasic = $this->seedUser();
        $tenantAdminPrivilege = DB::table('cms_privileges')->insertGetId([
            'name' => 'Altro Tenantadmin', 'is_superadmin' => 0, 'is_tenantadmin' => 1, 'theme_color' => 'blue',
        ]);
        $sameTenantAdmin = $this->seedUser(['tenant' => $tenantId, 'id_cms_privileges' => $tenantAdminPrivilege]);

        $this->postJson($this->url('user-reset-password/' . $sameTenantBasic['id']))->assertStatus(200);
        $this->postJson($this->url('user-reset-password/' . $otherTenantBasic['id']))->assertStatus(403);
        $this->postJson($this->url('user-reset-password/' . $sameTenantAdmin['id']))->assertStatus(403);
    }

    public function test_modificare_se_stessi_dal_form_utenti_non_cambia_email_e_password(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = DB::table('cms_users')->where('id', $actor['userId'])->first();

        $this->post($this->url('edit-save/' . $actor['userId']), [
            'name' => $before->name,
            'email' => 'aggirata@example.com',
            'password' => 'password-aggirata-123',
            'password_confirmation' => 'password-aggirata-123',
            'id_cms_privileges' => $before->id_cms_privileges,
            'tenant' => $before->tenant,
            'primary_group' => $before->primary_group,
            'status' => 'Active',
        ]);

        $after = DB::table('cms_users')->where('id', $actor['userId'])->first();
        $this->assertSame($before->email, $after->email);
        $this->assertSame($before->password, $after->password);
    }

    public function test_la_modifica_del_proprio_record_porta_al_profilo(): void
    {
        $actor = $this->actingAsSuperadmin();

        $response = $this->get($this->url('edit/' . $actor['userId']));

        $response->assertStatus(302);
        $this->assertStringContainsString('/admin/users/profile', $response->headers->get('Location'));
    }

    public function test_cambiare_lemail_di_un_altro_utente_chiude_le_sue_sessioni_e_revoca_i_dispositivi(): void
    {
        $this->actingAsSuperadmin();
        $target = $this->seedUser();
        DB::table('mfa_trusted_devices')->insert([
            'user_id' => $target['id'],
            'selector' => 'sel-altro',
            'validator_hash' => 'hash-altro',
            'trusted_until' => now()->addDays(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post($this->url('edit-save/' . $target['id']), [
            'name' => $target['name'],
            'email' => 'cambiata.dall.admin@example.com',
            'id_cms_privileges' => $target['id_cms_privileges'],
            'tenant' => $target['tenant'],
            'primary_group' => $target['primary_group'],
            'status' => 'Active',
        ]);

        $after = DB::table('cms_users')->where('id', $target['id'])->first();
        $this->assertSame('cambiata.dall.admin@example.com', $after->email);
        $this->assertSame(1, (int) $after->session_version);
        $this->assertSame(0, DB::table('mfa_trusted_devices')->where('user_id', $target['id'])->count());
    }

    public function test_la_password_si_sceglie_solo_in_creazione_non_in_modifica(): void
    {
        $this->actingAsSuperadmin();
        $target = $this->seedUser();
        $hashPrima = $target['password'];

        // Anche con una POST costruita a mano, in modifica la password altrui
        // non viene toccata: il campo non e' nel form (cbInit()).
        $this->post($this->url('edit-save/' . $target['id']), [
            'name' => $target['name'],
            'email' => $target['email'],
            'password' => 'password-imposta-dallAdmin-99',
            'password_confirmation' => 'password-imposta-dallAdmin-99',
            'id_cms_privileges' => $target['id_cms_privileges'],
            'tenant' => $target['tenant'],
            'primary_group' => $target['primary_group'],
            'status' => 'Active',
        ]);

        $this->assertSame($hashPrima, DB::table('cms_users')->where('id', $target['id'])->value('password'));
    }
}
