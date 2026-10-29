<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione (batch 0 del piano 317, docs/refactoring/piani/
 * 317-models-standard-laravel.md) per i due metodi di Menu che leggono la
 * pivot menu_tenants:
 *
 * - Menu::tenants_name(), raggiunto da MenuHelper::menu_to_html() e mostrato
 *   solo al superadmin nella riga "meta" di ogni voce (icona buildings);
 * - Menu::tenants(), raggiunto da UserHelper::can_menu() e osservabile dalla
 *   lista di Menu Management (voce modificabile oppure riga "ui-state-
 *   disabled", e voce visibile o meno per tenant, vedi MenuHelper::get_menu()).
 *
 * Le asserzioni passano da GET admin/menu_management (e non da una chiamata
 * diretta agli helper) perche' CRUDBooster::isUpdate()/isDelete() per un
 * non-superadmin dipendono dal path della richiesta corrente.
 *
 * Nota: il caso "ultimo tenant soft-deleted" e' stato rovesciato di proposito
 * dal batch 2b del piano 317 (niente piu' virgola finale in tenants_name()).
 */
class MenuTenantsCharacterizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    /**
     * Semina una voce di menu attiva di primo livello con i tenant indicati
     * nella pivot, nell'ordine dato (gli id dei tenant crescono nello stesso
     * ordine, quindi l'ordine "naturale" della pivot e' quello atteso).
     * Un nome con prefisso '!' viene creato gia' soft-deleted (la riga
     * resta comunque nella pivot, come fa il motore quando cancella un tenant).
     *
     * @param array<int,string> $tenantNames
     * @return array{menuId:int,tenantIds:array<int,int>}
     */
    private function seedMenuWithTenants(array $tenantNames, string $menuName): array
    {
        $menu = $this->seedMenu(['name' => $menuName]);
        $tenantIds = [];

        foreach ($tenantNames as $tenantName) {
            $deleted = str_starts_with($tenantName, '!');
            $tenantId = $this->seedTenant();
            DB::table('tenants')->where('id', $tenantId)->update([
                'name' => ltrim($tenantName, '!'),
                'deleted_at' => $deleted ? now() : null,
            ]);
            DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);
            $tenantIds[] = $tenantId;
        }

        return ['menuId' => $menu['id'], 'tenantIds' => $tenantIds];
    }

    private function menuListHtml(): string
    {
        $response = $this->get('http://localhost/admin/menu_management');
        $response->assertStatus(200);

        return $response->getContent();
    }

    /**
     * Testo mostrato accanto all'icona "buildings" nella riga della voce
     * (e = Menu::tenants_name() dopo e()), oppure null se la voce non e' nella
     * lista. Il testo NON e' trimmato: serve per vedere la virgola finale.
     */
    private function tenantsLabelOf(string $html, int $menuId): ?string
    {
        $pattern = "/<li class='[^']*' data-id='{$menuId}' data-name='.*?bi-buildings-fill'><\\/i> (.*?)<\\/span>/s";
        if (!preg_match($pattern, $html, $matches)) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    /**
     * Valore dell'attributo class del <li> della voce (stringa vuota =
     * modificabile; 'ui-state-disabled' = can_menu('edit') negato), oppure
     * null se la voce non compare nella lista.
     */
    private function rowClassOf(string $html, int $menuId): ?string
    {
        if (!preg_match("/<li class='([^']*)' data-id='{$menuId}' data-name='/", $html, $matches)) {
            return null;
        }

        return $matches[1];
    }

    // ---------------------------------------------------------------
    // 1. Menu::tenants_name() via MenuHelper::menu_to_html()
    // ---------------------------------------------------------------

    public function test_tenants_name_con_due_tenant_li_separa_con_virgola_e_spazio(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['Alfa', 'Beta'], 'Voce Due Tenant');

        $this->assertSame('Alfa, Beta', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    public function test_tenants_name_con_un_solo_tenant_non_ha_virgole(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['Alfa'], 'Voce Un Tenant');

        $this->assertSame('Alfa', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    public function test_tenants_name_senza_tenant_e_stringa_vuota(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants([], 'Voce Senza Tenant');

        $this->assertSame('', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    /**
     * Comportamento CAMBIATO di proposito dal batch 2b del piano 317: prima
     * la virgola dopo il penultimo nome era decisa su "key < count-1" (conta
     * anche il tenant soft-deleted), quindi con il soft-deleted per ULTIMO
     * restava "Alfa, ". Ora i nomi presenti sono uniti con implode: "Alfa".
     */
    public function test_tenants_name_con_ultimo_tenant_soft_deleted_non_lascia_la_virgola_finale(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['Alfa', '!Beta'], 'Voce Ultimo Cancellato');

        $this->assertSame('Alfa', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    /**
     * Caso che il batch 2b NON deve cambiare: soft-deleted in mezzo, nessuna
     * virgola in eccesso.
     */
    public function test_tenants_name_con_tenant_soft_deleted_in_mezzo_non_lascia_virgole_in_eccesso(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['Alfa', '!Beta', 'Gamma'], 'Voce Tenant Cancellato In Mezzo');

        $this->assertSame('Alfa, Gamma', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    /**
     * Caso che il batch 2b NON deve cambiare: soft-deleted in testa.
     */
    public function test_tenants_name_con_primo_tenant_soft_deleted_mostra_solo_gli_altri(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['!Alfa', 'Beta'], 'Voce Primo Cancellato');

        $this->assertSame('Beta', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    /**
     * Caso che il batch 2b NON deve cambiare: tutti soft-deleted.
     */
    public function test_tenants_name_con_tutti_i_tenant_soft_deleted_e_stringa_vuota(): void
    {
        $this->actingAsSuperadmin();
        $seed = $this->seedMenuWithTenants(['!Alfa', '!Beta'], 'Voce Tutti Cancellati');

        $this->assertSame('', $this->tenantsLabelOf($this->menuListHtml(), $seed['menuId']));
    }

    // ---------------------------------------------------------------
    // 2. UserHelper::can_menu() via Menu::tenants()
    //    (la pivot include anche i tenant soft-deleted: Menu::tenants()
    //    legge menu_tenants senza passare da Tenant)
    // ---------------------------------------------------------------

    public function test_tenantadmin_vede_e_puo_modificare_solo_il_menu_assegnato_solo_al_proprio_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);

        $own = $this->seedMenu(['name' => 'Voce Del Mio Tenant']);
        DB::table('menu_tenants')->insert(['menu_id' => $own['id'], 'tenant_id' => $tenantId]);

        $html = $this->menuListHtml();

        $this->assertSame('', $this->rowClassOf($html, $own['id']), 'Voce del solo tenant dell\'utente: deve essere modificabile.');
    }

    public function test_tenantadmin_non_vede_il_menu_assegnato_solo_a_un_altro_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);

        $foreign = $this->seedMenu(['name' => 'Voce Di Un Altro Tenant']);
        DB::table('menu_tenants')->insert(['menu_id' => $foreign['id'], 'tenant_id' => $otherTenantId]);

        $html = $this->menuListHtml();

        $this->assertNull($this->rowClassOf($html, $foreign['id']));
    }

    public function test_tenantadmin_vede_ma_non_puo_modificare_un_menu_condiviso_con_un_altro_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);

        $shared = $this->seedMenu(['name' => 'Voce Condivisa Tra Tenant']);
        DB::table('menu_tenants')->insert(['menu_id' => $shared['id'], 'tenant_id' => $tenantId]);
        DB::table('menu_tenants')->insert(['menu_id' => $shared['id'], 'tenant_id' => $otherTenantId]);

        $html = $this->menuListHtml();

        $this->assertSame('ui-state-disabled', $this->rowClassOf($html, $shared['id']));
    }

    /**
     * FOTOGRAFA il comportamento attuale con un tenant soft-deleted nella
     * pivot: Menu::tenants() lo conta comunque (count == 2), quindi
     * can_menu() nega la modifica anche se per Tenant::find() il menu
     * "appartiene" al solo tenant dell'utente. I batch 1-3 devono preservarlo
     * (Menu::tenants() resta "id dal pivot, inclusi i soft-deleted").
     */
    public function test_fotografa_tenant_soft_deleted_nella_pivot_conta_per_can_menu(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['menu_management']);

        $menu = $this->seedMenu(['name' => 'Voce Con Tenant Cancellato']);
        $deletedTenantId = $this->seedTenant();
        DB::table('tenants')->where('id', $deletedTenantId)->update(['deleted_at' => now()]);
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $tenantId]);
        DB::table('menu_tenants')->insert(['menu_id' => $menu['id'], 'tenant_id' => $deletedTenantId]);

        $html = $this->menuListHtml();

        $this->assertSame('ui-state-disabled', $this->rowClassOf($html, $menu['id']));
    }
}
