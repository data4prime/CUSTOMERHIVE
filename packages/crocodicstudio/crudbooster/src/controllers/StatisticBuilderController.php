<?php

namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;


use \crocodicstudio\crudbooster\controllers\QlikMashupController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;

class StatisticBuilderController extends CBController
{
    public function cbInit()
    {
        $this->table = "cms_statistics";
        $this->primary_key = "id";
        $this->title_field = "name";
        $this->limit = 20;
        $this->orderby = ["id" => "desc"];
        $this->global_privilege = false;

        $this->button_table_action = true;
        $this->button_action_style = "button_icon_text";
        $this->button_add = true;
        $this->button_delete = true;
        $this->button_edit = true;
        $this->button_detail = false;
        $this->button_show = true;
        $this->button_filter = false;
        $this->button_export = false;
        $this->button_import = false;

        $this->col = [];
        $this->col[] = ["label" => "Name", "name" => "name"];
        $this->col[] = ["label" => "Layout", "name" => "layout"];

        $this->form = [];
        $this->form[] = [
            "label" => "Name",
            "name" => "name",
            "type" => "text",
            "required" => true,
            "validation" => "required|min:3|max:255",
            "placeholder" => "",
        ];

        $this->form[] = [
            "label" => "Layout",
            "name" => "layout",
            "type" => "select",
            "required" => true,
            "datatable" => "dashboard_layouts,layoutname",
            "validation" => "required",
            "placeholder" => "",
        ];

        $this->addaction = [];
$this->addaction[] = ['label' => 'Builder', 'url' => CRUDBooster::mainpath('builder') . '/[id]', 'icon' => 'fa fa-wrench'];


    }



    public function getShowDashboard()
    {
        $this->cbLoader();
        $m = CRUDBooster::sidebarDashboard();
        $m->path = str_replace("statistic_builder/show/", "", $m->path);
        if ($m->type != 'Statistic') {
            redirect('/');
        }
        $row = CRUDBooster::first($this->table, ['slug' => $m->path]);

        $id_cms_statistics = $row->id;
        $page_title = $row->name;

        

        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics'));
    }

    public function getDashboard()
    {
        $this->cbLoader();

        $menus = DB::table('cms_menus')->whereRaw("cms_menus.id IN (select id_cms_menus from cms_menus_privileges where id_cms_privileges = '" . CRUDBooster::myPrivilegeId() . "')")->where('is_dashboard', 1)->where('is_active', 1)->first();

        $slug = str_replace("statistic_builder/show/", "", $menus->path);
        $row = CRUDBooster::first($this->table, ['slug' => $slug]);
        $id_cms_statistics = isset($row->id) ? $row->id : 0;
        $page_title = isset($row->name) ? $row->name : 'Dashboard';

        $layout = $row->layout;

        $layout = DB::table('dashboard_layouts')->where('id', $layout)->first()->code_layout;





        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics', 'layout'));
    }

    public function getShow($slug)
    {

        $this->cbLoader();
        $row = CRUDBooster::first($this->table, ['slug' => $slug]);
        $id_cms_statistics = $row->id;
        $page_title = $row->name;

        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics'));
    }

    public function getBuilder($id_cms_statistics)
    {
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Builder', 'module' => 'Statistic']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $page_title = 'Statistic Builder';

        
        $layout = CRUDBooster::first($this->table, ['id' => $id_cms_statistics])->layout;
        $layout = DB::table('dashboard_layouts')->where('id', $layout)->first()->code_layout;

        return view('crudbooster::statistic_builder.builder', compact('page_title', 'id_cms_statistics', 'layout'));
    }

    public function getListComponent($id_cms_statistics, $area_name)
    {
        $rows = DB::table('cms_statistic_components')->where('id_cms_statistics', $id_cms_statistics)->where('area_name', $area_name)
                ->orderby('sorting', 'asc')->get();

        return response()->json(['components' => $rows]);
    }

    public function getViewComponent($componentID)
    {

        $component = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();



        $command = 'layout';
        $config = json_decode($component->config);
        if ($config) {
            $mashup = DB::table('qlik_mashups')->where('id', $config->mashups)->first();
            if ($mashup) {
                $conf = QlikMashupController::getConf($mashup->conf);
            } else {
                $conf = null;
            }
        } else {
            $conf = null;
            $config = new \stdClass();
            $config->mashups = 0;
            $config->object = 0;
        }
        $mashup = QlikMashupController::getMashupFromCompID($componentID);
        if (isset($conf->id)) {
        $token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
            } else {
                $token = '';
            }
        $layout = view('crudbooster::statistic_builder.components.' . $component->component_name, compact('command', 'componentID', 'conf', 'config','mashup', 'token'))->render();

        $component_name = $component->component_name;
        $area_name = $component->area_name;

        if ($config) {
            foreach ($config as $key => $value) {
                if ($value) {
                    $command = 'showFunction';
                    $value = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'value', 'key', 'config', 'conf', 'componentID', 'mashup', 'token'))->render();
                    $layout = str_replace('[' . $key . ']', $value, $layout);
                }
            }
        }

        return response()->json(compact('componentID', 'layout', 'config', 'conf'));
    }

    public function postAddComponent()
    {
        $this->cbLoader();
        $component_name = Request::get('component_name');
        $id_cms_statistics = Request::get('id_cms_statistics');
        $sorting = Request::get('sorting');
        $area = Request::get('area');

        $componentID = md5(time());

        $command = 'layout';
        $layout = view('crudbooster::statistic_builder.components.' . $component_name, compact('command', 'componentID'))->render();


        $data = [
            'id_cms_statistics' => $id_cms_statistics,
            'componentID' => $componentID,
            'component_name' => $component_name,
            'area_name' => $area,
            'sorting' => $sorting,
            'name' => 'Untitled',
        ];
        CRUDBooster::insert('cms_statistic_components', $data);

        return response()->json(compact('layout', 'componentID'));
    }

    public function postUpdateAreaComponent()
    {
        DB::table('cms_statistic_components')->where('componentID', Request::get('componentid'))->update([
            'sorting' => Request::get('sorting'),
            'area_name' => Request::get('areaname'),
        ]);

        return response()->json(['status' => true]);
    }

    public function getEditComponent($componentID)
    {
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Edit Component', 'module' => 'Statistic']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $component_row = CRUDBooster::first('cms_statistic_components', ['componentID' => $componentID]);

        $config = json_decode($component_row->config);

        if (!$config) {
            $config = new \stdClass();
            $config->mashups = 0;
            $config->object = 0;

        }

        if (!isset($config->mashups)) {
            $config->mashups = 0;
        }

        if (!isset($config->object)) {
            $config->object = 0;
        }   

        $conf = QlikMashupController::getConf($config->mashups);
        //dd($conf);

        $command = 'configuration';

        $mashups = QlikMashupController::getMashups();
        $mashup = QlikMashupController::getMashupFromCompID($componentID);

        if (!$mashup) {
            $mashup = new \stdClass();
            $mashup->id = 0;
        }

        if (isset($conf->id)) {
            $token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
        } else {
            $token = '';
        }

        return view('crudbooster::statistic_builder.components.' . $component_row->component_name, compact('command', 'componentID', 'config', 'mashups', 'conf', 'mashup', 'token'));
    }

    public function postSaveComponent()
    {
        DB::table('cms_statistic_components')->where('componentID', Request::get('componentid'))->update([
            'name' => Request::get('name'),
            'config' => json_encode(Request::get('config')),
        ]);

        return response()->json(['status' => true]);
    }

    public function getDeleteComponent($id)
    {
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Delete Component', 'module' => 'Statistic']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        DB::table('cms_statistic_components')->where('componentID', $id)->delete();

        return response()->json(['status' => true]);
    }

    public function hook_before_add(&$arr)
    {
        $arr['slug'] = str_slug($arr['name']);
    }

    public function mashup($componentID) {

            $mashups = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

            $qlik_conf = null;
            $mashup = null;

            if ($mashups) {
                $mashups = json_decode($mashups->config);

                if (isset($mashups->mashups)) {
                    $mashup = DB::table('qlik_mashups')->where('id', $mashups->mashups)->first();
                    if ($mashup) {
                        $qlik_conf_record = DB::table('qlik_confs')->where('id', $mashup->conf)->first();
                        if ($qlik_conf_record) {
                            $qlik_conf = $qlik_conf_record->id;
                        }
                    }
                }
            }

            if ($mashup && $qlik_conf) {
                return view('mashup', compact('componentID', 'mashup', 'qlik_conf', 'mashups'));
            }



    }

    public function mashup_objects($mashup, $componentID, $objectid) {
        $comp = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

        $config = new \stdClass();
        $config->mashups = 0;
        $config->object = 0;

        if ($comp) {
            $decodedConfig = json_decode($comp->config);
            if ($decodedConfig) {
                $config = $decodedConfig;
            }
        }

        if (!isset($config->mashups)) {
            $config->mashups = 0;
        }
        if (!isset($config->object)) {
            $config->object = 0;
        }

        $mashup = DB::table('qlik_mashups')->where('id', $mashup)->first();
        $qlik_conf = null;

        if ($mashup) {
            $qlik_conf_record = DB::table('qlik_confs')->where('id', $mashup->conf)->first();
            if ($qlik_conf_record) {
                $qlik_conf = $qlik_conf_record->id;
            }
        }

        if ($mashup && $qlik_conf) {
            return view('mashup_objects', compact('componentID', 'mashup', 'qlik_conf', 'config'));
        }

    }




}
