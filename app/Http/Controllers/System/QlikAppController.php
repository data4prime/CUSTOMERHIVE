<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Session;
use Request;
use DB;
use CRUDBooster;
use \App\Helpers\UserHelper;
use \App\Helpers\QlikHelper;
use App\Helpers\LicenseHelper;
use App\Http\Controllers\System\Concerns\HandlesQlikSyncStart;
use App\QlikSyncRun;
use App\Services\QlikSync\QlikSyncRollback;
use App\Services\QlikSync\QlikSyncService;
use App\Services\QlikSync\QlikSyncUi;


class QlikAppController extends CBController
{
	use HandlesQlikSyncStart;

	public function cbInit()
	{

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->title_field = "appname";
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
		$this->table = "qlik_apps";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "App Name", "name" => "appname"];
        $this->col[] = ["label" => "App ID", "name" => "appid"];
		//$this->col[] = ["label" => "Conf", "name" => "conf"];
		$this->col[] = array("label" => "Qlik Conf", "name" => "conf", "join" => "qlik_confs,confname");
		// Badge "non piu' presente su Qlik" (solo per le app sincronizzate)
		$this->col[] = ["label" => trans('crudbooster.qlik_sync_col_status'), "name" => "is_missing", "callback" => function ($row) {
			return !empty($row->is_missing)
				? "<span class='label label-warning' style='background:#f0ad4e;color:#fff;padding:2px 6px;border-radius:3px'>" . e(trans('crudbooster.qlik_sync_missing_badge')) . "</span>"
				: '';
		}];


		# END COLUMNS DO NOT REMOVE THIS LINE


		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'App Name', 'name' => 'appname', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter App Name'];
        $this->form[] = ['label' => 'App ID', 'name' => 'appid', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter App ID'];
        // select2 (ricercabile, ordinata per confname) al posto di select; niente
        // relationship_table: la conf e' un valore singolo, non una relazione N-N.
        $this->form[] = ['label' => 'Conf', 'name' => 'conf', 'type' => 'select2', 'width' => 'col-sm-10',
                            "datatable" => "qlik_confs,confname",
                            'required' => true,
                        ];



    if (CRUDBooster::isSuperadmin()) {
      $this->form[] = [
        'label' => 'Tenant',
        'name' => 'qlikapps_tenants',
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "tenants,name",
        "relationship_table" => "qlikapps_tenants",
        'required' => true,
        'validation' => 'required',
        'value' => UserHelper::current_user_tenant()
      ];

      $this->form[] = [
        "label" => "Group",
        "name" => "qlikapps_groups",
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "groups,name",
        "relationship_table" => "qlikapps_groups",
        "required" => true,
        'parent_select' => 'qlikapps_tenants',
        'parent_crosstable' => 'group_tenants',
        'fk_name' => 'tenant_id',
        'child_crosstable_fk_name' => 'group_id'
      ];
    } elseif (UserHelper::isTenantAdmin()) {

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

      $this->form[] = [
        "label" => "Group",
        "name" => "menu_groups",
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "groups,name",
        "relationship_table" => "qlikapps_groups",
        "required" => true,
        'parent_select' => 'tenant',
        'parent_crosstable' => 'group_tenants',
        'fk_name' => 'tenant_id',
        'child_crosstable_fk_name' => 'group_id'
      ];
    }


		# Users submodule
		// #RAMA questo subform riesce ad aggiungere nuovi utenti e a mostrarli ma permette di aggiungere due volte lo stesso utente allo stesso gruppo, non riesco a mostrare un secondo campo nel form e nella tabella, non posso nascondere il tasto edit dalla tabella, fa confusione come interfaccia
		// $columns[] = ['label'=>'User','name'=>'user_id','type'=>'datamodal','datamodal_table'=>'cms_users','datamodal_columns'=>'name','datamodal_select_to'=>'email:email','datamodal_where'=>'','datamodal_size'=>'large'];
		// $this->form[] = ['label'=>'Group members','name'=>'users_groups','type'=>'child','columns'=>$columns,'table'=>'users_groups','foreign_key'=>'group_id'];
		# END FORM DO NOT REMOVE THIS LINE

		# OLD START FORM
		//$this->form = [];
		//$this->form[] = ['label'=>'Name','name'=>'name','type'=>'text','validation'=>'required|string|min:3|max:70','width'=>'col-sm-10','placeholder'=>'You can only enter the letter only'];
		//$this->form[] = ['label'=>'Description','name'=>'description','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-10'];
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
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('members/[id]'), 'icon' => 'fa fa-user', 'color' => 'info', 'title' => 'Members'];
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('items/[id]'), 'icon' => 'fa fa-shield', 'color' => 'info', 'title' => 'Items'];

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
		$this->alert = array();



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

		// Pulsanti "Sincronizza da Qlik" / "Sincronizzazioni" nella lista.
		QlikSyncUi::boot($this, 'apps');
	}

	// ------------------------------------------------------------------
	// Sincronizzazione app da Qlik (docs/piano-qlik-sync-app-items.md)
	// ------------------------------------------------------------------

	/**
	 * Avvia la sincronizzazione delle app di una configurazione.
	 * Route auto-instradata: POST admin/qlik_apps/sync-start.
	 */
	public function postSyncStart()
	{
		return $this->qlikSyncStart(QlikSyncRun::TYPE_APPS);
	}

	/** Solo superadmin e modulo Qlik in licenza; altrimenti torna alla home. */
	private function qlikSyncDenied()
	{
		if (!CRUDBooster::isSuperadmin() || !LicenseHelper::isActiveQlik()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		return null;
	}

	private function qlikSyncRunsQuery()
	{
		return DB::table('qlik_sync_runs as r')
			->leftJoin('qlik_confs as c', 'c.id', '=', 'r.qlik_conf_id')
			->leftJoin('cms_users as u', 'u.id', '=', 'r.user_id')
			->leftJoin('qlik_apps as a', 'a.id', '=', 'r.qlik_app_id')
			->select('r.*', 'c.confname', 'u.name as user_name', 'a.appname');
	}

	/** Elenco delle sincronizzazioni: GET admin/qlik_apps/sync-runs */
	public function syncRuns()
	{
		if ($denied = $this->qlikSyncDenied()) {
			return $denied;
		}

		$data['runs'] = $this->qlikSyncRunsQuery()->orderByDesc('r.id')->limit(100)->get();
		$data['has_active'] = $data['runs']->contains(function ($r) {
			return in_array($r->status, QlikSyncRun::activeStatuses(), true);
		});
		$data['stuck'] = QlikSyncService::hasStuckQueuedRuns();
		$data['page_title'] = trans('crudbooster.qlik_sync_runs_title');
		$data['page_icon'] = 'fa fa-tasks';

		$this->cbView('qlik_sync.runs', $data);
	}

	/** Dettaglio di una sincronizzazione: GET admin/qlik_apps/sync-runs/{id} */
	public function syncRunDetail($id)
	{
		if ($denied = $this->qlikSyncDenied()) {
			return $denied;
		}

		$run = $this->qlikSyncRunsQuery()->where('r.id', (int) $id)->first();
		if (!$run) {
			return CRUDBooster::redirect(QlikSyncUi::runsUrl(), trans('crudbooster.qlik_sync_run_not_found'), 'warning');
		}

		$records = DB::table('qlik_sync_run_records as rr')
			->leftJoin('qlik_apps as a', function ($join) {
				$join->on('a.id', '=', 'rr.record_id')->where('rr.record_type', '=', 'app');
			})
			->leftJoin('qlik_items as i', function ($join) {
				$join->on('i.id', '=', 'rr.record_id')->where('rr.record_type', '=', 'item');
			})
			->where('rr.run_id', (int) $id)
			->select('rr.*', DB::raw('COALESCE(a.appname, i.title) as record_name'))
			->orderByDesc('rr.id')
			->limit(500)
			->get();

		$data['run'] = $run;
		$data['records'] = $records;
		$data['records_total'] = DB::table('qlik_sync_run_records')->where('run_id', (int) $id)->count();
		$data['is_active'] = in_array($run->status, QlikSyncRun::activeStatuses(), true);
		$data['can_rollback'] = !$data['is_active'] && $run->rolled_back_at === null
			&& DB::table('qlik_sync_run_records')->where('run_id', (int) $id)->whereIn('action', ['created', 'linked'])->exists();
		$data['stuck'] = QlikSyncService::hasStuckQueuedRuns();
		$data['page_title'] = trans('crudbooster.qlik_sync_run_title', ['id' => $run->id]);
		$data['page_icon'] = 'fa fa-tasks';

		$this->cbView('qlik_sync.run', $data);
	}

	/** Annulla un run attivo: POST admin/qlik_apps/sync-runs/{id}/cancel (mode = keep | delete) */
	public function syncRunCancel($id)
	{
		if ($denied = $this->qlikSyncDenied()) {
			return $denied;
		}

		$run = QlikSyncRun::find((int) $id);
		if (!$run) {
			return CRUDBooster::redirect(QlikSyncUi::runsUrl(), trans('crudbooster.qlik_sync_run_not_found'), 'warning');
		}

		QlikSyncService::requestCancel($run, Request::input('mode') === 'delete');

		return CRUDBooster::redirect(QlikSyncUi::runsUrl() . '/' . $run->id, trans('crudbooster.qlik_sync_cancel_requested'), 'info');
	}

	/** Annulla gli effetti di un run concluso: POST admin/qlik_apps/sync-runs/{id}/rollback */
	public function syncRunRollback($id)
	{
		if ($denied = $this->qlikSyncDenied()) {
			return $denied;
		}

		$run = QlikSyncRun::find((int) $id);
		if (!$run) {
			return CRUDBooster::redirect(QlikSyncUi::runsUrl(), trans('crudbooster.qlik_sync_run_not_found'), 'warning');
		}
		if ($run->isActive() || $run->rolled_back_at !== null) {
			return CRUDBooster::redirect(QlikSyncUi::runsUrl() . '/' . $run->id, trans('crudbooster.qlik_sync_rollback_not_allowed'), 'warning');
		}

		$result = QlikSyncRollback::rollback($run);

		return CRUDBooster::redirect(
			QlikSyncUi::runsUrl() . '/' . $run->id,
			trans('crudbooster.qlik_sync_rollback_done', $result),
			$result['kept'] > 0 ? 'warning' : 'success'
		);
	}

	public static function getMashupFromCompID($compID) {
		$mashup = DB::table('cms_statistic_components')->where('componentID', $compID)->first();
		if (isset($mashup)){
			$mashup = json_decode($mashup->config);
			if (isset($mashup->mashups)) {
				$mashup = $mashup->mashups;
				$mashup = DB::table('qlik_apps')->where('id', $mashup)->first();
			} else {
				$mashup = null;
			}

			
		} else {
			$mashup = null;
		}

		return $mashup;

	}

	public static function getConf($id) {
		$qlik_conf = DB::table('qlik_confs')->where('id', $id)->first();
		$return = [];

		$return['type'] = isset($qlik_conf->type) ? $qlik_conf->type : null;

		if (isset($id) && $qlik_conf) {
				$return['id'] = $qlik_conf->id;
				$return['host'] = $qlik_conf->url;
				$return['webIntegrationId'] = '';
				$return['port'] = $qlik_conf->port;
				$return['prefix'] = $qlik_conf->endpoint;
				$return['auth'] = $qlik_conf->auth;
			if (QlikHelper::confIsSAAS($id)) {
				$return['id'] = $qlik_conf->id;
				$return['host'] = $qlik_conf->url;
				$return['webIntegrationId'] = $qlik_conf->web_int_id;
				$return['port'] = $qlik_conf->port;
				$return['prefix'] = $qlik_conf->endpoint;
				$return['auth'] = $qlik_conf->auth;
			} 
		}

		$return = (object) $return;
		return $return;

	}

	public static function getMashups() {
		//se super admin prendo tutti i mashups
		if (CRUDBooster::isSuperadmin()) {
			$mashups = DB::table('qlik_apps')
			->select('qlik_apps.*')

			->get();
		} elseif (UserHelper::isTenantAdmin()) {
			$mashups = DB::table('qlik_apps')
				->select('qlik_apps.*')
				->join('qlikapps_tenants', 'qlik_apps.id', '=', 'qlikapps_tenants.qlikmashup_id')

				->where('tenant_id', UserHelper::current_user_tenant())

				->get();
		} else {
			$mashups = DB::table('qlik_apps')
						->select('qlik_apps.*')
				->join('qlikapps_groups', 'qlik_apps.id', '=', 'qlikapps_groups.qlikmashup_id')
				->join('group_users', 'group_users.group_id', '=', 'qlikapps_groups.group_id')
				->where('group_users.user_id', CRUDBooster::myId())
				->get();
		}



		return $mashups;

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
		//Your code here
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


}
