<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Session;
use DB;
use CRUDBooster;

use Illuminate\Support\Facades\Route;

use App\Helpers\ChatAIHelper;
use App\Helpers\LicenseHelper;

use App\Actions\ChatAI\AllowGroupForChatAI;
use App\Actions\ChatAI\AllowTenantForChatAI;
use App\Actions\ChatAI\RevokeGroupForChatAI;
use App\Actions\ChatAI\RevokeTenantForChatAI;
use App\Actions\ChatAI\SetPrimaryChatAIConf;
use App\Models\ChatAIConf;
use App\Models\ChatAIGroupAllowed;
use App\Models\ChatAITenantAllowed;
use App\Http\Controllers\System\Concerns\SafeRedirectTarget;
use App\Http\Requests\ChatAI\SendAgentMessageRequest;
use App\Http\Requests\ChatAI\SendChatMessageRequest;
use App\Models\Menu;
use App\Services\ChatAI\ChatAIClient;

use MyHelper;

class AdminChatAIController extends CBController
{
	use SafeRedirectTarget;

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
		$this->button_show = false;
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
		$this->form[] = ['label' => 'Title', 'name' => 'title', 'type' => 'text', 'validation' => 'required|string|min:1|max:70', 'width' => 'col-sm-10', 'placeholder' => 'Chat AI title'];
		$this->form[] = ['label' => 'Method', 'name' => 'method', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'GET;POST', 'placeholder' => 'Method to call the API'];
		$this->form[] = ['label' => 'Auth', 'name' => 'auth', 'type' => 'select', 'validation' => 'required', 'width' => 'col-sm-10', 'dataenum' => 'JWT;', 'placeholder' => 'Authentication method'];
		$this->form[] = ['label' => 'Url', 'name' => 'url', 'type' => 'text', 'validation' => 'required|string', 'width' => 'col-sm-10', 'placeholder' => 'API endpoint'];
		// min:32: firebase/php-jwt 7.x rifiuta chiavi HS256 piu' corte di 32
		// byte (vedi docs/refactoring/083). Vale per nuove configurazioni e
		// per ogni modifica; le esistenti piu' corte vanno aggiornate.
		$this->form[] = ['label' => 'Passphrase', 'name' => 'token', 'type' => 'textarea', 'validation' => 'required|string|min:32', 'width' => 'col-sm-10', 'placeholder' => 'Token Passphrase', 'help' => trans('crudbooster.chatai_passphrase_help')];
		$this->form[] = ['label' => 'Primary', 'name' => 'primary', 'type' => 'checkbox', 'value' => 1, 'width' => 'col-sm-10', 'placeholder' => 'Primary configuration',];
		$this->form[] = ['label' => 'URL Help', 'name' => 'url_help', 'type' => 'text', 'validation' => 'string|min:1|max:200', 'width' => 'col-sm-10', 'placeholder' => 'Chat AI helper'];

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
        | @icon        = Font awesome class icon. e.g : bi bi-list
        | @color 	   = Default is primary. (primary, warning, succecss, info)
        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
        |
        */
		$this->addaction = array();
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('content/[id]'), 'icon' => 'bi bi-search', 'color' => 'info', 'title' => 'View item'];
		//modulo interamente ChatAI: senza il modulo in licenza niente pulsanti
		if (LicenseHelper::isActiveChatAI()) {
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('access/[id]'), 'icon' => 'bi bi-people-fill', 'color' => 'info', 'title' => 'Set group'];
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('tenant/[id]'), 'icon' => 'bi bi-buildings-fill', 'color' => 'primary', 'title' => 'Set tenant'];
		}


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
		$this->normalizePrimary($postdata);
	}

	/**
	 * Checkbox "primary": il form invia la stringa "primary", il DB vuole 1/0.
	 * Privato: ogni metodo pubblico di un controller CB diventa una rotta.
	 */
	private function normalizePrimary(array &$postdata): void
	{
		$postdata['primary'] = $postdata['primary'] == "primary" ? 1 : 0;
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
		//get record with id = $id
		$record = ChatAIConf::find($id);

		//se primary = 1 azzera le altre configurazioni
		(new SetPrimaryChatAIConf())->handle($record);
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

		$this->normalizePrimary($postdata);

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
		//get record with id = $id
		$record = ChatAIConf::find($id);

		//se primary = 1 azzera le altre configurazioni
		(new SetPrimaryChatAIConf())->handle($record);
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
		// Cascata sulle sole tabelle ChatAI: le righe Qlik con lo stesso id non c'entrano
		ChatAIGroupAllowed::where('chatai_conf_id', $id)->delete();
		ChatAITenantAllowed::where('chatai_conf_id', $id)->delete();
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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$data = $this->authorizationPageData($item_id, $alert_id, [
			'pivot' => 'chatai_groups_allowed',
			'entity' => 'groups',
			'foreignKey' => 'group_id',
			'fieldType' => 'item_access_datamodal',
			'titleKey' => 'chatai_authorize_group',
			'placeholderKey' => 'chatai_group_description_placeholder',
			'actionPath' => '/auth',
			'returnPath' => 'access/',
		]);

		$this->cbView('chat_ai.access', $data);
	}

	public function tenant($item_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data = $this->authorizationPageData($item_id, $alert_id, [
			'pivot' => 'chatai_tenants_allowed',
			'entity' => 'tenants',
			'foreignKey' => 'tenant_id',
			'fieldType' => 'item_tenant_datamodal',
			'titleKey' => 'chatai_authorize_tenant',
			'placeholderKey' => 'chatai_tenant_description_placeholder',
			'actionPath' => '/add_tenant',
			'returnPath' => 'tenant/',
		]);

		$this->cbView('chat_ai.tenant', $data);
	}

	/**
	 * Dati comuni delle pagine access() e tenant(): elenco gia' autorizzato,
	 * eventuale alert e form di aggiunta. La chiave dell'elenco ('groups' /
	 * 'tenants') coincide con la tabella, come si aspettano le viste. Privato:
	 * ogni metodo pubblico di un controller CB diventa una rotta. Piano 332.
	 *
	 * @param array{pivot:string,entity:string,foreignKey:string,fieldType:string,titleKey:string,placeholderKey:string,actionPath:string,returnPath:string} $cfg
	 */
	private function authorizationPageData($item_id, $alert_id, array $cfg): array
	{
		$data['item_id'] = $item_id;
		$data['chat_ai'] = ChatAIConf::find($item_id);
		$data[$cfg['entity']] = DB::table($cfg['pivot'])
			->where($cfg['pivot'] . '.chatai_conf_id', $item_id)
			->join($cfg['entity'], $cfg['entity'] . '.id', '=', $cfg['pivot'] . '.' . $cfg['foreignKey'])
			->select($cfg['entity'] . '.*')
			->get();
		$data['page_title'] = trans('crudbooster.' . $cfg['titleKey']);

		//prendo $_GET &alert=
		if (!empty($alert_id)) {
			//se è alert=1
			if ($alert_id == '1') {
				//mostra messaggio di warning per tasto add premuto senza valori required
				$data['alerts'][] = ['message' => '<h4><i class="icon bi bi-exclamation-triangle-fill"></i> ' . trans('crudbooster.qlik_warning') . '</h4>' . trans('crudbooster.chatai_select_element_to_add'), 'type' => 'warning'];
			}
		}

		//form di aggiunta (gruppo o tenant)
		$data['forms'] = [];
		$data['forms'][] = ['label' => trans('crudbooster.name'), 'name' => 'name', 'type' => $cfg['fieldType'], 'width' => 'col-sm-6', 'datamodal_table' => $cfg['entity'], 'datamodal_where' => '', 'datamodal_columns' => 'name', 'datamodal_columns_alias' => trans('crudbooster.name'), 'datamodal_select_to' => $item_id, 'datamodal_allowed_scope' => 'chatai', 'required' => true];
		$data['forms'][] = ['label' => trans('crudbooster.description'), 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => trans('crudbooster.' . $cfg['placeholderKey']), 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($item_id . $cfg['actionPath']);
		$data['return_url'] = CRUDBooster::mainpath($cfg['returnPath'] . $item_id);
		$data['command'] = 'add';
		$data['button_addmore'] = false;

		return $data;
	}

	public function add_tenant($item_id)
	{

		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$tenant_id = request()->input('name');
		$ref_mainpath = $this->safeRedirectTarget(request()->input('ref_mainpath'), CRUDBooster::mainpath());
		$return_url = $this->safeRedirectTarget(request()->input('return_url'), '');

		$conf = MyHelper::is_int($item_id) ? ChatAIConf::find($item_id) : null;
		if (!$conf) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		if (empty($tenant_id) || !MyHelper::is_int($tenant_id) || !DB::table('tenants')->where('id', $tenant_id)->exists()) {
			return redirect($return_url . '/alert/1');
		}

		(new AllowTenantForChatAI())->handle($conf, $tenant_id);

		//redirect
		if (empty($return_url)) {
			$return_url = $ref_mainpath;
		}
		return redirect($return_url);
	}

	public function remove_tenant($item_id, $tenant_id)
	{
		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if tenant_id and user_id are int
		if (!MyHelper::is_int($tenant_id) or !MyHelper::is_int($item_id)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		(new RevokeTenantForChatAI())->handle($item_id, $tenant_id);

		return redirect('admin/chat_ai/tenant/' . $item_id);
	}

	//aggiungi un gruppo a quelli autorizzati a vedere il qlik item
	public function add_authorization($item_id)
	{
		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$group_id = request()->input('name');
		$ref_mainpath = $this->safeRedirectTarget(request()->input('ref_mainpath'), CRUDBooster::mainpath());
		$return_url = $this->safeRedirectTarget(request()->input('return_url'), '');

		$conf = MyHelper::is_int($item_id) ? ChatAIConf::find($item_id) : null;
		if (!$conf) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		if (empty($group_id) || !MyHelper::is_int($group_id) || !DB::table('groups')->where('id', $group_id)->exists()) {
			return redirect($return_url . '/alert/1');
		}

		(new AllowGroupForChatAI())->handle($conf, $group_id);

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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if group_id and user_id are int
		if (!MyHelper::is_int($group_id) or !MyHelper::is_int($item_id)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		(new RevokeGroupForChatAI())->handle($item_id, $group_id);

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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		$module = CRUDBooster::getCurrentModule();

		$page_menu = Route::getCurrentRoute()->getActionName();
		$page_title = trans("crudbooster.detail_data_page_title", ['module' => $module->name, 'name' => $row->{$this->title_field}]);
		$command = 'detail';

		Session::put('current_row_id', $id);

		return view('chat_ai.form', compact('row', 'page_menu', 'page_title', 'command', 'id'));
	}

	public function send_message(SendChatMessageRequest $request) {

		//check if in the session exists chat_messages array
		if (!Session::has('chat_messages')) {
			Session::put('chat_messages', []);
		}



		$message = $request->validated()['message'];

		// Le conf soft-deleted non partecipano (getToken() le esclude gia' con find())
		$chatai_conf = DB::table('chatai_confs')->where('primary', 1)->whereNull('deleted_at')->first();

		// Nessuna conf primary attiva: prima era un 500 su $chatai_conf->url
		if (!$chatai_conf) {
			echo json_encode(["message" => trans('crudbooster.missing_item')]);
			return;
		}

		$this->relayToAgent($chatai_conf, $message, 'chat_messages', null);


	}


	public static function getConf() {


		$chatai_conf = DB::table('chatai_confs')->first();





		return $chatai_conf;


	}

	public function content_view($chat_ai_id)
	{
		$allowed = ChatAIHelper::can_see_item($chat_ai_id);
		if (!$allowed) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$row = ChatAIConf::find($chat_ai_id);
		if (empty($row)) {

			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.missing_item"));
		}

		//menu: quello indicato da ?m= (anche se vuoto, come isset) oppure la dashboard attiva
		$menu = request()->query->has('m')
			? Menu::find(request()->query('m'))
			: Menu::activeDashboard()->first();

		$data = $this->frameData($row, $menu);

		if (isset($menu->target_layout) && $menu->target_layout == 1) {
			$this->cbView('chat_ai.fullscreen_view', $data);
		} else {
			$this->cbView('chat_ai.view', $data);
		}

	}

	/**
	 * Dimensioni del frame, layout e dati di pagina di content_view(): dal menu
	 * se c'e', altrimenti 100%. Privato: ogni metodo pubblico di un controller CB
	 * diventa una rotta. Piano 332.
	 */
	private function frameData(ChatAIConf $row, $menu): array
	{
		if (empty($menu)) {
			$row->frame_width = '100%';
			$row->frame_height = '100%';
		} else {
			$row->frame_width = $menu->frame_width;
			$row->frame_height = $menu->frame_height;
		}
		$row->target_layout = isset($menu) ? $menu->target_layout : '';

		return [
			'row' => $row,
			'page_icon' => '',
			'page_title' => $row->title,
			'help' => $row->url_help,
			'subtitle' => $row->subtitle,
		];
	}

	public function send_message_agent(SendAgentMessageRequest $request) {
		//check if in the session exists chat_messages array

		// Il controllo can_see_item($agent_id) (doc 329) e la validazione sono nel
		// Form Request: un rifiuto risponde in JSON prima di toccare la sessione.
		$validated = $request->validated();

		$message = $validated['message'];

		$agent_id = $validated['agent_id'];

		if (!Session::has('chat_messages_'.$agent_id)) {
			Session::put('chat_messages_'.$agent_id, []);
		}

		$chatai_conf = DB::table('chatai_confs')->where('id', $agent_id)->whereNull('deleted_at')->first();

		// Raggiungibile solo dal superadmin (can_see_item e' sempre vero per lui):
		// un id inesistente dava un 500 su $chatai_conf->url.
		if (!$chatai_conf) {
			echo json_encode(["message" => trans('crudbooster.missing_item')]);
			return;
		}


		$this->relayToAgent($chatai_conf, $message, 'chat_messages_'.$agent_id, $agent_id);


	}

	/**
	 * Parte comune di send_message() e send_message_agent(): token, richiesta
	 * all'agente, sessione, storico, risposta. Privato: ogni metodo pubblico di un
	 * controller CB diventa una rotta. Piano 331.
	 *
	 * @param object      $chatai_conf     riga di chatai_confs gia' scelta dal chiamante
	 * @param string      $sessionKey      chiave di sessione della conversazione
	 * @param int|null    $historyConfId   chat_ai_id dello storico (null per la chat flottante)
	 */
	private function relayToAgent($chatai_conf, $message, string $sessionKey, $historyConfId) {

		$token = ChatAIHelper::getToken($chatai_conf->id);

		// Passphrase non utilizzabile (es. < 32 caratteri con php-jwt 7.x). Vedi
		// docs/refactoring/083.
		if (!$token) {
			echo json_encode(["message" => trans('crudbooster.chatai_token_error')]);
			return;
		}

		$result = (new ChatAIClient())->send($chatai_conf->url, $token, $message, CRUDBooster::myId());

		$chat_messages = Session::get($sessionKey);
		$chat_messages[] = ['message' => $message, 'response' => $result['message']];

		Session::put($sessionKey, $chat_messages);
		ChatAIHelper::saveChatHistory($historyConfId, ['message' => $message, 'response' => $result['message']]);

		echo $result['raw'];
	}


}
