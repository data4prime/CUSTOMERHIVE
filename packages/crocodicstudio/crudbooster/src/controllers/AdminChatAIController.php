<?php

namespace crocodicstudio\crudbooster\controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use GroupHelper;

use Illuminate\Support\Facades\Route;

use crocodicstudio\crudbooster\helpers\ChatAIHelper;

use App\ChatAIConf;
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
		$this->table = "chatai_confs";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Title", "name" => "title"];
		$this->col[] = ["label" => "Method", "name" => "method"];
		$this->col[] = ["label" => "Auth", "name" => "auth"];
		$this->col[] = ["label" => "Url", "name" => "url"];


		//primary
		$this->col[] = ["label" => "Primary", "name" => "primary", "callback_php" => '($row->primary == 1) ? "Yes" : "No" '];

		

		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Title', 'name' => 'title', 'type' => 'text', 'validation' => 'required|string|min:1|max:70', 'width' => 'col-sm-10', 'placeholder' => 'Item title'];
		$this->form[] = ['label' => 'Method', 'name' => 'method', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'GET;POST;PUT;DELETE', 'placeholder' => 'Method to call the API'];
		$this->form[] = ['label' => 'Auth', 'name' => 'auth', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'JWT;', 'placeholder' => 'Authentication method'];
		$this->form[] = ['label' => 'Url', 'name' => 'url', 'type' => 'text', 'validation' => 'required|string', 'width' => 'col-sm-10', 'placeholder' => 'API endpoint'];
		$this->form[] = ['label' => 'Token', 'name' => 'token', 'type' => 'textarea', 'validation' => 'required|string', 'width' => 'col-sm-10', 'placeholder' => 'API token'];
		$this->form[] = ['label' => 'Primary', 'name' => 'primary', 'type' => 'radio', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => '1;0', 'placeholder' => 'Primary configuration'];
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
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('content/[id]'), 'icon' => 'fa fa-search', 'color' => 'info', 'title' => 'View item'];
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


		//get record with id = $id
		$record = ChatAIConf::find($id);

		//if primary = 1
		if ($record->primary == 1) {
			//edit all configuration, set primary to 0
			ChatAIConf::where('id', '!=', $id)
				->update(['primary' => 0]);
		}


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

		//get record with id = $id
		$record = ChatAIConf::find($id);

		//if primary = 1
		if ($record->primary == 1) {
			//edit all configuration, set primary to 0
			ChatAIConf::where('id', '!=', $id)
				->update(['primary' => 0]);
		}



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
		$data['chat_ai'] = ChatAIConf::find($item_id);
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

		$this->cbView('chat_ai.access', $data);
	}

	public function tenant($item_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['item_id'] = $item_id;
		$data['chat_ai'] = ChatAIConf::find($item_id);
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
		$this->cbView('chat_ai.tenant', $data);
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

		return redirect('admin/chat_ai/tenant/' . $item_id);
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

		return redirect('admin/chat_ai/access/' . $item_id);
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


		$button_save = true;


		return view('chat_ai.form', compact('id', 'row', 'page_menu', 'page_title', 'command',  'button_save'));
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

		return view('chat_ai.form', compact('row', 'page_menu', 'page_title', 'command', 'id'));
	}

	public function send_message() {

		//check if in the session exists chat_messages array
		if (!Session::has('chat_messages')) {
			Session::put('chat_messages', []);
		}

		

		$message = Request::all()['message'];

		$chatai_conf = DB::table('chatai_confs')->where('primary', 1)->first();


		$url = $chatai_conf->url;
		$token = $chatai_conf->token;

		// Imposta i dati da inviare nel corpo della richiesta
		$data = [
			"action" => "sendMessage",
			"sessionId" => '"'.CRUDBooster::myId().'"',
			"chatInput" => $message
		];

		// Inizializza cURL
		$ch = curl_init($url);

		// Configura le opzioni cURL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'Content-Type: application/json',
			"Authorization: Bearer $token"
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


		$response = curl_exec($ch);

		//dd($response);

		if ($response === false) {
			//echo json_encode(['message' => 'Errore nella richiesta. Verifica la configurazione attiva!']);


			//$response->message = 'Errore nella richiesta. Verifica la configurazione attiva!';

			//$response_message = ['message' => 'Errore nella richiesta. Verifica la configurazione attiva!'];
			$response_message = 'Errore nella richiesta. Verifica la configurazione attiva!';

			$response = json_encode(["message" => $response_message]);


		} else {
			$response_message = json_decode($response, true);
			if (isset($response_message["text"])) {
				$response_message = $response_message["text"];
			} else {
				$response_message = $response_message["message"];
			}
		}



		curl_close($ch);

		

		$chat_messages = Session::get('chat_messages');
		$chat_messages[] = ['message' => $message, 'response' => $response_message];

		Session::put('chat_messages', $chat_messages);

		//echo json_encode($response_message);

		// Stampa la risposta
		echo $response;


	}


	public static function getConf() {


		$chatai_conf = DB::table('chatai_confs')->first();





		return $chatai_conf;


	}

	public function content_view($qlik_item_id)
	{
		$allowed = ChatAIHelper::can_see_item($qlik_item_id);
		if (!$allowed) {
			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$data = [];
		$data['row'] = ChatAIConf::find($qlik_item_id);
		//dd($data);
		if (empty($data['row'])) {

			CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.missing_item"));
		}

		//$type = CRUDBooster::getSetting('type');
		//add menu settings
		if (isset($_GET['m'])) {
			$menu = Menu::find($_GET['m']);
		} else {
			$menu = Menu::where('name', 'Dashboard')->where('is_active', 1)->where('is_dashboard', 1)->first();
		}
		//dd($menu);
		//$menu = Menu::find(isset($_GET['m']) ? $_GET['m'] : '89');
		if (empty($menu)) {
			$data['row']->frame_width = '100%';
			$data['row']->frame_height = '100%';
		} else {
			$data['row']->frame_width = $menu->frame_width;
			$data['row']->frame_height = $menu->frame_height;
		}
		$data['row']->target_layout = isset($menu) ? $menu->target_layout : '';

		$data['page_icon'] = '';
		$data['page_title'] = $data['row']->title;
		$data['help'] = $data['row']->description;
		$data['subtitle'] = $data['row']->subtitle;


			if ($menu->target_layout == 1) {
				$this->cbView('chat_ai.fullscreen_view', $data);
			} else {
				$this->cbView('chat_ai.view', $data);
			}
		
	}

	public function send_message_agent() {
		//check if in the session exists chat_messages array
		if (!Session::has('chat_messages')) {
			//Session::put('chat_messages', []);
		}

		

		$message = Request::all()['message'];

		$agent_id = Request::all()['agent_id'];

		$chatai_conf = DB::table('chatai_confs')->where('id', $agent_id)->first();


		$url = $chatai_conf->url;
		$token = $chatai_conf->token;

		// Imposta i dati da inviare nel corpo della richiesta
		$data = [
			"action" => "sendMessage",
			"sessionId" => '"'.CRUDBooster::myId().'"',
			"chatInput" => $message
		];

		// Inizializza cURL
		$ch = curl_init($url);

		// Configura le opzioni cURL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'Content-Type: application/json',
			"Authorization: Bearer $token"
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));


		$response = curl_exec($ch);

		//dd($response);

		if ($response === false) {
			//echo json_encode(['message' => 'Errore nella richiesta. Verifica la configurazione attiva!']);


			//$response->message = 'Errore nella richiesta. Verifica la configurazione attiva!';

			//$response_message = ['message' => 'Errore nella richiesta. Verifica la configurazione attiva!'];
			$response_message = 'Errore nella richiesta. Verifica la configurazione attiva!';

			$response = json_encode(["message" => $response_message]);


		} else {
			$response_message = json_decode($response, true);
			if (isset($response_message["text"])) {
				$response_message = $response_message["text"];
			} else {
				$response_message = $response_message["message"];
			}
		}



		curl_close($ch);

		

		$chat_messages = Session::get('chat_messages');
		$chat_messages[] = ['message' => $message, 'response' => $response_message];

		//Session::put('chat_messages', $chat_messages);

		//echo json_encode($response_message);

		// Stampa la risposta
		echo $response;


	}


}
