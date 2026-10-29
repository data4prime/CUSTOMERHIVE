<?php

namespace Tests\Feature;

use App\Helpers\QlikHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione (batch 0 del piano 317) per
 * QlikHelper::can_see_item(), che legge QlikItem::allowedTenants() (tabella
 * tenants_allowed, chiave item_id) e QlikItem::allowedGroups() (tabella
 * items_allowed, chiave item_id) e li confronta con il tenant dell'utente
 * corrente e con GroupHelper::myGroups() (SOLO la tabella users_groups: il
 * gruppo primario in cms_users NON conta qui).
 *
 * Regole fotografate: il superadmin vede sempre; un item pubblico
 * (proxy_token valorizzato) lo vedono tutti; il tenantadmin ha bisogno solo
 * del tenant abilitato; un utente standard ha bisogno del tenant abilitato E
 * di un gruppo (users_groups) abilitato.
 *
 * Niente test per ChatAIHelper::can_see_item(): ChatAIConf::allowedTenants()/
 * allowedGroups() interrogano la colonna chataiconf_id, che nessuna migrazione
 * crea su tenants_allowed/items_allowed (vedi report del batch 0). Va chiarito
 * con la persona prima di fissare quel comportamento.
 *
 * Gli helper vengono chiamati in-process: l'utente corrente arriva dalla
 * sessione 'admin_id' impostata da actingAsTenantUser()/actingAsSuperadmin().
 */
class QlikItemVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    private function seedQlikItem(array $overrides = []): int
    {
        return DB::table('qlik_items')->insertGetId(array_merge([
            'title' => 'Item Qlik Test',
            'created_at' => now(),
        ], $overrides));
    }

    private function allowTenant(int $itemId, int $tenantId): void
    {
        DB::table('tenants_allowed')->insert(['item_id' => $itemId, 'tenant_id' => $tenantId]);
    }

    private function allowGroup(int $itemId, int $groupId): void
    {
        DB::table('items_allowed')->insert(['item_id' => $itemId, 'group_id' => $groupId]);
    }

    public function test_superadmin_vede_sempre_qualunque_item(): void
    {
        $this->actingAsSuperadmin();
        $itemId = $this->seedQlikItem();

        $this->assertTrue(QlikHelper::can_see_item($itemId));
    }

    public function test_item_pubblico_e_visibile_a_un_utente_senza_tenant_ne_gruppi_abilitati(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $itemId = $this->seedQlikItem(['proxy_token' => str_repeat('a', 40)]);

        $this->assertTrue(QlikHelper::can_see_item($itemId));
    }

    public function test_tenantadmin_vede_un_item_il_cui_tenant_e_abilitato_senza_controllo_sul_gruppo(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $tenantId);

        $this->assertTrue(QlikHelper::can_see_item($itemId));
    }

    public function test_tenantadmin_non_vede_un_item_abilitato_solo_a_un_altro_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $otherTenantId);

        $this->assertFalse(QlikHelper::can_see_item($itemId));
    }

    public function test_utente_standard_vede_un_item_con_tenant_e_gruppo_di_appartenenza_abilitati(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $groupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $groupId]);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $tenantId);
        $this->allowGroup($itemId, $groupId);

        $this->assertTrue(QlikHelper::can_see_item($itemId));
    }

    public function test_utente_standard_non_vede_un_item_se_il_gruppo_abilitato_non_e_il_suo(): void
    {
        $tenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $ownGroupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $ownGroupId]);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $tenantId);
        $this->allowGroup($itemId, $otherGroupId);

        $this->assertFalse(QlikHelper::can_see_item($itemId));
    }

    public function test_utente_standard_non_vede_un_item_se_il_tenant_non_e_abilitato_anche_con_gruppo_abilitato(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $actor = $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: []);
        $groupId = $this->seedGroup();
        DB::table('users_groups')->insert(['user_id' => $actor['userId'], 'group_id' => $groupId]);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $otherTenantId);
        $this->allowGroup($itemId, $groupId);

        $this->assertFalse(QlikHelper::can_see_item($itemId));
    }

    /**
     * FOTOGRAFA un comportamento da non cambiare nei batch 1-3: il controllo
     * dei gruppi usa GroupHelper::myGroups() (solo users_groups), quindi un
     * item abilitato al solo gruppo PRIMARIO dell'utente (cms_users.
     * primary_group, senza riga in users_groups) non risulta visibile.
     */
    public function test_fotografa_il_gruppo_primario_senza_riga_in_users_groups_non_basta(): void
    {
        $tenantId = $this->seedTenant();
        $primaryGroupId = $this->seedGroup();
        $this->actingAsTenantUser($tenantId, isTenantadmin: false, visibleModulePaths: [], userOverrides: ['primary_group' => $primaryGroupId]);
        $itemId = $this->seedQlikItem();
        $this->allowTenant($itemId, $tenantId);
        $this->allowGroup($itemId, $primaryGroupId);

        $this->assertFalse(QlikHelper::can_see_item($itemId));
    }

    public function test_id_non_intero_o_inesistente_non_e_visibile_a_un_utente_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: []);

        $this->assertFalse(QlikHelper::can_see_item('non-un-intero'));
        $this->assertFalse(QlikHelper::can_see_item(999999));
    }
}
