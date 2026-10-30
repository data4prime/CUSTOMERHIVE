<?php

namespace Tests\Feature;

use App\Helpers\UserHelper;
use App\Models\Group;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione (batch 0 del piano 317) per User::tenant() e
 * User::primary_group() (Tenant::find()/Group::find() sull'attributo omonimo,
 * quindi i soft-deleted e gli id inesistenti danno null) e per
 * UserHelper::current_user_primary_group_name() che ci legge ->name sopra.
 *
 * Nota di schema: cms_users.primary_group e cms_users.tenant sono NOT NULL,
 * quindi il caso "orfano" reale e' un id che punta a un gruppo/tenant
 * soft-deleted o inesistente, non una colonna NULL.
 *
 * Nota: il caso "gruppo primario orfano" di current_user_primary_group_name()
 * e' stato rovesciato di proposito dal batch 2b del piano 317 ('' al posto
 * dell'ErrorException).
 */
class UserModelLookupsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    private function seedOrphanableUser(?int $groupId = null, ?int $tenantId = null): array
    {
        $tenantId ??= $this->seedTenant();
        $groupId ??= $this->seedGroup();

        return $this->seedUser(['tenant' => $tenantId, 'primary_group' => $groupId]);
    }

    public function test_tenant_e_primary_group_validi_restituiscono_i_modelli_attesi(): void
    {
        $tenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        $row = $this->seedOrphanableUser($groupId, $tenantId);

        $user = User::find($row['id']);

        $this->assertInstanceOf(Tenant::class, $user->tenant());
        $this->assertSame($tenantId, (int) $user->tenant()->id);
        $this->assertInstanceOf(Group::class, $user->primary_group());
        $this->assertSame($groupId, (int) $user->primary_group()->id);
    }

    /**
     * Le proprieta' $user->tenant e $user->primary_group sono le COLONNE
     * (id grezzo), non le relazioni: e' per questo che nel batch 2 le nuove
     * relazioni devono avere nomi diversi.
     */
    public function test_le_proprieta_tenant_e_primary_group_sono_gli_id_della_colonna(): void
    {
        $tenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        $row = $this->seedOrphanableUser($groupId, $tenantId);

        $user = User::find($row['id']);

        $this->assertSame($tenantId, (int) $user->tenant);
        $this->assertSame($groupId, (int) $user->primary_group);
    }

    public function test_primary_group_soft_deleted_o_inesistente_restituisce_null(): void
    {
        $deletedGroupId = $this->seedGroup();
        DB::table('groups')->where('id', $deletedGroupId)->update(['deleted_at' => now()]);

        $withDeleted = User::find($this->seedOrphanableUser($deletedGroupId)['id']);
        $withMissing = User::find($this->seedOrphanableUser(999999)['id']);

        $this->assertNull($withDeleted->primary_group());
        $this->assertNull($withMissing->primary_group());
    }

    public function test_tenant_soft_deleted_o_inesistente_restituisce_null(): void
    {
        $deletedTenantId = $this->seedTenant();
        DB::table('tenants')->where('id', $deletedTenantId)->update(['deleted_at' => now()]);

        $withDeleted = User::find($this->seedOrphanableUser(null, $deletedTenantId)['id']);
        $withMissing = User::find($this->seedOrphanableUser(null, 999999)['id']);

        $this->assertNull($withDeleted->tenant());
        $this->assertNull($withMissing->tenant());
    }

    public function test_current_user_primary_group_name_con_gruppo_valido_restituisce_il_nome(): void
    {
        $groupId = $this->seedGroup();
        DB::table('groups')->where('id', $groupId)->update(['name' => 'Gruppo Primario Valido']);
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: false, visibleModulePaths: [], userOverrides: ['primary_group' => $groupId]);

        $this->assertSame('Gruppo Primario Valido', UserHelper::current_user_primary_group_name());
    }

    /**
     * Comportamento CAMBIATO di proposito dal batch 2b del piano 317: con il
     * gruppo primario soft-deleted User::primary_group() torna null e prima
     * ->name su null sollevava ErrorException ("Attempt to read property
     * "name" on null"). Ora restituisce '' ("nessun default" per i form che
     * lo usano come 'default' del campo Group/Primary Group).
     */
    public function test_current_user_primary_group_name_con_gruppo_primario_orfano_restituisce_stringa_vuota(): void
    {
        $deletedGroupId = $this->seedGroup();
        DB::table('groups')->where('id', $deletedGroupId)->update(['deleted_at' => now()]);
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: false, visibleModulePaths: [], userOverrides: ['primary_group' => $deletedGroupId]);

        $this->assertSame('', UserHelper::current_user_primary_group_name());
    }

    public function test_current_user_primary_group_name_con_gruppo_primario_inesistente_restituisce_stringa_vuota(): void
    {
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: false, visibleModulePaths: [], userOverrides: ['primary_group' => 999999]);

        $this->assertSame('', UserHelper::current_user_primary_group_name());
    }

    /**
     * Caso che il batch 2b NON deve cambiare: current_user_primary_group()
     * restituisce l'id grezzo dalla colonna anche se il gruppo e' orfano.
     */
    public function test_current_user_primary_group_restituisce_l_id_grezzo_anche_se_il_gruppo_e_orfano(): void
    {
        $deletedGroupId = $this->seedGroup();
        DB::table('groups')->where('id', $deletedGroupId)->update(['deleted_at' => now()]);
        $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: false, visibleModulePaths: [], userOverrides: ['primary_group' => $deletedGroupId]);

        $this->assertSame($deletedGroupId, (int) UserHelper::current_user_primary_group());
    }
}
