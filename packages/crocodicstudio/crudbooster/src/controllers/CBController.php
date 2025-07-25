<?php

namespace crocodicstudio\crudbooster\controllers;

error_reporting(E_ALL ^ E_NOTICE);

//use CB;
use \crocodicstudio\crudbooster\helpers\CB;
//use CRUDBooster;
use \crocodicstudio\crudbooster\helpers\CRUDBooster;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;
//use Schema;
//use UserHelper;
use \crocodicstudio\crudbooster\helpers\UserHelper;
use \crocodicstudio\crudbooster\helpers\ModuleHelper;
//use ModuleHelper;
use \crocodicstudio\crudbooster\controllers\ExportData;
use \crocodicstudio\crudbooster\controllers\ImportData;

class CBController extends Controller
{
    public $data_inputan;

    public $columns_table;

    public $module_name;

    public $table;

    public $title_field;

    public $primary_key = 'id';

    public $arr = [];

    public $col = [];

    public $form = [];

    public $data = [];

    public $addaction = [];

    public $orderby = null;

    public $password_candidate = null;

    public $date_candidate = null;

    public $limit = 20;

    public $global_privilege = false;

    public $show_numbering = false;

    public $alert = [];

    public $index_button = [];

    public $button_filter = true;

    public $button_export = true;

    public $button_import = true;

    public $button_show = true;

    public $button_addmore = true;

    public $button_table_action = true;

    public $button_bulk_action = true;

    public $button_add = true;

    public $button_delete = true;

    public $button_cancel = true;

    public $button_save = true;

    public $button_edit = true;

    public $button_detail = true;

    public $button_action_style = 'button_icon';

    public $button_action_width = null;

    public $index_statistic = [];

    public $index_additional_view = [];

    public $pre_index_html = null;

    public $post_index_html = null;

    public $load_js = [];

    public $load_css = [];

    public $script_js = null;

    public $style_css = null;

    public $sub_module = [];

    public $show_addaction = true;

    public $table_row_color = [];

    public $button_selected = [];

    public $return_url = null;

    public $parent_field = null;

    public $parent_id = null;

    public $hide_form = [];

    public $index_return = false; //for export

    public $sidebar_mode = 'normal';

    public function cbLoader()
    {

        $this->cbInit();


        $this->checkHideForm();

        $this->primary_key = CB::pk($this->table);
        $this->columns_table = $this->col;
        $this->data_inputan = $this->form;
        $this->data['pk'] = $this->primary_key;
        $this->data['forms'] = $this->data_inputan;
        $this->data['hide_form'] = $this->hide_form;
        $this->data['addaction'] = ($this->show_addaction) ? $this->addaction : null;
        $this->data['table'] = $this->table;
        $this->data['title_field'] = $this->title_field;
        $this->data['appname'] = CRUDBooster::getSetting('appname');
        $this->data['alerts'] = $this->alert;
        $this->data['index_button'] = $this->index_button;
        $this->data['show_numbering'] = $this->show_numbering;
        $this->data['button_detail'] = $this->button_detail;
        $this->data['button_edit'] = $this->button_edit;
        $this->data['button_show'] = $this->button_show;
        $this->data['button_add'] = $this->button_add;
        $this->data['button_delete'] = $this->button_delete;
        $this->data['button_filter'] = $this->button_filter;
        $this->data['button_export'] = $this->button_export;
        $this->data['button_addmore'] = $this->button_addmore;
        $this->data['button_cancel'] = $this->button_cancel;
        $this->data['button_save'] = $this->button_save;
        $this->data['button_table_action'] = $this->button_table_action;
        $this->data['button_bulk_action'] = $this->button_bulk_action;
        $this->data['button_import'] = $this->button_import;
        $this->data['button_action_width'] = $this->button_action_width;
        $this->data['button_selected'] = $this->button_selected;
        $this->data['index_statistic'] = $this->index_statistic;
        $this->data['index_additional_view'] = $this->index_additional_view;
        $this->data['table_row_color'] = $this->table_row_color;
        $this->data['pre_index_html'] = $this->pre_index_html;
        $this->data['post_index_html'] = $this->post_index_html;
        $this->data['load_js'] = $this->load_js;
        $this->data['load_css'] = $this->load_css;
        $this->data['script_js'] = $this->script_js;
        $this->data['style_css'] = $this->style_css;
        $this->data['sub_module'] = $this->sub_module;
        $this->data['parent_field'] = (g('parent_field')) ?: $this->parent_field;
        $this->data['parent_id'] = (g('parent_id')) ?: $this->parent_id;

        if ($this->sidebar_mode == 'mini') {
            $this->data['sidebar_mode'] = 'sidebar-mini';
        } elseif ($this->sidebar_mode == 'collapse') {
            $this->data['sidebar_mode'] = 'sidebar-collapse';
        } elseif ($this->sidebar_mode == 'collapse-mini') {
            $this->data['sidebar_mode'] = 'sidebar-collapse sidebar-mini';
        } else {
            $this->data['sidebar_mode'] = '';
        }

        if (CRUDBooster::getCurrentMethod() == 'getProfile') {
            Session::put('current_row_id', CRUDBooster::myId());
            $this->data['return_url'] = Request::fullUrl();
        }
        view()->share($this->data);
    }

    public function cbView($template, $data)
    {
        header("Content-Type: text/html");
        $this->cbLoader();
        echo view($template, $data);
    }

    private function checkHideForm()
    {
        if ($this->hide_form && count($this->hide_form)) {
            foreach ($this->form as $i => $f) {
                if (in_array($f['name'], $this->hide_form)) {
                    unset($this->form[$i]);
                }
            }
        }
    }

    public function getIndex()
    {

        $this->cbLoader();

        $module = CRUDBooster::getCurrentModule();
       

        if (!CRUDBooster::isView() && !$this->global_privilege) {
            CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        if (Request::get('parent_table')) {
            $parentTablePK = CB::pk(g('parent_table'));
            $data['parent_table'] = DB::table(Request::get('parent_table'))->where($parentTablePK, Request::get('parent_id'))->first();
            if (Request::get('foreign_key')) {
                $data['parent_field'] = Request::get('foreign_key');
            } else {
                $data['parent_field'] = CB::getTableForeignKey(g('parent_table'), $this->table);
            }
            $parent_field = $data['parent_field'];
            $this->parent_field = $parent_field;

            if (isset($parent_field)) {
                foreach ($this->columns_table as $i => $col) {
                    if ($col['name'] == $parent_field) {
                        unset($this->columns_table[$i]);
                    }
                }
            }
        }

        $data['table'] = $this->table;
        $data['table_pk'] = CB::pk($this->table);
        $data['page_title'] = $module->name;
        $data['page_description'] = trans('crudbooster.default_module_description');
        $data['date_candidate'] = $this->date_candidate;
        $data['limit'] = $limit = (Request::get('limit')) ? Request::get('limit') : $this->limit;

        $tablePK = $data['table_pk'];
        $table_columns = CB::getTableColumns($this->table);
        $result = DB::table($this->table)->select(DB::raw($this->table . "." . $this->primary_key));

        if (Request::get('parent_id')) {
            $table_parent = $this->table;
            $table_parent = CRUDBooster::parseSqlTable($table_parent)['table'];
            $result->where($table_parent . '.' . Request::get('foreign_key'), Request::get('parent_id'));
        }

        $this->hook_query_index($result);

        if (in_array('deleted_at', $table_columns)) {
            $result->where($this->table . '.deleted_at', null);
        }
        // if it's a manually generated module..
        //..no filter needed for superadmin
        if (ModuleHelper::is_manually_generated($this->table) and !CRUDBooster::isSuperadmin()) {
            //.. for Tenantadmin and basic add filter by tenant
            //$result->where($this->table . '.tenant', UserHelper::current_user_tenant());

            if (!UserHelper::isTenantAdmin()) {
                //.. for basic also filter by group
                $result->whereIn($this->table . '.group', UserHelper::current_user_groups());
            }
        }

        $alias = [];
        $join_alias_count = 0;
        $join_table_temp = [];
        $table = $this->table;
        $columns_table = $this->columns_table;
        

        //add group and tenant columns for admins
        $columns_table = ModuleHelper::add_default_column_headers($table, $columns_table);
        foreach ($columns_table as $index => $coltab) {

            $join = @$coltab['join'];
            $join_where = @$coltab['join_where'];
            $join_id = @$coltab['join_id'];
            $field = @$coltab['name'];
            $join_table_temp[] = $table;

            if (!$field) {
                continue;
            }

            if (strpos($field, ' as ') !== false) {
                $field = substr($field, strpos($field, ' as ') + 4);
                $field_with = (array_key_exists('join', $coltab)) ? str_replace(",", ".", $coltab['join']) : $field;
                $result->addselect(DB::raw($coltab['name']));
                $columns_table[$index]['type_data'] = 'varchar';
                $columns_table[$index]['field'] = $field;
                $columns_table[$index]['field_raw'] = $field;
                $columns_table[$index]['field_with'] = $field_with;
                $columns_table[$index]['is_subquery'] = true;
                continue;
            }

            if (strpos($field, '.') !== false) {
                $result->addselect($field);
            } else {
                $result->addselect($table . '.' . $field);
            }

            $field_array = explode('.', $field);

            if (isset($field_array[1])) {
                $field = $field_array[1];
                $table = $field_array[0];
            } else {
                $table = $this->table;
            }

            if ($join) {

                $join_exp = explode(',', $join);

                $join_table = $join_exp[0];
                $joinTablePK = CB::pk($join_table);
                $join_column = $join_exp[1];
                $join_alias = str_replace(".", "_", $join_table);

                if (in_array($join_table, $join_table_temp)) {
                    $join_alias_count += 1;
                    $join_alias = $join_table . $join_alias_count;
                }
                $join_table_temp[] = $join_table;

                $result->leftjoin($join_table . ' as ' . $join_alias, $join_alias . (($join_id) ? '.' . $join_id : '.' . $joinTablePK), '=', DB::raw($table . '.' . $field . (($join_where) ? ' AND ' . $join_where . ' ' : '')));
                $result->addselect($join_alias . '.' . $join_column . ' as ' . $join_alias . '_' . $join_column);

                $join_table_columns = CRUDBooster::getTableColumns($join_table);
                if ($join_table_columns) {
                    foreach ($join_table_columns as $jtc) {
                        $result->addselect($join_alias . '.' . $jtc . ' as ' . $join_alias . '_' . $jtc);
                    }
                }

                $alias[] = $join_alias;
                $columns_table[$index]['type_data'] = CRUDBooster::getFieldType($join_table, $join_column);
                $columns_table[$index]['field'] = $join_alias . '_' . $join_column;
                $columns_table[$index]['field_with'] = $join_alias . '.' . $join_column;
                $columns_table[$index]['field_raw'] = $join_column;

                @$join_table1 = $join_exp[2];
                @$joinTable1PK = CB::pk($join_table1);
                @$join_column1 = $join_exp[3];
                @$join_alias1 = $join_table1;

                if ($join_table1 && $join_column1) {

                    if (in_array($join_table1, $join_table_temp)) {
                        $join_alias_count += 1;
                        $join_alias1 = $join_table1 . $join_alias_count;
                    }

                    $join_table_temp[] = $join_table1;

                    $result->leftjoin($join_table1 . ' as ' . $join_alias1, $join_alias1 . '.' . $joinTable1PK, '=', $join_alias . '.' . $join_column);
                    $result->addselect($join_alias1 . '.' . $join_column1 . ' as ' . $join_column1 . '_' . $join_alias1);
                    $alias[] = $join_alias1;
                    $columns_table[$index]['type_data'] = CRUDBooster::getFieldType($join_table1, $join_column1);
                    $columns_table[$index]['field'] = $join_column1 . '_' . $join_alias1;
                    $columns_table[$index]['field_with'] = $join_alias1 . '.' . $join_column1;
                    $columns_table[$index]['field_raw'] = $join_column1;
                }
            } else {

                if (isset($field_array[1])) {
                    $result->addselect($table . '.' . $field . ' as ' . $table . '_' . $field);
                    $columns_table[$index]['type_data'] = CRUDBooster::getFieldType($table, $field);
                    $columns_table[$index]['field'] = $table . '_' . $field;
                    $columns_table[$index]['field_raw'] = $table . '.' . $field;
                } else {
                    $result->addselect($table . '.' . $field);
                    $columns_table[$index]['type_data'] = CRUDBooster::getFieldType($table, $field);
                    $columns_table[$index]['field'] = $field;
                    $columns_table[$index]['field_raw'] = $field;
                }

                $columns_table[$index]['field_with'] = $table . '.' . $field;
            }
        }

        if (Request::get('q')) {
            $result->where(function ($w) use ($columns_table) {
                foreach ($columns_table as $col) {
                    if (!$col['field_with']) {
                        continue;
                    }
                    if (isset($col['is_subquery'])) {
                        continue;
                    }
                    $w->orwhere($col['field_with'], "like", "%" . Request::get("q") . "%");
                }
            });
        }

        if (Request::get('where')) {
            foreach (Request::get('where') as $k => $v) {
                $result->where($table . '.' . $k, $v);
            }
        }

        $filter_is_orderby = false;
        if (Request::get('filter_column')) {

            $filter_column = Request::get('filter_column');
            $result->where(function ($w) use ($filter_column) {
                foreach ($filter_column as $key => $fc) {

                    $value = @$fc['value'];
                    $type = @$fc['type'];

                    if ($type == 'empty') {
                        $w->whereNull($key)->orWhere($key, '');
                        continue;
                    }

                    if ($value == '' || $type == '') {
                        continue;
                    }

                    if ($type == 'between') {
                        continue;
                    }

                    switch ($type) {
                        default:
                            if ($key && $type && $value) {
                                $w->where($key, $type, $value);
                            }
                            break;
                        case 'like':
                        case 'not like':
                            $value = '%' . $value . '%';
                            if ($key && $type && $value) {
                                $w->where($key, $type, $value);
                            }
                            break;
                        case 'in':
                        case 'not in':
                            if ($value) {
                                $value = explode(',', $value);
                                if ($key && $value) {
                                    $w->whereIn($key, $value);
                                }
                            }
                            break;
                    }
                }
            });

            foreach ($filter_column as $key => $fc) {
                $value = @$fc['value'];
                $type = @$fc['type'];
                $sorting = @$fc['sorting'];

                if ($sorting != '') {
                    if ($key) {
                        $result->orderby($key, $sorting);
                        $filter_is_orderby = true;
                    }
                }

                if ($type == 'between') {
                    if ($key && $value) {

                        //dates in $value are in mm/dd/yyyy format, convert to yyyy-mm-dd
                        $value = array_map(function ($v) {
                            return date('Y-m-d', strtotime($v));
                        }, $value);

                        //dd($value);
                        
                        $result->whereBetween($key, $value);
                    }
                } else {
                    continue;
                }
            }
        }


        if ($filter_is_orderby == true) {
            $data['result'] = $result->paginate($limit);
        } else {
            if ($this->orderby) {
                if (is_array($this->orderby)) {
                    foreach ($this->orderby as $k => $v) {
                        if (strpos($k, '.') !== false) {
                            $orderby_table = explode(".", $k)[0];
                            $k = explode(".", $k)[1];
                        } else {
                            $orderby_table = $this->table;
                        }
                        $result->orderby($orderby_table . '.' . $k, $v);
                        
                    }
                } else {
                    $this->orderby = explode(";", $this->orderby);
                    foreach ($this->orderby as $o) {
                        $o = explode(",", $o);
                        $k = $o[0];
                        $v = $o[1];
                        if (strpos($k, '.') !== false) {
                            $orderby_table = explode(".", $k)[0];
                        } else {
                            $orderby_table = $this->table;
                        }
                        $result->orderby($orderby_table . '.' . $k, $v);
                    }
                }
                $data['result'] = $result->paginate($limit);
            } else {
                $data['result'] = $result->orderby($this->table . '.' . $this->primary_key, 'desc')->paginate($limit);
            }
        }



        $data['columns'] = $columns_table;

        if ($this->index_return) {
            return $data;
        }

        //LISTING INDEX HTML
        $addaction = $this->data['addaction'];

        if ($this->sub_module) {
            foreach ($this->sub_module as $s) {
                $table_parent = CRUDBooster::parseSqlTable($this->table)['table'];
                $addaction[] = [
                    'label' => $s['label'],
                    'icon' => $s['button_icon'],
                    'url' => CRUDBooster::adminPath($s['path']) . '?return_url=' . urlencode(Request::fullUrl()) . '&parent_table=' . $table_parent . '&parent_columns=' . $s['parent_columns'] . '&parent_columns_alias=' . $s['parent_columns_alias'] . '&parent_id=[' . (!isset($s['custom_parent_id']) ? "id" : $s['custom_parent_id']) . ']&foreign_key=' . $s['foreign_key'] . '&label=' . urlencode($s['label']),
                    'color' => $s['button_color'],
                    'showIf' => $s['showIf'],
                ];
            }
        }

        $mainpath = CRUDBooster::mainpath();
        //$orig_mainpath = $this->data['mainpath'];
        $orig_mainpath = $mainpath;
        $title_field = $this->title_field;
        $html_contents = [];
        $page = (Request::get('page')) ? Request::get('page') : 1;
        $number = ($page - 1) * $limit + 1;
        foreach ($data['result'] as $ind => $row) {
            if (!ModuleHelper::can_view($this, $row)) {
                unset($data['result'][$ind]);
                continue;
            }
            $html_content = [];

            if ($this->button_bulk_action) {

                $html_content[] = "<input type='checkbox' class='checkbox' name='checkbox[]' value='" . $row->{$tablePK} . "'/>";
            }

            if ($this->show_numbering) {
                $html_content[] = $number . '. ';
                $number++;
            }

            foreach ($columns_table as $col) {
                if (isset($col['visible']) && $col['visible'] === false) {
                    continue;
                }
               // dd($col);
                $value = @$row->{$col['field']};

                if ($col['type_data'] == 'tinyint' ) {
                    $value = ($value == 1) ? trans('crudbooster.confirmButtonText') : trans('crudbooster.confirmation_no');
                }


                $title = @$row->{$this->title_field};
                $label = $col['label'];

                if (isset($col['image'])) {
                    if ($value == '') {
                        //$value = "<a  data-lightbox='roadtrip' rel='group_{{$table}}' title='$label: $title' href='/storage" . UserHelper::icon(@$row->id) . "'><img width='40px' height='40px' src='/storage" . UserHelper::icon(@$row->id) . "'/></a>";
                        $value = "<a  data-lightbox='roadtrip' rel='group_{{$table}}' title='$label: $title' href='" . UserHelper::icon(@$row->id) . "'><img width='40px' height='40px' src='" . UserHelper::icon(@$row->id) . "'/></a>";
                    } else {
                        $pic = (strpos($value, 'http://') !== false) ? $value : asset($value);
                        //$pic = '/storage' . $value;
                        $value = "<a data-lightbox='roadtrip'  rel='group_{{$table}}' title='$label: $title' href='" . $pic . "'><img width='40px' height='40px' src='" . $pic . "'/></a>";
                    }
                }

                if (@$col['download']) {
                    $url = '/storage'. (strpos($value, 'http://') !== false) ? $value : asset($value) . '?download=1';
                    if ($value) {
                        $value = "<a class='btn btn-sm btn-primary' href='$url' target='_blank' title='Download File'><i class='fa fa-download'></i> Download</a>";
                    } else {
                        $value = " - ";
                    }
                }

                if (isset($col['str_limit']) && $col['str_limit']) {
                    $value = trim(strip_tags($value));
                    $value = str_limit($value, $col['str_limit']);
                }

                if (isset($col['nl2br']) && $col['nl2br']) {
                    $value = nl2br($value);
                }

                if (isset($col['callback_php']) && $col['callback_php']) {
                    foreach ($row as $k => $v) {
                        $col['callback_php'] = str_replace("[" . $k . "]", $v, $col['callback_php']);
                    }
                    @eval("\$value = " . $col['callback_php'] . ";");
                }

                //New method for callback
                if (isset($col['callback'])) {
                    $value = call_user_func($col['callback'], $row);
                }

                if (isset($col['query'])) {
                    $value = $this->call_user_query($col['query'], $row);
                }

                $datavalue = @unserialize($value);
                if ($datavalue !== false) {
                    if ($datavalue) {
                        $prevalue = [];
                        foreach ($datavalue as $d) {
                            if ($d['label']) {
                                $prevalue[] = $d['label'];
                            }
                        }
                        if ($prevalue && count($prevalue)) {
                            $value = implode(", ", $prevalue);
                        }
                    }
                }

                $html_content[] = $value;
            } //end foreach columns_table

            if ($this->button_table_action) {
                $button_action_style = $this->button_action_style;
                $this_module = $this;
                $parent_field = $this->parent_field;
                $html_content[] = "<div class='button_action' style='text-align:right'>" . view('crudbooster::components.action', compact('addaction', 'row', 'button_action_style', 'parent_field', 'this_module'))->render() . "</div>";
            }

            foreach ($html_content as $i => $v) {
                $this->hook_row_index($i, $v);
                $html_content[$i] = $v;
            }

            $html_contents[] = $html_content;
        } //end foreach data[result]

        $html_contents = ['html' => $html_contents, 'data' => $data['result']];

        $data['html_contents'] = $html_contents;

        $data['target_layout'] = isset(\App\Menu::find(Request::get('m'))->target_layout) ? \App\Menu::find(Request::get('m'))->target_layout : 'default';
        return view("crudbooster::default.index", $data);
    }

    public function call_user_query($query, $row)
    {
        preg_match_all('/:([a-zA-Z0-9_]+)/', $query, $matches);

        foreach ($matches[1] as $param) {
            if (isset($row->$param)) {
                $value = addslashes($row->$param);

                if (is_string($value)) {
                    $value = "'$value'";
                }

                $query = str_replace(':' . $param, $value, $query);
            }
        }
        if (isset(DB::select(DB::raw($query))[0])) {
            $value= DB::select(DB::raw($query))[0]->value;
        } else {
            $value = "";
        }
        //$value= DB::select(DB::raw($query))[0]->value;
        //dd($value);

        return $value;
    }

    public function getExportData()
    {
        return redirect(CRUDBooster::mainpath());
    }

    public function postExportData()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(180);

        $this->limit = Request::input('limit');
        $this->index_return = true;
        $filetype = Request::input('fileformat');
        $filename = Request::input('filename');
        $papersize = Request::input('page_size');
        $paperorientation = Request::input('page_orientation');
        $response = $this->getIndex();

        if (Request::input('default_paper_size')) {
            DB::table('cms_settings')->where('name', 'default_paper_size')->update(['content' => $papersize]);
        }

        switch ($filetype) {
            case "pdf":
                $view = view('crudbooster::export', ['response' => $response])->render();
                $pdf = App::make('dompdf.wrapper');
                $pdf->loadHTML($view);
                $pdf->setPaper($papersize, $paperorientation);

                return $pdf->stream($filename . '.pdf');
                break;
            case 'xls':
                return Excel::download(new ExportData($response, $filename, $paperorientation), $filename . '.xlsx');

                break;
            case 'csv':
                return Excel::download(new ExportData($response, $filename, $paperorientation), $filename . '.csv');

                break;
        }
    }

    public function postDataQuery()
    {
        $query = Request::get('query');
        $query = DB::select(DB::raw($query));

        return response()->json($query);
    }

    public function getDataTable()
    {
        $table = Request::get('table');
        $label = Request::get('label');
        $datatableWhere = urldecode(Request::get('datatable_where'));
        $foreign_key_name = Request::get('fk_name');
        $foreign_key_value = Request::get('fk_value');
        $parent_crosstable = Request::get('parent_crosstable');
        $child_crosstable_fk_name = Request::get('child_crosstable_fk_name');
        if (!empty($table) and !empty($label) and !empty($foreign_key_value) and $foreign_key_value != 'null') {
            if (!empty($parent_crosstable)) {
                $table_pk = CRUDBooster::findPrimaryKey($table);
                //parent and child are linked with a relationship table
                $query = DB::table($table)
                    ->join($parent_crosstable, $parent_crosstable . '.' . $child_crosstable_fk_name, $table . '.' . $table_pk);
                if ($datatableWhere) {
                    $query->whereRaw($datatableWhere);
                }
                $query->distinct($table . '.' . $label);
                $query->select($table . '.' . $table_pk . ' as select_value', $table . '.' . $label . ' as select_label');
                //se $foreign_key_value ha una virgola è un array
                if (strpos($foreign_key_value, ',') > -1) {
                    $query->whereIn($foreign_key_name, explode(',', $foreign_key_value));
                } else {
                    $query->where($foreign_key_name, $foreign_key_value);
                }
                $query->orderby($label, 'asc');

                return response()->json($query->get());
            } elseif (!empty($foreign_key_name)) {
                //child table has a parent column
                $query = DB::table($table);
                if ($datatableWhere) {
                    $query->whereRaw($datatableWhere);
                }
                $query->select('id as select_value', $label . ' as select_label');
                //se $foreign_key_value ha una virgola è un array
                if (strpos($foreign_key_value, ',') > -1) {
                    $query->whereIn($foreign_key_name, explode(',', $foreign_key_value));
                } else {
                    $query->where($foreign_key_name, $foreign_key_value);
                }
                $query->orderby($label, 'asc');

                return response()->json($query->get());
            }
        }
        return response()->json([]);
    }

    public function getModalData()
    {
        $table = Request::get('table');
        $where = Request::get('where');
        $where = urldecode($where);
        $columns = Request::get('columns');
        $columns = explode(",", $columns);

        $table = CRUDBooster::parseSqlTable($table)['table'];
        $tablePK = CB::pk($table);
        $result = DB::table($table);

        if (Request::get('q')) {
            $result->where(function ($where) use ($columns) {
                foreach ($columns as $c => $col) {
                    if ($c == 0) {
                        $where->where($col, 'like', '%' . Request::get('q') . '%');
                    } else {
                        $where->orWhere($col, 'like', '%' . Request::get('q') . '%');
                    }
                }
            });
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $result->whereNull('deleted_at');
        }


        if ($where) {
            $result->whereraw($where);
        }

        $result->orderby($tablePK, 'desc');

        //$result->useBootstrapFive();

        //dd($result->toSql());

        $data['result'] = $result->paginate(6);
        $data['where'] = $where;
        $data['columns'] = $columns;

        // #RAMA filtro le richieste con questo attributo per richiamare custom modal
        $data['q'] = Request::get('q');
        switch (Request::get('type')) {
            case 'group_members_datamodal':
                return view('crudbooster::default.type_components.group_members_datamodal.browser', $data);
                break;
            case 'group_items_datamodal':
                return view('crudbooster::default.type_components.group_items_datamodal.browser', $data);
                break;
            case 'item_access_datamodal':
                return view('crudbooster::default.type_components.item_access_datamodal.browser', $data);
                break;
            case 'user_groups_datamodal':
                return view('crudbooster::default.type_components.user_groups_datamodal.browser', $data);
                break;
            case 'user_groups_datamodal':
                return view('crudbooster::default.type_components.user_groups_datamodal.browser', $data);
                break;

            default:
                return view('crudbooster::default.type_components.datamodal.browser', $data);
                break;
        }
    }

    public function getUpdateSingle()
    {
        $table = Request::get('table');
        $column = Request::get('column');
        $value = Request::get('value');
        $id = Request::get('id');
        $tablePK = CB::pk($table);
        DB::table($table)->where($tablePK, $id)->update([$column => $value]);

        return redirect()->back()->with(['message_type' => 'success', 'message' => trans('crudbooster.alert_delete_data_success')]);
    }

    public function getFindData()
    {
        $q = Request::get('q');
        $id = Request::get('id');
        $limit = Request::get('limit') ?: 10;
        $format = Request::get('format');

        $table1 = (Request::get('table1')) ?: $this->table;
        $table1PK = CB::pk($table1);
        $column1 = (Request::get('column1')) ?: $this->title_field;

        @$table2 = Request::get('table2');
        @$column2 = Request::get('column2');

        @$table3 = Request::get('table3');
        @$column3 = Request::get('column3');

        $where = Request::get('where');

        $fk = Request::get('fk');
        $fk_value = Request::get('fk_value');

        if ($q || $id || $table1) {
            $rows = DB::table($table1);
            $rows->select($table1 . '.*');
            $rows->take($limit);

            if (CRUDBooster::isColumnExists($table1, 'deleted_at')) {
                $rows->where($table1 . '.deleted_at', null);
            }

            if ($fk && $fk_value) {
                $rows->where($table1 . '.' . $fk, $fk_value);
            }

            if ($table1 && $column1) {

                $orderby_table = $table1;
                $orderby_column = $column1;
            }

            if ($table2 && $column2) {
                $table2PK = CB::pk($table2);
                $rows->join($table2, $table2 . '.' . $table2PK, '=', $table1 . '.' . $column1);
                $columns = CRUDBooster::getTableColumns($table2);
                foreach ($columns as $col) {
                    $rows->addselect($table2 . "." . $col . " as " . $table2 . "_" . $col);
                }
                $orderby_table = $table2;
                $orderby_column = $column2;
            }

            if ($table3 && $column3) {
                $table3PK = CB::pk($table3);
                $rows->join($table3, $table3 . '.' . $table3PK, '=', $table2 . '.' . $column2);
                $columns = CRUDBooster::getTableColumns($table3);
                foreach ($columns as $col) {
                    $rows->addselect($table3 . "." . $col . " as " . $table3 . "_" . $col);
                }
                $orderby_table = $table3;
                $orderby_column = $column3;
            }

            if ($id) {
                $rows->where($table1 . "." . $table1PK, $id);
            }

            if ($where) {
                $rows->whereraw($where);
            }

            if ($format) {
                $format = str_replace('&#039;', "'", $format);
                $rows->addselect(DB::raw("CONCAT($format) as text"));
                if ($q) {
                    $rows->whereraw("CONCAT($format) like '%" . $q . "%'");
                }
            } else {
                $rows->addselect($orderby_table . '.' . $orderby_column . ' as text');
                if ($q) {
                    $rows->where($orderby_table . '.' . $orderby_column, 'like', '%' . $q . '%');
                }
                $rows->orderBy($orderby_table . '.' . $orderby_column, 'asc');
            }

            $result = [];
            $result['items'] = $rows->get();
        } else {
            $result = [];
            $result['items'] = [];
        }

        return response()->json($result);
    }

    public function validation($id = null, $request_all = null)
    {
        if (empty($request_all)) {
            $request_all = Request::all();
        }
        $array_input = [];
        foreach ($this->data_inputan as $di) {
            $ai = [];
            $name = $di['name'];

            if (!isset($request_all[$name])) {
                continue;
            }

            if (isset($di['type']) && $di['type'] != 'upload') {
                if (@$di['required']) {
                    $ai[] = 'required';
                }
            }

            if (isset($di['type']) && $di['type'] == 'upload') {
                if ($id) {
                    $row = DB::table($this->table)->where($this->primary_key, $id)->first();
                    if ($row->{$di['name']} == '') {
                        $ai[] = 'required';
                    }
                }
            }

            if (@$di['min']) {
                $ai[] = 'min:' . $di['min'];
            }
            if (@$di['max']) {
                $ai[] = 'max:' . $di['max'];
            }
            if (@$di['image']) {
                $ai[] = 'image';
            }
            if (@$di['mimes']) {
                $ai[] = 'mimes:' . $di['mimes'];
            }
            $name = $di['name'];
            if (!$name) {
                continue;
            }

            if (isset($di['type']) && $di['type'] == 'money') {
                $request_all[$name] = preg_replace('/[^\d-]+/', '', $request_all[$name]);
            }

            if (isset($di['type']) && $di['type'] == 'child') {
                $slug_name = str_slug($di['label'], '');
                foreach ($di['columns'] as $child_col) {
                    if (isset($child_col['validation'])) {
                        //https://laracasts.com/discuss/channels/general-discussion/array-validation-is-not-working/
                        if (strpos($child_col['validation'], 'required') !== false) {
                            $array_input[$slug_name . '-' . $child_col['name']] = 'required';

                            str_replace('required', '', $child_col['validation']);
                        }

                        $array_input[$slug_name . '-' . $child_col['name'] . '.*'] = $child_col['validation'];
                    }
                }
            }

            if (@$di['validation']) {

                $exp = explode('|', $di['validation']);
                if ($exp && count($exp)) {
                    foreach ($exp as &$validationItem) {
                        if (substr($validationItem, 0, 6) == 'unique') {
                            $parseUnique = explode(',', str_replace('unique:', '', $validationItem));

                            $uniqueTable = ($parseUnique[0]) ?: $this->table;
                            $uniqueColumn = ($parseUnique[1]) ?: $name;
                            //$uniqueIgnoreId = isset($parseUnique[2]) ?: (($id) ?: '');
                            $uniqueIgnoreId = isset($parseUnique[2]) ? $parseUnique[2] : (($id) ?: '');


                            //Make sure table name
                            $uniqueTable = CB::parseSqlTable($uniqueTable)['table'];

                            //Rebuild unique rule
                            $uniqueRebuild = [];
                            $uniqueRebuild[] = $uniqueTable;
                            $uniqueRebuild[] = $uniqueColumn;
                            if ($uniqueIgnoreId) {
                                $uniqueRebuild[] = $uniqueIgnoreId;
                            } else {
                                $uniqueRebuild[] = 'NULL';
                            }

                            //Check whether deleted_at exists or not
                            if (CB::isColumnExists($uniqueTable, 'deleted_at')) {
                                $uniqueRebuild[] = CB::findPrimaryKey($uniqueTable);
                                $uniqueRebuild[] = 'deleted_at';
                                $uniqueRebuild[] = 'NULL';
                            }
                            $uniqueRebuild = array_filter($uniqueRebuild);
                            $validationItem = 'unique:' . implode(',', $uniqueRebuild);
                        }
                    }
                } else {
                    $exp = [];
                }

                $validation = implode('|', $exp);

                $array_input[$name] = $validation;
            } else {
                $array_input[$name] = implode('|', $ai);
            }
        }

        $validator = Validator::make($request_all, $array_input);

        if ($validator->fails()) {
            $message = $validator->messages();
            $message_all = $message->all();

            if (Request::ajax()) {
                $res = response()->json([
                    'message' => trans('crudbooster.alert_validation_error', ['error' => implode(', ', $message_all)]),
                    'message_type' => 'warning',
                ])->send();
                exit;
            } else {
                $res = redirect()->back()->with("errors", $message)->with([
                    'message' => trans('crudbooster.alert_validation_error', ['error' => implode(', ', $message_all)]),
                    'message_type' => 'warning',
                ])->withInput();
                \Session::driver()->save();
                $res->send();
                exit;
            }
        }
    }

    public function input_assignment($id = null)
    {

        $hide_form = (Request::get('hide_form')) ? unserialize(Request::get('hide_form')) : [];

        foreach ($this->data_inputan as $ro) {
            $name = $ro['name'];

            if (!$name) {
                continue;
            }

            if (isset($ro['exception'])) {
                continue;
            }

            if ($name == 'hide_form') {
                continue;
            }

            if ($hide_form && count($hide_form)) {
                if (in_array($name, $hide_form)) {
                    continue;
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'checkbox' && isset($ro['relationship_table']) && $ro['relationship_table']) {
                continue;
            }

            if (isset($ro['type']) && $ro['type']  == 'select2' && isset($ro['relationship_table']) && $ro['relationship_table']) {
                continue;
            }

            $inputdata = Request::get($name);

            if (isset($ro['type']) && $ro['type']  == 'money') {
                $inputdata = preg_replace('/[^\d-]+/', '', $inputdata);
            }

            if (isset($ro['type']) && $ro['type']  == 'child') {
                continue;
            }

            if ($name) {
                if ($inputdata != '') {
                    $this->arr[$name] = $inputdata;
                } else {
                    if (CB::isColumnNULL($this->table, $name) && isset($ro['type']) &&  $ro['type'] != 'upload') {
                        $this->arr[$name] = null;
                    } else {
                        $this->arr[$name] = "";
                    }
                }
            }

            $password_candidate = explode(',', config('crudbooster.PASSWORD_FIELDS_CANDIDATE'));
            if (in_array($name, $password_candidate)) {
                if (!empty($this->arr[$name])) {
                    $this->arr[$name] = Hash::make($this->arr[$name]);
                } else {
                    unset($this->arr[$name]);
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'checkbox') {


                if (is_array($inputdata)) {
                    if (isset($ro['datatable']) && $ro['datatable'] != '') {
                        $table_checkbox = explode(',', $ro['datatable'])[0];
                        $field_checkbox = explode(',', $ro['datatable'])[1];
                        $table_checkbox_pk = CB::pk($table_checkbox);
                        $data_checkbox = DB::table($table_checkbox)->whereIn($table_checkbox_pk, $inputdata)->pluck($field_checkbox)->toArray();
                        $this->arr[$name] = implode(";", $data_checkbox);
                    } else {
                        $this->arr[$name] = implode(";", $inputdata);
                    }
                }
            }

            //multitext colomn
            if (isset($ro['type']) && $ro['type']  == 'multitext') {
                $name = $ro['name'];
                $multitext = "";
                $maxI = ($this->arr[$name]) ? count($this->arr[$name]) : 0;
                for ($i = 0; $i <= $maxI - 1; $i++) {
                    $multitext .= $this->arr[$name][$i] . "|";
                }
                $multitext = substr($multitext, 0, strlen($multitext) - 1);
                $this->arr[$name] = $multitext;
            }

            if (isset($ro['type']) && $ro['type']  == 'googlemaps') {
                if ($ro['latitude'] && $ro['longitude']) {
                    $latitude_name = $ro['latitude'];
                    $longitude_name = $ro['longitude'];
                    $this->arr[$latitude_name] = Request::get('input-latitude-' . $name);
                    $this->arr[$longitude_name] = Request::get('input-longitude-' . $name);
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'select' || isset($ro['type']) && $ro['type'] == 'select2') {
                if (isset($ro['datatable'])) {
                    if ($inputdata == '') {
                        $this->arr[$name] = 0;
                    }
                }
            }

            if (@$ro['type'] == 'upload') {

                $this->arr[$name] = CRUDBooster::uploadFile($name, (isset($ro['encrypt']) ? $ro['encrypt'] : '')  || (isset($ro['upload_encrypt']) ? $ro['upload_encrypt'] : ''), (isset($ro['resize_width']) ? $ro['resize_width'] : ''), (isset($ro['resize_height']) ? $ro['resize_height'] : ''), CB::myId());

               
                if (!$this->arr[$name]) {
                    $this->arr[$name] = Request::get('_' . $name);
                }
            }

            if (@$ro['type'] == 'filemanager') {
                $filename = str_replace('/' . config('lfm.prefix') . '/' . config('lfm.files_folder_name') . '/', '', $this->arr[$name]);
                $url = 'storage/uploads/' . $filename;
                $this->arr[$name] = $url;
            }
        }
    }

    public function getAdd()
    {
        $this->cbLoader();
        if (!CRUDBooster::isCreate() && $this->global_privilege == false || $this->button_add == false) {
            CRUDBooster::insertLog(trans('crudbooster.log_try_add', ['module' => CRUDBooster::getCurrentModule()->name]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
        }

        $page_title = trans("crudbooster.add_data_page_title", ['module' => CRUDBooster::getCurrentModule()->name]);
        $page_menu = Route::getCurrentRoute()->getActionName();
        $command = 'add';

        $target_layout = \App\Menu::find(Request::get('m'));
        $target_layout = isset($target_layout) ? $target_layout->target_layout : 'default';

        return view('crudbooster::default.form', compact('page_title', 'page_menu', 'command', 'target_layout'));
    }

    public function postAddSave()
    {
        $this->cbLoader();
        if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
            CRUDBooster::insertLog(trans('crudbooster.log_try_add_save', [
                'name' => Request::input($this->title_field),
                'module' => CRUDBooster::getCurrentModule()->name,
            ]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
        }
        $request = $this->hook_before_validation();

        $this->validation(null, $request);
        $this->input_assignment();

        // #RAMA created_at should already be populated by sql
        if (Schema::hasColumn($this->table, 'created_at')) {
            $this->arr['created_at'] = date('Y-m-d H:i:s');
        }
        if (Schema::hasColumn($this->table, 'created_by')) {
            $this->arr['created_by'] = CRUDBooster::myId();
        }
        if (Schema::hasColumn($this->table, 'updated_by')) {
            $this->arr['updated_by'] = CRUDBooster::myId();
        }
        //save group e tenant se la tabella li prevede
        if (Schema::hasColumn($this->table, 'group')) {
            if (!empty($_POST['group'])) {
                //salva il valore ricevuto dal form
                $this->arr['group'] = $_POST['group'];
            } else {
                //salva il primary group dell'utente che ha salvato
                $this->arr['group'] = UserHelper::current_user_primary_group();
            }
        }
        if (Schema::hasColumn($this->table, 'tenant')) {
            if (!empty($_POST['tenant'])) {
                //salva il valore ricevuto dal form
                $this->arr['tenant'] = $_POST['tenant'];
            } else {
                //salva il tenant dell'utente che ha salvato
                $this->arr['tenant'] = UserHelper::current_user_tenant();
            }
        }

        $this->hook_before_add($this->arr);

        //$this->arr[$this->primary_key] = $id = CRUDBooster::newId($this->table); //error on sql server
        $lastInsertId = $id = DB::table($this->table)->insertGetId($this->arr);
        //$slug = DB::table($this->table)->where('slug', $this->arr['slug'])->first();
        //fix bug if primary key is uuid
        if (isset($this->arr[$this->primary_key]) && $this->arr[$this->primary_key] != $id) {
            $id = $this->arr[$this->primary_key];
        } else if (!isset($this->arr[$this->primary_key]) && isset($this->arr['slug'])) {
            $id = DB::table($this->table)->where('slug', $this->arr['slug'])->first()->id;
        }

        //Looping Data Input Again After Insert
        foreach ($this->data_inputan as $ro) {
            $name = $ro['name'];
            if (!$name) {
                continue;
            }

            $inputdata = Request::get($name);

            //Insert Data Checkbox if Type Datatable
            if (isset($ro['type']) && $ro['type']  == 'checkbox') {
                if (isset($ro['relationship_table'])) {
                    $datatable = explode(",", $ro['datatable'])[0];
                    $foreignKey2 = CRUDBooster::getForeignKey($datatable, $ro['relationship_table']);
                    $foreignKey = CRUDBooster::getForeignKey($this->table, $ro['relationship_table']);
                    DB::table($ro['relationship_table'])->where($foreignKey, $id)->delete();

                    if ($inputdata) {
                        $relationship_table_pk = CB::pk($ro['relationship_table']);
                        foreach ($inputdata as $input_id) {
                            DB::table($ro['relationship_table'])->insert([
                                //                                 $relationship_table_pk => CRUDBooster::newId($ro['relationship_table']),
                                $foreignKey => $id,
                                $foreignKey2 => $input_id,
                            ]);
                        }
                    }
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'select2') {
                if (isset($ro['relationship_table'])) {
                    $datatable = explode(",", $ro['datatable'])[0];
                    $foreignKey2 = CRUDBooster::getForeignKey($datatable, $ro['relationship_table']);
                    $foreignKey = CRUDBooster::getForeignKey($this->table, $ro['relationship_table']);
                    DB::table($ro['relationship_table'])->where($foreignKey, $id)->delete();
                    if ($inputdata) {
                        foreach ($inputdata as $input_id) {
                            $relationship_table_pk = CB::pk($ro['relationship_table']);
                            DB::table($ro['relationship_table'])->insert([
                                //                                 $relationship_table_pk => CRUDBooster::newId($ro['relationship_table']),
                                $foreignKey => $lastInsertId,
                                $foreignKey2 => $input_id,
                            ]);
                        }
                    }
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'child') {
                $name = str_slug($ro['label'], '');
                $columns = $ro['columns'];
                $getColName = Request::get($name . '-' . $columns[0]['name']);
                $count_input_data = ($getColName) ? (count($getColName) - 1) : 0;
                $child_array = [];
                $fk = $ro['foreign_key'];

                for ($i = 0; $i <= $count_input_data; $i++) {
                    $column_data = [];
                    foreach ($columns as $col) {
                        $colname = $col['name'];
                        $colvalue = Request::get($name . '-' . $colname)[$i];
                        if (!empty($colvalue)) 
                        { 
                            $column_data[$colname] = $colvalue; 
                        } /*else { 
                            $column_data[$colname] = null; 
                        }*/
                    }
                    if (!empty($column_data)) {
                        $column_data[$fk] = $id;
                        $child_array[] = $column_data;
                    }
                }

                $childtable = CRUDBooster::parseSqlTable($ro['table'])['table'];
                
                DB::table($childtable)->insert($child_array);
            }
        }

        $this->hook_after_add($lastInsertId);

        $this->return_url = ($this->return_url) ? $this->return_url : Request::get('return_url');

        //insert log
        CRUDBooster::insertLog(trans("crudbooster.log_add", ['name' => isset($this->arr[strtolower($this->title_field)]) ? $this->arr[strtolower($this->title_field)] : '', 'module' => CRUDBooster::getCurrentModule()->name]));

        if ($this->return_url) {
            if (Request::get('submit') == trans('crudbooster.button_save_more')) {
                CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans("crudbooster.alert_add_data_success"), 'success');
            } else {
                CRUDBooster::redirect($this->return_url, trans("crudbooster.alert_add_data_success"), 'success');
            }
        } else {
            if (Request::get('submit') == trans('crudbooster.button_save_more')) {
                CRUDBooster::redirect(CRUDBooster::mainpath('add'), trans("crudbooster.alert_add_data_success"), 'success');
            } else {
                CRUDBooster::redirect(CRUDBooster::mainpath(), trans("crudbooster.alert_add_data_success"), 'success');
            }
        }
    }

    public function getEdit($id)
    {
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        //kicks out if user shouldn't view the record $row
        if (!ModuleHelper::can_edit($this, $row)) {
            //log denied access
            CRUDBooster::insertLog(trans("crudbooster.log_try_add", [
                'name' => $module->{$this->title_field},
                'module' => CRUDBooster::getCurrentModule()->name
            ]));
            //kick out
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $page_menu = Route::getCurrentRoute()->getActionName();
        $page_title = trans("crudbooster.edit_data_page_title", ['module' => CRUDBooster::getCurrentModule()->name, 'name' => $row->{$this->title_field}]);
        $command = 'edit';
        Session::put('current_row_id', $id);

        //$target_layout = \App\Menu::find(Request::get('m'))->target_layout;
        $target_layout = \App\Menu::find(Request::get('m'));
        $target_layout = isset($target_layout) && isset($target_layout->target_layout) ? $target_layout->target_layout : 'default';

        return view('crudbooster::default.form', compact('id', 'row', 'page_menu', 'page_title', 'command', 'target_layout'));
    }

    public function postEditSave($id, $validate = null)
    {


        $module = CRUDBooster::getCurrentModule();


        if (!$validate) {
            $this->cbLoader();
        }

        $row = DB::table($this->table)->where($this->primary_key, $id)->first();
        //kicks out if user shouldn't edit the record $row
        if (!ModuleHelper::can_edit($this, $row)) {
            //log denied access
            CRUDBooster::insertLog(trans("crudbooster.log_try_add", [
                'name' => $module->{$this->title_field},
                'module' => CRUDBooster::getCurrentModule()->name
            ]));
            //kick out
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }


        if (!$validate) {
            $this->validation($id);
        }

        
        $this->input_assignment($id);

        if (Schema::hasColumn($this->table, 'updated_at')) {
            $this->arr['updated_at'] = date('Y-m-d H:i:s');
        }
        if (Schema::hasColumn($this->table, 'updated_by')) {
            $this->arr['updated_by'] = CRUDBooster::myId();
        }

        //save group e tenant se la tabella li prevede
        if (Schema::hasColumn($this->table, 'group') and !empty($_POST['group'])) {
            //salva il valore ricevuto dal form
            $this->arr['group'] = $_POST['group'];
        }
        if (Schema::hasColumn($this->table, 'tenant') and !empty($_POST['tenant'])) {
            //salva il valore ricevuto dal form
            $this->arr['tenant'] = $_POST['tenant'];
        }

        $this->hook_before_edit($this->arr, $id);



        DB::table($this->table)
            ->where($this->primary_key, $id)
            ->update($this->arr);

        //Looping Data Input Again After Insert
        foreach ($this->data_inputan as $ro) {
            $name = $ro['name'];
            if (!$name) {
                continue;
            }

            $inputdata = Request::get($name);

            //Insert Data Checkbox if Type Datatable
            if (isset($ro['type']) && $ro['type']  == 'checkbox') {
                if (isset($ro['relationship_table'])) {
                    $datatable = explode(",", $ro['datatable'])[0];

                    $foreignKey2 = CRUDBooster::getForeignKey($datatable, $ro['relationship_table']);
                    $foreignKey = CRUDBooster::getForeignKey($this->table, $ro['relationship_table']);
                    DB::table($ro['relationship_table'])->where($foreignKey, $id)->delete();

                    if ($inputdata) {
                        foreach ($inputdata as $input_id) {
                            $relationship_table_pk = CB::pk($ro['relationship_table']);
                            DB::table($ro['relationship_table'])->insert([
                                //$relationship_table_pk => CRUDBooster::newId($ro['relationship_table']),
                                $foreignKey => $id,
                                $foreignKey2 => $input_id,
                            ]);
                        }
                    }
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'select2') {
                //dd($ro);
                if (isset($ro['relationship_table']) && ((isset($ro["datatable_orig"]) && $ro["datatable_orig"] == "") || (!isset($ro["datatable_orig"])))) {
                    $datatable = explode(",", $ro['datatable'])[0];

                    $foreignKey2 = CRUDBooster::getForeignKey($datatable, $ro['relationship_table']);
                    $foreignKey = CRUDBooster::getForeignKey($this->table, $ro['relationship_table']);
                    DB::table($ro['relationship_table'])->where($foreignKey, $id)->delete();
                    //dd($inputdata);

                    if ($inputdata) {
                        foreach ($inputdata as $input_id) {
                            $relationship_table_pk = CB::pk($ro['relationship_table']);
                            DB::table($ro['relationship_table'])->insert([
                                //$relationship_table_pk => CRUDBooster::newId($ro['relationship_table']),
                                $foreignKey => $id,
                                $foreignKey2 => $input_id,
                            ]);
                        }
                    }
                }
                if (isset($ro['relationship_table']) && isset($ro["datatable_orig"]) &&  $ro["datatable_orig"] != "") {
                    $params = explode("|", $ro['datatable_orig']);
                    if (!isset($params[2])) $params[2] = "id";
                    DB::table($params[0])->where($params[2], $id)->update([$params[1] => implode(",", $inputdata)]);
                }
            }

            if (isset($ro['type']) && $ro['type']  == 'child') {
                $name = str_slug($ro['label'], '');
                $columns = $ro['columns'];
                $getColName = Request::get($name . '-' . $columns[0]['name']);
                $count_input_data = ($getColName) ? (count($getColName) - 1) : 0;
                $child_array = [];
                $childtable = CRUDBooster::parseSqlTable($ro['table'])['table'];
                $fk = $ro['foreign_key'];

                DB::table($childtable)->where($fk, $id)->delete();
                $lastId = CRUDBooster::newId($childtable);
                $childtablePK = CB::pk($childtable);
                //dd($column_data);
                for ($i = 0; $i <= $count_input_data; $i++) {
                    $column_data = [];
                    foreach ($columns as $col) {
                        $colname = $col['name'];
                        $colvalue = isset(Request::get($name . '-' . $colname)[$i]) ? Request::get($name . '-' . $colname)[$i] : null;
                        if (!empty($colvalue)) { 
                            $column_data[$colname] = $colvalue;
                        } else {
                            $column_data[$colname] = null;
                        }
                    }
                    if (!empty($column_data)) {
                        $column_data[$childtablePK] = $lastId;
                        $column_data[$fk] = $id;
                        $child_array[] = $column_data;
                        $lastId++;
                    }
                }
                $child_array = array_reverse($child_array);
                DB::table($childtable)->insert($child_array);
            }
        }

        $this->hook_after_edit($id);

        $this->return_url = ($this->return_url) ? $this->return_url : Request::get('return_url');

        //insert log
        $old_values = json_decode(json_encode($row), true);
        CRUDBooster::insertLog(trans("crudbooster.log_update", [
            'name' => isset($this->arr[$this->title_field]) ? $this->arr[$this->title_field] : '',
            'module' => CRUDBooster::getCurrentModule()->name,
        ]), LogsController::displayDiff($old_values, $this->arr));

        if (!$validate) {
            if ($this->return_url) {
                CRUDBooster::redirect($this->return_url, trans("crudbooster.alert_update_data_success"), 'success');
            } else {
                if (Request::get('submit') == trans('crudbooster.button_save_more')) {
                    CRUDBooster::redirect(CRUDBooster::mainpath('add'), trans("crudbooster.alert_update_data_success"), 'success');
                } else {
                    CRUDBooster::redirect(CRUDBooster::mainpath(), trans("crudbooster.alert_update_data_success"), 'success');
                }
            }
        } 


    }

    public function getDelete($id)
    {
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        //kicks out if user shouldn't view the record $row
        if (!ModuleHelper::can_delete($this, $row)) {
            //log denied access
            CRUDBooster::insertLog(trans("crudbooster.log_try_delete", [
                'name' => $module->{$this->title_field},
                'module' => CRUDBooster::getCurrentModule()->name
            ]));
            //kick out
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        //insert log
        CRUDBooster::insertLog(
            trans(
                "crudbooster.log_delete",
                [
                    'name' => $row->{strtolower($this->title_field)},
                    'module' => CRUDBooster::getCurrentModule()->name
                ]
            )
        );

        $this->hook_before_delete($id);

        if (CRUDBooster::isColumnExists($this->table, 'deleted_by')) {
            DB::table($this->table)->where($this->primary_key, $id)->update(['deleted_by' => CRUDBooster::myId()]);
        }
        if (CRUDBooster::isColumnExists($this->table, 'deleted_at')) {
            DB::table($this->table)->where($this->primary_key, $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        } else {
            DB::table($this->table)->where($this->primary_key, $id)->delete();
        }

        $this->hook_after_delete($id);

        $url = g('return_url') ?: CRUDBooster::referer();

        CRUDBooster::redirect($url, trans("crudbooster.alert_delete_data_success"), 'success');
    }

    public function getDetail($id)
    {
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        //kicks out if user shouldn't view the record $row
        if (!ModuleHelper::can_view($this, $row)) {
            //log denied access
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
                'name' => $this->table,
                'module' => CRUDBooster::getCurrentModule()->name,
            ]));
            //kick out
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $module = CRUDBooster::getCurrentModule();

        $page_menu = Route::getCurrentRoute()->getActionName();
        $tf = strtolower($this->title_field);
        $page_title = trans("crudbooster.detail_data_page_title", ['module' => $module->name, 'name' => $row->{$tf}]);
        $command = 'detail';

        Session::put('current_row_id', $id);

        $target_layout = \App\Menu::find(Request::get('m'));
        $target_layout = isset($target_layout) ? $target_layout->target_layout : 'default';

        return view('crudbooster::default.form', compact('row', 'page_menu', 'page_title', 'command', 'id', 'target_layout'));
    }

    /*
    public function getImportData()
    {
        $this->cbLoader();
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['page_title'] = 'Import Data ' . (isset($module->name) ? $module->name : '');

        if (Request::get('file') && !Request::get('import')) {
            $file = base64_decode(Request::get('file'));
            $file = storage_path('app/' . $file);
            $rows = Excel::load($file, function ($reader) {
            })->get();

            $countRows = ($rows) ? count($rows) : 0;

            Session::put('total_data_import', $countRows);

            $data_import_column = [];
            foreach ($rows as $value) {
                $a = [];
                foreach ($value as $k => $v) {
                    $a[] = $k;
                }
                if ($a && count($a)) {
                    $data_import_column = $a;
                }
                break;
            }

            $table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);

            $data['table_columns'] = $table_columns;
            $data['data_import_column'] = $data_import_column;
        }

        return view('crudbooster::import', $data);
    }
*/
    public function getImportData()
    {
        $this->cbLoader();
        $data['page_menu'] = Route::getCurrentRoute()->getActionMethod();
        $data['page_title'] = 'Import Data ' . (isset($this->module->name) ? $this->module->name : '');

        if (Request::has('file') && !Request::has('import')) {
            $file = base64_decode(Request::get('file'));
            $file = storage_path('app/public/' . $file);
            //dd(file_get_contents($file));
            //dd($file);

            //load data from $file
            //  
            //$data = Excel::toArray([], $file);
            //dd($data);

            //dd(Excel::import(new ImportData,$file));

            $rows = Excel::toCollection(new ImportData, $file)[0]; // Utilizza una classe di importazione personalizzata
            //dd($rows);

            $countRows = ($rows->count() > 0) ? $rows->first()->count() : 0;

            Session::put('total_data_import', $countRows);

            //$data_import_column = ($countRows > 0) ? $rows->first()->keys()->all() : [];
            $data_import_column = ($countRows > 0) ? $rows->first() : [];

            //file_put_contents(__DIR__.'/data_import_column.txt',json_encode($rows->first()));


            //file_put_contents(__DIR__.'/data_import_column.txt',json_encode($data_import_column));

            $table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);



            //file_put_contents(__DIR__.'/table_columns.txt',json_encode($table_columns));

            //file_put_contents(__DIR__.'/data_import_column.txt',json_encode($data_import_column));

            $data['table_columns'] = $table_columns;
            $data['data_import_column'] = $data_import_column;
        }

        return view('crudbooster::import', $data);
    }

    public function postDoneImport()
    {
        $this->cbLoader();
        $data['page_menu'] = Route::getCurrentRoute()->getActionName();
        $data['page_title'] = trans('crudbooster.import_page_title', ['module' => (isset($this->module->name) ? $this->module->name : '')]);
        Session::put('select_column', Request::get('select_column'));

        return view('crudbooster::import', $data);
    }

/*
    public function postDoImportChunk()
    {
        $this->cbLoader();
        $file_md5 = md5(Request::get('file'));

        if (Request::get('file') && Request::get('resume') == 1) {
            $total = Session::get('total_data_import');
            $prog = intval(Cache::get('success_' . $file_md5)) / $total * 100;
            $prog = round($prog, 2);
            if ($prog >= 100) {
                Cache::forget('success_' . $file_md5);
            }

            return response()->json(['progress' => $prog, 'last_error' => Cache::get('error_' . $file_md5)]);
        }

        $select_column = Session::get('select_column');
        $select_column = array_filter($select_column);
        $table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);

        $file = base64_decode(Request::get('file'));
        $file = storage_path('app/public/' . $file);

        $rows = Excel::toCollection(new ImportData, $file);
        file_put_contents(__DIR__.'/test.txt',json_encode($select_column));

 

        $has_created_at = false;
        if (CRUDBooster::isColumnExists($this->table, 'created_at')) {
            $has_created_at = true;
        }

        $data_import_column = [];
        foreach ($rows as $value) {
            $a = [];
            foreach ($select_column as $sk => $s) {
                $colname = $table_columns[$sk];

                if (CRUDBooster::isForeignKey($colname)) {

                    //Skip if value is empty
                    if ($value->$s == '') {
                        continue;
                    }

                    if (intval($value->$s)) {
                        $a[$colname] = $value->$s;
                    } else {
                        $relation_table = CRUDBooster::getTableForeignKey($colname);
                        $relation_moduls = DB::table('cms_moduls')->where('table_name', $relation_table)->first();

                        $relation_class = __NAMESPACE__ . '\\' . $relation_moduls->controller;
                        if (!class_exists($relation_class)) {
                            $relation_class = '\App\Http\Controllers\\' . $relation_moduls->controller;
                        }
                        $relation_class = new $relation_class;
                        $relation_class->cbLoader();

                        $title_field = $relation_class->title_field;

                        $relation_insert_data = [];
                        $relation_insert_data[$title_field] = $value->$s;

                        if (CRUDBooster::isColumnExists($relation_table, 'created_at')) {
                            $relation_insert_data['created_at'] = date('Y-m-d H:i:s');
                        }

                        try {
                            $relation_exists = DB::table($relation_table)->where($title_field, $value->$s)->first();
                            if ($relation_exists) {
                                $relation_primary_key = $relation_class->primary_key;
                                $relation_id = $relation_exists->$relation_primary_key;
                            } else {
                                $relation_id = DB::table($relation_table)->insertGetId($relation_insert_data);
                            }

                            $a[$colname] = $relation_id;
                        } catch (\Exception $e) {
                            exit($e);
                        }
                    } //END IS INT

                } else {
                    $a[$colname] = $value->$s;
                }
            }

            $has_title_field = true;
            foreach ($a as $k => $v) {
                if ($k == $this->title_field && $v == '') {
                    $has_title_field = false;
                    break;
                }
            }

            if ($has_title_field == false) {
                continue;
            }

            try {

                if ($has_created_at) {
                    $a['created_at'] = date('Y-m-d H:i:s');
                }

                DB::table($this->table)->insert($a);
                Cache::increment('success_' . $file_md5);
            } catch (\Exception $e) {
                $e = (string) $e;
                Cache::put('error_' . $file_md5, $e, 500);
            }
        }

        return response()->json(['status' => true]);
    }
*/
    public function postDoImportChunk()
    {
        $this->cbLoader();
        $file_md5 = md5(Request::get('file'));

        if (Request::get('file') && Request::get('resume') == 1) {
            $total = Session::get('total_data_import');
            $prog = intval(Cache::get('success_' . $file_md5)) / $total * 100;
            $prog = round($prog, 2);
            if ($prog >= 100) {
                Cache::forget('success_' . $file_md5);
            }

            return response()->json(['progress' => $prog, 'last_error' => Cache::get('error_' . $file_md5)]);
        }

        $select_column = Session::get('select_column');

        $type = gettype($select_column);
        //file_put_contents(__DIR__.'/sc.txt',$type."\n".json_encode($select_column)."\n", FILE_APPEND);

        // Filtra mantenendo lo "0"
        $select_column = array_filter($select_column, function($value) {
            return $value !== '' || $value === '0';
        });


        //$select_column = array_filter($select_column);

        $type = gettype($select_column);
        //file_put_contents(__DIR__.'/sc.txt',$type."\n".json_encode($select_column)."\n", FILE_APPEND);




        //$select_column = array_filter($select_column);
        $table_columns = DB::getSchemaBuilder()->getColumnListing($this->table);
        //file_put_contents(__DIR__.'/tc.txt',json_encode($table_columns)."\n", FILE_APPEND);
        //file_put_contents(__DIR__.'/sc.txt',json_encode($select_column)."\n", FILE_APPEND);

        $file = base64_decode(Request::get('file'));
        $file = storage_path('app/public/' . $file);
        //dd(file_get_contents($file));

        //file_put_contents(__DIR__.'/test.txt', file_get_contents($file));

        /*$rows = Excel::load($file, function ($reader) {
        })->get();*/
        $rows = Excel::toCollection(new ImportData, $file)[0];
        

        //remove first item from rows
        $rows->shift();
        //file_put_contents(__DIR__.'/rows.txt',json_encode($rows));

        //dd($rows);

        $has_created_at = false;
        if (CRUDBooster::isColumnExists($this->table, 'created_at')) {
            $has_created_at = true;
        }

        $data_import_column = [];
        foreach ($rows as $value) {
            $a = [];
            foreach ($select_column as $sk => $s) {
                /*if (!is_int($s)) {
                    file_put_contents(__DIR__.'/s.txt',$s."\n", FILE_APPEND);
                    continue;
                }*/
                //file_put_contents(__DIR__.'/sc.txt',$sk." - ".$s."\n", FILE_APPEND);
                $colname = $table_columns[$sk];
                //file_put_contents(__DIR__.'/colname.txt',$colname."\n", FILE_APPEND);



                if (CRUDBooster::isForeignKey($colname)) {

                    //file_put_contents(__DIR__.'/isForeignKey.txt',$colname."\n", FILE_APPEND);

                    //Skip if value is empty
                    if ($value[$s] == '') {
                        continue;
                    }

                    if (intval($value[$s])) {
                        $a[$colname] = $value[$s];
                    } else {
                        $relation_table = CRUDBooster::getTableForeignKey($colname);
                        //file_put_contents(__DIR__.'/isForeignKey.txt',$relation_table."\n", FILE_APPEND);
                        $relation_moduls = DB::table('cms_moduls')->where('table_name', $relation_table)->first();
                        //file_put_contents(__DIR__.'/isForeignKey.txt',json_encode($relation_moduls)."\n", FILE_APPEND);

                        $relation_class = __NAMESPACE__ . '\\' . $relation_moduls->controller;
                        if (!class_exists($relation_class)) {
                            $relation_class = '\App\Http\Controllers\\' . $relation_moduls->controller;
                        }
                        $relation_class = new $relation_class;
                        $relation_class->cbLoader();

                        $title_field = $relation_class->title_field;

                        $relation_insert_data = [];
                        $relation_insert_data[$title_field] = $value[$s];

                        if (CRUDBooster::isColumnExists($relation_table, 'created_at')) {
                            $relation_insert_data['created_at'] = date('Y-m-d H:i:s');
                        }

                        try {
                            $relation_exists = DB::table($relation_table)->where($title_field, $value[$s])->first();
                            //file_put_contents(__DIR__.'/relation_exists.txt',json_encode($relation_exists)."\n", FILE_APPEND);
                            if ($relation_exists) {
                                $relation_primary_key = $relation_class->primary_key;
                                $relation_id = $relation_exists->$relation_primary_key;
                            } else {
                                $relation_id = DB::table($relation_table)->insertGetId($relation_insert_data);
                            }
                            //file_put_contents(__DIR__.'/relation_id.txt',$relation_id."\n", FILE_APPEND);

                            $a[$colname] = $relation_id;
                        } catch (\Exception $e) {
                            exit($e);
                        }
                    } //END IS INT

                } else {
                    $a[$colname] = $value[$s];
                }
                //file_put_contents(__DIR__.'/a.txt',json_encode($a)."\n", FILE_APPEND);
            }

            $has_title_field = true;
            foreach ($a as $k => $v) {
                if ($k == $this->title_field && $v == '') {
                    $has_title_field = false;
                    //file_put_contents(__DIR__.'/htf.txt',$k."-".$v."\n", FILE_APPEND);
                    break;
                }
            }

            if ($has_title_field == false) {
                continue;
            }

            try {

                if ($has_created_at) {
                    $a['created_at'] = date('Y-m-d H:i:s');
                }

                //file_put_contents(__DIR__.'/test.txt',json_encode($a));

                DB::table($this->table)->insert($a);
                Cache::increment('success_' . $file_md5);
            } catch (\Exception $e) {
                $e = (string) $e;
                Cache::put('error_' . $file_md5, $e, 500);
            }
        }

        return response()->json(['status' => true]);
    }
    public function postDoUploadImportData()
    {
        $this->cbLoader();
        if (Request::hasFile('userfile')) {
            $file = Request::file('userfile');
            $ext = $file->getClientOriginalExtension();

            $validator = Validator::make([
                'extension' => $ext,
            ], [
                'extension' => 'in:xls,xlsx,csv',
            ]);

            if ($validator->fails()) {
                $message = $validator->errors()->all();

                return redirect()->back()->with(['message' => implode('<br/>', $message), 'message_type' => 'warning']);
            }

            //Create Directory Monthly
            $filePath = 'uploads/' . CB::myId() . '/' . date('Y-m');
            Storage::makeDirectory($filePath, 0777, true);

            //Move file to storage
            $filename = md5(str_random(5)) . '.' . $ext;
            $url_filename = '';
            if (Storage::putFileAs($filePath, $file, $filename)) {
                $url_filename = $filePath . '/' . $filename;
            }
            $url = CRUDBooster::mainpath('import-data') . '?file=' . base64_encode($url_filename);

            return redirect($url);
        } else {
            return redirect()->back();
        }
    }

    public function postActionSelected()
    {
        $this->cbLoader();
        $id_selected = Request::input('checkbox');
        $button_name = Request::input('button_name');

        if (!$id_selected) {
            CRUDBooster::redirect($_SERVER['HTTP_REFERER'], trans("crudbooster.alert_select_a_data"), 'warning');
        }

        if ($button_name == 'delete') {
            if (!CRUDBooster::isDelete()) {
                CRUDBooster::insertLog(trans("crudbooster.log_try_delete_selected", ['module' => CRUDBooster::getCurrentModule()->name]));
                CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
            }

            $this->hook_before_delete($id_selected);
            $tablePK = CB::pk($this->table);
            if (CRUDBooster::isColumnExists($this->table, 'deleted_at')) {

                DB::table($this->table)->whereIn($tablePK, $id_selected)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            } else {
                DB::table($this->table)->whereIn($tablePK, $id_selected)->delete();
            }
            CRUDBooster::insertLog(trans("crudbooster.log_delete", ['name' => implode(',', $id_selected), 'module' => CRUDBooster::getCurrentModule()->name]));

            $this->hook_after_delete($id_selected);

            $message = trans("crudbooster.alert_delete_selected_success");

            return redirect()->back()->with(['message_type' => 'success', 'message' => $message]);
        }

        $action = str_replace(['-', '_'], ' ', $button_name);
        $action = ucwords($action);
        $type = 'success';
        $message = trans("crudbooster.alert_action", ['action' => $action]);

        if ($this->actionButtonSelected($id_selected, $button_name) === false) {
            $message = !empty($this->alert['message']) ? $this->alert['message'] : 'Error';
            $type = !empty($this->alert['type']) ? $this->alert['type'] : 'danger';
        }

        return redirect()->back()->with(['message_type' => $type, 'message' => $message]);
    }

    public function getDeleteImage()
    {
        $this->cbLoader();
        $id = Request::get('id');
        $column = Request::get('column');

        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        if (!CRUDBooster::isDelete() && $this->global_privilege == false) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_delete_image", [
                'name' => $row->{$this->title_field},
                'module' => CRUDBooster::getCurrentModule()->name,
            ]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        $file = str_replace('/storage/uploads/', '', $row->{$column});
        if (Storage::exists($file)) {
            Storage::delete($file);
        }

        DB::table($this->table)->where($this->primary_key, $id)->update([$column => null]);

        //dd($row);

        CRUDBooster::insertLog(trans("crudbooster.log_delete_image", [
            'name' => $row->{$this->title_field},
            'module' => CRUDBooster::getCurrentModule()->name,
        ]));

        CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('crudbooster.alert_delete_data_success'), 'success');
    }

    public function postUploadSummernote()
    {
        $this->cbLoader();
        $name = 'userfile';
        if ($file = CRUDBooster::uploadFile($name, true)) {
            echo asset($file);
        }
    }

    public function postUploadFile()
    {
        $this->cbLoader();
        $name = 'userfile';
        if ($file = CRUDBooster::uploadFile($name, true)) {
            echo asset($file);
        }
    }


    //function for mass editing
    public function postMassEdit()
    {
        //dd($this->arr);
        //$module = CRUDBooster::getCurrentModule();
        $this->cbLoader();
        
        //dd($this);
        $id_selected = Request::input('ids');
        if (!$id_selected) {
            CRUDBooster::redirect($_SERVER['HTTP_REFERER'], trans("crudbooster.alert_select_a_data"), 'warning');
        }
        $table = Request::input('table');

        $changed = Request::all();
     
        $changed = array_filter($changed);

        $tablePK = CB::pk($table);

        $new_data_input = [];

        //dd($changed);
        //dd($this->form);
        foreach($this->form as $k => $v) {

            if (isset($changed["mass_edit_".$v['name']]) && is_array($changed["mass_edit_".$v['name']])) {
                if (isset($changed["mass_edit_".$v['name']][0])  && $changed["mass_edit_".$v['name']][0] == 'on') {
                    $new_data_input[] = $v;
                }
            } else {


                if (isset($changed["mass_edit_".$v['name']])  && $changed["mass_edit_".$v['name']] == 'on') {
                    $new_data_input[] = $v;
                }
            }
        }
        $this->data_inputan = $new_data_input;
        $new_data_input = [];
        foreach($this->data_inputan as $k => $v) {
            if (isset($changed["mass_edit_".$v['name']]) && is_array($changed["mass_edit_".$v['name']])) {
                if (isset($changed["mass_edit_".$v['name']][0])  && $changed["mass_edit_".$v['name']][0] == 'on') {
                    $new_data_input[] = $v;
                }
            } else {


                if (isset($changed["mass_edit_".$v['name']])  && $changed["mass_edit_".$v['name']] == 'on') {
                    $new_data_input[] = $v;
                }
            }
        }

        
        
        $this->form = $new_data_input;
        //dd($this->data_inputan);
        //dd($this->form);
        foreach ($id_selected as $k => $id) {

            $this->postEditSave($id, 'validate');

        }


        if ($this->return_url) {
            CRUDBooster::redirect($this->return_url, trans("crudbooster.alert_update_data_success"), 'success');
        } else {
            if (Request::get('submit') == trans('crudbooster.button_save_more')) {
                CRUDBooster::redirect(CRUDBooster::mainpath('add'), trans("crudbooster.alert_update_data_success"), 'success');
            } else {
                CRUDBooster::redirect(CRUDBooster::mainpath(), trans("crudbooster.alert_update_data_success"), 'success');
            }
        }



    }




    public function actionButtonSelected($id_selected, $button_name)
    {
    }

    public function hook_query_index(&$query)
    {
    }

    public function hook_row_index($index, &$value)
    {
    }

    public function hook_before_add(&$arr)
    {
    }

    public function hook_before_validation()
    {
    }

    public function hook_after_add($id)
    {
    }

    public function hook_before_edit(&$arr, $id)
    {
    }

    public function hook_after_edit($id)
    {
    }

    public function hook_before_delete($id)
    {
    }

    public function hook_after_delete($id)
    {
    }
}
