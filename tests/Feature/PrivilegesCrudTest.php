<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il CRUD del modulo Privileges
 * (PrivilegesController - non passa per il CBController condiviso, ha una
 * propria implementazione di postAddSave()/postEditSave()/getDelete()).
 *
 * Stesso stile di TenantsCrudTest/GroupsCrudTest - vedi i commenti li' (e
 * su SeedsCmsData) per il perche' di setUp() e dell'assenza di mock/
 * isolamento di processo.
 *
 * "superprivilege" e' un campo virtuale del form (non una colonna reale di
 * cms_privileges): 1 = Superadmin, 2 = Tenantadmin, altro/assente =
 * Standard - tradotto in is_superadmin/is_tenantadmin al salvataggio.
 */
class PrivilegesCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    public function test_lista_privileges_mostra_i_record_esistenti(): void
    {
        $this->actingAsSuperadmin();
        DB::table('cms_privileges')->insert([
            'name' => 'Privilegio Esistente Nella Lista',
            'is_superadmin' => 0,
            'is_tenantadmin' => 0,
            'theme_color' => 'blue',
        ]);

        $response = $this->get('http://localhost/admin/privileges');

        $response->assertStatus(200);
        $response->assertSee('Privilegio Esistente Nella Lista');
    }

    public function test_creazione_privilegio_standard_riesce(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/privileges/add-save', [
            'name' => 'Nuovo Privilegio Standard',
            'superprivilege' => 0,
            'theme_color' => 'blue',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('cms_privileges')->where('name', 'Nuovo Privilegio Standard')->first();
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row->is_superadmin);
        $this->assertSame(0, (int) $row->is_tenantadmin);
    }

    public function test_creazione_privilegio_superadmin_riesce(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/privileges/add-save', [
            'name' => 'Nuovo Privilegio Super',
            'superprivilege' => 1,
            'theme_color' => 'red',
        ]);

        $response->assertStatus(302);

        $row = DB::table('cms_privileges')->where('name', 'Nuovo Privilegio Super')->first();
        $this->assertSame(1, (int) $row->is_superadmin);
        $this->assertSame(0, (int) $row->is_tenantadmin);
    }

    public function test_creazione_privilegio_tenantadmin_riesce(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/privileges/add-save', [
            'name' => 'Nuovo Privilegio Tenantadmin',
            'superprivilege' => 2,
            'theme_color' => 'green',
        ]);

        $response->assertStatus(302);

        $row = DB::table('cms_privileges')->where('name', 'Nuovo Privilegio Tenantadmin')->first();
        $this->assertSame(0, (int) $row->is_superadmin);
        $this->assertSame(1, (int) $row->is_tenantadmin);
    }

    public function test_modifica_privilegio_riesce(): void
    {
        $this->actingAsSuperadmin();
        $privilegeId = DB::table('cms_privileges')->insertGetId([
            'name' => 'Privilegio Da Modificare',
            'is_superadmin' => 0,
            'is_tenantadmin' => 0,
            'theme_color' => 'blue',
        ]);

        $response = $this->post("http://localhost/admin/privileges/edit-save/{$privilegeId}", [
            'name' => 'Privilegio Modificato',
            'superprivilege' => 2,
            'theme_color' => 'orange',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('cms_privileges')->where('id', $privilegeId)->first();
        $this->assertSame('Privilegio Modificato', $row->name);
        $this->assertSame('orange', $row->theme_color);
        $this->assertSame(1, (int) $row->is_tenantadmin);
    }

    public function test_cancellazione_privilegio_standard_riesce(): void
    {
        $this->actingAsSuperadmin();
        $privilegeId = DB::table('cms_privileges')->insertGetId([
            'name' => 'Privilegio Da Cancellare',
            'is_superadmin' => 0,
            'is_tenantadmin' => 0,
            'theme_color' => 'blue',
        ]);

        $response = $this->get("http://localhost/admin/privileges/delete/{$privilegeId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        // cms_privileges non ha deleted_at: qui e' una DELETE reale, non un
        // soft delete (a differenza di tenants/groups).
        $this->assertDatabaseMissing('cms_privileges', ['id' => $privilegeId]);
    }

    public function test_cancellazione_privilegio_superadmin_fallisce(): void
    {
        $this->actingAsSuperadmin();
        $privilegeId = DB::table('cms_privileges')->insertGetId([
            'name' => 'Privilegio Superadmin Da Non Cancellare',
            'is_superadmin' => 1,
            'is_tenantadmin' => 0,
            'theme_color' => 'red',
        ]);

        // getDelete() blocca esplicitamente la cancellazione di un
        // privilegio con is_superadmin=1 - vedi PrivilegesController: senza
        // questo controllo potrebbe non restare piu' nessun privilegio con
        // accesso completo.
        $response = $this->get("http://localhost/admin/privileges/delete/{$privilegeId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.cant_delete_role'));

        $this->assertDatabaseHas('cms_privileges', ['id' => $privilegeId]);
    }
}
