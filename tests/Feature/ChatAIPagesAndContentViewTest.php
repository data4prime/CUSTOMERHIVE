<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 332, batch 1: caratterizzazione delle pagine admin/chat_ai/access e
 * admin/chat_ai/tenant (elenco, alert, guardia superadmin, testi) e di
 * content_view (menu da ?m=, dimensioni, vista scelta, rifiuti).
 *
 * I testi delle pagine sono confrontati con trans('crudbooster.*') in en e it
 * (vedi la sezione "Testi"); in en coincidono con l'inglese che le pagine
 * mostravano prima della traduzione.
 *
 * Le pagine usano cbView() (echo): il corpo si cattura con ob_start(), come
 * negli altri test ChatAI. Il locale segue cms_users.lang (middleware
 * SetUserPreferredLanguage), quindi si imposta quello.
 */
class ChatAIPagesAndContentViewTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
        $this->registerAdminModule('chat_ai', 'AdminChatAIController', 'chatai_confs');

        // Stessa simulazione di ChatAIAuthorizationTest (modulo ChatAI non ancora sul license server).
        Storage::fake('license');
        // Le pagine access/tenant rendono l'admin template completo, che include il modale
        // di licenza (license_modal.blade.php): legge tutte queste chiavi, non solo "modules".
        Storage::disk('license')->put('license.json', json_encode([
            'modules' => [['name' => 'ChatAI']],
            'domain' => 'test.local',
            'license_key' => 'test-license-key',
            'status' => 'active',
            'expiration_date' => null,
            'is_trial' => 0,
            'is_lifetime' => 1,
            'clients_number' => 1,
            'tenants_number' => 1,
            'path' => 'N/A',
            'expires_in' => 'N/A',
        ]));
        DB::table('license')->insert(['license_key' => 'test-license-key']);
        Cache::put('license-connector:access-token-test-license-key', 'fake-token', now()->addMinutes(60));
    }

    private function seedConf(array $overrides = []): int
    {
        // url e token validi: la vista chat_ai.view chiama ChatAIHelper::getToken() sulla conf
        // (serve uno scheme nell'url e una passphrase di almeno 32 caratteri).
        return DB::table('chatai_confs')->insertGetId(array_merge([
            'title' => 'Agente Test',
            'url' => 'http://127.0.0.1:9/',
            'token' => str_repeat('a', 40),
            'created_at' => now(),
        ], $overrides));
    }

    private function seedNamedGroup(string $name, array $overrides = []): int
    {
        return DB::table('groups')->insertGetId(array_merge(['name' => $name, 'description' => 'desc ' . $name, 'created_at' => now()], $overrides));
    }

    private function seedNamedTenant(string $name, array $overrides = []): int
    {
        return DB::table('tenants')->insertGetId(array_merge([
            'name' => $name,
            'domain_name' => 'dom-' . uniqid(),
            'description' => 'desc ' . $name,
            'created_at' => now(),
        ], $overrides));
    }

    /** Lingua dell'utente loggato: il middleware la applica a ogni richiesta. */
    private function setUserLang(int $userId, string $lang): void
    {
        DB::table('cms_users')->where('id', $userId)->update(['lang' => $lang]);
    }

    /**
     * GET che cattura l'echo di cbView().
     *
     * @return array{0:\Illuminate\Testing\TestResponse,1:string}
     */
    private function getPage(string $path): array
    {
        ob_start();
        try {
            $response = $this->get('http://localhost/admin/chat_ai/' . $path);
        } finally {
            $echoed = (string) ob_get_clean();
        }

        return [$response, $echoed];
    }

    // ---------------------------------------------------------------
    // 1. access / tenant come superadmin
    // ---------------------------------------------------------------

    public function test_access_mostra_solo_i_gruppi_della_conf_richiesta(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $otherConf = $this->seedConf();
        $mine = $this->seedNamedGroup('ZzGruppoMio');
        $theirs = $this->seedNamedGroup('ZzGruppoAltrui');
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $conf, 'group_id' => $mine]);
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $otherConf, 'group_id' => $theirs]);

        [$response, $html] = $this->getPage('access/' . $conf);

        $response->assertStatus(200);
        $this->assertStringContainsString('ZzGruppoMio', $html);
        $this->assertStringNotContainsString('ZzGruppoAltrui', $html);
        $this->assertStringContainsString('/admin/chat_ai/' . $conf . '/auth', $html);
        $this->assertStringContainsString('/admin/chat_ai/' . $conf . '/deauth/' . $mine, $html);
    }

    public function test_tenant_mostra_solo_i_tenant_della_conf_richiesta(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $otherConf = $this->seedConf();
        $mine = $this->seedNamedTenant('ZzTenantMio');
        $theirs = $this->seedNamedTenant('ZzTenantAltrui');
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $conf, 'tenant_id' => $mine]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $otherConf, 'tenant_id' => $theirs]);

        [$response, $html] = $this->getPage('tenant/' . $conf);

        $response->assertStatus(200);
        $this->assertStringContainsString('ZzTenantMio', $html);
        $this->assertStringNotContainsString('ZzTenantAltrui', $html);
        $this->assertStringContainsString('/admin/chat_ai/' . $conf . '/add_tenant', $html);
        $this->assertStringContainsString('/admin/chat_ai/' . $conf . '/remove_tenant/' . $mine, $html);
    }

    public function test_alert_presente_solo_con_alert_1(): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], 'en');
        $conf = $this->seedConf();

        foreach (['access', 'tenant'] as $page) {
            [, $without] = $this->getPage($page . '/' . $conf);
            [$response, $with] = $this->getPage($page . '/' . $conf . '/alert/1');
            [, $other] = $this->getPage($page . '/' . $conf . '/alert/2');

            $response->assertStatus(200);
            $this->assertStringNotContainsString('Select an element to add...', $without, $page);
            $this->assertStringContainsString('Select an element to add...', $with, $page);
            $this->assertStringContainsString('bi-exclamation-triangle-fill', $with, $page);
            $this->assertStringNotContainsString('Select an element to add...', $other, $page);
        }
    }

    // ---------------------------------------------------------------
    // 2. non superadmin
    // ---------------------------------------------------------------

    public function test_un_non_superadmin_riceve_denied_access_su_access_e_tenant(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['chat_ai']);
        $conf = $this->seedConf();
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $conf, 'tenant_id' => $tenantId]);

        foreach (['access', 'tenant'] as $page) {
            [$response, $html] = $this->getPage($page . '/' . $conf);

            $response->assertRedirect();
            $response->assertSessionHas('message', trans('crudbooster.denied_access'));
            $this->assertStringNotContainsString('Authorize', $html, $page);
        }
    }

    // ---------------------------------------------------------------
    // 3. conf inesistente (comportamento di oggi: pagina con elenco vuoto)
    // ---------------------------------------------------------------

    public function test_conf_inesistente_da_superadmin_rende_la_pagina_con_elenco_vuoto(): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], 'en');
        $someGroup = $this->seedNamedGroup('ZzGruppoQualunque');
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $this->seedConf(), 'group_id' => $someGroup]);

        foreach (['access', 'tenant'] as $page) {
            [$response, $html] = $this->getPage($page . '/999999');

            $response->assertStatus(200);
            $this->assertStringContainsString('Authorize', $html, $page);
            $this->assertStringNotContainsString('ZzGruppoQualunque', $html, $page);
        }
    }

    // ---------------------------------------------------------------
    // 4. soft-deleted (fissato com'e': NESSUN filtro su deleted_at)
    // ---------------------------------------------------------------

    /**
     * Caratterizzazione di un comportamento PROBABILMENTE SCORRETTO, fissato
     * com'e' e non corretto (follow-up, piano 332): un gruppo o tenant
     * cancellato (soft delete) compare ancora nell'elenco degli autorizzati,
     * perche' la query e' un DB::table() con join senza filtro su deleted_at.
     * Quando si correggera', questo test va rovesciato.
     */
    public function test_un_gruppo_o_tenant_soft_deleted_compare_comunque_nell_elenco_comportamento_da_correggere(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $group = $this->seedNamedGroup('ZzGruppoCancellato', ['deleted_at' => now()]);
        $tenant = $this->seedNamedTenant('ZzTenantCancellato', ['deleted_at' => now()]);
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $conf, 'group_id' => $group]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $conf, 'tenant_id' => $tenant]);

        [, $groupsHtml] = $this->getPage('access/' . $conf);
        [, $tenantsHtml] = $this->getPage('tenant/' . $conf);

        // La query e' DB::table() con join, senza deleted_at: compaiono (comportamento da correggere).
        $this->assertStringContainsString('ZzGruppoCancellato', $groupsHtml);
        $this->assertStringContainsString('ZzTenantCancellato', $tenantsHtml);
    }

    // ---------------------------------------------------------------
    // 5. Testi: confrontati con trans('crudbooster.*') per en (identico
    //    all'inglese di prima) e it (tradotto).
    // ---------------------------------------------------------------

    /** @return array<string,array{0:string}> */
    public static function localeProvider(): array
    {
        return ['en' => ['en'], 'it' => ['it']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('localeProvider')]
    public function test_testi_di_access_seguono_la_lingua_dell_utente(string $lang): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], $lang);
        $conf = $this->seedConf();
        $group = $this->seedNamedGroup('ZzGruppo');
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $conf, 'group_id' => $group]);
        $t = fn (string $key) => trans('crudbooster.' . $key, [], $lang);

        [, $html] = $this->getPage('access/' . $conf . '/alert/1');
        [, $withReturn] = $this->getPage('access/' . $conf . '?return_url=/admin/chat_ai');
        [, $noReturn] = $this->getPage('access/' . $conf);

        $this->assertStringContainsString($t('chatai_authorize_group'), $html);
        $this->assertStringContainsString($t('qlik_warning'), $html);
        $this->assertStringContainsString($t('chatai_select_element_to_add'), $html);
        $this->assertStringContainsString($t('chatai_group_description_placeholder'), $html);
        $this->assertStringContainsString("title='" . $t('adm_remove') . "'", $html);
        $this->assertStringContainsString("title='" . $t('chatai_tooltip_return') . "'", $withReturn);
        $this->assertStringContainsString("title='" . $t('chatai_tooltip_main_module') . "'", $noReturn);
    }

    public function test_testi_inglesi_di_access_restano_quelli_di_prima(): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], 'en');
        $conf = $this->seedConf();

        [, $html] = $this->getPage('access/' . $conf . '/alert/1');

        $this->assertStringContainsString('Authorize Group', $html);
        $this->assertStringContainsString('<h4><i class="icon bi bi-exclamation-triangle-fill"></i> Warning!</h4>Select an element to add...', $html);
        $this->assertStringContainsString('Group description', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('localeProvider')]
    public function test_testi_di_tenant_seguono_la_lingua_dell_utente(string $lang): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], $lang);
        $conf = $this->seedConf();
        $tenant = $this->seedNamedTenant('ZzTenant');
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $conf, 'tenant_id' => $tenant]);
        $t = fn (string $key) => trans('crudbooster.' . $key, [], $lang);

        [, $html] = $this->getPage('tenant/' . $conf . '/alert/1');
        [, $withReturn] = $this->getPage('tenant/' . $conf . '?return_url=/admin/chat_ai');
        [, $noReturn] = $this->getPage('tenant/' . $conf);

        $this->assertStringContainsString($t('chatai_authorize_tenant'), $html);
        $this->assertStringContainsString($t('qlik_warning'), $html);
        $this->assertStringContainsString($t('chatai_select_element_to_add'), $html);
        $this->assertStringContainsString($t('chatai_tenant_description_placeholder'), $html);
        $this->assertStringContainsString($t('chatai_authorized_tenants'), $html);
        $this->assertStringContainsString("title='" . $t('adm_remove') . "'", $html);
        $this->assertStringContainsString("title='" . $t('chatai_tooltip_return') . "'", $withReturn);
        $this->assertStringContainsString("title='" . $t('chatai_tooltip_main_module') . "'", $noReturn);
    }

    public function test_testi_inglesi_di_tenant_restano_quelli_di_prima(): void
    {
        $actor = $this->actingAsSuperadmin();
        $this->setUserLang($actor['userId'], 'en');
        $conf = $this->seedConf();

        [, $html] = $this->getPage('tenant/' . $conf . '/alert/1');

        $this->assertStringContainsString('Authorize Tenant', $html);
        $this->assertStringContainsString('<h4><i class="icon bi bi-exclamation-triangle-fill"></i> Warning!</h4>Select an element to add...', $html);
        $this->assertStringContainsString('Tenant description', $html);
        $this->assertStringContainsString('Authorized Tenants', $html);
    }

    // ---------------------------------------------------------------
    // 6. content_view
    // ---------------------------------------------------------------

    /**
     * Esegue content_view con ?m= e restituisce [response, vista scelta, riga passata alla vista].
     * content_view legge ?m= dalla query della richiesta, valorizzata dall'URL.
     * $_GET['m'] viene popolato lo stesso, per compatibilita' con il codice che lo
     * leggeva direttamente (il client di test di Laravel non lo fa); oggi non serve.
     *
     * @return array{0:\Illuminate\Testing\TestResponse,1:?string,2:?object}
     */
    private function runContentView(int|string $confId, ?string $m): array
    {
        $chosen = null;
        $row = null;
        foreach (['chat_ai.view', 'chat_ai.fullscreen_view'] as $name) {
            View::composer($name, function ($view) use (&$chosen, &$row, $name) {
                $chosen = $name;
                $row = $view->getData()['row'] ?? null;
            });
        }

        $url = 'http://localhost/admin/chat_ai/content/' . $confId;
        $previousGet = $_GET;
        if ($m !== null) {
            $url .= '?m=' . $m;
            $_GET['m'] = $m;
        }

        ob_start();
        try {
            $response = $this->get($url);
        } finally {
            ob_get_clean();
            $_GET = $previousGet;
        }

        return [$response, $chosen, $row];
    }

    public function test_content_view_senza_m_usa_100_per_cento_e_la_vista_normale(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf(['title' => 'Agente Vista']);

        [$response, $chosen, $row] = $this->runContentView($conf, null);

        $response->assertStatus(200);
        $this->assertSame('chat_ai.view', $chosen);
        $this->assertSame('100%', $row->frame_width);
        $this->assertSame('100%', $row->frame_height);
        $this->assertSame('', $row->target_layout);
    }

    public function test_content_view_con_m_vuoto_o_inesistente_usa_100_per_cento(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();

        foreach (['', '999999'] as $m) {
            [$response, $chosen, $row] = $this->runContentView($conf, $m);

            $response->assertStatus(200);
            $this->assertSame('chat_ai.view', $chosen, "m='$m'");
            $this->assertSame('100%', $row->frame_width, "m='$m'");
            $this->assertSame('100%', $row->frame_height, "m='$m'");
        }
    }

    public function test_content_view_con_m_valido_applica_le_dimensioni_del_menu(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $menu = $this->seedMenu(['frame_width' => '640px', 'frame_height' => '480px', 'target_layout' => 0]);

        [$response, $chosen, $row] = $this->runContentView($conf, (string) $menu['id']);

        $response->assertStatus(200);
        $this->assertSame('chat_ai.view', $chosen);
        $this->assertSame('640px', $row->frame_width);
        $this->assertSame('480px', $row->frame_height);
        $this->assertEquals(0, $row->target_layout);
    }

    public function test_content_view_con_menu_target_layout_1_usa_la_vista_fullscreen(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $menu = $this->seedMenu(['frame_width' => '90%', 'frame_height' => '70%', 'target_layout' => 1]);

        [$response, $chosen, $row] = $this->runContentView($conf, (string) $menu['id']);

        $response->assertStatus(200);
        $this->assertSame('chat_ai.fullscreen_view', $chosen);
        $this->assertSame('90%', $row->frame_width);
    }

    public function test_content_view_senza_m_ripiega_sulla_dashboard_attiva(): void
    {
        $this->actingAsSuperadmin();
        $conf = $this->seedConf();
        $this->seedMenu(['name' => 'Dashboard', 'is_dashboard' => 1, 'frame_width' => '55%', 'frame_height' => '45%']);

        [$response, $chosen, $row] = $this->runContentView($conf, null);

        $response->assertStatus(200);
        $this->assertSame('chat_ai.view', $chosen);
        $this->assertSame('55%', $row->frame_width);
        $this->assertSame('45%', $row->frame_height);
    }

    public function test_content_view_conf_non_visibile_rimanda_con_denied_access(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['chat_ai']);
        $conf = $this->seedConf();
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $conf, 'tenant_id' => $this->seedTenant()]);

        [$response, $chosen] = $this->runContentView($conf, null);

        $response->assertRedirect();
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertNull($chosen);
    }

    public function test_content_view_conf_inesistente_da_superadmin_rimanda_con_missing_item(): void
    {
        $this->actingAsSuperadmin();

        [$response, $chosen] = $this->runContentView(999999, null);

        $response->assertRedirect();
        $response->assertSessionHas('message', trans('crudbooster.missing_item'));
        $this->assertNull($chosen);
    }
}
