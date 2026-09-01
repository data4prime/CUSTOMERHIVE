<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il CRUD del modulo Users (CBController +
 * AdminCmsUsersController::hook_after_add()/hook_before_edit()/
 * hook_before_delete()).
 *
 * Stesso stile di TenantsCrudTest/GroupsCrudTest/PrivilegesCrudTest - vedi
 * i commenti li' (e su SeedsCmsData) per il perche' di setUp() e
 * dell'assenza di mock/isolamento di processo.
 *
 * Fuori scope volutamente: il sotto-form Qlik (mai renderizzato nei test,
 * LicenseHelper::isActiveQlik() e' false senza licenza) e le sotto-pagine
 * di gestione gruppi (groups/add_group/remove_group - stesso trattamento
 * gia' dato a members/items per Tenants/Groups). L'attore e' sempre
 * superadmin, come per gli altri tre moduli - non testate le varianti del
 * form per tenantadmin/utente base.
 */
class UsersCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private $previousServerValues = [];

    // Chiavi $_SERVER lette DIRETTAMENTE da codice legacy non coperto da
    // questi test (rendering/pre-riempimento form in AdminCmsUsersController
    // ::cbInit() e CRUDBooster::isAddPage()/isProfilePage(), logging in
    // add_log_ch() chiamata da GroupHelper::add()) - il client di test di
    // Laravel non le popola sempre, esattamente come $_SERVER['HTTP_HOST']
    // in AdminController::postLogin() (vedi Tests\Concerns\LogsInAdmin).
    // Valori qualsiasi bastano ad evitare l'errore "chiave non definita"
    // senza cambiare il comportamento verificato dai test.
    private const FAKE_SERVER_VALUES = [
        'REQUEST_URI' => '/admin/users',
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

    public function test_lista_utenti_mostra_i_record_esistenti(): void
    {
        $this->actingAsSuperadmin();
        $this->seedUser(['name' => 'Utente Esistente Nella Lista', 'email' => 'esistente@example.com']);

        $response = $this->get('http://localhost/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Utente Esistente Nella Lista');
    }

    public function test_creazione_utente_riesce_e_lo_aggiunge_al_gruppo_primario(): void
    {
        $actor = $this->actingAsSuperadmin();
        $privilegeId = $this->seedPrivilege();
        $groupId = $this->seedGroup();

        $response = $this->post('http://localhost/admin/users/add-save', [
            'name' => 'Nuovo Utente',
            'email' => 'nuovo.utente@example.com',
            'id_cms_privileges' => $privilegeId,
            'tenant' => $actor['tenantId'],
            'primary_group' => $groupId,
            'status' => 'Active',
            'lang' => 'en',
            'password' => 'password-corretta-123',
            'password_confirmation' => 'password-corretta-123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('cms_users')->where('email', 'nuovo.utente@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('Nuovo Utente', $row->name);
        $this->assertSame((string) $groupId, (string) $row->primary_group);

        // hook_after_add() aggiunge sempre il nuovo utente al suo gruppo
        // primario - vedi AdminCmsUsersController e GroupHelper::add().
        $this->assertDatabaseHas('users_groups', [
            'user_id' => $row->id,
            'group_id' => $groupId,
        ]);
    }

    public function test_creazione_fallisce_se_lemail_e_gia_in_uso(): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->seedUser(['email' => 'duplicata@example.com']);
        $privilegeId = $this->seedPrivilege();
        $groupId = $this->seedGroup();

        // Il form ha 'unique:cms_users,email' - la validazione fallita
        // deve bloccare la creazione, non farla proseguire.
        $response = $this->post('http://localhost/admin/users/add-save', [
            'name' => 'Utente Con Email Duplicata',
            'email' => 'duplicata@example.com',
            'id_cms_privileges' => $privilegeId,
            'tenant' => $actor['tenantId'],
            'primary_group' => $groupId,
            'status' => 'Active',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');

        $this->assertSame(
            1,
            DB::table('cms_users')->where('email', 'duplicata@example.com')->count(),
            'Non deve essere stato creato un secondo utente con la stessa email.'
        );
    }

    public function test_modifica_utente_riesce(): void
    {
        $this->actingAsSuperadmin();
        $user = $this->seedUser(['name' => 'Utente Da Modificare', 'status' => 'Active']);

        $response = $this->post("http://localhost/admin/users/edit-save/{$user['id']}", [
            'name' => 'Utente Modificato',
            'email' => $user['email'],
            'id_cms_privileges' => $user['id_cms_privileges'],
            'tenant' => $user['tenant'],
            'primary_group' => $user['primary_group'],
            'status' => 'Inactive',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('cms_users')->where('id', $user['id'])->first();
        $this->assertSame('Utente Modificato', $row->name);
        $this->assertSame('Inactive', $row->status);
    }

    public function test_modifica_senza_cambiare_tenant_non_tocca_lappartenenza_ai_gruppi(): void
    {
        $this->actingAsSuperadmin();
        $user = $this->seedUser(['name' => 'Utente Stabile']);
        DB::table('users_groups')->insert([
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'created_at' => now(),
        ]);

        // Stesso tenant e stesso primary_group di prima: hook_before_edit()
        // non deve rimuovere l'appartenenza esistente (confronto
        // "e' cambiato?" tra il valore in DB e quello del form).
        $response = $this->post("http://localhost/admin/users/edit-save/{$user['id']}", [
            'name' => 'Utente Stabile',
            'email' => $user['email'],
            'id_cms_privileges' => $user['id_cms_privileges'],
            'tenant' => $user['tenant'],
            'primary_group' => $user['primary_group'],
            'status' => 'Active',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users_groups', [
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'deleted_at' => null,
        ]);
    }

    public function test_modifica_cambiando_tenant_e_gruppo_aggiorna_lappartenenza(): void
    {
        $this->actingAsSuperadmin();
        $user = $this->seedUser(['name' => 'Utente Che Cambia Tenant']);
        DB::table('users_groups')->insert([
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'created_at' => now(),
        ]);

        $newTenantId = $this->seedTenant();
        $newGroupId = $this->seedGroup();

        // hook_before_edit(): tenant cambiato -> rimuove tutti i vecchi
        // gruppi; primary_group cambiato -> aggiunge il nuovo. Risultato
        // atteso: membro SOLO del nuovo gruppo primario.
        $response = $this->post("http://localhost/admin/users/edit-save/{$user['id']}", [
            'name' => 'Utente Che Cambia Tenant',
            'email' => $user['email'],
            'id_cms_privileges' => $user['id_cms_privileges'],
            'tenant' => $newTenantId,
            'primary_group' => $newGroupId,
            'status' => 'Active',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('cms_users', ['id' => $user['id'], 'tenant' => $newTenantId]);
        $this->assertDatabaseHas('users_groups', [
            'user_id' => $user['id'],
            'group_id' => $newGroupId,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseMissing('users_groups', [
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'deleted_at' => null,
        ]);
    }

    public function test_cancellazione_utente_riesce(): void
    {
        $this->actingAsSuperadmin();
        $user = $this->seedUser(['name' => 'Utente Da Cancellare']);

        $response = $this->get("http://localhost/admin/users/delete/{$user['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        // cms_users non ha deleted_at: qui e' una DELETE reale, come per
        // cms_privileges (a differenza di tenants/groups che fanno soft
        // delete).
        $this->assertDatabaseMissing('cms_users', ['id' => $user['id']]);
    }

    public function test_cancellazione_fallisce_se_lutente_prova_a_cancellare_se_stesso(): void
    {
        $actor = $this->actingAsSuperadmin();

        // hook_before_delete() vieta esplicitamente di cancellare il
        // proprio account - vedi AdminCmsUsersController.
        $response = $this->get("http://localhost/admin/users/delete/{$actor['userId']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.delete_self'));

        $this->assertDatabaseHas('cms_users', ['id' => $actor['userId']]);
    }
}
