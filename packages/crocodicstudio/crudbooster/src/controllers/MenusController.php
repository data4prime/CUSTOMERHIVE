<?php

namespace crocodicstudio\crudbooster\controllers;

//use CRUDBooster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use \crocodicstudio\crudbooster\helpers\CRUDBooster;
use crocodicstudio\crudbooster\fonts\Fontawesome;
use \crocodicstudio\crudbooster\helpers\UserHelper;
use \crocodicstudio\crudbooster\helpers\MenuHelper;
use \App\Menu;
use \App\MenuTenants;


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
    $id_module = $id_statistic = $id_qlik_item = $id_chat_ai = 0;

    //dd($row);
    if (isset($row) && isset($row->type) && $row->type == 'Module') {
      $m = CRUDBooster::first('cms_moduls', ['path' => $row->path]);
      //$id_module = $m->id;
    } elseif (isset($row) && isset($row->type) && $row->type == 'Statistic') {
      $row->path = str_replace('statistic_builder/show/', '', $row->path);
      $m = CRUDBooster::first('cms_statistics', ['slug' => $row->path]);
      $id_statistic = isset($m->id) ? $m->id : 0;
    } elseif (isset($row) && isset($row->type) && $row->type == 'Qlik') {
      //ricava id del qlik item a cui fa riferimento questa voce di menu dal path cioè l'href della voce di menu
      $id_qlik_item = str_replace('qlik_items/content/', '', $row->path);
    } elseif (isset($row) && isset($row->type) && $row->type == 'Agent AI') {
      //ricava id del qlik item a cui fa riferimento questa voce di menu dal path cioè l'href della voce di menu
      $id_chat_ai = str_replace('chat_ai/content/', '', $row->path);
    }

    if (isset($row) && isset($row->type)) {
      $type_for_js = $row->type;
    } else {
      $type_for_js = '';
    }

    $this->script_js = "
  			$( document ).ready(function()
        {
  				var current_id = " . $id . ";
  				var current_type = '" . $type_for_js . "';
  				var type_menu = $('input[name=type][checked]').val();
  				type_menu = (current_type)?current_type:type_menu;

          //if is_custom is checked, show icon upload and hide fontawesome select
          if($('input[name=is_custom]:checked').val() == 1)
          {
            $('#form-group-icon').hide();
            $('#form-group-icon_upload').show();
          }else {
            $('#form-group-icon').show();
            $('#form-group-icon_upload').hide();
          }


  				if(type_menu == 'Module') {
  					$('#form-group-module_slug').show();
  					$('#qlik_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#form-group-statistic_slug,#form-group-path').hide();
  					$('#form-group-qlik_slug,#form-group-path').hide();
  					$('#form-group-frame_width,#form-group-frame_height').hide();
            $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					$('#module_slug').prop('required',true);
  					$('#form-group-module_slug label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
  				}
          else if(type_menu == 'Statistic')
          {
  					$('#form-group-statistic_slug').show();
  					$('#module_slug').prop('required',false);
  					$('#qlik_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-path').hide();
  					$('#form-group-qlik_slug,#form-group-path').hide();
  					$('#form-group-frame_width,#form-group-frame_height').hide();
            $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					$('#statistic_slug').prop('required',true);
  					$('#form-group-statistic_slug label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
  				}
          else if(type_menu == 'Qlik')
          {
  					$('#form-group-qlik_slug').show();
  					$('#form-group-frame_width,#form-group-frame_height').show();
  					$('#module_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-path').hide();
  					$('#form-group-statistic_slug,#form-group-path').hide();
            $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					$('#qlik_slug').prop('required',true);
  					$('#form-group-qlik_slug label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
  				}else  if(type_menu == 'Agent AI')
          {
  					$('#form-group-chat_ai').show();
  					$('#form-group-frame_width,#form-group-frame_height').show();
  					$('#module_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-path').hide();
  					$('#form-group-statistic_slug,#form-group-path').hide();
              $('#form-group-qlik_slug').hide();
              $('#qlik_slug,#path').prop('required',false);

            $('#form-group-is_dashboard').hide();
            $('#is_dashboard,#path').prop('required',false);

            $('#form-group-target_layout').hide();
            $('#target_layout,#path').prop('required',false);

            $('#form-group-target_layout').hide();
            $('#target_layout,#path').prop('required',false);

            $('#form-group-frame_width').hide();
            $('#frame_width,#path').prop('required',false);

            $('#form-group-frame_height').hide();
            $('#frame_height,#path').prop('required',false);


  					$('#chat_ai').prop('required',true);
  					$('#form-group-chat_ai label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
          } else
          {
  					$('#module_slug').prop('required',false);
  					$('#statistic_slug').prop('required',false);
  					$('#qlik_slug').prop('required',false);
  					$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
  					$('#form-group-frame_width,#form-group-frame_height').hide();
  					$('#form-group-path').show();
  				}

  				function format(icon)
          {
  	                  var originalOption = icon.element;
  	                  var label = $(originalOption).text();
  	                  var val = $(originalOption).val();
  	                  if(!val) return label;
  	                  var \$resp = $('<span><i style=\"margin-top:5px\" class=\"pull-right ' + $(originalOption).val() + '\"></i> ' + $(originalOption).data('label') + '</span>');
  	                  return \$resp;
  	              }
  	              $('#list-icon').select2(
                    {
  	                  width: \"100%\",
  	                  templateResult: format,
  	                  templateSelection: format
  	              });

          $('input[name=is_custom]').change(function(){

            if($(this).val() == 1)
            {
              $('#form-group-icon').hide();
              $('#form-group-icon_upload').show();
            }else {
              $('#form-group-icon').show();
              $('#form-group-icon_upload').hide();
            }



          });


  				$('input[name=type]').change(function()
          {
  					var default_placeholder_path = 'NameController@methodName';
  					var n = $(this).val();
  					var isCheck = $(this).prop('checked');
  					// console.log('Click the module type '+n);
  					$('#module_slug').prop('required',false);
  					$('input[name=path]').attr('placeholder',default_placeholder_path);
  					if(n == 'Module')
            {
  						$('#form-group-path').hide();
  						$('#form-group-statistic_slug').hide();
  						$('#form-group-qlik_slug').hide();
    					$('#form-group-frame_width,#form-group-frame_height').hide();
  						$('#statistic_slug,#path').prop('required',false);
  						$('#qlik_slug,#path').prop('required',false);

  						$('#form-group-module_slug').show();
  						$('#module_slug').prop('required',true);
  						$('#form-group-module_slug label .text-danger').remove();
  					}
            else if (n == 'Statistic')
            {
  						$('#form-group-path').hide();
  						$('#form-group-module_slug').hide();
  						$('#form-group-qlik_slug').hide();
              $('#form-group-statistic_slug,#form-group-chat_ai').hide();
    					$('#form-group-frame_width,#form-group-frame_height').hide();
  						$('#module_slug,#path').prop('required',false);
  						$('#qlik_slug,#path').prop('required',false);

  						$('#form-group-statistic_slug').show();
  						$('#statistic_slug').prop('required',true);
  						$('#form-group-statistic_slug label .text-danger').remove();
  						$('#form-group-statistic_slug label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
  					}
            else if (n == 'Qlik')
            {
  						$('#form-group-path').hide();
  						$('#form-group-module_slug').hide();
  						$('#form-group-statistic_slug').hide();
              $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  						$('#module_slug,#path').prop('required',false);
  						$('#statistic_slug,#path').prop('required',false);

  						$('#form-group-qlik_slug').show();
    					$('#form-group-frame_width,#form-group-frame_height').show();
  						$('#qlik_slug').prop('required',true);
  						$('#form-group-qlik_slug label .text-danger').remove();
  						$('#form-group-qlik_slug label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');
  					}
            else if (n == 'URL')
            {

  						$('input[name=\"path\"]').attr('placeholder','Please enter your URL');

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_width,#form-group-frame_height').hide();
              $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					}
            else if (n == 'Route')
            {
  						$('input[name=path]').attr('placeholder','Please enter the Route');

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_width,#form-group-frame_height').hide();
              $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					}
            else if (n == 'Agent AI')
            {

  						$('#form-group-path').hide();
  						$('#form-group-module_slug').hide();
              
  						$('#form-group-statistic_slug').hide();
  						$('#module_slug,#path').prop('required',false);
  						$('#statistic_slug,#path').prop('required',false);
              $('#form-group-qlik_slug').hide();
              $('#qlik_slug,#path').prop('required',false);


            $('#form-group-is_dashboard').hide();
            $('#is_dashboard,#path').prop('required',false);

            $('#form-group-target_layout').hide();
            $('#target_layout,#path').prop('required',false);

            $('#form-group-target_layout').hide();
            $('#target_layout,#path').prop('required',false);

            $('#form-group-frame_width').hide();
            $('#frame_width,#path').prop('required',false);

            $('#form-group-frame_height').hide();
            $('#frame_height,#path').prop('required',false);

  						$('#form-group-chat_ai').show();
    					$('#form-group-frame_width,#form-group-frame_height').show();
  						$('#chat_ai').prop('required',true);
  						$('#form-group-chat_ai label .text-danger').remove();
  						$('#form-group-chat_ai label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');

/*
                
                $('#form-group-method').show();
                $('#form-group-url').show();
                $('#form-group-token').show();

                $('#form-group-path').hide();
                $('#form-group-module_slug').hide();
*/

/*
                $('input[name=path]').attr('placeholder','Please enter the Route');

                $('#path').prop('required',true);
                $('#form-group-path label .text-danger').remove();
                $('#form-group-path label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');

                $('#form-group-path').show();
                $('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
                $('#form-group-frame_width,#form-group-frame_height').hide();
*/
  					}
            else
            {
  						$('#module_slug,#statistic_slug,#qlik_slug').prop('required',false);

  						$('#path').prop('required',true);
  						$('#form-group-path label .text-danger').remove();
  						$('#form-group-path label').append('<span class=\"text-danger\" title=\"" . trans('crudbooster.this_field_is_required') . "\">*</span>');

  						$('#form-group-path').show();
  						$('#form-group-module_slug,#form-group-statistic_slug,#form-group-qlik_slug').hide();
    					$('#form-group-frame_width,#form-group-frame_height').hide();
              $('#form-group-statistic_slug,#form-group-chat_ai').hide();
  					}
  				})
  			})

        $(function()
        {
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

        });
  			";

    $this->col = [];
    $this->col[] = ["label" => "Name", "name" => "name"];
    $this->col[] = ["label" => "Is Active", "name" => "is_active"];
    $this->col[] = ["label" => "Privileges", "name" => "id_cms_privileges", "join" => "cms_privileges,name"];
    $this->col[] = ["label" => "Width", "name" => "frame_width"];
    $this->col[] = ["label" => "Height", "name" => "frame_height"];

    $this->form = [];
    $this->form[] = [
      "label" => "Privileges",
      "name" => "cms_menus_privileges",
      "type" => "select2",
      "select2_multiple" => true,
      "datatable" => "cms_privileges,name",
      "relationship_table" => "cms_menus_privileges",
      "required" => true
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
      'dataenum' => ['Module', 'Qlik', 'Statistic', 'URL', 'Controller & Method', 'Route', 'Agent AI'],
      'value' => 'Module'
    ];

    //only superadmin can edit tenant
    if (CRUDBooster::isSuperadmin()) {
      $this->form[] = [
        'label' => 'Tenant',
        'name' => 'menu_tenants',
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "tenants,name",
        "relationship_table" => "menu_tenants",
        'required' => true,
        'validation' => 'required',
        'value' => UserHelper::current_user_tenant() //default value per creazione nuovo record
      ];
      //superadmin vede i gruppi come cascading dropdown in base al tenant
      $this->form[] = [
        "label" => "Group",
        "name" => "menu_groups",
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "groups,name",
        "relationship_table" => "menu_groups",
        "required" => true,
        'parent_select' => 'menu_tenants',
        'parent_crosstable' => 'group_tenants',
        'fk_name' => 'tenant_id',
        'child_crosstable_fk_name' => 'group_id'
      ];
    } elseif (UserHelper::isTenantAdmin()) {
      //Tenantadmin vede tenant in readonly (disabled) ma può modificare il group
      $this->form[] = [
        "label" => "Tenant",
        "name" => "tenant",
        'required' => true,
        'type' => 'select',
        'datatable' => "tenants,name",
        'default' => UserHelper::current_user_tenant_name(),
        'value' => UserHelper::current_user_tenant(),
        'disabled' => true
      ];
      //Tenantadmin vede solo i gruppi del proprio tenant
      $this->form[] = [
        "label" => "Group",
        "name" => "menu_groups",
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "groups,name",
        "relationship_table" => "menu_groups",
        "required" => true,
        'parent_select' => 'tenant',
        'parent_crosstable' => 'group_tenants',
        'fk_name' => 'tenant_id',
        'child_crosstable_fk_name' => 'group_id'
      ];
    }

    //superadmin can see all modules
    if (CRUDBooster::isSuperadmin()) {
      $this->form[] = [
        "label" => "Module",
        "name" => "module_slug",
        "type" => "select",
        "datatable" => "cms_moduls,name",
        "datatable_where" => "is_protected = 0 AND table_name LIKE '" . config('app.module_generator_prefix') . "%' ",
        "value" => $id_module,
      ];
    }
    //tenantadmin can see only modules enabled for his tenant
    elseif (UserHelper::isTenantAdmin()) {
      $this->form[] = [
        "label" => "Module",
        "name" => "module_slug",
        "type" => "select",
        "dataquery" => "SELECT cms_moduls.name as label, cms_moduls.id as value
                              FROM cms_moduls
                              INNER JOIN module_tenants
                              ON module_tenants.module_id = cms_moduls.id
                              WHERE tenant_id=" . UserHelper::current_user_tenant(),
        "value" => $id_module,
      ];
    }
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
      "default" => "** Select a Qlik Item",
      "dataquery" => "SELECT qlik_items.title as label, qlik_items.id as value
                            FROM qlik_items
                            LEFT JOIN tenants_allowed
                            ON tenants_allowed.item_id = qlik_items.id
                            WHERE (tenants_allowed.tenant_id=" . UserHelper::current_user_tenant() . "
                            OR qlik_items.proxy_token IS NOT NULL)
                            AND qlik_items.deleted_at IS NULL",
      "value" => $id_qlik_item,
    ];

    $this->form[] = [
      "label" => "Agent AI",
      "name" => "chat_ai",
      "type" => "select",
      "default" => "** Select a Agent AI",
      "dataquery" => "SELECT chatai_confs.title as label, chatai_confs.id as value
                            FROM chatai_confs
                            LEFT JOIN tenants_allowed
                            ON tenants_allowed.item_id = chatai_confs.id
                            WHERE (tenants_allowed.tenant_id=" . UserHelper::current_user_tenant() . ")
                            AND chatai_confs.deleted_at IS NULL",
      "value" => $id_chat_ai,
    ];

    $this->form[] = [
      "label" => "Value",
      "name" => "path",
      "type" => "text",
      'help' => 'If you select type controller, you can fill this field with controller name, you may include the method also',
      'placeholder' => 'NameController or NameController@methodName',
      "style" => "display:none",
    ];

/*
    $this->form[] = ['label' => 'Method', 'name' => 'method', 'type' => 'select',   'dataenum' => 'GET;POST;PUT;DELETE', 'placeholder' => 'Method to call the API' , "style" => "display:none",];
		$this->form[] = ['label' => 'Auth', 'name' => 'auth', 'type' => 'select', 'validation' => 'required',  'dataenum' => 'JWT;', 'placeholder' => 'Authentication method', "style" => "display:none",];
		$this->form[] = ['label' => 'Url', 'name' => 'url', 'type' => 'text',   'placeholder' => 'API endpoint', "style" => "display:none",];
		$this->form[] = ['label' => 'Token', 'name' => 'token', 'type' => 'textarea',  'placeholder' => 'API token', "style" => "display:none",];
*/

    $fontawesome = Fontawesome::getIcons();

    $custom = view('crudbooster::components.list_icon', compact('fontawesome', 'row'))->render();

    $this->form[] = [
      "label" => "Custom Icon",
      "name" => "is_custom",
      "type" => "radio",
      "required" => false,
      "validation" => "required|integer",
      "dataenum" => ['1|Custom', '0|Font Awesome'],
      'value' => '0',
    ];

    $this->form[] = [
      'label' => 'Icon Upload', 'name' => 'icon_upload', 
      'type' => 'upload', 'width' => 'col-sm-10',
     'placeholder' => 'Upload your custom icon',
      'required' => false,
    ];



    $this->form[] = ['label' => 'Icon', 'name' => 'icon', 'type' => 'custom', 'html' => $custom, 'required' => false];
    
    $this->form[] = [
      'label' => 'Color',
      'name' => 'color',
      'type' => 'select2',
      'dataenum' => ['normal', 'red', 'green', 'aqua', 'light-blue', 'red', 'yellow', 'muted'],
      'required' => false,
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
    $this->form[] = ['label' => 'Target Layout', 'name' => 'target_layout', 'type' => 'radio', 'dataenum' => '0|Standard;1|Full Screen;2|Fill Content', 'width' => 'col-sm-3', 'value' => '0'];
    $this->form[] = ['label' => 'Width', 'name' => 'frame_width', 'type' => 'number', 'validation' => 'required|int|min:1|max:10000', 'width' => 'col-sm-2', 'value' => '100'];
    $this->form[] = ['label' => '', 'name' => 'frame_width_unit', 'type' => 'select', 'validation' => '', 'width' => 'col-sm-2', 'dataenum' => 'px', 'default' => '%'];
    $this->form[] = ['label' => 'Height', 'name' => 'frame_height', 'type' => 'number', 'validation' => 'required|int|min:1|max:10000', 'width' => 'col-sm-2', 'value' => '100'];
    $this->form[] = ['label' => '', 'name' => 'frame_height_unit', 'type' => 'select', 'validation' => '', 'width' => 'col-sm-2', 'dataenum' => 'px', 'default' => '%'];
		

    $id_cms_privileges = Request::get('id_cms_privileges');
    $this->form[] = ["label" => "id_cms_privileges", "name" => "id_cms_privileges", "type" => "hidden", "value" => $id_cms_privileges];
  }

  public function getIndex()
  {
    $this->cbLoader();

    $module = CRUDBooster::getCurrentModule();
    if (!CRUDBooster::isView() && $this->global_privilege == false) {
      CRUDBooster::insertLog(trans('crudbooster.log_try_view', ['module' => $module->name]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }

    $privileges = DB::table('cms_privileges')->get();

    $id_cms_privileges = Request::get('id_cms_privileges');
    $id_cms_privileges = ($id_cms_privileges) ?: CRUDBooster::myPrivilegeId();

    $menu_active = MenuHelper::get_menu(1);
    $menu_inactive = MenuHelper::get_menu(0);

    $return_url = urlencode(Request::fullUrl());

    $menu_active_html = MenuHelper::menu_to_html($menu_active, $return_url);
    //dd($menu_active);
    $menu_inactive_html = MenuHelper::menu_to_html($menu_inactive, $return_url);

    $page_title = 'Menu Management';

    return view('crudbooster::menus_management', compact('menu_active_html', 'menu_active', 'menu_inactive_html',  'menu_inactive', 'privileges', 'id_cms_privileges', 'return_url', 'page_title'));
  }

  public function customEdit($id)
  {

    //load edit page
    $this->cbLoader();
    $row = DB::table($this->table)->where($this->primary_key, $id)->first();

    //TODO in altri moduli qui usa isRead anzichè isUpdate
    //forse potrei controllare qui solo isread per vedere i dettagli della voce di menu
    //ma il form è in readonly se non ha isupdate
    if (!UserHelper::can_menu('edit', $row->id)) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
        'name' => $row->{$this->title_field},
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    $page_title = 'Edit Menu';
    $command = 'add';
    $button_addmore = false;
    //$button_addmore = false;


    return view('crudbooster::menus.form', compact('row', 'id', 'page_title', 'command', 'button_addmore'));
  }

  public function hook_before_add(&$postdata)
  {
    if (!$postdata['id_cms_privileges']) {
      $postdata['id_cms_privileges'] = CRUDBooster::myPrivilegeId();
    }
    //new menu non è inizialmente figlio di nessuno
    $postdata['parent_id'] = 0;

    if ($postdata['type'] == 'Statistic') {
      $stat = CRUDBooster::first('cms_statistics', ['id' => $postdata['statistic_slug']]);
      $postdata['path'] = 'statistic_builder/show/' . $stat->slug;
    } elseif ($postdata['type'] == 'Module') {
      $stat = CRUDBooster::first('cms_moduls', ['id' => $postdata['module_slug']]);
      $postdata['path'] = $stat->path;
    } elseif ($postdata['type'] == 'Qlik') {
      $stat = CRUDBooster::first('qlik_items', ['id' => $postdata['qlik_slug']]);
      $postdata['path'] = 'qlik_items/content/' . $postdata['qlik_slug'];
    }elseif ($postdata['type'] == 'Agent AI') {
      $stat = CRUDBooster::first('chatai_confs', ['id' => $postdata['chat_ai']]);
      $postdata['path'] = 'chat_ai/content/' . $postdata['chat_ai'];
    }
    //id that this menu will be saved with
    $id = (Menu::orderby('id', 'desc')->first()->id) + 1;
    //add menu id as GET parameter for target layout fullpage/fillcontent
    $postdata['path'] .= '?m=' . $id;

    unset($postdata['module_slug']);
    unset($postdata['statistic_slug']);
    unset($postdata['qlik_slug']);
    unset($postdata['chat_ai']);
    //frame width and height data
    $postdata['frame_width'] .= $postdata['frame_width_unit'];
    $postdata['frame_height'] .= $postdata['frame_height_unit'];
    unset($postdata['frame_width_unit']);
    unset($postdata['frame_height_unit']);

    if ($postdata['is_dashboard'] == 1) {
      //If set dashboard, so unset for first all dashboard
      // DB::table('cms_menus')
      //     ->where('id_cms_privileges', $postdata['id_cms_privileges'])
      //     ->where('is_dashboard', 1)
      //     ->update(['is_dashboard' => 0]);
      Cache::forget('sidebarDashboard' . CRUDBooster::myPrivilegeId());
    }

    if (!UserHelper::isSuperAdmin()) {
      //only superadmin can update menu's tenants
      unset($postdata['tenant']);
    }
  }

  public function hook_after_add($menu_id)
  {
    if (!UserHelper::isSuperAdmin()) {
      //user didn't set menu's tenants, add user's tenant as menu default tenant
      Menu::find($menu_id)->assign_default_tenant();
    }
  }

  public function hook_before_edit(&$postdata, $id)
  {
    //solo superadmin o tenant admin con permesso di update su un menu del proprio tenant
    //possono modificare il menu
    if (!UserHelper::can_menu('edit', $id)) {
      CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
        'name' => $id,
        'module' => CRUDBooster::getCurrentModule()->name,
      ]));
      CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
    }
    //frame width and height data
    $postdata['frame_width'] .= $postdata['frame_width_unit'];
    $postdata['frame_height'] .= $postdata['frame_height_unit'];
    unset($postdata['frame_width_unit']);
    unset($postdata['frame_height_unit']);

    if ($postdata['is_dashboard'] == 1) {
      //If set dashboard, so unset for first all dashboard
      //DB::table('cms_menus')->where('id_cms_privileges', $postdata['id_cms_privileges'])->where('is_dashboard', 1)->update(['is_dashboard' => 0]);
      Cache::forget('sidebarDashboard' . CRUDBooster::myPrivilegeId());
    }

    if ($postdata['type'] == 'Statistic') {
      $stat = CRUDBooster::first('cms_statistics', ['id' => $postdata['statistic_slug']]);
      $stat = isset($stat) ? $stat : null;
      $postdata['path'] = 'statistic_builder/show/' . (isset($stat) && $stat != null) ? $stat->slug : '';
    } elseif ($postdata['type'] == 'Module') {
      $stat = CRUDBooster::first('cms_moduls', ['id' => $postdata['module_slug']]);
      $postdata['path'] = $stat->path;
    } elseif ($postdata['type'] == 'Qlik') {
      $stat = CRUDBooster::first('qlik_items', ['id' => $postdata['qlik_slug']]);
      $postdata['path'] = 'qlik_items/content/' . $postdata['qlik_slug'];
    } elseif ($postdata['type'] == 'Agent AI') {
      $stat = CRUDBooster::first('chatai_confs', ['id' => $postdata['chat_ai']]);
      $postdata['path'] = 'chat_ai/content/' . $postdata['chat_ai'];
    }
    //add menu id as GET parameter for target layout fullpage/fillcontent

    $path = isset(explode('?', $postdata['path'])[0]) ? explode('?', $postdata['path'])[0] : '';
    $args = isset(explode('?', $postdata['path'])[1]) ? explode('?', $postdata['path'])[1] : '';

    $key = isset(explode('=', $args)[0]) ? explode('=', $args)[0] : '';
    $value = isset(explode('=', $args)[1]) ? explode('=', $args)[1] : '';

    //list($path, $args) = explode('?', $postdata['path']);
    //list($key, $value) = explode('=', $args);
    if ($key == 'm') {
      //overwrite m argument
      $postdata['path'] = $path . '?m=' . $id;
    }
    //TODO se ci sono altri args?

    unset($postdata['module_slug']);
    unset($postdata['statistic_slug']);
    unset($postdata['qlik_slug']);
    unset($postdata['chat_ai']);
    if (!UserHelper::isSuperAdmin()) {
      //only superadmin can update menu's tenants
      unset($postdata['tenant']);
    }
  }


  public function hook_before_delete($id)
  {
    //solo superadmin o tenant admin con permesso di delete su un menu del proprio tenant
    //possono eliminare il menu
    if (!UserHelper::can_menu('delete', $id)) {
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
    //promote children removing this menu as their parent
    MenuHelper::promote_orphans($id);
  }

  public function postSaveMenu()
  {
    $menu_list = Request::input('menus');
    $isActive = Request::input('isActive');
    $menu_list_decoded = json_decode($menu_list, true);
    $levels = config('app.menu_max_nesting_levels');

    $counter = 1;
    foreach ($menu_list_decoded[0] as $menu) {
      MenuHelper::save_menu($menu, $counter, $isActive);
      $counter++;
    }

    return response()->json(['success' => true, 'active' => $isActive]);
  }
}
