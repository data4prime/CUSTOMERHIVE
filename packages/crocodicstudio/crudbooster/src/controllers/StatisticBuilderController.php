<?php

namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;
use crocodicstudio\crudbooster\helpers\LicenseHelper;

use \crocodicstudio\crudbooster\controllers\QlikAppController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;

use Illuminate\Support\Facades\Log;

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
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_export = false;
        $this->button_import = false;

        $this->col = [];
        $this->col[] = ["label" => "Name", "name" => "name"];
        //$this->col[] = ["label" => "Layout", "name" => "layout"];
        $this->col[] = array("label" => "Layout", "name" => "layout", "join" => "dashboard_layouts,layoutname");

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

        $layout = isset($row->layout) ? $row->layout : 0 ;

        $layout = DB::table('dashboard_layouts')->where('id', $layout)->first();

        if ($layout) {
            $code_layout = html_entity_decode($layout->code_layout);
        } else {
            $code_layout = "
                <div class='statistic-row row'>
        <div id='area1' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area2' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area3' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area4' class='col-sm-3 connectedSortable'>

        </div>
    </div>

<div class='statistic-row row'>
        <div id='area5' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area6' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area7' class='col-sm-3 connectedSortable'>

        </div>
        <div id='area8' class='col-sm-3 connectedSortable'>

        </div>
    </div>

    <div class='statistic-row row'>
        <div id='area9' class='col-sm-12 connectedSortable'>

        </div>
</div>
        ";
        }

        return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics', 'layout', 'code_layout'));
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
        $layout = DB::table('dashboard_layouts')->where('id', $layout)->first();//->code_layout;

        if ($layout) {
            $code_layout = html_entity_decode($layout->code_layout);
        } else {
            $code_layout = '';
        }

        return view('crudbooster::statistic_builder.builder', compact('page_title', 'id_cms_statistics', 'layout', 'code_layout'));
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

            if (isset($config->mashups)) {
                $mashup = DB::table('qlik_apps')->where('id', $config->mashups)->first();
            }  else {
                $mashup = null;
            }


            
            if ($mashup) {
                $conf = QlikAppController::getConf($mashup->conf);
            } else {
                $conf = null;
            }
        } else {
            $conf = null;
            $config = new \stdClass();
            $config->mashups = 0;
            $config->object = 0;
        }

        //$mashup = 

        if (isset($config->mashups)) {
            $mashup = QlikAppController::getMashupFromCompID($componentID);
        } else {
            $mashup = null;
        }


        if ($conf && isset($conf->id) && $conf->type == 'SAAS') {
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
        $errors = [];
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Edit Component', 'module' => 'Statistic']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $component_row = CRUDBooster::first('cms_statistic_components', ['componentID' => $componentID]);

        $config = json_decode($component_row->config);


        if (!$config) {
            $errors[] = 'Widget configuration is empty. Please, add configuration for this widget.';

        }

        if (isset($config->mashups)) {
            $conf = QlikAppController::getConf($config->mashups);
        } else {
            $errors[] = 'Mashup is not selected. Please, select mashup for this widget.';
        }

        $command = 'configuration';

        //dd(QlikAppController::getMashups());
        $mashups = QlikAppController::getMashups();
        if (isset($config->mashups)) {
            
            $mashup = QlikAppController::getMashupFromCompID($componentID);
        } else {
            $mashup = null;
        }



        if (!$mashup && isset($config->mashups)) {
            $errors[] = 'Mashup is not selected. Please, select mashup for this widget.';
        }

        if (isset($conf) && isset($conf->id) && $conf->type == 'SAAS') {
            $token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
        } else {
            //$errors[] = 'Qlik configuration is empty or not selected.';
            $conf = null;
            $token = null;
        }

        //dd($mashups);

        return view('crudbooster::statistic_builder.components.' . $component_row->component_name, compact('command', 'componentID', 'config', 'mashups', 'conf', 'mashup', 'token', 'errors'));
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

    public function hook_before_edit(&$postdata, $id)
    {
        $postdata['slug'] = str_slug($postdata['name']);
    }

    public function mashup($componentID) {

            //LICENSE CHECK
            if (!LicenseHelper::isActiveQlik()) {
                return view('mashup_noqlik');
            }   



            $mashups = DB::table('cms_statistic_components')->where('componentID', $componentID)->first();

            $qlik_conf = null;
            $mashup = null;

            if ($mashups) {
                $mashups = json_decode($mashups->config);

                if (isset($mashups->mashups)) {
                    $mashup = DB::table('qlik_apps')->where('id', $mashups->mashups)->first();
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
            //LICENSE CHECK
            if (!LicenseHelper::isActiveQlik()) {
                return view('mashup_noqlik');
            }   
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

        $mashup = DB::table('qlik_apps')->where('id', $mashup)->first();
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
