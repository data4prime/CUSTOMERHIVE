<?php

namespace crocodicstudio\crudbooster\controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use GroupHelper;

use Illuminate\Support\Facades\Route;

use App\ItemsAllowed;
use App\TenantsAllowed;
use App\Menu;

use MyHelper;

class AdminChatAIController extends CBController
{

	public function cbInit()
	{
		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->title_field = "title";
		$this->limit = "20";
		$this->orderby = "id,desc";
		$this->global_privilege = true;
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
		$this->table = "qlik_items";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Title", "name" => "title"];
		$this->col[] = ["label" => "Method", "name" => "method"];
		$this->col[] = ["label" => "Auth", "name" => "auth"];
		$this->col[] = ["label" => "Url", "name" => "url"];

		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Title', 'name' => 'title', 'type' => 'text', 'validation' => 'required|string|min:1|max:70', 'width' => 'col-sm-10', 'placeholder' => 'Item title'];
		$this->form[] = ['label' => 'Method', 'name' => 'method', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'GET;POST;PUT;DELETE', 'placeholder' => 'Method to call the API'];
		$this->form[] = ['label' => 'Auth', 'name' => 'auth', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'JWT;', 'placeholder' => 'Authentication method'];
		$this->form[] = ['label' => 'Url', 'name' => 'url', 'type' => 'text', 'validation' => 'required|string', 'width' => 'col-sm-10', 'placeholder' => 'API endpoint'];
		$this->form[] = ['label' => 'Token', 'name' => 'token', 'type' => 'textarea', 'validation' => 'required|string', 'width' => 'col-sm-10', 'placeholder' => 'API token'];
		# END FORM DO NOT REMOVE THIS LINE


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
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('content/[id]'), 'icon' => 'fa fa-search', 'color' => 'info', 'title' => 'View item'];
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('access/[id]'), 'icon' => 'fa fa-users', 'color' => 'warning', 'title' => 'Set group'];
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('tenant/[id]'), 'icon' => 'fa fa-industry', 'color' => 'primary', 'title' => 'Set tenant'];


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
		$this->script_js = "";

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
		$this->style_css = "";



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
		//TODO filtrare permesso gruppi
	}

	/*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate row of index table html
	    | ----------------------------------------------------------------------
	    |
	    */
	public function hook_row_index($column_index, &$column_value)
	{

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

	// #RAMA custom methods


	public function access($item_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$data['item_id'] = $item_id;
		$data['qlik_item'] = QlikItem::find($item_id);
		$data['groups'] = ItemsAllowed::where('item_id', $item_id)
			->join('groups', 'groups.id', '=', 'items_allowed.group_id')
			->get();
		$data['page_title'] = 'Authorize Group';

		//prendo $_GET &alert=
		if (!empty($alert_id)) {
			//se è alert=1
			if ($alert_id == '1') {
				//mostra messaggio di warning per tasto add premuto senza valori required
				$data['alerts'][] = ['message' => '<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...', 'type' => 'warning'];
			}
		}
		//add group form
		$data['forms'] = [];
		$data['forms'][] = ['label' => 'Name', 'name' => 'name', 'type' => 'item_access_datamodal', 'width' => 'col-sm-6', 'datamodal_table' => 'groups', 'datamodal_where' => '', 'datamodal_columns' => 'name', 'datamodal_columns_alias' => 'Name', 'datamodal_select_to' => $item_id, 'required' => true];
		$data['forms'][] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => 'Group description', 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($item_id . "/auth");
		$data['return_url'] = CRUDBooster::mainpath('access/' . $item_id);
		$data['command'] = 'add';
		$data['button_addmore'] = false;

		$this->cbView('qlik_items.access', $data);
	}

	public function tenant($item_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['item_id'] = $item_id;
		$data['qlik_item'] = QlikItem::find($item_id);
		$data['tenants'] = TenantsAllowed::where('item_id', $item_id)
			->join('tenants', 'tenants.id', '=', 'tenants_allowed.tenant_id')
			->get();
		$data['page_title'] = 'Authorize Tenant';

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
		$data['forms'][] = ['label' => 'Name', 'name' => 'name', 'type' => 'item_tenant_datamodal', 'width' => 'col-sm-6', 'datamodal_table' => 'tenants', 'datamodal_where' => '', 'datamodal_columns' => 'name', 'datamodal_columns_alias' => 'Name', 'datamodal_select_to' => $item_id, 'required' => true];
		$data['forms'][] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => 'Tenant description', 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($item_id . "/add_tenant");
		$data['return_url'] = CRUDBooster::mainpath('tenant/' . $item_id);

		$data['command'] = 'add';
		$data['button_addmore'] = false;
		$this->cbView('qlik_items.tenant', $data);
	}

	public function add_tenant($item_id)
	{

		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$tenant_id = $_POST['name'];
		$return_url = $_POST['return_url'];
		$ref_mainpath = $_POST['ref_mainpath'];

		if (empty($tenant_id)) {
			return redirect($return_url . '/alert/1');
		}
		//check if tenant is already allowed
		$allowed = TenantsAllowed::where('item_id', $item_id)
			->where('tenant_id', $tenant_id)
			->count();

		if ($allowed == 0) {
			$add_tenant = new TenantsAllowed;
			$add_tenant->item_id = $item_id;
			$add_tenant->tenant_id = $tenant_id;
			$add_tenant->save();
		}

		//redirect
		if (empty($return_url)) {
			$return_url = $ref_mainpath;
		}
		return redirect($return_url);
	}

	public function remove_tenant($item_id, $tenant_id)
	{
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if tenant_id and user_id are int
		if (!MyHelper::is_int($tenant_id) or !MyHelper::is_int($item_id)) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['delete'] = TenantsAllowed::where('item_id', $item_id)
			->where('tenant_id', $tenant_id)
			->delete();

		return redirect('admin/qlik_items/tenant/' . $item_id);
	}

	//aggiungi un gruppo a quelli autorizzati a vedere il qlik item
	public function add_authorization($item_id)
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
		//check if item is already in group
		$access = ItemsAllowed::where('item_id', $item_id)
			->where('group_id', $group_id)
			->count();

		if ($access == 0) {
			$add_group = new ItemsAllowed;
			$add_group->item_id = $item_id;
			$add_group->group_id = $group_id;
			$add_group->save();
		}

		//redirect
		if (empty($return_url)) {
			$return_url = $ref_mainpath;
		}
		return redirect($return_url);
	}

	//rimuovi un gruppo da quelli autorizzati a vedere il qlik item
	public function remove_authorization($item_id, $group_id)
	{
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if group_id and user_id are int
		if (!MyHelper::is_int($group_id) or !MyHelper::is_int($item_id)) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['delete'] = ItemsAllowed::where('item_id', $item_id)
			->where('group_id', $group_id)
			->delete();

		return redirect('admin/qlik_items/access/' . $item_id);
	}

	//load edit page
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
		$page_title = trans(
			"crudbooster.edit_data_page_title",
			[
				'module' => CRUDBooster::getCurrentModule()->name,
				'name' => $row->{$this->title_field}
			]
		);
		$command = 'edit';
		Session::put('current_row_id', $id);

		$qlik_item = QlikItem::find($id);
		$is_public = $qlik_item->isPublic();
		$button_save = true;


		return view('qlik_items.form', compact('id', 'row', 'page_menu', 'page_title', 'command', 'is_public', 'button_save'));
	}

	//overwrite default method
	public function getDetail($id)
	{
		//load detail page
		$this->cbLoader();
		$row = DB::table($this->table)->where($this->primary_key, $id)->first();

		if (!CRUDBooster::isRead() && $this->global_privilege == false || $this->button_detail == false) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
				'name' => $row->{$this->title_field},
				'module' => CRUDBooster::getCurrentModule()->name,
			]));
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		$module = CRUDBooster::getCurrentModule();

		$page_menu = Route::getCurrentRoute()->getActionName();
		$page_title = trans("crudbooster.detail_data_page_title", ['module' => $module->name, 'name' => $row->{$this->title_field}]);
		$command = 'detail';

		Session::put('current_row_id', $id);

		return view('qlik_items.form', compact('row', 'page_menu', 'page_title', 'command', 'id'));
	}

	public function GetRouteSenseHub($qlik_item)
	{
		$this->cbLoader();
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::insertLog(trans('crudbooster.log_try_add', ['module' => CRUDBooster::getCurrentModule()->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data = [];
		$conf = DB::table('qlik_confs')->where('id', $qlik_item)->first();

		$view = 'qlik_items.view';
		$data = [];
		$js_login = '';
		if ($conf->auth == 'JWT') {

			if ($conf->type == 'SAAS') {
				$url = $conf->url;
				$url .= '/hub/';
				$token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
				$js_login = "js/qliksaas_login.js";


			} else {
				$url = $conf->url;
				$url .= '/hub/';
				$token = HelpersQlikHelper::getJWTTokenOP(CRUDBooster::myId(), $conf->id);
				$js_login = "js/qlik_op_jwt_login.js";

			}


			if (empty($token)) {
				$data['error'] = 'JWT Token generation failed!';
				CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
			}
			$data['token'] = $token;
			$data['item_url'] = $conf->url;

			$data['prefix'] = $conf->endpoint;

			$data['tenant'] = $conf->url;
			$data['web_int_id'] = $conf->web_int_id;

			$view = 'qlik_items.view_saas';

		} else {
			$url = $conf->qrsurl;
			$url .= '/hub/';
			$qlik_ticket = QlikHelper::getTicket($qlik_item);
			$url .= '?';
			$url .= 'xrfkey=0123456789abcdef&QlikTicket=' . $qlik_ticket;
			$js_login = "js/qlik_login.js";


		}

		$row = new \stdClass;
		$row->frame_width = '100%';
		$row->frame_height = '100%';
		$row->url = $url;
		$row->title = 'Qlik Sense';
		$row->subtitle = 'Hub';
		$row->description = '';

		$data['row'] = $row;
		$data['page_icon'] = 'qlik_icon';
		$data['page_title'] = $data['row']->title;
		$data['help'] = $data['row']->description;
		$data['subtitle'] = $data['row']->subtitle;
		$data['item_url'] =  htmlspecialchars_decode($data['row']->url);
		$data['debug'] = $conf->debug;

		$data['js_login'] = $js_login;

		$this->cbView($view, $data);
	}

	public function GetRouteSenseQMC($qlik_item)
	{
		$this->cbLoader();
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::insertLog(trans('crudbooster.log_try_add', ['module' => CRUDBooster::getCurrentModule()->name]));
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$conf = DB::table('qlik_confs')->where('id', $qlik_item)->first();

		$view = 'qlik_items.view';
		$data = [];


		$js_login = '';
		if ($conf->auth == 'JWT') {

			if ($conf->type == 'SAAS') {

				$url = $conf->url;
				$url .= '/qmc/';
				$token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);

				$js_login = "js/qliksaas_login.js";

			} else {
				$url = $conf->url;
				$url .= '/qmc/';
			
				$token = HelpersQlikHelper::getJWTTokenOP(CRUDBooster::myId(), $conf->id);

				$js_login = "js/qlik_op_jwt_login.js";

			}

			if (empty($token)) {
				$data['error'] = 'JWT Token generation failed!';
				CRUDBooster::redirect(CRUDBooster::adminPath(), $data['error']);
			}
			$data['token'] = $token;
			$data['item_url'] = $conf->url;

			$data['prefix'] = $conf->endpoint;

			$data['tenant'] = $conf->url;
			$data['web_int_id'] = $conf->web_int_id;

			$view = 'qlik_items.view_saas';

		} else {
			$url = $conf->qrsurl;
			$url .= '/qmc/';
			$qlik_ticket = QlikHelper::getTicket($qlik_item);
			$url .= '?';
			$url .= 'xrfkey=0123456789abcdef&QlikTicket=' . $qlik_ticket;

			$js_login = "js/qlik_login.js";


		}

		

		$row = new \stdClass;
		$row->frame_width = '100%';
		$row->frame_height = '100%';
		$row->url = $url;
		$row->title = 'Qlik Sense';
		$row->subtitle = 'qmc';
		$row->description = '';

		$data['row'] = $row;
		$data['page_icon'] = 'qlik_icon';
		$data['page_title'] = $data['row']->title;
		$data['help'] = $data['row']->description;
		$data['subtitle'] = $data['row']->subtitle;
		$data['item_url'] =  htmlspecialchars_decode($data['row']->url);
		$data['debug'] = $conf->debug;
		$data['js_login'] = $js_login;

		$this->cbView($view, $data);
	}
}
