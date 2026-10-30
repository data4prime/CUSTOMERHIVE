<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione di export/import dei moduli del Module Generator
 * (ModulsController::getExport()/postImport(), che delegano a
 * App\Services\ModuleGenerator\ModuleExporter/ModuleImporter; piano
 * docs/refactoring/piani/335-module-generator-import-export-service.md).
 * Fissano il comportamento che il modulo aveva prima dell'estrazione nei
 * Service, quindi devono restare verdi a ogni modifica di questi flussi.
 *
 * ATTENZIONE - DDL e transazioni: l'import fa Schema::create() (e i test
 * creano/droppano tabelle). In MySQL il DDL fa commit implicito, quindi il
 * rollback di RefreshDatabase NON ripulisce piu' nulla di cio' che e' stato
 * scritto prima/dopo. Il tearDown() quindi: cancella i controller generati,
 * droppa le tabelle mg_phpunit_*, e cancella a mano le righe aggiunte dal test
 * (tutto cio' che ha id > dello snapshot preso in setUp()) nelle tabelle
 * seminate dai trait. Come ModuleGeneratorCrudTest, i file finiscono nell'albero
 * sorgente vero (app/Http/Controllers): i nomi dei moduli contengono sempre
 * "Phpunit Test" cosi' lo sweep *PhpunitTest*.php li prende.
 *
 * I messaggi sono confrontati con le frasi italiane LETTERALI che il codice
 * mostrava prima di passare a trans() (self::MESSAGES): l'utente di test ha
 * lang 'it', e il testo it delle chiavi nuove e' identico a quelle frasi.
 */
class ModuleExportImportTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const BASE = 'http://localhost/admin/module_generator';

    private const IMPORT_PAGE = 'http://localhost/admin/module_generator/import';

    /**
     * Chiavi dei messaggi di export/import (resources/lang crudbooster.php, en e
     * it) => frase italiana originale, con placeholder Laravel (:name, :table).
     * Il testo it delle chiavi deve restare identico a quello qui.
     *
     * crudbooster.module_export_not_exportable
     * crudbooster.module_export_controller_missing
     * crudbooster.module_import_invalid_file
     * crudbooster.module_import_bad_extension
     * crudbooster.module_import_invalid_json
     * crudbooster.module_import_name_exists        (:name)
     * crudbooster.module_import_reserved_table
     * crudbooster.module_import_table_used         (:table)
     * crudbooster.module_import_no_columns
     * crudbooster.module_import_success            (:name)
     */
    private const MESSAGES = [
        'module_export_not_exportable' => "Questo modulo non e' esportabile",
        'module_export_controller_missing' => 'Controller del modulo non trovato sul disco',
        'module_import_invalid_file' => 'Seleziona un file JSON valido',
        'module_import_bad_extension' => 'Il file deve avere estensione .json',
        'module_import_invalid_json' => 'File JSON non valido o incompleto',
        'module_import_name_exists' => "Esiste gia' un modulo chiamato \":name\"",
        'module_import_reserved_table' => 'Nome tabella riservato, non importabile',
        'module_import_table_used' => "La tabella \":table\" e' gia' usata da un altro modulo",
        'module_import_no_columns' => 'Il file JSON non contiene colonne per creare la tabella',
        'module_import_success' => 'Modulo ":name" importato con successo',
    ];

    /** Tabelle seminate dai trait/test, in ordine sicuro di cancellazione. */
    private const SNAPSHOT_TABLES = [
        'cms_privileges_roles', 'cms_logs', 'cms_menus', 'cms_moduls',
        'cms_users', 'cms_privileges', 'groups', 'tenants',
    ];

    private array $fixtureFiles = [];

    private array $tables = [];

    private array $idSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::SNAPSHOT_TABLES as $table) {
            $this->idSnapshot[$table] = (int) DB::table($table)->max('id');
        }

        $this->registerAdminModules();
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->fixtureFiles as $path) {
                @unlink($path);
            }
            foreach ($this->controllerFiles() as $path) {
                @unlink($path);
            }
            foreach (glob(base_path('app/Http/Controllers/*PhpunitTest*.php')) ?: [] as $path) {
                @unlink($path);
            }

            $this->dropTestTables();
            $this->deleteRowsAddedSinceSetUp();
        } finally {
            parent::tearDown();
        }
    }

    // ---------------------------------------------------------------
    // Helper di setup/cleanup
    // ---------------------------------------------------------------

    private function controllerFiles(): array
    {
        return glob(\App\Helpers\CRUDBooster::customControllersPath() . '/*PhpunitTest*.php') ?: [];
    }

    private function dropTestTables(): void
    {
        $found = [];
        foreach (DB::select("SHOW TABLES LIKE 'mg\\_phpunit\\_%'") as $row) {
            $found[] = array_values((array) $row)[0];
        }
        foreach (array_unique(array_merge($this->tables, $found)) as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function deleteRowsAddedSinceSetUp(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::SNAPSHOT_TABLES as $table) {
            DB::table($table)->where('id', '>', $this->idSnapshot[$table] ?? 0)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function unique(): string
    {
        return substr(md5(uniqid('', true)), 0, 8);
    }

    /**
     * Nome modulo (con "Phpunit Test" per lo sweep) e nome tabella univoci:
     * i nomi vanno resi unici per test perche' la classe del controller
     * generato resta caricata nel processo (class_exists nell'export).
     *
     * @return array{0:string,1:string}
     */
    private function newNames(string $tag = 'Imp'): array
    {
        $u = $this->unique();
        $table = 'mg_phpunit_' . $u;
        $this->tables[] = $table;

        return ['Phpunit Test ' . $tag . ' ' . $u, $table];
    }

    private function actAsSuperadmin(): void
    {
        $ctx = $this->actingAsSuperadmin();
        $this->setUserLangIt($ctx['userId']);
    }

    private function actAsTenantAdmin(): void
    {
        $ctx = $this->actingAsTenantUser($this->seedTenant(), isTenantadmin: true, visibleModulePaths: ['module_generator']);
        $this->setUserLangIt($ctx['userId']);
    }

    private function setUserLangIt(int $userId): void
    {
        // SetUserPreferredLanguage imposta il locale dalla lingua dell'utente:
        // la fisso a 'it' perche' i messaggi di oggi sono italiani.
        if (Schema::hasColumn('cms_users', 'lang')) {
            DB::table('cms_users')->where('id', $userId)->update(['lang' => 'it']);
        }
    }

    /** Frase italiana di riferimento (vedi self::MESSAGES) con i placeholder sostituiti. */
    private function msg(string $key, array $params = []): string
    {
        $replace = [];
        foreach ($params as $k => $v) {
            $replace[':' . $k] = $v;
        }

        return strtr(self::MESSAGES[$key], $replace);
    }

    private function basePayload(string $name, string $table, ?array $columns = null): array
    {
        return [
            'format_version' => 1,
            'module' => ['name' => $name, 'icon' => 'bi bi-box'],
            'table' => [
                'name' => $table,
                'columns' => $columns ?? [['name' => 'nome', 'type' => 'text', 'size' => 50]],
            ],
        ];
    }

    private function postImportJson(array|string $payload, string $filename = 'modulo.json')
    {
        $contents = is_array($payload) ? json_encode($payload) : $payload;
        $file = UploadedFile::fake()->createWithContent($filename, $contents);

        return $this->from(self::IMPORT_PAGE)->post(self::BASE . '/import', ['json_file' => $file]);
    }

    /** Import che deve riuscire: ritorna riga cms_moduls e contenuto del controller generato. */
    private function importOk(array $payload): array
    {
        $response = $this->postImportJson($payload);
        $response->assertRedirect(url('admin/module_generator'));
        $response->assertSessionHas('message_type', 'success');
        $response->assertSessionHas('message', $this->msg('module_import_success', ['name' => $payload['module']['name']]));

        $row = DB::table('cms_moduls')->where('name', $payload['module']['name'])->first();
        $this->assertNotNull($row, 'Import riuscito ma nessuna riga in cms_moduls.');
        $path = \App\Helpers\CRUDBooster::customControllersPath() . '/' . $row->controller . '.php';
        $this->fixtureFiles[] = $path;
        $this->assertFileExists($path);

        return [$row, file_get_contents($path)];
    }

    /** @return array{moduls:int,files:int} */
    private function stateSnapshot(): array
    {
        return ['moduls' => DB::table('cms_moduls')->count(), 'files' => count($this->controllerFiles())];
    }

    private function assertImportRejected($response, string $message, string $type, array $before, string $table): void
    {
        $response->assertRedirect(self::IMPORT_PAGE);
        $response->assertSessionHas('message', $message);
        $response->assertSessionHas('message_type', $type);
        $this->assertSame($before, $this->stateSnapshot(), 'Rifiuto: sono comparsi righe cms_moduls o file controller.');
        $this->assertFalse(Schema::hasTable($table), 'Rifiuto: la tabella e\' stata creata.');
    }

    private function fullLayout(): array
    {
        return ['v' => 2, 'tabs' => [['id' => 't1', 'title' => 'Dati', 'blocks' => [
            ['id' => 'b1', 'title' => 'Principale', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 4, 'fields' => [
                ['name' => 'nome', 'w' => 6],
                ['name' => 'note_brevi', 'w' => 6],
            ]],
        ]]]];
    }

    private function fullCol(): array
    {
        return [
            ['label' => 'Nome', 'name' => 'nome', 'width' => '120', 'str_limit' => 30, 'visible' => true],
            ['label' => 'Prezzo', 'name' => 'prezzo', 'format' => 'currency', 'decimals' => 2],
        ];
    }

    private function fullForm(): array
    {
        return [
            ['label' => 'Nome', 'name' => 'nome', 'type' => 'text', 'required' => true, 'validation' => 'required|min:1|max:50', 'width' => 'col-sm-10'],
            ['label' => 'Note', 'name' => 'note_brevi', 'type' => 'textarea', 'validation' => 'string'],
        ];
    }

    private function fullConfig(): array
    {
        return [
            'title_field' => 'nome',
            'limit' => 50,
            'orderby' => 'id,asc',
            'global_privilege' => true,
            'button_table_action' => false,
            'button_bulk_action' => false,
            'button_action_style' => 'button_text',
            'button_add' => false,
            'button_edit' => false,
            'button_delete' => false,
            'button_detail' => false,
            'button_filter' => false,
            'button_import' => true,
            'button_export' => true,
        ];
    }

    private function allTypesColumns(): array
    {
        return [
            ['name' => 'quantita', 'type' => 'number', 'size' => 11],
            ['name' => 'attivo', 'type' => 'boolean'],
            ['name' => 'note_brevi', 'type' => 'plaintext'],
            ['name' => 'descrizione', 'type' => 'longtext'],
            ['name' => 'prezzo', 'type' => 'decimal', 'size' => '10,2'],
            ['name' => 'data_ordine', 'type' => 'date'],
            ['name' => 'data_ora', 'type' => 'datetime'],
            ['name' => 'ora', 'type' => 'time'],
            ['name' => 'nome', 'type' => 'text', 'size' => 50],
        ];
    }

    private function fullPayload(string $name, string $table): array
    {
        return $this->basePayload($name, $table, $this->allTypesColumns()) + [
            'config' => $this->fullConfig(),
            'col' => $this->fullCol(),
            'form' => $this->fullForm(),
            'form_layout' => $this->fullLayout(),
        ];
    }

    /** @return array<string,object> nome colonna => riga information_schema */
    private function dbColumns(string $table): array
    {
        $out = [];
        $rows = DB::select(
            'select COLUMN_NAME as cname, DATA_TYPE as dtype, COLUMN_TYPE as ctype, NUMERIC_PRECISION as nprec, '
            . 'NUMERIC_SCALE as nscale, CHARACTER_MAXIMUM_LENGTH as clen '
            . 'from information_schema.COLUMNS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ?',
            [$table]
        );
        foreach ($rows as $r) {
            $out[$r->cname] = $r;
        }

        return $out;
    }

    private function assertValidPhp(string $contents): void
    {
        // TOKEN_PARSE lancia ParseError se il sorgente generato non e' sintatticamente valido.
        token_get_all($contents, TOKEN_PARSE);
        $this->assertTrue(true);
    }

    /** Come assertPropertySafelyEmbedded() di ModuleGeneratorCrudTest: solo via var_export(). */
    private function assertPropertySafelyEmbedded(string $contents, string $property, string $value): void
    {
        $pattern = '/\$this->' . preg_quote($property, '/') . '\s*=\s*' . preg_quote(var_export($value, true), '/') . '\s*;/';
        $this->assertMatchesRegularExpression($pattern, $contents, "\$this->$property non e' stato incollato via var_export().");
    }

    private function assertArrayValueSafelyEmbedded(string $contents, string $key, string $value): void
    {
        $this->assertStringContainsString("'" . $key . "'=>" . var_export($value, true), $contents, "'$key' non e' stato incollato via var_export().");
    }

    // ---------------------------------------------------------------
    // 1. Import, percorso felice con tabella nuova
    // ---------------------------------------------------------------

    public function test_import_felice_crea_tabella_modulo_controller_e_blocchi_senza_menu_ne_ruoli(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();
        $payload = $this->fullPayload($name, $table);
        $menusBefore = DB::table('cms_menus')->count();
        $rolesBefore = DB::table('cms_privileges_roles')->count();

        [$row, $contents] = $this->importOk($payload);

        // riga cms_moduls
        $this->assertSame($table, $row->path);
        $this->assertSame($table, $row->table_name);
        $this->assertSame('bi bi-box', $row->icon);
        $this->assertEquals(0, $row->is_protected);
        $this->assertEquals(1, $row->is_active);
        $this->assertNull($row->deleted_at);

        // tabella: colonne dell'import + colonne di cornice
        $this->assertTrue(Schema::hasTable($table));
        $cols = $this->dbColumns($table);
        $expected = [
            'quantita' => 'int', 'attivo' => 'tinyint', 'note_brevi' => 'text', 'descrizione' => 'longtext',
            'prezzo' => 'decimal', 'data_ordine' => 'date', 'data_ora' => 'datetime', 'ora' => 'time', 'nome' => 'varchar',
        ];
        foreach ($expected as $col => $type) {
            $this->assertArrayHasKey($col, $cols, "Colonna $col mancante.");
            $this->assertSame($type, strtolower($cols[$col]->dtype), "Tipo errato per $col.");
        }
        $this->assertEquals(10, $cols['prezzo']->nprec);
        $this->assertEquals(2, $cols['prezzo']->nscale);
        $this->assertEquals(50, $cols['nome']->clen);
        foreach (['id', 'group', 'tenant', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'] as $frame) {
            $this->assertArrayHasKey($frame, $cols, "Colonna di cornice $frame mancante.");
        }

        // controller: i quattro blocchi
        $this->assertValidPhp($contents);
        $this->assertStringContainsString('# START COLUMNS DO NOT REMOVE THIS LINE', $contents);
        foreach ($this->fullCol() as $col) {
            $this->assertStringContainsString('$this->col[] = ' . min_var_export($col) . ';', $contents);
        }
        foreach ($this->fullForm() as $field) {
            $this->assertStringContainsString('$this->form[] = ' . min_var_export($field) . ';', $contents);
        }
        $this->assertStringContainsString(\App\Helpers\ModuleGeneratorLayout::START, $contents);
        $this->assertStringContainsString('$this->form_layout = ' . min_var_export($this->fullLayout()) . ';', $contents);
        $this->assertPropertySafelyEmbedded($contents, 'table', $table);
        foreach ($this->fullConfig() as $key => $value) {
            $this->assertStringContainsString('$this->' . $key . ' = ' . var_export($value, true) . ';', $contents, "Config $key non scritta.");
        }

        // niente menu ne' ruoli (voluto, doc 071)
        $this->assertSame($menusBefore, DB::table('cms_menus')->count());
        $this->assertSame($rolesBefore, DB::table('cms_privileges_roles')->count());
        $this->assertSame(0, DB::table('cms_privileges_roles')->where('id_cms_moduls', $row->id)->count());
    }

    // ---------------------------------------------------------------
    // 2. Round trip import -> export
    // ---------------------------------------------------------------

    public function test_round_trip_export_restituisce_cio_che_l_import_ha_scritto(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();
        $payload = $this->fullPayload($name, $table);
        [$row] = $this->importOk($payload);

        $response = $this->get(self::BASE . '/export/' . $row->id);

        $response->assertStatus(200);
        $export = json_decode($response->getContent(), true);
        $this->assertSame(1, $export['format_version']);
        $this->assertArrayHasKey('exported_at', $export);
        $this->assertSame($name, $export['module']['name']);
        $this->assertSame('bi bi-box', $export['module']['icon']);
        $this->assertSame($table, $export['table']['name']);
        $this->assertEquals($this->fullCol(), $export['col']);
        $this->assertEquals($this->fullForm(), $export['form']);
        $this->assertEquals($this->fullLayout(), $export['form_layout']);
        $this->assertEquals(['table' => $table] + $this->fullConfig(), $export['config']);

        $byName = [];
        foreach ($export['table']['columns'] as $c) {
            $byName[$c['name']] = $c;
        }
        foreach ($this->allTypesColumns() as $c) {
            $this->assertArrayHasKey($c['name'], $byName);
            $this->assertSame($c['type'], $byName[$c['name']]['type'], "Tipo di {$c['name']} non preservato.");
        }
        $this->assertSame('10,2', $byName['prezzo']['size']);
        $this->assertEquals(50, $byName['nome']['size']);
        $this->assertCount(count($this->allTypesColumns()), $export['table']['columns'], 'Le colonne di cornice non vanno esportate.');
    }

    // ---------------------------------------------------------------
    // 3. Sicurezza (non deve peggiorare)
    // ---------------------------------------------------------------

    public function test_import_valori_pericolosi_finiscono_nel_controller_solo_via_var_export(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames('Sec');
        $evil = "\"; system('x'); //";
        $payload = $this->basePayload($name, $table, [['name' => 'campo', 'type' => 'text']]) + [
            'config' => ['title_field' => $evil],
            'col' => [['label' => $evil, 'name' => $evil]],
            'form' => [['label' => $evil, 'name' => 'campo', 'type' => 'text']],
        ];

        [, $contents] = $this->importOk($payload);

        $this->assertValidPhp($contents);
        $this->assertPropertySafelyEmbedded($contents, 'title_field', $evil);
        $this->assertArrayValueSafelyEmbedded($contents, 'label', $evil);
        $this->assertArrayValueSafelyEmbedded($contents, 'name', $evil);
        $this->assertStringNotContainsString("system('x')", $contents);
    }

    public function test_import_scarta_chiavi_fuori_whitelist_e_campi_incompleti_ma_tiene_quelli_validi(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames('Sec');
        $payload = $this->basePayload($name, $table, [['name' => 'campo', 'type' => 'text']]) + [
            'config' => ['limit' => 15, 'evil_prop' => 'x'],
            'col' => [
                ['label' => 'Ok', 'name' => 'campo', 'width' => '90', 'evil_key' => 'x'],
                ['name' => 'senza_label'],
                ['label' => 'Senza name'],
            ],
            'form' => [
                ['label' => 'Valido', 'name' => 'campo_ok', 'type' => 'text'],
                ['name' => 'form_senza_label', 'type' => 'text'],
                ['label' => 'Form senza name', 'type' => 'text'],
            ],
        ];

        [, $contents] = $this->importOk($payload);

        // positivi: quanto e' valido/ammesso c'e'
        $this->assertStringContainsString('$this->col[] = ' . min_var_export(['label' => 'Ok', 'name' => 'campo', 'width' => '90']) . ';', $contents);
        $this->assertStringContainsString('$this->form[] = ' . min_var_export(['label' => 'Valido', 'name' => 'campo_ok', 'type' => 'text']) . ';', $contents);
        $this->assertStringContainsString('$this->limit = 15;', $contents);
        // negativi
        $this->assertStringNotContainsString('evil_key', $contents);
        $this->assertStringNotContainsString('evil_prop', $contents);
        $this->assertStringNotContainsString('senza_label', $contents);
        $this->assertStringNotContainsString('Senza name', $contents);
        $this->assertStringNotContainsString('form_senza_label', $contents);
        $this->assertStringNotContainsString('Form senza name', $contents);
    }

    public function test_import_scarta_un_layout_non_valido_e_tiene_quello_valido_a_parita_di_form(): void
    {
        $this->actAsSuperadmin();
        $form = [['label' => 'Nome', 'name' => 'nome', 'type' => 'text']];
        $validLayout = ['v' => 2, 'tabs' => [['id' => 't1', 'title' => '', 'blocks' => [
            ['id' => 'b1', 'title' => 'B', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 3, 'fields' => [['name' => 'nome', 'w' => 12]]],
        ]]]];

        // positivo: stesso form, layout valido -> blocco presente
        [$okName, $okTable] = $this->newNames('Lay');
        [, $okContents] = $this->importOk($this->basePayload($okName, $okTable) + ['form' => $form, 'form_layout' => $validLayout]);
        $this->assertStringContainsString(\App\Helpers\ModuleGeneratorLayout::START, $okContents);

        // negativo: campo del layout che non esiste nel form (e valore pericoloso) -> scartato, nessun errore
        $evilLayout = $validLayout;
        $evilLayout['tabs'][0]['blocks'][0]['fields'] = [['name' => "x\"; system('x'); //", 'w' => 12]];
        [$badName, $badTable] = $this->newNames('Lay');
        [$badRow, $badContents] = $this->importOk($this->basePayload($badName, $badTable) + ['form' => $form, 'form_layout' => $evilLayout]);

        $this->assertStringNotContainsString(\App\Helpers\ModuleGeneratorLayout::START, $badContents);
        $this->assertStringNotContainsString('form_layout', $badContents);
        $this->assertStringNotContainsString("system('x')", $badContents);
        $this->assertStringContainsString('$this->form[] = ' . min_var_export($form[0]) . ';', $badContents);
        $this->assertNotNull($badRow);
    }

    // ---------------------------------------------------------------
    // 4. Import, rifiuti (ognuno preceduto dal positivo con lo stesso setup)
    // ---------------------------------------------------------------

    public function test_import_stesso_file_come_superadmin_e_accettato(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();

        $this->importOk($this->basePayload($name, $table));

        $this->assertTrue(Schema::hasTable($table));
    }

    public function test_import_non_superadmin_e_negato_senza_scritture(): void
    {
        $this->actAsTenantAdmin();
        [$name, $table] = $this->newNames();
        $before = $this->stateSnapshot();

        $response = $this->postImportJson($this->basePayload($name, $table));

        $response->assertRedirect(url('admin/'));
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertSame($before, $this->stateSnapshot());
        $this->assertFalse(Schema::hasTable($table));
        $this->assertSame(0, DB::table('cms_moduls')->where('name', $name)->count());
    }

    public function test_import_senza_file_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        [$okName, $okTable] = $this->newNames();
        $this->importOk($this->basePayload($okName, $okTable));
        [, $table] = $this->newNames();
        $before = $this->stateSnapshot();

        $response = $this->from(self::IMPORT_PAGE)->post(self::BASE . '/import', []);

        $this->assertImportRejected($response, $this->msg('module_import_invalid_file'), 'warning', $before, $table);
    }

    public function test_import_con_estensione_non_json_e_rifiutato_e_json_maiuscolo_e_accettato(): void
    {
        $this->actAsSuperadmin();
        // positivo: stesso contenuto, estensione maiuscola (strtolower nel controller)
        [$okName, $okTable] = $this->newNames();
        $this->postImportJson($this->basePayload($okName, $okTable), 'MODULO.JSON');
        $okRow = DB::table('cms_moduls')->where('name', $okName)->first();
        $this->assertNotNull($okRow, 'Estensione .JSON maiuscola dovrebbe essere accettata.');
        $this->fixtureFiles[] = \App\Helpers\CRUDBooster::customControllersPath() . '/' . $okRow->controller . '.php';

        [$name, $table] = $this->newNames();
        $before = $this->stateSnapshot();
        $response = $this->postImportJson($this->basePayload($name, $table), 'modulo.txt');

        $this->assertImportRejected($response, $this->msg('module_import_bad_extension'), 'warning', $before, $table);
    }

    public function test_import_con_json_invalido_o_incompleto_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        [$okName, $okTable] = $this->newNames();
        $this->importOk($this->basePayload($okName, $okTable));

        [$name, $table] = $this->newNames();
        $cases = [
            'json rotto' => '{non e json',
            'scalare' => '5',
            'senza module.name' => json_encode(['module' => ['icon' => 'x'], 'table' => ['name' => $table, 'columns' => [['name' => 'a']]]]),
            'senza table.name' => json_encode(['module' => ['name' => $name], 'table' => ['columns' => [['name' => 'a']]]]),
        ];
        foreach ($cases as $label => $contents) {
            $before = $this->stateSnapshot();

            $response = $this->postImportJson($contents);

            $this->assertImportRejected($response, $this->msg('module_import_invalid_json'), 'danger', $before, $table);
            $this->assertSame(0, DB::table('cms_moduls')->where('name', $name)->count(), $label);
        }
    }

    public function test_import_con_nome_modulo_gia_esistente_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();
        $this->importOk($this->basePayload($name, $table));
        [, $otherTable] = $this->newNames();
        $before = $this->stateSnapshot();

        $response = $this->postImportJson($this->basePayload($name, $otherTable));

        $this->assertImportRejected($response, $this->msg('module_import_name_exists', ['name' => $name]), 'warning', $before, $otherTable);
    }

    public function test_import_con_tabella_riservata_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        [$okName, $okTable] = $this->newNames();
        $this->importOk($this->basePayload($okName, $okTable));
        [$name] = $this->newNames();
        $reserved = config('app.reserved_tables_prefix') . 'phpunit_' . $this->unique();
        $before = $this->stateSnapshot();

        $response = $this->postImportJson($this->basePayload($name, $reserved));

        $this->assertImportRejected($response, $this->msg('module_import_reserved_table'), 'danger', $before, $reserved);
        $this->assertSame(0, DB::table('cms_moduls')->where('name', $name)->count());
    }

    public function test_import_con_tabella_gia_usata_da_un_altro_modulo_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();
        $this->importOk($this->basePayload($name, $table));
        [$otherName] = $this->newNames();
        $before = $this->stateSnapshot();

        $response = $this->postImportJson($this->basePayload($otherName, $table));

        $response->assertRedirect(self::IMPORT_PAGE);
        $response->assertSessionHas('message', $this->msg('module_import_table_used', ['table' => $table]));
        $response->assertSessionHas('message_type', 'warning');
        $this->assertSame($before, $this->stateSnapshot());
        $this->assertSame(0, DB::table('cms_moduls')->where('name', $otherName)->count());
    }

    public function test_import_su_tabella_inesistente_senza_colonne_e_rifiutato_e_con_colonne_e_accettato(): void
    {
        $this->actAsSuperadmin();
        // positivo: con colonne
        [$okName, $okTable] = $this->newNames();
        $this->importOk($this->basePayload($okName, $okTable));

        foreach (['chiave assente' => null, 'array vuoto' => []] as $label => $columns) {
            [$name, $table] = $this->newNames();
            $payload = $this->basePayload($name, $table);
            if ($columns === null) {
                unset($payload['table']['columns']);
            } else {
                $payload['table']['columns'] = $columns;
            }
            $before = $this->stateSnapshot();

            $response = $this->postImportJson($payload);

            $this->assertImportRejected($response, $this->msg('module_import_no_columns'), 'danger', $before, $table);
            $this->assertSame(0, DB::table('cms_moduls')->where('name', $name)->count(), $label);
        }
    }

    // ---------------------------------------------------------------
    // 5. Import su tabella gia' esistente
    // ---------------------------------------------------------------

    public function test_import_su_tabella_esistente_non_la_ricrea_ne_la_tocca_ma_crea_il_modulo(): void
    {
        $this->actAsSuperadmin();
        [$name, $table] = $this->newNames();
        Schema::create($table, function ($t) {
            $t->increments('id');
            $t->string('nome', 40)->nullable();
        });
        DB::table($table)->insert(['nome' => 'riga preesistente']);
        $columnsBefore = array_keys($this->dbColumns($table));

        // il JSON dichiara una colonna in piu': non deve essere aggiunta
        $payload = $this->basePayload($name, $table, [
            ['name' => 'nome', 'type' => 'text', 'size' => 40],
            ['name' => 'colonna_extra', 'type' => 'number'],
        ]);
        [$row] = $this->importOk($payload);

        $this->assertSame($table, $row->table_name);
        $this->assertEqualsCanonicalizing($columnsBefore, array_keys($this->dbColumns($table)));
        $this->assertSame(['riga preesistente'], DB::table($table)->pluck('nome')->all());
    }

    // ---------------------------------------------------------------
    // 6. Export, rifiuti
    // ---------------------------------------------------------------

    /**
     * Scrive un controller "reale" (istanziabile, con cbInit) e la tabella, e
     * semina la riga cms_moduls che li collega.
     *
     * @return array{id:int,table:string,class:string}
     */
    private function seedExportableModule(bool $withLayout = false, array $overrides = [], ?string $table = null): array
    {
        $u = $this->unique();
        $class = 'PhpunitTestExportFixture' . $u . 'Controller';
        $table ??= 'mg_phpunit_' . $u;
        $this->tables[] = $table;
        if (! Schema::hasTable($table)) {
            Schema::create($table, function ($t) {
                $t->increments('id');
                $t->string('nome', 50)->nullable();
            });
        }

        $layoutCode = $withLayout
            ? '$this->form_layout = ' . min_var_export(['v' => 2, 'tabs' => [['id' => 't1', 'title' => 'Dati', 'blocks' => [
                ['id' => 'b1', 'title' => 'B', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 3, 'fields' => [['name' => 'nome', 'w' => 12]]],
            ]]]]) . ';'
            : '';
        $path = base_path('app/Http/Controllers/' . $class . '.php');
        File::put($path, '<?php namespace App\Http\Controllers;' . "\n"
            . 'class ' . $class . ' extends \crocodicstudio\crudbooster\controllers\CBController {' . "\n"
            . '    public function cbInit() {' . "\n"
            . '        $this->table = ' . var_export($table, true) . ';' . "\n"
            . '        $this->title_field = "nome"; $this->limit = 20; $this->orderby = "id,desc"; $this->global_privilege = false;' . "\n"
            . '        $this->button_table_action = true; $this->button_bulk_action = true; $this->button_action_style = "button_icon";' . "\n"
            . '        $this->button_add = true; $this->button_edit = true; $this->button_delete = true; $this->button_detail = true;' . "\n"
            . '        $this->button_filter = true; $this->button_import = false; $this->button_export = false;' . "\n"
            . '        $this->col = []; $this->form = [["label" => "Nome", "name" => "nome", "type" => "text"]];' . "\n"
            . '        ' . $layoutCode . "\n"
            . '    }' . "\n"
            . '}' . "\n");
        $this->fixtureFiles[] = $path;

        $id = DB::table('cms_moduls')->insertGetId(array_merge([
            'name' => 'Phpunit Test Export ' . $u,
            'table_name' => $table,
            'controller' => $class,
            'path' => 'phpunit_test_export_' . $u,
            'is_protected' => 0,
            'is_active' => 1,
            'created_at' => now(),
        ], $overrides));

        return ['id' => $id, 'table' => $table, 'class' => $class];
    }

    public function test_export_superadmin_di_un_modulo_valido_scarica_il_json(): void
    {
        $this->actAsSuperadmin();
        $module = $this->seedExportableModule();

        $response = $this->get(self::BASE . '/export/' . $module['id']);

        $response->assertStatus(200);
        $this->assertSame(1, json_decode($response->getContent(), true)['format_version']);
    }

    public function test_export_non_superadmin_e_negato(): void
    {
        $this->actAsTenantAdmin();
        $module = $this->seedExportableModule();

        $response = $this->get(self::BASE . '/export/' . $module['id']);

        $response->assertRedirect(url('admin/'));
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_export_id_inesistente_cancellato_o_protetto_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        $module = $this->seedExportableModule();
        $url = self::BASE . '/export/' . $module['id'];

        // positivo con lo stesso setup
        $this->get($url)->assertStatus(200);

        // id inesistente
        $response = $this->get(self::BASE . '/export/' . ($module['id'] + 100000));
        $response->assertRedirect(url('admin/module_generator'));
        $response->assertSessionHas('message', $this->msg('module_export_not_exportable'));
        $response->assertSessionHas('message_type', 'warning');

        // is_protected = 1
        DB::table('cms_moduls')->where('id', $module['id'])->update(['is_protected' => 1]);
        $response = $this->get($url);
        $response->assertRedirect(url('admin/module_generator'));
        $response->assertSessionHas('message', $this->msg('module_export_not_exportable'));
        $response->assertSessionHas('message_type', 'warning');

        // cancellato (deleted_at)
        DB::table('cms_moduls')->where('id', $module['id'])->update(['is_protected' => 0, 'deleted_at' => now()]);
        $response = $this->get($url);
        $response->assertRedirect(url('admin/module_generator'));
        $response->assertSessionHas('message', $this->msg('module_export_not_exportable'));
        $response->assertSessionHas('message_type', 'warning');
    }

    public function test_export_con_controller_mancante_sul_disco_e_rifiutato(): void
    {
        $this->actAsSuperadmin();
        $module = $this->seedExportableModule();
        $url = self::BASE . '/export/' . $module['id'];

        // positivo con lo stesso setup
        $this->get($url)->assertStatus(200);

        DB::table('cms_moduls')->where('id', $module['id'])->update(['controller' => 'PhpunitTestNonEsistente' . $this->unique() . 'Controller']);
        $response = $this->get($url);

        $response->assertRedirect(url('admin/module_generator'));
        $response->assertSessionHas('message', $this->msg('module_export_controller_missing'));
        $response->assertSessionHas('message_type', 'danger');
    }

    // ---------------------------------------------------------------
    // 7. Export, colonne e intestazioni
    // ---------------------------------------------------------------

    public function test_export_mappa_i_tipi_delle_colonne_ed_esclude_quelle_riservate(): void
    {
        $this->actAsSuperadmin();
        $table = 'mg_phpunit_' . $this->unique();
        $this->tables[] = $table;
        DB::statement(
            'CREATE TABLE `' . $table . '` ('
            . '`id` int unsigned NOT NULL AUTO_INCREMENT, `c_int` int NULL, `c_tiny` tinyint(1) NULL, `c_text` text NULL, '
            . '`c_medium` mediumtext NULL, `c_long` longtext NULL, `c_dec` decimal(10,2) NULL, `c_date` date NULL, '
            . '`c_dt` datetime NULL, `c_ts` timestamp NULL, `c_time` time NULL, `c_var` varchar(50) NULL, `c_big` bigint NULL, '
            . '`group` int NULL, `tenant` int NULL, `created_at` datetime NULL, `created_by` int NULL, `updated_at` datetime NULL, '
            . '`updated_by` int NULL, `deleted_at` datetime NULL, `deleted_by` int NULL, PRIMARY KEY (`id`))'
        );
        $module = $this->seedExportableModule(false, [], $table);

        $response = $this->get(self::BASE . '/export/' . $module['id']);

        $response->assertStatus(200);
        $this->assertStringStartsWith('application/json', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $table . '_module_export.json"');
        $export = json_decode($response->getContent(), true);
        $byName = [];
        foreach ($export['table']['columns'] as $c) {
            $byName[$c['name']] = $c;
        }
        $expected = [
            'c_int' => 'number', 'c_tiny' => 'boolean', 'c_text' => 'plaintext', 'c_medium' => 'plaintext',
            'c_long' => 'longtext', 'c_dec' => 'decimal', 'c_date' => 'date', 'c_dt' => 'datetime',
            'c_ts' => 'datetime', 'c_time' => 'time', 'c_var' => 'text', 'c_big' => 'text',
        ];
        foreach ($expected as $col => $type) {
            $this->assertArrayHasKey($col, $byName, "Colonna $col non esportata.");
            $this->assertSame($type, $byName[$col]['type'], "Mappatura errata per $col.");
        }
        $this->assertSame('10,2', $byName['c_dec']['size']);
        $this->assertEquals(50, $byName['c_var']['size']);
        $this->assertEquals(1, $byName['c_tiny']['size']);
        // la display width di int dipende dalla versione di MySQL (8: assente, 5.7: 11)
        $this->assertContains($byName['c_int']['size'], [null, 11]);
        foreach (config('app.reserved_column_names') as $reserved) {
            $this->assertArrayNotHasKey($reserved, $byName, "Colonna riservata $reserved esportata.");
        }
        $this->assertCount(count($expected), $export['table']['columns']);
    }

    public function test_export_include_form_layout_solo_se_il_modulo_ha_un_layout_attivo(): void
    {
        $this->actAsSuperadmin();
        $withLayout = $this->seedExportableModule(true);
        $without = $this->seedExportableModule(false);

        $on = json_decode($this->get(self::BASE . '/export/' . $withLayout['id'])->getContent(), true);
        $off = json_decode($this->get(self::BASE . '/export/' . $without['id'])->getContent(), true);

        $this->assertArrayHasKey('form_layout', $on);
        $this->assertSame('Dati', $on['form_layout']['tabs'][0]['title']);
        $this->assertArrayNotHasKey('form_layout', $off);
    }

    // ---------------------------------------------------------------
    // 8. i18n delle chiavi dei messaggi
    // ---------------------------------------------------------------

    /**
     * Le chiavi crudbooster.module_export_* / module_import_* (elenco in
     * self::MESSAGES) esistono in en e it; il testo it coincide con la frase
     * italiana originale (letterale in self::MESSAGES), il testo en e' diverso.
     */
    public function test_chiavi_i18n_nuove_esistono_in_en_e_it_con_it_identico_a_oggi(): void
    {
        $params = ['name' => 'Modulo Demo', 'table' => 'tabella_demo'];

        foreach (array_keys(self::MESSAGES) as $key) {
            $full = 'crudbooster.' . $key;
            $this->assertTrue(trans()->has($full, 'it', false), "Chiave $full mancante in it.");
            $this->assertTrue(trans()->has($full, 'en', false), "Chiave $full mancante in en.");

            $it = trans($full, $params, 'it');
            $en = trans($full, $params, 'en');
            $this->assertSame($this->msg($key, $params), $it, "Il testo it di $full non coincide con la frase di oggi.");
            $this->assertNotSame($it, $en, "Il testo en di $full coincide con l'it.");
        }
    }
}
