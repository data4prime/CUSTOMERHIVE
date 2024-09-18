<?php

namespace crocodicstudio\crudbooster\controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use \App\Tenant;
use \App\User;
use \App\GroupTenants;
use Illuminate\Support\Facades\Route;
use TenantHelper;
use MyHelper;
use crocodicstudio\crudbooster\helpers\LicenseHelper;

class AdminTenantsController extends CBController
{

	public function cbInit()
	{

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->title_field = "name";
		$this->limit = "20";
		$this->orderby = "id,desc";
		$this->global_privilege = false;
		$this->button_table_action = true;
		$this->button_bulk_action = true;
		$this->button_action_style = "button_icon";
		$this->button_add = true;
		$this->button_edit = true;
		$this->button_delete = true;
		$this->button_detail = true;
		$this->button_show = true;
		$this->button_filter = true;
		$this->button_import = false;
		$this->button_export = false;
		$this->table = "tenants";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Id", "name" => "id"];
		$this->col[] = ["label" => "Name", "name" => "name"];
		$this->col[] = ["label" => "Description", "name" => "description"];
		$this->col[] = ["label" => "Logo", "name" => "logo"];
		$this->col[] = ["label" => "Favicon", "name" => "favicon"];
		// $this->col[] = ["label"=>"Login Background Color","name"=>"login_background_color"];
		// $this->col[] = ["label"=>"Login Background Image","name"=>"login_background_image"];
		// $this->col[] = ["label"=>"Login Font Color","name"=>"login_font_color"];
		$this->col[] = ["label" => "Created At", "name" => "created_at"];
		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Name', 'name' => 'name', 'type' => 'text', 'validation' => 'required', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Logo', 'name' => 'logo', 'type' => 'upload', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Favicon', 'name' => 'favicon', 'type' => 'upload', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Background Color', 'name' => 'login_background_color', 'type' => 'text', 'width' => 'col-sm-9', 'help' => 'use hex format i.e.: #4287f5'];
		$this->form[] = ['label' => 'Background Image', 'name' => 'login_background_image', 'type' => 'upload', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Font Color', 'name' => 'login_font_color', 'type' => 'text', 'width' => 'col-sm-9', 'help' => 'use hex format i.e.: #4287f5'];
		$this->form[] = ['label' => 'Domain name', 'name' => 'domain_name', 'type' => 'text', 'width' => 'col-sm-9', 'help' => 'use only letters and numbers', 'validation' => 'required|min:1|max:20|regex:/^[a-zA-Z0-9]+$/u'];
	$this->form[] = ['label' => 'Tenant Path', 'name' => 'tenant_path', 'type' => 'hidden', 'width' => 'col-sm-10', 'value' => env('APP_URL')];
		
# END FORM DO NOT REMOVE THIS LINE

		# OLD START FORM
		//$this->form = [];
		//$this->form[] = ['label'=>'Name','name'=>'name','type'=>'text','validation'=>'required','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Description','name'=>'description','type'=>'text','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Logo','name'=>'logo','type'=>'text','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Favicon','name'=>'favicon','type'=>'text','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Login Background Color','name'=>'login_background_color','type'=>'text','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Login Background Image','name'=>'login_background_image','type'=>'text','width'=>'col-sm-9'];
		//$this->form[] = ['label'=>'Login Font Color','name'=>'login_font_color','type'=>'text','width'=>'col-sm-9'];
		# OLD END FORM

		/*
	        | ----------------------------------------------------------------------
	        | Sub Module
	        | ----------------------------------------------------------------------
    			| @label          = Label of action
    			| @path           = Path of sub module
    			| @foreign_key 	  = foreign key of sub table/module
    			| @button_color   = Bootstrap Class (primary,success,warning,danger)
    			| @button_icon    = Font Awesome Class
    			| @parent_columns = Sparate with comma, e.g : name,created_at
	        |
	        */
		$this->sub_module = array();


		/*
	        | ----------------------------------------------------------------------
	        | Add More Action Button / Menu
	        | ----------------------------------------------------------------------
	        | @label       = Label of action
	        | @url         = Target URL, you can use field alias. e.g : [id], [name], [title], etc
	        | @icon        = Font awesome class icon. e.g : fa fa-bars
	        | @color 	   = Default is primary. (primary, warning, succecss, info)
	        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
	        |
	        */
		$this->addaction = array();
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('members/[id]'), 'icon' => 'fa fa-user', 'color' => 'info', 'title' => 'Members'];
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('group/[id]'), 'icon' => 'fa fa-users', 'color' => 'warning', 'title' => 'Groups'];

		/*
	        | ----------------------------------------------------------------------
	        | Add More Button Selected
	        | ----------------------------------------------------------------------
	        | @label       = Label of action
	        | @icon 	   = Icon from fontawesome
	        | @name 	   = Name of button
	        | Then about the action, you should code at actionButtonSelected method
	        |
	        */
		$this->button_selected = array();


		/*
	        | ----------------------------------------------------------------------
	        | Add alert message to this module at overheader
	        | ----------------------------------------------------------------------
	        | @message = Text of message
	        | @type    = warning,success,danger,info
	        |
	        */
		$this->alert        = array();



		/*
	        | ----------------------------------------------------------------------
	        | Add more button to header button
	        | ----------------------------------------------------------------------
	        | @label = Name of button
	        | @url   = URL Target
	        | @icon  = Icon from Awesome.
	        |
	        */
		$this->index_button = array();



		/*
	        | ----------------------------------------------------------------------
	        | Customize Table Row Color
	        | ----------------------------------------------------------------------
	        | @condition = If condition. You may use field alias. E.g : [id] == 1
	        | @color = Default is none. You can use bootstrap success,info,warning,danger,primary.
	        |
	        */
		$this->table_row_color = array();


		/*
	        | ----------------------------------------------------------------------
	        | You may use this below array to add statistic at dashboard
	        | ----------------------------------------------------------------------
	        | @label, @count, @icon, @color
	        |
	        */
		$this->index_statistic = array();



		/*
	        | ----------------------------------------------------------------------
	        | Add javascript at body
	        | ----------------------------------------------------------------------
	        | javascript code in the variable
	        | $this->script_js = "function() { ... }";
	        |
	        */
		$this->script_js = NULL;


		/*
	        | ----------------------------------------------------------------------
	        | Include HTML Code before index table
	        | ----------------------------------------------------------------------
	        | html code to display it before index table
	        | $this->pre_index_html = "<p>test</p>";
	        |
	        */
		$this->pre_index_html = null;



		/*
	        | ----------------------------------------------------------------------
	        | Include HTML Code after index table
	        | ----------------------------------------------------------------------
	        | html code to display it after index table
	        | $this->post_index_html = "<p>test</p>";
	        |
	        */
		$this->post_index_html = null;



		/*
	        | ----------------------------------------------------------------------
	        | Include Javascript File
	        | ----------------------------------------------------------------------
	        | URL of your javascript each array
	        | $this->load_js[] = asset("myfile.js");
	        |
	        */
		$this->load_js = array();



		/*
	        | ----------------------------------------------------------------------
	        | Add css style at body
	        | ----------------------------------------------------------------------
	        | css code in the variable
	        | $this->style_css = ".style{....}";
	        |
	        */
		$this->style_css = NULL;



		/*
	        | ----------------------------------------------------------------------
	        | Include css File
	        | ----------------------------------------------------------------------
	        | URL of your css each array
	        | $this->load_css[] = asset("myfile.css");
	        |
	        */
		$this->load_css = array();
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for button selected
	    | ----------------------------------------------------------------------
	    | @id_selected = the id selected
	    | @button_name = the name of button
	    |
	    */
	public function actionButtonSelected($id_selected, $button_name)
	{
		//Your code here
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate query of index result
	    | ----------------------------------------------------------------------
	    | @query = current sql query
	    |
	    */
	public function hook_query_index(&$query)
	{
		//Your code here
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate row of index table html
	    | ----------------------------------------------------------------------
	    |
	    */
	public function hook_row_index($column_index, &$column_value)
	{
		//Your code here
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate data input before add data is execute
	    | ----------------------------------------------------------------------
	    | @arr
	    |
	    */
	public function hook_before_add(&$postdata)
	{

		
		if (LicenseHelper::canAddTenant()) {

			//Your code here
			$domain_name = TenantHelper::domain_name_encode($postdata['name']);
			$postdata['domain_name'] = $domain_name;
		} else {
			$message = "The number of tenants has exceeded the limit allowed by the current license.";
			//$message .= '<br><br>' . "Please contact the administrator to increase the number of tenants allowed.";
			$message_type = 'warning';
			CRUDBooster::redirect( g('return_url') ?: CRUDBooster::referer() , $message, $message_type);
			//dd("License not valid");
		}
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after add public static function called
	    | ----------------------------------------------------------------------
	    | @id = last insert id
	    |
	    */
	public function hook_after_add($id)
	{
		//Your code here
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate data input before update data is execute
	    | ----------------------------------------------------------------------
	    | @postdata = input post data
	    | @id       = current id
	    |
	    */
	public function hook_before_edit(&$postdata, $id)
	{
		//Your code here
		$exists = Tenant::where('domain_name', $postdata['domain_name'])
			->where('id', '!=', $id)
			->count();
		if ($exists > 0) {
			CRUDBooster::redirect(CRUDBooster::adminPath('tenants'), trans('crudbooster.not_unique_domain'));
		}
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after edit public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
	public function hook_after_edit($id)
	{
		//Your code here
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for execute command before delete public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
	public function hook_before_delete($id)
	{
		$members_count = User::where('tenant', $id)->count();
		if ($members_count > 0) {
			CRUDBooster::redirect(CRUDBooster::adminPath('tenants'), trans('crudbooster.delete_not_empty_tenant'));
		}
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after delete public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
	public function hook_after_delete($id)
	{
		//Your code here
	}

	public function getEdit($id)
	{
		$this->cbLoader();
		$row = DB::table($this->table)->where($this->primary_key, $id)->first();

		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
				'name' => $row->{$this->title_field},
				'module' => CRUDBooster::getCurrentModule()->name,
			]));
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		$page_menu = Route::getCurrentRoute()->getActionName();
		$page_title = trans("crudbooster.edit_tenants");
		$command = 'edit';
		Session::put('current_row_id', $id);

		return view('tenants.form', compact('id', 'row', 'page_menu', 'page_title', 'command'));
	}

	public function members($tenant_id)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data = [];
		$data['members'] = DB::table('cms_users')
			->where('cms_users.tenant', $tenant_id)
			->select('cms_users.id', 'cms_users.name', 'cms_users.email', 'cms_users.photo')
			->get();

		$data['tenant'] = Tenant::find($tenant_id);
		$data['tenant_id'] = $tenant_id;
		$data['page_title'] = trans("crudbooster.Tenants");
		$data['content_title'] = $data['tenant']->name . ' members';

		$this->cbView('tenants.members', $data);
	}

	public function group($tenant_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['tenant_id'] = $tenant_id;
		$data['tenant'] = Tenant::find($tenant_id);
		$data['groups'] = GroupTenants::where('tenant_id', $tenant_id)
			->join('groups', 'groups.id', '=', 'group_tenants.group_id')
			->get();
		$data['page_title'] = 'Tenant Groups';

		//prendo $_GET &alert=
		if (!empty($alert_id)) {
			//se è alert=1
			if ($alert_id == '1') {
				//mostra messaggio di warning per tasto add premuto senza valori required
				$data['alerts'][] = ['message' => '<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...', 'type' => 'warning'];
			}
		}

		//add tenant form
		$data['forms'] = [];
		$data['forms'][] = ['label' => 'Name', 'name' => 'name', 'type' => 'tenant_group_datamodal', 'width' => 'col-sm-6', 'datamodal_table' => 'groups', 'datamodal_where' => '', 'datamodal_columns' => 'name', 'datamodal_columns_alias' => 'Name', 'datamodal_select_to' => $tenant_id, 'required' => true];
		$data['forms'][] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => 'Group description', 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($tenant_id . "/add_group");
		$data['return_url'] = CRUDBooster::mainpath('group/' . $tenant_id);

		$data['command'] = 'add';
		$data['button_addmore'] = false;

		$this->cbView('tenants.group', $data);
	}

	public function add_group($tenant_id)
	{

		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$group_id = $_POST['name'];
		$return_url = $_POST['return_url'];
		$ref_mainpath = $_POST['ref_mainpath'];

		if (empty($group_id)) {
			return redirect($return_url . '/alert/1');
		}
		//check if tenant is already allowed
		$allowed = GroupTenants::where('group_id', $group_id)
			->where('tenant_id', $tenant_id)
			->count();

		if ($allowed == 0) {
			$add_group = new GroupTenants;
			$add_group->group_id = $group_id;
			$add_group->tenant_id = $tenant_id;
			$add_group->save();
		}

		//redirect
		if (empty($return_url)) {
			$return_url = $ref_mainpath;
		}
		return redirect($return_url);
	}

	public function remove_group($tenant_id, $group_id)
	{
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if tenant_id and user_id are int
		if (!MyHelper::is_int($group_id) or !MyHelper::is_int($tenant_id)) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['delete'] = GroupTenants::where('group_id', $group_id)
			->where('tenant_id', $tenant_id)
			->delete();

		return redirect('admin/tenants/group/' . $tenant_id);
	}
}
