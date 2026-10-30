<?php

namespace Tests\Feature;

use App\AccessLog;
use App\ChatAIConf;
use App\Log;
use App\Menu;
use App\QlikItem;
use App\QlikSyncRun;
use App\QlikSyncRunRecord;
use App\Services\QlikSync\QlikSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 339 (batch B5): spostamento in App\Models degli ultimi modelli di
 * sistema (Menu, ChatAIConf, QlikItem, QlikSyncRun, QlikSyncRunRecord, Log,
 * AccessLog), con alias sui vecchi nomi.
 *
 * Il test sull'alias verifica che i vecchi nomi (App\Menu, ...) siano alias dei
 * modelli in App\Models; gli altri fotografano il comportamento e usano apposta
 * i vecchi nomi, cosi' passano dall'alias come il codice custom dei clienti.
 *
 * Gia' coperti altrove (non duplicati): scope/relazioni di Menu da
 * ModelScopesTest/ModelRelationsTest, QlikItem::allowed*() e accesso pubblico
 * da PivotModelMoveTest/QlikItemPublicAccessTest, ChatAIConf::allowed*() da
 * PivotModelMoveTest, scrittura di cms_logs da ApiTokensAddSaveTest (senza
 * passare dal modello AccessLog: qui solo il modello). Lacuna dichiarata:
 * QlikSyncService::step()/upsert* richiedono un driver Qlik (rete), non
 * testati; si esercitano requestCancel()/markFailed() e il rollback, che
 * usano QlikSyncRun, QlikSyncRunRecord e Menu senza rete.
 */
class RemainingModelMoveTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    /** @return array<string, array{0: string}> */
    public static function modelNames(): array
    {
        return [
            'Menu' => ['Menu'],
            'ChatAIConf' => ['ChatAIConf'],
            'QlikItem' => ['QlikItem'],
            'QlikSyncRun' => ['QlikSyncRun'],
            'QlikSyncRunRecord' => ['QlikSyncRunRecord'],
            'Log' => ['Log'],
            'AccessLog' => ['AccessLog'],
        ];
    }

    /** @return array<string, array{0: string, 1: string, 2: bool}> nome => [nome, tabella, timestamps] */
    public static function tables(): array
    {
        return [
            'Menu' => ['Menu', 'cms_menus', true],
            'ChatAIConf' => ['ChatAIConf', 'chatai_confs', false],
            'QlikItem' => ['QlikItem', 'qlik_items', false],
            'QlikSyncRun' => ['QlikSyncRun', 'qlik_sync_runs', true],
            'QlikSyncRunRecord' => ['QlikSyncRunRecord', 'qlik_sync_run_records', true],
            'Log' => ['Log', 'log', false],
            'AccessLog' => ['AccessLog', 'cms_logs', false],
        ];
    }

    #[DataProvider('modelNames')]
    public function test_il_vecchio_nome_e_alias_del_modello_in_app_models(string $name): void
    {
        $this->assertTrue(class_exists('App\\' . $name));
        $this->assertTrue(class_exists('App\\Models\\' . $name));
        $this->assertSame('App\\Models\\' . $name, (new ReflectionClass('App\\' . $name))->getName());
        $this->assertInstanceOf('App\\Models\\' . $name, new ('App\\' . $name));
    }

    #[DataProvider('tables')]
    public function test_tabella_e_timestamp_restano_invariati(string $name, string $table, bool $timestamps): void
    {
        $model = new ('App\\' . $name);

        $this->assertSame($table, $model->getTable());
        $this->assertSame($timestamps, $model->usesTimestamps());
    }

    public function test_soft_delete_su_chatai_conf_e_qlik_item(): void
    {
        $insert = fn (string $table, ?string $deletedAt) => DB::table($table)->insertGetId([
            'title' => 'Riga',
            'created_at' => now(),
            'deleted_at' => $deletedAt,
        ]);

        foreach ([ChatAIConf::class => 'chatai_confs', QlikItem::class => 'qlik_items'] as $class => $table) {
            $liveId = $insert($table, null);
            $deletedId = $insert($table, now()->toDateTimeString());

            // positivo: la riga viva e' visibile
            $this->assertNotNull($class::find($liveId), $class);
            // negativo, stesso setup: la soft-deleted e' esclusa dal global scope...
            $this->assertNull($class::find($deletedId), $class);
            // ...ma torna con withTrashed()
            $this->assertNotNull($class::withTrashed()->find($deletedId), $class);
        }
    }

    public function test_menu_e_qlik_sync_non_usano_il_soft_delete(): void
    {
        foreach ([Menu::class, QlikSyncRun::class, QlikSyncRunRecord::class] as $class) {
            $this->assertNotContains(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($class),
                $class
            );
        }
    }

    // ---------------------------------------------------------------
    // QlikSyncRun / QlikSyncRunRecord
    // ---------------------------------------------------------------

    private function seedRun(array $overrides = []): QlikSyncRun
    {
        return QlikSyncRun::create(array_merge([
            'type' => QlikSyncRun::TYPE_ITEMS,
            'qlik_conf_id' => 1,
            'user_id' => 1,
            'tenant_id' => 5,
            'group_id' => 6,
            'status' => QlikSyncRun::STATUS_QUEUED,
        ], $overrides));
    }

    public function test_costanti_dei_run_e_dei_record_hanno_i_valori_attuali(): void
    {
        $this->assertSame('apps', QlikSyncRun::TYPE_APPS);
        $this->assertSame('items', QlikSyncRun::TYPE_ITEMS);
        $this->assertSame('queued', QlikSyncRun::STATUS_QUEUED);
        $this->assertSame('running', QlikSyncRun::STATUS_RUNNING);
        $this->assertSame('cancelling', QlikSyncRun::STATUS_CANCELLING);
        $this->assertSame('cancelled', QlikSyncRun::STATUS_CANCELLED);
        $this->assertSame('completed', QlikSyncRun::STATUS_COMPLETED);
        $this->assertSame('failed', QlikSyncRun::STATUS_FAILED);
        $this->assertSame('created', QlikSyncRunRecord::ACTION_CREATED);
        $this->assertSame('linked', QlikSyncRunRecord::ACTION_LINKED);
        $this->assertSame('updated', QlikSyncRunRecord::ACTION_UPDATED);
        $this->assertSame('skipped', QlikSyncRunRecord::ACTION_SKIPPED);
        $this->assertSame('failed', QlikSyncRunRecord::ACTION_FAILED);
    }

    public function test_active_statuses_contiene_solo_gli_stati_non_conclusi(): void
    {
        $this->assertSame(['queued', 'running', 'cancelling'], QlikSyncRun::activeStatuses());

        foreach (['queued', 'running', 'cancelling'] as $status) {
            $this->assertTrue($this->seedRun(['status' => $status])->isActive(), $status);
        }
        foreach (['cancelled', 'completed', 'failed'] as $status) {
            $this->assertFalse($this->seedRun(['status' => $status])->isActive(), $status);
        }
    }

    public function test_run_e_record_si_creano_e_si_rileggono_con_i_cast(): void
    {
        $run = $this->seedRun([
            'cancel_requested' => 1,
            'selected_app_ids' => ['a1', 'a2'],
            'selected_sheet_ids' => ['s1'],
        ]);
        $record = QlikSyncRunRecord::create([
            'run_id' => $run->id,
            'record_type' => 'item',
            'record_id' => 42,
            'action' => QlikSyncRunRecord::ACTION_CREATED,
        ]);

        $reloaded = QlikSyncRun::find($run->id);
        $this->assertTrue($reloaded->cancel_requested);
        $this->assertFalse($reloaded->cancel_delete);
        $this->assertSame(['a1', 'a2'], $reloaded->selected_app_ids);
        $this->assertSame(['s1'], $reloaded->selected_sheet_ids);
        $this->assertSame(42, (int) QlikSyncRunRecord::find($record->id)->record_id);
    }

    public function test_records_restituisce_solo_le_righe_del_proprio_run(): void
    {
        $runA = $this->seedRun();
        $runB = $this->seedRun();
        $mine = QlikSyncRunRecord::create(['run_id' => $runA->id, 'record_type' => 'app', 'action' => 'created']);
        $other = QlikSyncRunRecord::create(['run_id' => $runB->id, 'record_type' => 'app', 'action' => 'created']);

        $this->assertSame([$mine->id], $runA->records()->pluck('id')->all());
        $this->assertSame([$other->id], $runB->records()->pluck('id')->all());
        $this->assertInstanceOf(QlikSyncRunRecord::class, $runA->records()->first());
    }

    // ---------------------------------------------------------------
    // QlikSyncService / QlikSyncRollback (percorsi senza rete)
    // ---------------------------------------------------------------

    public function test_request_cancel_su_run_in_coda_con_eliminazione_applica_il_rollback(): void
    {
        $deletableItem = DB::table('qlik_items')->insertGetId(['title' => 'Da eliminare', 'created_at' => now()]);
        $keptItem = DB::table('qlik_items')->insertGetId(['title' => 'In menu', 'created_at' => now()]);
        $this->seedMenu(['path' => 'qlik_items/content/' . $keptItem]);

        $run = $this->seedRun(['tenant_id' => 5, 'group_id' => 6]);
        foreach ([$deletableItem, $keptItem] as $itemId) {
            QlikSyncRunRecord::create([
                'run_id' => $run->id,
                'record_type' => 'item',
                'record_id' => $itemId,
                'action' => QlikSyncRunRecord::ACTION_CREATED,
            ]);
        }

        QlikSyncService::requestCancel($run, true);

        $reloaded = QlikSyncRun::find($run->id);
        $this->assertSame(QlikSyncRun::STATUS_CANCELLED, $reloaded->status);
        $this->assertNotNull($reloaded->finished_at);
        $this->assertNotNull($reloaded->rolled_back_at);
        // positivo: l'item creato dal run e non usato e' eliminato
        $this->assertNull(DB::table('qlik_items')->where('id', $deletableItem)->first());
        // negativo, stesso setup: quello con voce di menu (Menu::qlikItemContent) resta
        $this->assertNotNull(DB::table('qlik_items')->where('id', $keptItem)->first());
        $this->assertNotNull(
            QlikSyncRunRecord::where('run_id', $run->id)->where('record_id', $keptItem)->value('message')
        );
        $this->assertNull(
            QlikSyncRunRecord::where('run_id', $run->id)->where('record_id', $deletableItem)->value('message')
        );
    }

    public function test_request_cancel_senza_eliminazione_non_tocca_i_record(): void
    {
        $itemId = DB::table('qlik_items')->insertGetId(['title' => 'Resta', 'created_at' => now()]);
        $run = $this->seedRun();
        QlikSyncRunRecord::create(['run_id' => $run->id, 'record_type' => 'item', 'record_id' => $itemId, 'action' => 'created']);

        QlikSyncService::requestCancel($run, false);

        $reloaded = QlikSyncRun::find($run->id);
        $this->assertSame(QlikSyncRun::STATUS_CANCELLED, $reloaded->status);
        $this->assertNull($reloaded->rolled_back_at);
        $this->assertNotNull(DB::table('qlik_items')->where('id', $itemId)->first());
    }

    public function test_request_cancel_su_run_in_corso_passa_a_cancelling_e_su_run_concluso_non_fa_nulla(): void
    {
        $running = $this->seedRun(['status' => QlikSyncRun::STATUS_RUNNING]);
        $completed = $this->seedRun(['status' => QlikSyncRun::STATUS_COMPLETED]);

        QlikSyncService::requestCancel($running, true);
        QlikSyncService::requestCancel($completed, true);

        $this->assertSame(QlikSyncRun::STATUS_CANCELLING, QlikSyncRun::find($running->id)->status);
        $this->assertTrue(QlikSyncRun::find($running->id)->cancel_requested);
        $this->assertSame(QlikSyncRun::STATUS_COMPLETED, QlikSyncRun::find($completed->id)->status);
        $this->assertFalse(QlikSyncRun::find($completed->id)->cancel_requested);
    }

    public function test_mark_failed_segna_solo_i_run_attivi(): void
    {
        $active = $this->seedRun(['status' => QlikSyncRun::STATUS_RUNNING]);
        $done = $this->seedRun(['status' => QlikSyncRun::STATUS_COMPLETED]);

        QlikSyncService::markFailed($active->id, 'timeout');
        QlikSyncService::markFailed($done->id, 'timeout');

        $this->assertSame(QlikSyncRun::STATUS_FAILED, QlikSyncRun::find($active->id)->status);
        $this->assertSame('timeout', QlikSyncRun::find($active->id)->error);
        $this->assertSame(QlikSyncRun::STATUS_COMPLETED, QlikSyncRun::find($done->id)->status);
    }

    // ---------------------------------------------------------------
    // Log / AccessLog
    // ---------------------------------------------------------------

    public function test_add_log_ch_inserisce_una_riga_in_log_con_utente_e_url(): void
    {
        $actor = $this->actingAsSuperadmin();
        $before = Log::count();

        $id = add_log_ch('Categoria', 'Descrizione di prova', 'Warning');

        $this->assertSame($before + 1, Log::count());
        $row = DB::table('log')->where('id', $id)->first();
        $this->assertSame('Categoria', $row->category);
        $this->assertSame('Warning', $row->type);
        $this->assertSame('Descrizione di prova', $row->description);
        $this->assertSame($actor['userId'], (int) $row->created_by);
        $this->assertNotNull($row->url);
    }

    public function test_add_log_ch_tronca_la_descrizione_a_65000_caratteri(): void
    {
        $this->actingAsSuperadmin();

        $id = add_log_ch('Cat', str_repeat('a', 70000));

        $this->assertSame(65000, strlen((string) DB::table('log')->where('id', $id)->value('description')));
        $this->assertSame('log', DB::table('log')->where('id', $id)->value('type'));
    }

    public function test_access_log_conta_le_righe_di_cms_logs(): void
    {
        $before = AccessLog::count();
        DB::table('cms_logs')->insert(['description' => 'Login', 'created_at' => now()]);

        $this->assertSame($before + 1, AccessLog::count());
        $this->assertSame(0, AccessLog::where('description', 'inesistente')->count());
    }

    // ---------------------------------------------------------------
    // Menu inline in CBController (target_layout)
    // ---------------------------------------------------------------

    /** @return array<string, array{0: string}> */
    public static function cbControllerPages(): array
    {
        return [
            'index' => ['index'],
            'add' => ['add'],
            'edit' => ['edit'],
            'detail' => ['detail'],
        ];
    }

    #[DataProvider('cbControllerPages')]
    public function test_cb_controller_applica_il_target_layout_del_menu_indicato_da_m(string $page): void
    {
        $this->actingAsSuperadmin();
        $groupId = $this->seedGroup();
        $menu = $this->seedMenu(['target_layout' => 1]);

        $viewName = $page === 'index' ? 'crudbooster::default.index' : 'crudbooster::default.form';
        $captured = null;
        View::composer($viewName, function ($view) use (&$captured) {
            $captured = $view->getData()['target_layout'] ?? '(assente)';
        });

        $path = match ($page) {
            'index' => 'http://localhost/admin/groups',
            'add' => 'http://localhost/admin/groups/add',
            'edit' => "http://localhost/admin/groups/edit/{$groupId}",
            'detail' => "http://localhost/admin/groups/detail/{$groupId}",
        };

        // positivo: m = menu esistente -> il suo target_layout
        ob_start();
        try {
            $this->get($path . '?m=' . $menu['id']);
        } finally {
            ob_get_clean();
        }
        $this->assertEquals(1, $captured, $page);

        // negativo, stesso setup: m inesistente -> 'default'
        $captured = null;
        ob_start();
        try {
            $this->get($path . '?m=999999');
        } finally {
            ob_get_clean();
        }
        $this->assertSame('default', $captured, $page);
    }

    // ---------------------------------------------------------------
    // Blade con QlikItem inline
    // ---------------------------------------------------------------

    /** @return array<string, array{0: string}> */
    public static function formBodies(): array
    {
        return [
            'qlik_items' => ['qlik_items.form_body'],
            'chat_ai' => ['chat_ai.form_body'],
        ];
    }

    #[DataProvider('formBodies')]
    public function test_form_body_con_item_pubblico_costruisce_il_link_tramite_qlik_item(string $view): void
    {
        $this->actingAsSuperadmin();
        $itemId = DB::table('qlik_items')->insertGetId(['title' => 'Item Pubblico', 'created_at' => now()]);
        $item = QlikItem::find($itemId);
        $item->enablePublicAccess();

        $html = view($view, [
            'forms' => [['name' => 'public_access', 'label' => 'Public', 'type' => 'text']],
            'row' => (object) ['id' => $itemId, 'public_access' => ''],
            'is_public' => true,
            'parent_field' => null,
            'parent_id' => null,
            'command' => 'edit',
            // le view renderizzate fuori dal middleware web non hanno $errors
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertIsString($html);
        // il vecchio comportamento: nessuna eccezione e item risolto per id
        $this->assertTrue(QlikItem::find($itemId)->isPublic());
    }
}
