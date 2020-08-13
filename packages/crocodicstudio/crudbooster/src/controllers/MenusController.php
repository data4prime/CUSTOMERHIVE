<?php namespace crocodicstudio\crudbooster\controllers;

use CRUDBooster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use crocodicstudio\crudbooster\fonts\Fontawesome;
use \crocodicstudio\crudbooster\helpers\UserHelper;
use \App\Menu;

class MenusController extends CBController
{
    public function cbInit()
    {
        $this->table = "cms_menus";
        $this->primary_key = "id";
        $this->title_field = "name";
        $this->limit = 20;
        $this->orderby = ["id" => "desc"];

        $this->button_table_action = true;
        $this->button_action_style = "FALSE";
        $this->button_add = false;
        $this->button_delete = true;
        $this->button_edit = true;
        $this->button_detail = true;
        $this->button_show = false;
        $this->button_filter = true;
        $this->button_export = false;
        $this->button_import = false;

        $id = CRUDBooster::getCurrentId();
        if (Request::segment(3) == 'edit') {
            $id = Request::segment(4);
            Session::put('current_row_id', $id);
        }
        $row = CRUDBooster::first($this->table, $id);
        $row = (Request::segment(3) == 'edit') ? $row : null;

        //id da preselezionare nelle dropdown del form dopo la scelta del type della voce di menu
        $id_module = $id_statistic = $id_qlik_item = 0;

        if ($row->type == 'Module') {
            $m = CRUDBooster::first('cms_moduls', ['path' => $row->path]);
            $id_module = $m->id;
        } elseif ($row->type == 'Statistic') {
            $row->path = str_replace('statistic_builder/show/', '', $row->path);
            $m = CRUDBooster::first('cms_statistics', ['slug' => $row->path]);
            $id_statistic = $m->id;
        } elseif ($row->type == 'Qlik') {
            //ricava id del qlik item a cui fa riferimento questa voce di menu dal path cioè l'href della voce di menu
            $id_qlik_item = str_replace('qlik_items/content/', '', $row->path);
        }

        $this->script_js = "
  			$( document ).ready(function() {
  				var current_id = '$id';
  				var current_type = '$row->type';
  				var type_menu = $('input[name=type][checked]').val();
  				type_menu = (current_type)?current_type:type_menu;
  				console.log(type_menu);
  				if(type_menu == 'Module') {
  					$('#form-group-module_slug').show();
  					$('#qlik_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#form-group-statistic_slug,#form-group-path').hide();
  					$('#form-group-qlik_slug,#form-group-path').hide();
  					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					$('#module_slug').prop('required',true);
  					$('#form-group-module_slug label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');
  				}else if(type_menu == 'Statistic') {
  					$('#form-group-statistic_slug').show();
  					$('#module_slug').prop('required',false);
  					$('#qlik_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-path').hide();
  					$('#form-group-qlik_slug,#form-group-path').hide();
  					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					$('#statistic_slug').prop('required',true);
  					$('#form-group-statistic_slug label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');
  				}else if(type_menu == 'Qlik') {
  					$('#form-group-qlik_slug').show();
  					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').show();
  					$('#module_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-path').hide();
  					$('#form-group-statistic_slug,#form-group-path').hide();
  					$('#qlik_slug').prop('required',true);
  					$('#form-group-qlik_slug label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');
  				}else{
  					$('#module_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#qlik_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
  					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					$('#form-group-path').show();
  				}

  				function format(icon) {
  	                  var originalOption = icon.element;
  	                  var label = $(originalOption).text();
  	                  var val = $(originalOption).val();
  	                  if(!val) return label;
  	                  var \$resp = $('<span><i style=\"margin-top:5px\" class=\"pull-right ' + $(originalOption).val() + '\"></i> ' + $(originalOption).data('label') + '</span>');
  	                  return \$resp;
  	              }
  	              $('#list-icon').select2({
  	                  width: \"100%\",
  	                  templateResult: format,
  	                  templateSelection: format
  	              });

  				$('input[name=type]').change(function() {
  					var default_placeholder_path = 'NameController@methodName';
  					var n = $(this).val();
  					var isCheck = $(this).prop('checked');
  					console.log('Click the module type '+n);
  					$('#module_slug').prop('required',false);
  					$('input[name=path]').attr('placeholder',default_placeholder_path);
  					if(n == 'Module') {
  						$('#form-group-path').hide();
  						$('#form-group-statistic_slug').hide();
  						$('#form-group-qlik_slug').hide();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  						$('#statistic_slug,#path').prop('required',false);
  						$('#qlik_slug,#path').prop('required',false);

  						$('#form-group-module_slug').show();
  						$('#module_slug').prop('required',true);
  						$('#form-group-module_slug label .text-danger').remove();
  					}else if (n == 'Statistic') {
  						$('#form-group-path').hide();
  						$('#form-group-module_slug').hide();
  						$('#form-group-qlik_slug').hide();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  						$('#module_slug,#path').prop('required',false);
  						$('#qlik_slug,#path').prop('required',false);

  						$('#form-group-statistic_slug').show();
  						$('#statistic_slug').prop('required',true);
  						$('#form-group-statistic_slug label .text-danger').remove();
  						$('#form-group-statistic_slug label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');
  					}else if (n == 'Qlik') {
  						$('#form-group-path').hide();
  						$('#form-group-module_slug').hide();
  						$('#form-group-statistic_slug').hide();
  						$('#module_slug,#path').prop('required',false);
  						$('#statistic_slug,#path').prop('required',false);

  						$('#form-group-qlik_slug').show();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').show();
  						$('#qlik_slug').prop('required',true);
  						$('#form-group-qlik_slug label .text-danger').remove();
  						$('#form-group-qlik_slug label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');
  					}else if (n == 'URL') {
  						$('input[name=path]').attr('placeholder','Please enter your URL');

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					}else if (n == 'Route') {
  						$('input[name=path]').attr('placeholder','Please enter the Route');

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					}else {
  						$('#module_slug,#statistic_slug,#qlik_slug').prop('required',false);

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"".trans('crudbooster.this_field_is_required')."\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_full_page,#form-group-frame_width,#form-group-frame_height,#form-group-frame_full_screen').hide();
  					}
  				})
  			})

        /**
        * Frame width and height script
        * fill content, full screen
        */

        function checkFullPage(){
          if(
            $('#frame_width').val()=='100' &&
            $('#frame_height').val()=='100' &&
            $('#frame_width_unit').children('option:selected')[0].label == '%' &&
            $('#frame_height_unit').children('option:selected')[0].label == '%'
          ){
            $('input[name^=frame_full_page]').prop('checked', true);
          }
          else{
            $('input[name^=frame_full_page]').prop('checked', false);
          }
        }

        function setFullPage(){
          console.log();
          if($('input[name^=frame_full_page]').prop('checked')){
            $('#frame_width').val(100);
            $('#frame_width_unit option').filter(function() {
                return ($(this).text() == '%');
            }).prop('selected', true);
            $('#frame_height').val(100);
            $('#frame_height_unit option').filter(function() {
                return ($(this).text() == '%');
            }).prop('selected', true);
          }
        }

        $(function() {
          //set iframe size
          // $('.qi_iframe').css('height', $(window).height()+'px').css('width', '100%');

          // sposta scelta unità di misura della width a fianco della dimensione
          $('#form-group-frame_width_unit label').remove();
          var unit_select = $('#form-group-frame_width_unit').html();
          $('#form-group-frame_width').append(unit_select);
          var unit_select = $('#form-group-frame_width_unit').remove();

          // sposta scelta unità di misura della height a fianco della dimensione
          $('#form-group-frame_height_unit label').remove();
          var unit_select = $('#form-group-frame_height_unit').html();
          $('#form-group-frame_height').append(unit_select);
          var unit_select = $('#form-group-frame_height_unit').remove();

          //metti spunta automatica su checkbox se width 100% e height 100%
          checkFullPage();
          $('#frame_width').change(function () {checkFullPage()});
          $('#frame_width_unit').change(function () {checkFullPage()});
          $('#frame_height').change(function () {checkFullPage()});
          $('#frame_height_unit').change(function () {checkFullPage()});
          //setta 100% 100% se spunti Full Page checkbox
          $('input[name^=frame_full_page]').change(function () {setFullPage()});

        });
  			";

        $this->col = [];
        $this->col[] = ["label" => "Name", "name" => "name"];
        $this->col[] = ["label" => "Is Active", "name" => "is_active"];
        $this->col[] = ["label" => "Privileges", "name" => "id_cms_privileges", "join" => "cms_privileges,name"];
				$this->col[] = ["label"=>"Width","name"=>"frame_width"];
				$this->col[] = ["label"=>"Height","name"=>"frame_height"];

        $this->form = [];
        $this->form[] = [
            "label" => "Privileges",
            "name" => "cms_menus_privileges",
            "type" => "select2",
            "select2_multiple" => true,
            "datatable" => "cms_privileges,name",
            "relationship_table" => "cms_menus_privileges",
            "required" => true,
        ];
        $this->form[] = [
            "label" => "Name",
            "name" => "name",
            "type" => "text",
            "required" => true,
            "validation" => "required|min:3|max:255"
        ];
        $this->form[] = [
            "label" => "Type",
            "name" => "type",
            "type" => "radio",
            "required" => true,
            'dataenum' => ['Module', 'Qlik', 'Statistic', 'URL', 'Controller & Method', 'Route'],
            'value' => 'Module',
        ];

        //only superadmin can edit tenant
        if(CRUDBooster::isSuperadmin())
        {
          $this->form[] = [
            'label'=>'Tenant',
            'name'=>'tenant',
            "type"=>"select2",
            "datatable"=>"tenants,name",
            'required'=>true,
            'validation'=>'required|int|min:1',
            'value'=>UserHelper::current_user_tenant()//default value per creazione nuovo record
          ];
        }
        elseif(UserHelper::isAdvanced())
    		{
    			//advanced vede tenant in readonly (disabled) ma può modificare il group
    			$this->form[] = [
    				"label"=>"Tenant",
    				"name"=>"tenant",
    				'required'=>true,
    				'type'=>'select',
    				'datatable'=>"tenants,name",
    				'default'=>UserHelper::current_user_tenant_name(),
    				'disabled'=>true
    			];
    			//aggiungo un campo tenant hidden perchè con la tenant select disabled viene salvato uno 0
    			$this->form[] = [
    				"label"=>"Tenant",
    				"name"=>"tenant",
    				'type'=>'hidden'
    			];
        }
        //only superadmin and advanced can see group
        if((UserHelper::isAdvanced() OR CRUDBooster::isSuperadmin()))
        {
          if(CRUDBooster::isSuperadmin())
          {
            //superadmin vede i gruppi come cascading dropdown in base al tenant
            $this->form[] = [
              'label'=>'Group',
              'name'=>'group',
              "type"=>"select",
              "datatable"=>"groups,name",
              'required'=>true,
              'validation'=>'required|int|min:1',
              'value'=>UserHelper::current_user_primary_group(),//default value per creazione nuovo record
              'parent_select'=>'tenant'
            ];
            // $field = ['label'=>'Group','name'=>'group',"type"=>"select","datatable"=>"groups,name",'required'=>true,'validation'=>'required|int|min:1','default'=>UserHelper::current_user_primary_group_name(),'value'=>UserHelper::current_user_primary_group(),'parent_select'=>'tenant'];
          }
          else
          {
            //Advanced vede solo i gruppi del proprio tenant
            $this->form[] = [
              'label'=>'Group',
              'name'=>'group',
              "type"=>"select2",
              "datatable"=>"groups,name",
              'required'=>true,
              'validation'=>'required|int|min:1',
              'default'=>UserHelper::current_user_primary_group_name(),
              'value'=>UserHelper::current_user_primary_group(),
              //advanced vede nella dropdown solo i gruppi del proprio tenant
              'datatable_where'=>'tenant = '.UserHelper::current_user_tenant()
            ];
          }
        }
    		// $this->form[] = array("label"=>"Group","name"=>"group",'required'=>true,'type'=>'select','datatable'=>"groups,name",'validation'=>'required','default'=>'');

        $this->form[] = [
            "label" => "Module",
            "name" => "module_slug",
            "type" => "select",
            "datatable" => "cms_moduls,name",
            "datatable_where" => "is_protected = 0",
            "value" => $id_module,
        ];
        $this->form[] = [
            "label" => "Statistic",
            "name" => "statistic_slug",
            "type" => "select",
            "datatable" => "cms_statistics,name",
            "style" => "display:none",
            "value" => $id_statistic,
        ];

        $this->form[] = [
            "label" => "Qlik",
            "name" => "qlik_slug",
            "type" => "select",
            "datatable" => "qlik_items,title",
            "default" => "** Select a Qlik Item",
            // "datatable_where" => "is_protected = 0",
            "value" => $id_qlik_item,
        ];

        $this->form[] = [
            "label" => "Value",
            "name" => "path",
            "type" => "text",
            'help' => 'If you select type controller, you can fill this field with controller name, you may include the method also',
            'placeholder' => 'NameController or NameController@methodName',
            "style" => "display:none",
        ];

        $fontawesome = Fontawesome::getIcons();

        $custom = view('crudbooster::components.list_icon', compact('fontawesome', 'row'))->render();
        $this->form[] = ['label' => 'Icon', 'name' => 'icon', 'type' => 'custom', 'html' => $custom, 'required' => true];
        $this->form[] = [
            'label' => 'Color',
            'name' => 'color',
            'type' => 'select2',
            'dataenum' => ['normal', 'red', 'green', 'aqua', 'light-blue', 'red', 'yellow', 'muted'],
            'required' => true,
            'value' => 'normal',
        ];
        $this->form[] = [
            "label" => "Active",
            "name" => "is_active",
            "type" => "radio",
            "required" => true,
            "validation" => "required|integer",
            "dataenum" => ['1|Active', '0|InActive'],
            'value' => '1',
        ];
        $this->form[] = [
            "label" => "Dashboard",
            "name" => "is_dashboard",
            "type" => "radio",
            "required" => true,
            "validation" => "required|integer",
            "dataenum" => ['1|Yes', '0|No'],
            'value' => '0',
        ];
        $this->form[] = [
            "label" => "Open in a new tab",
            "name" => "new_tab",
            "type" => "radio",
            "required" => true,
            "validation" => "required|integer",
            "dataenum" => ['1|Yes', '0|No'],
            'value' => '0',
        ];
        $this->form[] = ['label'=>'Full Screen','name'=>'frame_full_screen','type'=>'checkbox','width'=>'col-sm-1'];
				$this->form[] = ['label'=>'Fill Content','name'=>'frame_full_page','type'=>'checkbox','width'=>'col-sm-1'];
				$this->form[] = ['label'=>'Width','name'=>'frame_width','type'=>'number','validation'=>'required|int|min:1|max:10000','width'=>'col-sm-2','value'=>'100'];
				$this->form[] = ['label'=>'','name'=>'frame_width_unit','type'=>'select','validation'=>'','width'=>'col-sm-2','dataenum'=>'px','default'=>'%'];
				$this->form[] = ['label'=>'Height','name'=>'frame_height','type'=>'number','validation'=>'required|int|min:1|max:10000','width'=>'col-sm-2','value'=>'100'];
				$this->form[] = ['label'=>'','name'=>'frame_height_unit','type'=>'select','validation'=>'','width'=>'col-sm-2','dataenum'=>'px','default'=>'%'];

        $id_cms_privileges = Request::get('id_cms_privileges');
        $this->form[] = ["label" => "id_cms_privileges", "name" => "id_cms_privileges", "type" => "hidden", "value" => $id_cms_privileges];
    }

    public function getIndex()
    {
        $this->cbLoader();

        $module = CRUDBooster::getCurrentModule();
        if (! CRUDBooster::isView() && $this->global_privilege == false) {
            CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $privileges = DB::table('cms_privileges')->get();

        $id_cms_privileges = Request::get('id_cms_privileges');
        $id_cms_privileges = ($id_cms_privileges) ?: CRUDBooster::myPrivilegeId();

        $menu_active = DB::table('cms_menus')
                            ->where('parent_id', 0)
                            ->where('is_active', 1)
                            ->orderby('sorting', 'asc')
                            ->get();

        if(!CRUDBooster::isSuperadmin())
        {
          //tenant admin vede nella lista solo le voci di menu del proprio tenant
          $menu_active = $menu_active->where('tenant',UserHelper::current_user_tenant());
        }

        foreach ($menu_active as &$menu) {
            $child = DB::table('cms_menus')
                        ->where('is_active', 1)
                        ->where('parent_id', $menu->id)
                        ->orderby('sorting', 'asc')
                        ->get();

            if (count($child)) {
                $menu->children = $child;
            }
        }

        $menu_inactive = DB::table('cms_menus')
                            ->where('parent_id', 0)
                            ->where('is_active', 0)
                            ->orderby('sorting', 'asc')
                            ->get();

        foreach ($menu_inactive as &$menu) {
            $child = DB::table('cms_menus')
                        ->where('is_active', 1)
                        ->where('parent_id', $menu->id)
                        ->orderby('sorting', 'asc')
                        ->get();

            if (count($child)) {
                $menu->children = $child;
            }
        }

        $return_url = Request::fullUrl();

        $page_title = 'Menu Management';

        return view('crudbooster::menus_management', compact('menu_active', 'menu_inactive', 'privileges', 'id_cms_privileges', 'return_url', 'page_title'));
    }

    public function customEdit($id)
    {
				//load edit page
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        //TODO in altri moduli qui usa isRead anzichè isUpdate
        //forse potrei controllare qui solo isread per vedere i dettagli della voce di menu
        //ma il form è in readonly se non ha isupdate
        if (
            !CRUDBooster::isSuperadmin() AND
            !(
              CRUDBooster::isUpdate() AND
              $row->tenant == UserHelper::current_user_tenant()
            )
          ) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
                'name' => $row->{$this->title_field},
                'module' => CRUDBooster::getCurrentModule()->name,
            ]));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        return view('crudbooster::menus.form', compact('row', 'id'));
    }

    public function hook_before_add(&$postdata)
    {
        if (! $postdata['id_cms_privileges']) {
            $postdata['id_cms_privileges'] = CRUDBooster::myPrivilegeId();
        }
        $postdata['parent_id'] = 0;

        if ($postdata['type'] == 'Statistic') {
            $stat = CRUDBooster::first('cms_statistics', ['id' => $postdata['statistic_slug']]);
            $postdata['path'] = 'statistic_builder/show/'.$stat->slug;
        } elseif ($postdata['type'] == 'Module') {
            $stat = CRUDBooster::first('cms_moduls', ['id' => $postdata['module_slug']]);
            $postdata['path'] = $stat->path;
        } elseif ($postdata['type'] == 'Qlik') {
            $stat = CRUDBooster::first('qlik_items', ['id' => $postdata['qlik_slug']]);
            $postdata['path'] = 'qlik_items/content/'.$postdata['qlik_slug'].'?m='.$id;
        }

        unset($postdata['module_slug']);
        unset($postdata['statistic_slug']);
        unset($postdata['qlik_slug']);
        //frame width and height data
        $postdata['frame_width'] .= $postdata['frame_width_unit'];
        $postdata['frame_height'] .= $postdata['frame_height_unit'];
        unset($postdata['frame_full_page']);
        unset($postdata['frame_width_unit']);
        unset($postdata['frame_height_unit']);

        if ($postdata['is_dashboard'] == 1) {
            //If set dashboard, so unset for first all dashboard
            // DB::table('cms_menus')
            //     ->where('id_cms_privileges', $postdata['id_cms_privileges'])
            //     ->where('is_dashboard', 1)
            //     ->update(['is_dashboard' => 0]);
            Cache::forget('sidebarDashboard'.CRUDBooster::myPrivilegeId());
        }
    }

    public function hook_before_edit(&$postdata, $id)
    {
      //solo superadmin o tenant admin con permesso di update su un menu del proprio tenant
      //possono modificare il menu
      if(
          !CRUDBooster::isSuperadmin() AND
          !(
            CRUDBooster::isUpdate() AND
            Menu::find($id)->tenant == UserHelper::current_user_tenant()
          )
        ) {
          CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
              'name' => $id,
              'module' => CRUDBooster::getCurrentModule()->name,
          ]));
          CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
      }
      //frame width and height data
        $postdata['frame_width'] .= $postdata['frame_width_unit'];
        $postdata['frame_height'] .= $postdata['frame_height_unit'];
        unset($postdata['frame_full_page']);
        unset($postdata['frame_width_unit']);
        unset($postdata['frame_height_unit']);

        if ($postdata['is_dashboard'] == 1) {
            //If set dashboard, so unset for first all dashboard
            //DB::table('cms_menus')->where('id_cms_privileges', $postdata['id_cms_privileges'])->where('is_dashboard', 1)->update(['is_dashboard' => 0]);
            Cache::forget('sidebarDashboard'.CRUDBooster::myPrivilegeId());
        }

        if ($postdata['type'] == 'Statistic') {
            $stat = CRUDBooster::first('cms_statistics', ['id' => $postdata['statistic_slug']]);
            $postdata['path'] = 'statistic_builder/show/'.$stat->slug;
        } elseif ($postdata['type'] == 'Module') {
            $stat = CRUDBooster::first('cms_moduls', ['id' => $postdata['module_slug']]);
            $postdata['path'] = $stat->path;
        } elseif ($postdata['type'] == 'Qlik') {
            $stat = CRUDBooster::first('qlik_items', ['id' => $postdata['qlik_slug']]);
            $postdata['path'] = 'qlik_items/content/'.$postdata['qlik_slug'].'?m='.$id;
        }

        unset($postdata['module_slug']);
        unset($postdata['statistic_slug']);
        unset($postdata['qlik_slug']);
    }


    public function hook_before_delete($id)
    {
      //solo superadmin o tenant admin con permesso di delete su un menu del proprio tenant
      //possono eliminare il menu
      if(
          !CRUDBooster::isSuperadmin() AND
          !(
            CRUDBooster::isDelete() AND
            Menu::find($id)->tenant == UserHelper::current_user_tenant()
          )
        ) {
          CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
              'name' => $id,
              'module' => CRUDBooster::getCurrentModule()->name,
          ]));
          CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
      }
    }

    public function hook_after_delete($id)
    {
      DB::table('cms_menus_privileges')->where('id_cms_menus', $id)->delete();
      DB::table('cms_menus')->where('parent_id', $id)->delete();
    }

    public function postSaveMenu()
    {
        $post = Request::input('menus');
        $isActive = Request::input('isActive');
        $post = json_decode($post, true);

        $i = 1;
        foreach ($post[0] as $ro) {
            $pid = $ro['id'];
            if ($ro['children'][0]) {
                $ci = 1;
                foreach ($ro['children'][0] as $c) {
                    $id = $c['id'];
                    DB::table('cms_menus')->where('id', $id)->update(['sorting' => $ci, 'parent_id' => $pid, 'is_active' => $isActive]);
                    $ci++;
                }
            }
            DB::table('cms_menus')->where('id', $pid)->update(['sorting' => $i, 'parent_id' => 0, 'is_active' => $isActive]);
            $i++;
        }

        return response()->json(['success' => true]);
    }
}
