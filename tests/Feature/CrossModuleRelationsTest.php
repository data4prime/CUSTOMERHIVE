<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione sulle RELAZIONI tra i moduli Tenants, Groups,
 * Privileges e Users, a complemento dei rispettivi *CrudTest.php (che
 * coprono solo il comportamento interno di ciascun modulo).
 *
 * Alcuni di questi test NON verificano un comportamento desiderato, ma
 * fotografano il comportamento ATTUALE per renderlo visibile e non
 * regredibile silenziosamente - in particolare l'isolamento (o la sua
 * assenza) tra tenant diversi nelle liste di Users/Groups/Tenants. Vedi il
 * riepilogo finale per quali di questi casi sono gia' stati decisi
 * esplicitamente con l'utente in passato (docs/refactoring/044, 046, 047)
 * e quali sono invece nuovi ritrovamenti da questa sessione.
 */
class CrossModuleRelationsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private $previousServerValues = [];

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

    /**
     * La riga NON viene filtrata a livello SQL in CBController::getIndex()
     * (il filtro per tenant li' e' commentato, e si applica comunque solo
     * ai moduli "manually generated" - cms_users non lo e'): l'isolamento
     * per Users avviene invece riga-per-riga DOPO la query, in
     * ModuleHelper::can_view() (chiamata da getIndex() subito prima di
     * renderizzare ogni riga), che per la tabella cms_users nasconde
     * esplicitamente le righe il cui tenant non coincide con quello
     * dell'attore quando non e' superadmin. Isolamento presente e
     * funzionante, solo implementato in un punto diverso da quello che ci
     * si aspetterebbe (query) - qui lo si conferma per un tenantadmin.
     */
    public function test_lista_utenti_isola_per_tenant_un_tenantadmin(): void
    {
        $ownTenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $this->seedUser(['tenant' => $otherTenantId, 'name' => 'Utente Di Un Altro Tenant']);

        $this->actingAsTenantUser($ownTenantId, isTenantadmin: true, visibleModulePaths: ['users']);

        $response = $this->get('http://localhost/admin/users');

        $response->assertStatus(200);
        $response->assertDontSee('Utente Di Un Altro Tenant');
    }

    /**
     * ModuleHelper::can_view()/get_tenant_id() non hanno NESSUN ramo per la
     * tabella 'tenants' (solo cms_users, groups e i moduli Qlik la
     * gestiscono esplicitamente): per qualunque riga di questo modulo,
     * get_tenant_id() ritorna semplicemente `true` come valore di
     * fallback, che non puo' mai combaciare con nessuno dei controlli
     * espliciti in can_view() - il risultato e' che un attore non
     * superadmin non vede NESSUN tenant in lista, nemmeno il proprio.
     * Non risulta deciso/documentato altrove (a differenza di 044/046/047):
     * possibile gap, da confermare con l'utente se un tenantadmin dovrebbe
     * invece poter vedere almeno la riga del proprio tenant.
     */
    public function test_lista_tenants_nessun_tenant_visibile_per_un_tenantadmin(): void
    {
        $ownTenantId = $this->seedTenant('tenant-proprio-del-tenantadmin');

        $this->actingAsTenantUser($ownTenantId, isTenantadmin: true, visibleModulePaths: ['tenants']);

        $response = $this->get('http://localhost/admin/tenants');

        $response->assertStatus(200);
        $response->assertDontSee('tenant-proprio-del-tenantadmin');
    }

    /**
     * Per Groups l'isolamento e' applicato DUE volte in modo ridondante:
     * a livello SQL in AdminGroupsController::hook_query_index() (solo per
     * il ramo tenantadmin) E riga-per-riga in ModuleHelper::can_view()
     * (per qualunque non-superadmin, vedi test successivo). Qui si
     * conferma il risultato finale per un tenantadmin: solo i gruppi del
     * proprio tenant.
     */
    public function test_lista_gruppi_tenantadmin_vede_solo_i_gruppi_del_proprio_tenant(): void
    {
        $ownTenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();

        $ownGroupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $ownGroupId, 'tenant_id' => $ownTenantId]);
        DB::table('groups')->where('id', $ownGroupId)->update(['name' => 'Gruppo Del Mio Tenant']);

        $otherGroupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $otherGroupId, 'tenant_id' => $otherTenantId]);
        DB::table('groups')->where('id', $otherGroupId)->update(['name' => 'Gruppo Di Un Altro Tenant']);

        $this->actingAsTenantUser($ownTenantId, isTenantadmin: true, visibleModulePaths: ['groups']);

        $response = $this->get('http://localhost/admin/groups');

        $response->assertStatus(200);
        $response->assertSee('Gruppo Del Mio Tenant');
        $response->assertDontSee('Gruppo Di Un Altro Tenant');
    }

    /**
     * A differenza dell'SQL di hook_query_index() (che filtra solo per il
     * ramo tenantadmin), ModuleHelper::can_view() applica il suo controllo
     * 'groups' a QUALUNQUE attore non superadmin, standard incluso: ci si
     * aspetta quindi che anche uno Standard veda solo i gruppi del proprio
     * tenant, non tutti. Se invece questo test fallisce con un errore
     * (non un semplice fallimento di assertSee), significa che
     * get_tenant_id() per un gruppo di un tenant estraneo va in crash
     * (la query interna non controlla se first() e' null prima di leggere
     * ->tenant_id) - da verificare empiricamente.
     */
    public function test_lista_gruppi_standard_vede_solo_i_gruppi_del_proprio_tenant(): void
    {
        $ownTenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();

        $ownGroupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $ownGroupId, 'tenant_id' => $ownTenantId]);
        DB::table('groups')->where('id', $ownGroupId)->update(['name' => 'Gruppo Del Mio Tenant Standard']);

        $otherGroupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $otherGroupId, 'tenant_id' => $otherTenantId]);
        DB::table('groups')->where('id', $otherGroupId)->update(['name' => 'Gruppo Di Un Altro Tenant Standard']);

        $this->actingAsTenantUser($ownTenantId, isTenantadmin: false, visibleModulePaths: ['groups']);

        $response = $this->get('http://localhost/admin/groups');

        $response->assertStatus(200);
        $response->assertSee('Gruppo Del Mio Tenant Standard');
        $response->assertDontSee('Gruppo Di Un Altro Tenant Standard');
    }

    /**
     * Deciso esplicitamente con l'utente in docs/refactoring/044 e 046-047:
     * PrivilegesController::getDelete() non controlla se la privilege e'
     * ancora assegnata a qualche utente (nessun guard "in uso"), e
     * App\User::isSuperAdmin()/isTenantAdmin() tollerano un
     * id_cms_privileges ormai orfano trattando l'utente come privo di
     * permessi speciali, senza crash.
     */
    public function test_cancellazione_privilegio_in_uso_riesce_e_utente_orfano_non_ha_piu_permessi_speciali(): void
    {
        $this->actingAsSuperadmin();
        $privilegeId = DB::table('cms_privileges')->insertGetId([
            'name' => 'Privilegio Che Verra Cancellato',
            'is_superadmin' => 0,
            'is_tenantadmin' => 0,
            'theme_color' => 'blue',
        ]);
        $orphanUser = $this->seedUser(['id_cms_privileges' => $privilegeId]);

        $response = $this->get("http://localhost/admin/privileges/delete/{$privilegeId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseMissing('cms_privileges', ['id' => $privilegeId]);

        $this->assertDatabaseHas('cms_users', ['id' => $orphanUser['id']]);
        $user = User::find($orphanUser['id']);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isTenantAdmin());
    }

    /**
     * AdminTenantsController::hook_before_delete() blocca la cancellazione
     * SOLO se il tenant ha ancora utenti membri (User::where('tenant',
     * $id)->count()) - non controlla group_tenants. Un tenant associato a
     * un Group ma senza nessun utente puo' quindi essere cancellato
     * (soft delete) lasciando una riga group_tenants orfana che punta a un
     * tenant ormai cancellato.
     */
    public function test_cancellazione_tenant_con_gruppo_associato_ma_senza_utenti_riesce_e_lascia_association_orfana(): void
    {
        $this->actingAsSuperadmin();
        $tenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        DB::table('group_tenants')->insert(['group_id' => $groupId, 'tenant_id' => $tenantId]);

        $response = $this->get("http://localhost/admin/tenants/delete/{$tenantId}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');

        $this->assertDatabaseHas('tenants', ['id' => $tenantId]);
        $this->assertSoftDeleted('tenants', ['id' => $tenantId]);

        // La riga resta, ma ora punta a un tenant soft-deleted: nessuna
        // pulizia/cascade e' applicata da hook_before_delete()/
        // hook_after_delete().
        $this->assertDatabaseHas('group_tenants', ['group_id' => $groupId, 'tenant_id' => $tenantId]);
    }

    /**
     * Cambiare la privilege di un utente (es. da Standard a Superadmin) non
     * deve toccare la sua appartenenza ai gruppi: hook_before_edit() di
     * AdminCmsUsersController reagisce solo a cambi di tenant/primary_group,
     * mai di id_cms_privileges.
     */
    public function test_modifica_privilegio_utente_non_tocca_lappartenenza_ai_gruppi(): void
    {
        $this->actingAsSuperadmin();
        $standardPrivilegeId = $this->seedPrivilege();
        $superadminPrivilegeId = $this->seedPrivilege(isSuperadmin: true);
        $user = $this->seedUser(['id_cms_privileges' => $standardPrivilegeId]);
        DB::table('users_groups')->insert([
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'created_at' => now(),
        ]);

        $response = $this->post("http://localhost/admin/users/edit-save/{$user['id']}", [
            'name' => $user['name'],
            'email' => $user['email'],
            'id_cms_privileges' => $superadminPrivilegeId,
            'tenant' => $user['tenant'],
            'primary_group' => $user['primary_group'],
            'status' => 'Active',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_users', ['id' => $user['id'], 'id_cms_privileges' => $superadminPrivilegeId]);
        $this->assertDatabaseHas('users_groups', [
            'user_id' => $user['id'],
            'group_id' => $user['primary_group'],
            'deleted_at' => null,
        ]);
    }

    /**
     * Group::add_tenant() (hook_after_add di AdminGroupsController) deve
     * associare il nuovo gruppo SOLO al tenant dell'attore che lo crea, non
     * mescolarlo con gruppi/tenant creati da altri attori in parallelo.
     */
    public function test_creazione_gruppi_in_tenant_diversi_non_si_mescolano(): void
    {
        $firstActor = $this->actingAsSuperadmin();
        $this->post('http://localhost/admin/groups/add-save', ['name' => 'Gruppo Del Primo Tenant']);
        $firstGroupId = DB::table('groups')->where('name', 'Gruppo Del Primo Tenant')->value('id');

        $secondActor = $this->actingAsSuperadmin();
        $this->post('http://localhost/admin/groups/add-save', ['name' => 'Gruppo Del Secondo Tenant']);
        $secondGroupId = DB::table('groups')->where('name', 'Gruppo Del Secondo Tenant')->value('id');

        $this->assertDatabaseHas('group_tenants', ['group_id' => $firstGroupId, 'tenant_id' => $firstActor['tenantId']]);
        $this->assertDatabaseMissing('group_tenants', ['group_id' => $firstGroupId, 'tenant_id' => $secondActor['tenantId']]);

        $this->assertDatabaseHas('group_tenants', ['group_id' => $secondGroupId, 'tenant_id' => $secondActor['tenantId']]);
        $this->assertDatabaseMissing('group_tenants', ['group_id' => $secondGroupId, 'tenant_id' => $firstActor['tenantId']]);
    }
}
