<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il CRUD del modulo Tenants (CBController +
 * AdminTenantsController::hook_before_add()/hook_before_edit()).
 *
 * Prima che CRUDBooster::redirect() ritornasse la response invece di fare
 * exit() (refactor fatto apposta per rendere testabili add/edit/delete),
 * questi test non erano scrivibili in modo pulito: ora seguono lo stesso
 * stile di LoginTest/CBBackendTest.
 *
 * setUp() registra il modulo "tenants" (più i moduli sempre referenziati
 * dal layout admin condiviso) via SeedsCmsData::registerAdminModules() -
 * vedi il commento su quel metodo per il perché è necessario.
 */
class TenantsCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    public function test_lista_tenants_mostra_i_record_esistenti(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Esistente Nella Lista',
            'domain_name' => 'tenantesistentenellalista',
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/tenants');

        $response->assertStatus(200);
        $response->assertSee('Tenant Esistente Nella Lista');
    }

    public function test_creazione_tenant_riesce_e_deriva_il_domain_name_dal_nome(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/tenants/add-save', [
            'name' => 'Nuovo Tenant Di Test',
            'description' => 'Creato dal test CRUD',
            // Deve comunque passare la regex del form (solo alfanumerico) -
            // hook_before_add() lo sovrascrive comunque, ma la validazione
            // sul valore grezzo gira PRIMA dell'hook.
            'domain_name' => 'placeholder',
            'login_background_color' => '#ffffff',
            'login_font_color' => '#000000',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('tenants')->where('name', 'Nuovo Tenant Di Test')->first();
        $this->assertNotNull($row);
        // hook_before_add() sovrascrive sempre domain_name con lo slug di
        // "name" (TenantHelper::domain_name_encode()), a prescindere da
        // cosa arriva dal form - vedi AdminTenantsController.
        $this->assertSame('nuovotenantditest', $row->domain_name);
        $this->assertSame('Creato dal test CRUD', $row->description);
        $this->assertNull($row->deleted_at);
    }

    public function test_creazione_tenant_fallisce_se_il_dominio_gia_esiste(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Con Nome Duplicato',
            'domain_name' => 'tenantconnomeduplicato',
            'created_at' => now(),
        ]);

        // "name" produce lo stesso domain_name di quello gia' esistente -
        // TenantHelper::unique_domain_name() (chiamato da domain_name_encode())
        // deve accorgersene e non creare un duplicato.
        $response = $this->post('http://localhost/admin/tenants/add-save', [
            'name' => 'Tenant Con Nome Duplicato',
            'description' => 'Non dovrebbe avere lo stesso domain_name del primo',
            // "domain_name" e' required dal form - il valore qui non conta
            // (hook_before_add() lo sovrascrive sempre), ma deve comunque
            // passare la regex per non far fallire la validazione a monte.
            'domain_name' => 'placeholder',
        ]);

        $response->assertStatus(302);

        $rows = DB::table('tenants')->where('name', 'Tenant Con Nome Duplicato')->get();
        $this->assertCount(2, $rows, 'Il secondo tenant con lo stesso nome deve comunque essere creato...');
        $this->assertNotSame($rows[0]->domain_name, $rows[1]->domain_name, '...ma con un domain_name reso univoco, non duplicato.');
    }

    public function test_modifica_tenant_riesce(): void
    {
        $this->actingAsSuperadmin();
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Tenant Da Modificare',
            'domain_name' => 'tenantdamodificare',
            'created_at' => now(),
        ]);

        $response = $this->post("http://localhost/admin/tenants/edit-save/{$tenantId}", [
            'name' => 'Tenant Modificato',
            'description' => 'Modificato dal test CRUD',
            'domain_name' => 'tenantdamodificare',
            'login_background_color' => '#ffffff',
            'login_font_color' => '#000000',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $row = DB::table('tenants')->where('id', $tenantId)->first();
        $this->assertSame('Tenant Modificato', $row->name);
        $this->assertSame('Modificato dal test CRUD', $row->description);
    }

    public function test_modifica_tenant_fallisce_se_il_nuovo_dominio_e_gia_usato_da_un_altro_tenant(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Dominio Occupato',
            'domain_name' => 'domoccupato',
            'created_at' => now(),
        ]);
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Tenant Che Prova A Rubare Il Dominio',
            'domain_name' => 'domrubatore',
            'created_at' => now(),
        ]);

        // hook_before_edit() blocca il salvataggio se domain_name coincide
        // con quello di un ALTRO tenant - vedi AdminTenantsController.
        // domain_name deve stare entro max:20 e solo alfanumerico (regex del
        // form: la validazione gira PRIMA dell'hook).
        $response = $this->post("http://localhost/admin/tenants/edit-save/{$tenantId}", [
            'name' => 'Tenant Che Prova A Rubare Il Dominio',
            'domain_name' => 'domoccupato',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.not_unique_domain'));

        $row = DB::table('tenants')->where('id', $tenantId)->first();
        $this->assertSame('domrubatore', $row->domain_name, 'Il domain_name non deve essere stato sovrascritto.');
    }

    public function test_cancellazione_tenant_riesce_con_soft_delete(): void
    {
        $this->actingAsSuperadmin();
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Tenant Da Cancellare',
            'domain_name' => 'tenantdacancellare',
            'created_at' => now(),
        ]);

        $response = $this->get("http://localhost/admin/tenants/delete/{$tenantId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        // tenants ha deleted_at/deleted_by: CBController::getDelete() fa
        // soft delete (UPDATE deleted_at), non una DELETE reale - vedi il
        // ramo CRUDBooster::isColumnExists($this->table, 'deleted_at').
        $row = DB::table('tenants')->where('id', $tenantId)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
    }

    public function test_cancellazione_tenant_fallisce_se_ha_ancora_membri(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Tenant Con Membri',
            'domain_name' => 'tenantconmembri',
            'created_at' => now(),
        ]);
        $this->seedUser(['tenant' => $tenantId]);
        $this->actingAsSuperadmin();

        // hook_before_delete() blocca la cancellazione se il tenant ha
        // ancora utenti - vedi AdminTenantsController.
        $response = $this->get("http://localhost/admin/tenants/delete/{$tenantId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.delete_not_empty_tenant'));

        $row = DB::table('tenants')->where('id', $tenantId)->first();
        $this->assertNull($row->deleted_at, 'Il tenant non deve risultare cancellato.');
    }
}
