<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use App\Helpers\ApiDocBuilder;
use CRUDbooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class ApiCustomController extends CBController
{
    public function cbInit()
    {
        $this->table = 'cms_apicustom';
        $this->primary_key = 'id';
        $this->title_field = "nama";
        $this->button_show = false;
        $this->button_new = false;
        $this->button_delete = false;
        $this->button_add = false;
        $this->button_import = false;
        $this->button_export = false;
    }

    /**
     * Tutto il modulo API Generator e' riservato al superadmin (come gia'
     * getIndex()/getGenerator()/getEditApi()): CBBackend verifica solo "sei
     * loggato", quindi senza questo controllo gli altri endpoint erano
     * usabili da qualunque utente autenticato. Vedi
     * docs/refactoring/078-api-generator-privilegi.md.
     * Ritorna la risposta di accesso negato, o null se si puo' procedere.
     */
    private function denyUnlessSuperadmin(string $name)
    {
        if (CRUDBooster::isSuperadmin()) {
            return null;
        }
        CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => $name, 'module' => 'API']));

        return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    function getIndex()
    {
        $this->cbLoader();

        if (! CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'API Index', 'module' => 'API']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $data = [];

        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['apis'] = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();
        $data['moduleNames'] = DB::table('cms_moduls')->whereNull('deleted_at')->pluck('name', 'table_name')->all();
        $data['bulkModules'] =DB::table('cms_moduls')->whereNull('deleted_at')
            ->whereIn('table_name', $this->apiTablesList())->orderBy('name')->get(['name', 'table_name']);

        foreach ($data['apis'] as $api) {
            //dd(unserialize($api->parameters));
            //$api->parameters = unserialize($api->parameters);
            //$api->responses = unserialize($api->responses);
        }

        //dd($data['apis'][2]->parameters);

        return view('crudbooster::api_documentation', $data);
    }

    function apiDocumentation()
    {
        $this->cbLoader();
        $data = [];

        $data['apis'] = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();

        return view('crudbooster::api_documentation_public', $data);
    }

    function getDownloadPostman()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Postman Export')) {
            return $denied;
        }
        $data = [];
        $data['variables'] = [];
        $data['info'] = [
            'name' => CRUDBooster::getSetting('appname').' - API',
            '_postman_id' => "1765dd11-73d1-2978-ae11-36921dc6263d",
            'description' => '',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
        ];
        $items = [];
        $folders = [];
        $moduleNames = DB::table('cms_moduls')->whereNull('deleted_at')->pluck('name', 'table_name')->all();
        $apis = DB::table('cms_apicustom')->orderby('nama', 'asc')->get();

        foreach ($apis as $a) {
            $parameters = unserialize($a->parameters);
            $formdata = [];
            $httpbuilder = [];
            $notes = $parameters ? ApiDocBuilder::notes((string) $a->tabel, $parameters) : [];
            if ($parameters) {
                foreach ($parameters as $p) {
                    $enabled = ($p['used'] == 0) ? false : true;
                    $name = $p['name'];
                    $httpbuilder[$name] = '';
                    if ($enabled) {
                        $field = ['key' => $name, 'value' => '', 'type' => 'text', 'enabled' => $enabled];
                        if (isset($notes[$name])) {
                            $field['description'] = ApiDocBuilder::toText($notes[$name]);
                        }
                        $formdata[] = $field;
                    }
                }
            }

            if (strtolower($a->method_type) == 'get') {
                if ($httpbuilder) {
                    $httpbuilder = "?".http_build_query($httpbuilder);
                } else {
                    $httpbuilder = '';
                }
            } else {
                $httpbuilder = '';
            }

            // Cartella Postman = nome del modulo (ripiego: nome tabella)
            $folder = $moduleNames[$a->tabel] ?? $a->tabel;
            $folders[$folder][] = [
                'name' => $a->nama,
                'request' => [
                    'url' => url('api2/'.$a->permalink).$httpbuilder,
                    'method' => $a->method_type ?: 'GET',
                    'header' => [
                        ['key' => 'Authorization', 'value' => 'Bearer <token>', 'type' => 'text'],
                    ],
                    'body' => [
                        'mode' => 'formdata',
                        'formdata' => $formdata,
                    ],
                    'description' => ApiDocBuilder::toText($a->keterangan),
                ],
            ];
        }
        ksort($folders, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($folders as $folder => $folderItems) {
            $items[] = ['name' => (string) $folder, 'item' => $folderItems];
        }
        $data['item'] = $items;

        $json = json_encode($data);

        return \Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename='.CRUDBooster::getSetting('appname').' - API For POSTMAN.json',
        ]);
    }

    public function getScreetKey()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Key List')) {
            return $denied;
        }
        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['apikeys'] = DB::table('cms_apikey')->get();

        return view('crudbooster::api_key', $data);
    }

    public function getGenerator()
    {
        $this->cbLoader();

        if (! CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'API Index', 'module' => 'API']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();

        $data['tables'] = $this->apiTablesList();

        return view('crudbooster::api_generator', $data);
    }

    /**
     * Tabelle selezionabili nel generatore API: solo quelle dei moduli
     * (prefisso module_generator_prefix), non le tabelle di sistema.
     * Se l'API in modifica punta già a un'altra tabella, resta in elenco.
     */
    private function apiTablesList($currentTable = null)
    {
        $tables_list = [];
        foreach (CRUDBooster::listTables('mg') as $tab) {
            foreach ($tab as $value) {
                $tables_list[] = $value;
            }
        }

        if ($currentTable && ! in_array($currentTable, $tables_list, true)) {
            $tables_list[] = $currentTable;
        }

        return $tables_list;
    }

    public function getEditApi($id)
    {
        $this->cbLoader();

        if (! CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'API Edit', 'module' => 'API']));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $row = DB::table('cms_apicustom')->where('id', $id)->first();

        $data['row'] = $row;
        $data['parameters'] = json_encode(unserialize($row->parameters));
        $data['responses'] = json_encode(unserialize($row->responses));
        $data['page_title'] = 'API Generator';
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();

        $data['tables'] = $this->apiTablesList($row->tabel ?? null);

        return view('crudbooster::api_generator', $data);
    }

    function getGenerateScreetKey()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Key Generate')) {
            return $denied;
        }
        //Generate a random string.
        $token = openssl_random_pseudo_bytes(16);

        //Convert the binary data into hexadecimal representation.
        $token = bin2hex($token);

        $id = DB::table('cms_apikey')->insertGetId([
            'screetkey' => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
            'hit' => 0,
        ]);

        $response = [];
        $response['key'] = $token;
        $response['id'] = $id;

        return response()->json($response);
    }

    public function getStatusApikey()
    {
        if ($denied = $this->denyUnlessSuperadmin('API Key Status')) {
            return $denied;
        }
        $validResult = CRUDBooster::valid(['id', 'status'], 'view');
        if ($validResult instanceof \Symfony\Component\HttpFoundation\Response) {
            return $validResult;
        }

        $id = Request::get('id');
        $status = (Request::get('status') == 1) ? "active" : "non active";

        DB::table('cms_apikey')->where('id', $id)->update(['status' => $status]);

        return redirect()->back()->with(['message' => 'You have been update api key status !', 'message_type' => 'success']);
    }

    public function getDeleteApiKey()
    {
        if ($denied = $this->denyUnlessSuperadmin('API Key Delete')) {
            return $denied;
        }

        $id = Request::get('id');
        if (DB::table('cms_apikey')->where('id', $id)->delete()) {
            return response()->json(['status' => 1]);
        } else {
            return response()->json(['status' => 0]);
        }
    }

    function getColumnTable($table, $type = 'list')
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Column Table')) {
            return $denied;
        }
        $result = [];

        $cols = CRUDBooster::getTableColumns($table);

        $except = ['created_at', 'deleted_at', 'updated_at'];

        $result = $cols;
        $new_result = [];
        foreach ($result as $ro) {

            if (in_array($ro, $except)) {
                continue;
            }

            $type_field = CRUDBooster::getFieldType($table, $ro);

            $type_field = (array_search($ro, explode(',', config('crudbooster.EMAIL_FIELDS_CANDIDATE'))) !== false) ? "email" : $type_field;
            $type_field = (array_search($ro, explode(',', config('crudbooster.IMAGE_FIELDS_CANDIDATE'))) !== false) ? "image" : $type_field;
            $type_field = (array_search($ro, explode(',', config('crudbooster.PASSWORD_FIELDS_CANDIDATE'))) !== false) ? "password" : $type_field;

            $type_field = (substr($ro, -3) == '_id') ? "integer" : $type_field;
            $type_field = (substr($ro, 0, 3) == 'id_') ? "integer" : $type_field;

            $new_result[] = ['name' => $ro, 'type' => $type_field];

            if ($type == 'list' || $type == 'detail') {
                if (substr($ro, 0, 3) == 'id_') {
                    $table2 = substr($ro, 3);
                    $t2 = DB::getSchemaBuilder()->getColumnListing($table2);
                    foreach ($t2 as $t) {
                        if ($t != 'id' && $t != 'created_at' && $t != 'updated_at' && $t != 'deleted_at') {

                            if (substr($t, 0, 3) == 'id_') {
                                continue;
                            }

                            $type_field = CRUDBooster::getFieldType($table2, $t);
                            $t = str_replace("_$table2", "", $t);
                            $new_result[] = ['name' => $table2.'_'.$t, 'type' => $type_field];
                        }
                    }
                }
            }
        }

        return response()->json($new_result);
    }

    function postSaveApiCustom()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Save')) {
            return $denied;
        }
        $posts = Request::all();

        $a = [];

        $a['nama'] = g('nama');
        $a['tabel'] = $posts['tabel'];
        $a['aksi'] = $posts['aksi'];
        $a['permalink'] = g('permalink');
        $a['method_type'] = g('method_type');

        $params_name = g('params_name');
        $params_type = g('params_type');
        $params_config = g('params_config');
        $params_required = g('params_required');
        $params_used = g('params_used');
        $json = [];

        for ($i = 0; $i <= count($params_name); $i++) {
            if (isset($params_name[$i])) {
                if (!empty($params_name[$i])) {
                    $json[] = [
                        'name' => $params_name[$i],
                        'type' => $params_type[$i],
                        'config' => $params_config[$i],
                        'required' => $params_required[$i],
                        'used' => $params_used[$i],
                    ];
                }

            }
        }

        $json = array_filter($json);
        $a['parameters'] = serialize($json);

        $a['sql_where'] = g('sql_where');

        $responses_name = g('responses_name');
        $responses_type = g('responses_type');
        $responses_subquery = g('responses_subquery');
        $responses_used = g('responses_used');
        $json = [];
        for ($i = 0; $i <= count($responses_name); $i++) {
            if (isset($responses_name[$i])) {
                if (!empty($responses_name[$i])) {
                    $json[] = [
                        'name' => $responses_name[$i],
                        'type' => $responses_type[$i],
                        'subquery' => $responses_subquery[$i],
                        'used' => $responses_used[$i],
                    ];
                }

            }
        }

        $json = array_filter($json);
        $a['responses'] = serialize($json);
        $a['keterangan'] = g('keterangan');

        if (Request::get('id')) {
            /*$controller = ucwords(str_replace('_', ' ', $a['permalink']));
            $controller = str_replace(' ', '', $controller);
            $controller = 'Api'.$controller.'Controller.php';

            dd($controller);*/

            $check_permalink = DB::table('cms_apicustom')->where('permalink', g('permalink'))->where('id','!=', g('id') )->first();

            if ($check_permalink) {
                // redirectBack() e' ancora exit()-based (non toccata - usata
                // anche da 2 Blade view Qlik, vedi docs/refactoring/063):
                // qui si usa redirect() (gia' return-based) verso il referer,
                // stesso comportamento visibile, testabile via HTTP simulato.
                return CRUDBooster::redirect(CRUDBooster::referer(), trans('crudbooster.api_permalink_already_exists'), 'error');
            }



            $controller = DB::table('cms_apicustom')->where('id', g('id'))->first();//->controller;

            if (isset($controller->controller) && !empty($controller->controller) ) {
                $controller = $controller->controller;
            } else {
                //return back
                return CRUDBooster::redirect(CRUDBooster::referer(), trans('crudbooster.api_controller_not_found'), 'error');

            }



            DB::table('cms_apicustom')->where('id', g('id'))->update($a);
            //get controller from App\Http\Controllers
            //$controller = "App\Http\Controllers\\".$controller;
            //$controller = app($controller);
            //dd($a);
            $controller_path = base_path("app/Http/Controllers/").$controller;
            $contents = file_get_contents($controller_path);

            // $a['permalink']/$a['tabel']/$a['method_type'] finiscono nel
            // sorgente PHP di un controller gia' su disco, live (autoloaded):
            // preg_replace() con una stringa di replacement grezza aveva DUE
            // problemi di sicurezza distinti. (1) il valore veniva incollato
            // dentro un literal PHP a doppi apici nel file generato: una
            // virgoletta rompeva il literal e permetteva di iniettare PHP
            // arbitrario (RCE) - risolto con var_export(), che produce un
            // literal a singoli apici correttamente escapato e mai
            // interpolato. (2) anche a parte questo, la STRINGA DI
            // REPLACEMENT di preg_replace() ha una sua sintassi speciale
            // ($1, \1, $0 vengono sostituiti con i gruppi catturati): un
            // valore contenente '$' o '\' produceva una sostituzione diversa
            // da quella attesa - risolto passando a preg_replace_callback(),
            // il cui valore di ritorno viene inserito letteralmente.
            $new_contents = preg_replace_callback(
                '/\$this->permalink\s*=\s*["\']((?:[^"\'\\\\]|\\\\.)*)["\']\\s*;/',
                fn () => '$this->permalink = ' . var_export($a['permalink'], true) . ';',
                $contents
            );

            $new_contents = preg_replace_callback(
                '/\$this->table\s*=\s*["\']((?:[^"\'\\\\]|\\\\.)*)["\']\\s*;/',
                fn () => '$this->table = ' . var_export($a['tabel'], true) . ';',
                $new_contents
            );

            $new_contents = preg_replace_callback(
                '/\$this->method_type\s*=\s*["\']((?:[^"\'\\\\]|\\\\.)*)["\']\\s*;/',
                fn () => '$this->method_type = ' . var_export($a['method_type'], true) . ';',
                $new_contents
            );

            file_put_contents($controller_path, $new_contents);
            

            //



        } else {
            if ($error = $this->storeNewApi($a)) {
                return CRUDBooster::redirect(CRUDBooster::referer(), trans($error), 'error');
            }
        }

        return redirect(CRUDBooster::mainpath())->with(['message' => 'Yeay, your api has been saved successfully !', 'message_type' => 'success']);
    }

    /**
     * Genera il controller dell'endpoint e inserisce la riga in cms_apicustom.
     * Ritorna null se tutto ok, altrimenti la chiave di traduzione dell'errore.
     */
    private function storeNewApi(array $a)
    {
        $controllerName = ucwords(str_replace('_', ' ', $a['permalink']));
        $controllerName = str_replace(' ', '', $controllerName);
        // $controllerName finisce nel NOME CLASSE e nel NOME FILE del
        // controller generato (non dentro una stringa PHP, quindi
        // var_export() qui non aiuta): senza sanificazione, un permalink
        // con '/', '.' o virgolette permetteva di scrivere il file fuori
        // da app/Http/Controllers/ (path traversal) o di iniettare
        // codice nel nome classe. Un nome classe PHP valido e' comunque
        // solo lettere/cifre/underscore, quindi questo non toglie nulla
        // a un permalink che produceva gia' un controller funzionante.
        $controllerName = preg_replace('/[^A-Za-z0-9_]/', '', $controllerName);

        //check if controller already exists
        $controller = 'Api'.$controllerName.'Controller.php';
        $controller_path = base_path("app/Http/Controllers/").$controller;
        if (file_exists($controller_path)) {
            // Se il file esiste ma non c'e' una riga cms_apicustom
            // corrispondente (file orfano/collisione con un controller
            // non generato da qui), ->first() torna null e ->id
            // crashava con 500 invece di un errore gestito.
            $collidingApi = DB::table('cms_apicustom')->where('controller', $controller)->first();
            if (!$collidingApi) {
                return 'crudbooster.api_controller_not_found';
            }
            $controller_db = $collidingApi->id;
            $controller = 'Api'.$controllerName.$controller_db.'Controller.php';
            $controllerName = $controllerName.$controller_db;
        }

        CRUDBooster::generateAPI($controllerName, $a['tabel'], $a['permalink'], $a['method_type']);

        $a['controller'] = 'Api'.$controllerName.'Controller.php';

        // Descrizione: se non e' stata scritta, si popola con la
        // documentazione automatica dei parametri (vedi ApiDocBuilder).
        $params = !empty($a['parameters']) ? (@unserialize($a['parameters']) ?: []) : [];
        $a['keterangan'] = ApiDocBuilder::merge($a['keterangan'] ?? '', ApiDocBuilder::build($a['tabel'], $a['aksi'] ?? '', $params));

        DB::table('cms_apicustom')->insert($a);

        return null;
    }

    /**
     * Tipo di validazione salvato per un tipo di colonna (stessa mappa usata
     * dal JS dell'editor). $default: cosa restituire per i tipi non mappati.
     */
    private function apiMapType($type, $default = null)
    {
        switch ($type) {
            case 'varchar':
            case 'nvarchar':
            case 'char':
            case 'text':
                return 'string';
            case 'integer':
                return 'integer';
            case 'double':
            case 'float':
            case 'decimal':
                return 'numeric';
            case 'date':
                return 'date';
            case 'datetime':
            case 'timestamp':
                return 'date_format:Y-m-d H:i:s';
            case 'email':
            case 'image':
            case 'password':
                return $type;
        }

        return $default ?? $type;
    }

    /**
     * Crea in un colpo solo gli endpoint standard (elenco, dettaglio,
     * creazione, modifica, eliminazione) per un modulo. Le impostazioni sono
     * quelle che l'editor propone di default: l'utente poi li ritocca uno
     * per uno. Gli endpoint con permalink gia' esistente vengono saltati.
     */
    public function postBulkCreate()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Bulk Create')) {
            return $denied;
        }

        $table = (string) Request::get('tabel');
        $module = DB::table('cms_moduls')->where('table_name', $table)->whereNull('deleted_at')->first();
        if (! $module || ! in_array($table, $this->apiTablesList(), true)) {
            return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.api_bulk_invalid_module'), 'warning');
        }

        // Colonne come le propone l'editor: per i parametri le colonne della
        // tabella, per le risposte anche quelle collegate (id_xxx).
        $cols = collect($this->getColumnTable($table, 'save_add')->getData(true));
        $respCols = collect($this->getColumnTable($table, 'list')->getData(true));

        // Obbligatorio in creazione = NOT NULL senza default e non auto_increment
        $notNull = [];
        foreach (DB::select('SHOW COLUMNS FROM `'.str_replace('`', '', $table).'`') as $c) {
            $notNull[$c->Field] = $c->Null === 'NO' && $c->Default === null && stripos((string) $c->Extra, 'auto_increment') === false;
        }

        $param = fn ($c, $required, $used) => [
            'name' => $c['name'],
            'type' => $this->apiMapType($c['type'], 'string'),
            'config' => '',
            'required' => $required ? '1' : '0',
            'used' => $used ? '1' : '0',
        ];
        $idParams = $cols->where('name', 'id')->map(fn ($c) => $param($c, true, true))->values()->all();

        $responses = serialize($respCols->map(fn ($c) => [
            'name' => $c['name'],
            'type' => $this->apiMapType($c['type']),
            'subquery' => '',
            'used' => '1',
        ])->values()->all());

        $definitions = [
            'list' => ['suffix' => 'list', 'method' => 'get', 'label' => 'api_action_list',
                'params' => $cols->map(fn ($c) => $param($c, false, false))->values()->all()],
            'detail' => ['suffix' => 'detail', 'method' => 'get', 'label' => 'api_action_detail',
                'params' => $idParams],
            'save_add' => ['suffix' => 'create', 'method' => 'post', 'label' => 'api_action_create',
                // Colonne scritte dal sistema: non si espongono come parametri.
                'params' => $cols->whereNotIn('name', ['id', 'created_by', 'updated_by', 'deleted_by'])->map(fn ($c) => $param($c, $notNull[$c['name']] ?? false, true))->values()->all()],
            'save_edit' => ['suffix' => 'update', 'method' => 'post', 'label' => 'api_action_update',
                'params' => $cols->map(fn ($c) => $param($c, $c['name'] === 'id', true))->values()->all()],
            'delete' => ['suffix' => 'delete', 'method' => 'post', 'label' => 'api_action_delete',
                'params' => $idParams],
        ];

        $created = 0;
        $skipped = 0;
        foreach ($definitions as $aksi => $d) {
            $permalink = $table.'_'.$d['suffix'];
            if (DB::table('cms_apicustom')->where('permalink', $permalink)->exists()) {
                $skipped++;
                continue;
            }
            $row = [
                'nama' => $module->name.' - '.trans('crudbooster.'.$d['label']),
                'tabel' => $table,
                'aksi' => $aksi,
                'permalink' => $permalink,
                'method_type' => $d['method'],
                'parameters' => serialize($d['params']),
                'sql_where' => '',
                'responses' => $responses,
                'keterangan' => '',
            ];
            if ($this->storeNewApi($row) === null) {
                $created++;
            } else {
                $skipped++;
            }
        }

        CRUDBooster::insertLog(trans('crudbooster.api_bulk_log', ['module' => $module->name, 'created' => $created]));

        return redirect(CRUDBooster::mainpath())->with([
            'message' => trans('crudbooster.api_bulk_result', ['created' => $created, 'skipped' => $skipped]),
            'message_type' => $created ? 'success' : 'warning',
        ]);
    }

    /**
     * "Aggiorna doc": rigenera il blocco automatico della Descrizione (nuovi
     * valori delle select, parametri cambiati...) di un endpoint (id) o di
     * tutti. Il testo scritto a mano fuori dal blocco non viene toccato.
     */
    public function postRefreshDoc()
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Refresh Doc')) {
            return $denied;
        }

        $query = DB::table('cms_apicustom');
        if (Request::get('id')) {
            $query->where('id', Request::get('id'));
        }

        $updated = 0;
        $total = 0;
        foreach ($query->get() as $api) {
            $total++;
            $params = $api->parameters ? (@unserialize($api->parameters) ?: []) : [];
            $block = ApiDocBuilder::build((string) $api->tabel, (string) $api->aksi, $params);
            if (ApiDocBuilder::sameBlock($api->keterangan, $block)) {
                continue;
            }
            DB::table('cms_apicustom')->where('id', $api->id)->update([
                'keterangan' => ApiDocBuilder::merge($api->keterangan, $block),
            ]);
            $updated++;
        }

        return redirect(CRUDBooster::mainpath())->with([
            'message' => $updated
                ? trans('crudbooster.api_autodoc_result', ['updated' => $updated, 'total' => $total])
                : trans('crudbooster.api_autodoc_uptodate'),
            'message_type' => $updated ? 'success' : 'info',
        ]);
    }

    function getDeleteApi($id)
    {
        $this->cbLoader();
        if ($denied = $this->denyUnlessSuperadmin('API Delete')) {
            return $denied;
        }
        $row =DB::table('cms_apicustom')->where('id', $id)->first();

        // Un $id inesistente crashava con 500 ($row->controller su null)
        // invece di un errore gestito.
        if (!$row) {
            return response()->json(['status' => 0]);
        }

        $controller = $row->controller;

        $controller = base_path("app/Http/Controllers/".$controller);

        DB::table('cms_apicustom')->where('id', $id)->delete();

        @unlink($controller);

        /*$controllername = ucwords(str_replace('_', ' ', $row->permalink));
        $controllername = str_replace(' ', '', $controllername);
        @unlink(base_path("app/Http/Controllers/Api".$controllername."Controller.php"));*/

        return response()->json(['status' => 1]);
    }
}
