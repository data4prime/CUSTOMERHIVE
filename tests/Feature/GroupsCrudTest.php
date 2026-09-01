<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il CRUD del modulo Groups (CBController +
 * AdminGroupsController::hook_after_add()/hook_before_delete()).
 *
 * Stesso stile di TenantsCrudTest - vedi i commenti li' (e su
 * SeedsCmsData::registerAdminModule()/actingAsSuperadmin()) per il perche'
 * di setUp() e dell'assenza di mock/isolamento di processo.
 */
class GroupsCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    public function test_lista_groups_mostra_i_record_esistenti(): void
    {
        $this->actingAsSuperadmin();
        DB::table('groups')->insert([
            'name' => 'Gruppo Esistente Nella Lista',
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/groups');

        $response->assertStatus(200);
        $response->assertSee('Gruppo Esistente Nella Lista');
    }

    public function test_creazione_gruppo_riesce_e_lo_associa_al_tenant_dellattore(): void
    {
        $actor = $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/groups/add-save', [
            'name' => 'Nuovo Gruppo Di Test',
            'description' => 'Creato dal test CRUD',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('groups')->where('name', 'Nuovo Gruppo Di Test')->first();
        $this->assertNotNull($row);
        $this->assertSame('Creato dal test CRUD', $row->description);
        $this->assertNull($row->deleted_at);

        // hook_after_add() associa sempre il nuovo gruppo al tenant di chi
        // lo ha creato - vedi AdminGroupsController e App\Group::add_tenant().
        $this->assertDatabaseHas('group_tenants', [
            'group_id' => $row->id,
            'tenant_id' => $actor['tenantId'],
        ]);
    }

    public function test_modifica_gruppo_riesce(): void
    {
        $this->actingAsSuperadmin();
        $groupId = DB::table('groups')->insertGetId([
            'name' => 'Gruppo Da Modificare',
            'created_at' => now(),
        ]);

        $response = $this->post("http://localhost/admin/groups/edit-save/{$groupId}", [
            'name' => 'Gruppo Modificato',
            'description' => 'Modificato dal test CRUD',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('groups')->where('id', $groupId)->first();
        $this->assertSame('Gruppo Modificato', $row->name);
        $this->assertSame('Modificato dal test CRUD', $row->description);
    }

    public function test_cancellazione_gruppo_riesce_con_soft_delete(): void
    {
        $this->actingAsSuperadmin();
        $groupId = DB::table('groups')->insertGetId([
            'name' => 'Gruppo Da Cancellare',
            'created_at' => now(),
        ]);

        $response = $this->get("http://localhost/admin/groups/delete/{$groupId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        // groups ha deleted_at/deleted_by: CBController::getDelete() fa
        // soft delete, non una DELETE reale.
        $row = DB::table('groups')->where('id', $groupId)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
    }

    public function test_cancellazione_gruppo_fallisce_se_ha_ancora_membri(): void
    {
        $actor = $this->actingAsSuperadmin();
        $groupId = DB::table('groups')->insertGetId([
            'name' => 'Gruppo Con Membri',
            'created_at' => now(),
        ]);
        DB::table('users_groups')->insert([
            'user_id' => $actor['userId'],
            'group_id' => $groupId,
            'created_at' => now(),
        ]);

        // hook_before_delete() blocca la cancellazione se il gruppo ha
        // ancora membri - vedi AdminGroupsController.
        $response = $this->get("http://localhost/admin/groups/delete/{$groupId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');

        $row = DB::table('groups')->where('id', $groupId)->first();
        $this->assertNull($row->deleted_at, 'Il gruppo non deve risultare cancellato.');
    }

    public function test_cancellazione_gruppo_fallisce_se_ha_ancora_tenant_associati(): void
    {
        $this->actingAsSuperadmin();
        $groupId = DB::table('groups')->insertGetId([
            'name' => 'Gruppo Con Tenant',
            'created_at' => now(),
        ]);
        $tenantId = $this->seedTenant();
        DB::table('group_tenants')->insert([
            'group_id' => $groupId,
            'tenant_id' => $tenantId,
        ]);

        // hook_before_delete() blocca la cancellazione anche se il gruppo
        // ha ancora tenant associati (non solo membri) - vedi
        // AdminGroupsController. Il filtro esclude i tenant soft-deleted:
        // qui il tenant e' vivo, quindi il blocco deve scattare.
        $response = $this->get("http://localhost/admin/groups/delete/{$groupId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');

        $row = DB::table('groups')->where('id', $groupId)->first();
        $this->assertNull($row->deleted_at, 'Il gruppo non deve risultare cancellato.');
    }
}
