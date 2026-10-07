<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

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
use App\Helpers\LicenseHelper;

class AdminTenantsController extends CBController
{

	public function cbInit()
	{

		// Pagine nuovo/modifica/dettaglio in stile mockup (intervento 232)
		\App\Helpers\FlatForm::share([trans('crudbooster.adm_tenant_new'), trans('crudbooster.adm_tenant_edit'), trans('crudbooster.adm_tenant_view')]);

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
		$this->button_show = false;
		$this->button_filter = true;
		$this->button_import = false;
		$this->button_export = false;
		$this->table = "tenants";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		// Lista snella (intervento 232): via Id, Favicon e i due colori di
		// login (si vedono nell'anteprima del form di modifica); dentro
		// ci sono dominio e conteggi utenti/gruppi. I conteggi sono colonne
		// con callback su "id" (non subquery: il filtro avanzato lavora su
		// colonne vere e con una subquery darebbe errore SQL).
		$this->col = [];
		// Logo: se manca, un quadrato con le iniziali del tenant (colore
		// scelto dal nome, sempre lo stesso per lo stesso tenant).
		$this->col[] = ["label" => "Logo", "name" => "logo", "callback" => function ($row) {
			if (!empty($row->logo)) {
				$pic = (strpos($row->logo, 'http') === 0) ? $row->logo : asset($row->logo);
				return "<a data-lightbox='roadtrip' title='" . e($row->name) . "' href='" . e($pic) . "'><img width='40' height='40' style='border-radius:8px;object-fit:cover' src='" . e($pic) . "' alt=''></a>";
			}
			$tones = ['blue', 'violet', 'success', 'warning', 'danger'];
			$tone = $tones[crc32((string) $row->name) % count($tones)];
			$words = preg_split('/\s+/u', trim((string) $row->name), -1, PREG_SPLIT_NO_EMPTY);
			$initials = '';
			foreach (array_slice($words, 0, 2) as $w) {
				$initials .= mb_strtoupper(mb_substr($w, 0, 1));
			}
			return "<span style='display:inline-grid;place-items:center;width:40px;height:40px;border-radius:8px;font-weight:700;font-size:13px;background:var(--ch-{$tone}-soft, var(--ch-accent-soft));color:var(--ch-{$tone})'>" . e($initials ?: '?') . "</span>";
		}];
		$this->col[] = ["label" => "Name", "name" => "name"];
		$this->col[] = ["label" => "Description", "name" => "description"];
		$this->col[] = ["label" => trans('crudbooster.adm_domain'), "name" => "domain_name"];
		$this->col[] = [
			"label" => trans('crudbooster.adm_users'),
			"align" => "center",
			"name" => "id",
			"callback" => function ($row) {
				return '<div class="text-center" style="font-variant-numeric:tabular-nums">' . (int) User::where('tenant', $row->id)->count() . '</div>';
			},
		];
		$this->col[] = [
			"label" => trans('crudbooster.adm_groups'),
			"align" => "center",
			"name" => "id",
			"callback" => function ($row) {
				return '<div class="text-center" style="font-variant-numeric:tabular-nums">' . (int) GroupTenants::where('tenant_id', $row->id)->count() . '</div>';
			},
		];
		// Data di sistema in formato italiano (gg/mm/aaaa hh:mm), come il registro accessi
		$this->col[] = ["label" => "Created At", "name" => "created_at", "callback" => function ($row) {
			return $row->created_at ? '<span style="white-space:nowrap">' . e(date('d/m/Y H:i', strtotime($row->created_at))) . '</span>' : '';
		}];
		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Name', 'name' => 'name', 'type' => 'text', 'validation' => 'required', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Logo', 'name' => 'logo', 'type' => 'image', 'shape' => 'square', 'icon' => 'bi-image', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Favicon', 'name' => 'favicon', 'type' => 'image', 'shape' => 'square', 'icon' => 'bi-star', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Background Color', 'name' => 'login_background_color', 'type' => 'color', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Background Image', 'name' => 'login_background_image', 'type' => 'image', 'shape' => 'square', 'icon' => 'bi-card-image', 'width' => 'col-sm-9', 'validation' => 'image|max:10000', 'help' => 'Supported types: jpg, png, gif. Max 10 MB'];
		$this->form[] = ['label' => 'Font Color', 'name' => 'login_font_color', 'type' => 'color', 'width' => 'col-sm-9'];
		$this->form[] = ['label' => 'Domain name', 'name' => 'domain_name', 'type' => 'text', 'width' => 'col-sm-9', 'help' => 'use only letters and numbers', 'validation' => 'required|min:1|max:20|regex:/^[a-zA-Z0-9]+$/u'];
	$this->form[] = ['label' => 'Tenant Path', 'name' => 'tenant_path', 'type' => 'hidden', 'width' => 'col-sm-10', 'value' => env('APP_URL')];

		// Pagina a schede come nel mockup (intervento 232): Identita' / Pagina di
		// login (campi + anteprima) / Dominio. "login_preview" e "login_uri" sono
		// campi 'custom' di sola visualizzazione ('exception': non si salvano).
		$tenantRow = ($tid = CRUDBooster::getCurrentId()) ? Tenant::find($tid) : null;
		$this->form[] = [
			'label' => trans('crudbooster.adm_login_preview'),
			'name' => 'login_preview',
			'type' => 'custom',
			'exception' => true,
			'html' => ($previewHtml = view('tenants.login_preview', ['row' => $tenantRow])->render()),
			'value' => $previewHtml,
		];
		if ($tenantRow) {
			$loginURI = TenantHelper::loginPath($tenantRow->id);
			$loginLink = '<a target="_blank" href="' . e($loginURI) . '">' . e($loginURI) . '</a>';
			$this->form[] = [
				'label' => trans('crudbooster.adm_tenant_login_uri'),
				'name' => 'login_uri',
				'type' => 'custom',
				'exception' => true,
				'html' => '<div class="form-control" style="height:auto">' . $loginLink . '</div>',
				'value' => $loginLink,
			];
		}
		$layoutBlock = function ($id, $x, $w, array $names) {
			$fields = [];
			foreach ($names as $name => $fw) {
				$fields[] = ['name' => $name, 'w' => $fw];
			}
			return ['id' => $id, 'title' => '', 'x' => $x, 'y' => 0, 'w' => $w, 'h' => max(3, 2 + count($fields)), 'fields' => $fields];
		};
		$this->form_layout = ['v' => 2, 'tabs' => [
			['id' => 't1', 'title' => trans('crudbooster.adm_tenant_tab_identity'), 'blocks' => [
				$layoutBlock('b1', 0, 12, ['name' => 6, 'description' => 6, 'logo' => 6, 'favicon' => 6]),
			]],
			['id' => 't2', 'title' => trans('crudbooster.adm_tenant_tab_login'), 'blocks' => [
				$layoutBlock('b2', 0, 8, ['login_background_color' => 6, 'login_font_color' => 6, 'login_background_image' => 12]),
				$layoutBlock('b3', 8, 4, ['login_preview' => 12]),
			]],
			['id' => 't3', 'title' => trans('crudbooster.adm_tenant_tab_domain'), 'blocks' => [
				$layoutBlock('b4', 0, 12, $tenantRow ? ['domain_name' => 6, 'login_uri' => 12] : ['domain_name' => 6]),
			]],
		]];

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
	        | @icon        = Font awesome class icon. e.g : bi bi-list
	        | @color 	   = Default is primary. (primary, warning, succecss, info)
	        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
	        |
	        */
		$this->addaction = array();
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('members/[id]'), 'icon' => 'bi bi-person-fill', 'color' => 'info', 'title' => trans('crudbooster.adm_tenant_users_action')];
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('group/[id]'), 'icon' => 'bi bi-people-fill', 'color' => 'info', 'title' => trans('crudbooster.adm_tenant_groups_action')];

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
			return CRUDBooster::redirect( g('return_url') ?: CRUDBooster::referer() , $message, $message_type);
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
			return CRUDBooster::redirect(CRUDBooster::adminPath('tenants'), trans('crudbooster.not_unique_domain'));
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
			return CRUDBooster::redirect(CRUDBooster::adminPath('tenants'), trans('crudbooster.delete_not_empty_tenant_count', ['count' => $members_count]));
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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		$page_menu = Route::getCurrentRoute()->getActionName();
		$page_title = trans("crudbooster.edit_tenants");
		$command = 'edit';
		Session::put('current_row_id', $id);

		return view('crudbooster::default.form', compact('id', 'row', 'page_menu', 'page_title', 'command'));
	}

	public function members($tenant_id)
	{
		//check auth
		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data = [];
		$data['members'] = DB::table('cms_users')
			->where('cms_users.tenant', $tenant_id)
			->leftJoin('cms_privileges', 'cms_privileges.id', '=', 'cms_users.id_cms_privileges')
			->leftJoin('groups', 'groups.id', '=', 'cms_users.primary_group')
			->select('cms_users.id', 'cms_users.name', 'cms_users.email', 'cms_users.photo', 'cms_users.status', 'cms_users.id_cms_privileges', 'cms_privileges.name as privilege', 'cms_privileges.is_superadmin', 'cms_privileges.is_tenantadmin', 'groups.name as primary_group_name')
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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['tenant_id'] = $tenant_id;
		$data['tenant'] = Tenant::find($tenant_id);
		$data['groups'] = GroupTenants::where('tenant_id', $tenant_id)
			->join('groups', 'groups.id', '=', 'group_tenants.group_id')
			->get();
		$data['page_title'] = trans('crudbooster.adm_tenant_add_group_title');
		// Gruppi ancora non associati al tenant: elenco della modale "Aggiungi gruppo"
		$data['available_groups'] = DB::table('groups')
			->whereNotExists(function ($query) use ($tenant_id) {
				$query->select(DB::raw(1))
					->from('group_tenants')
					->whereRaw('group_tenants.group_id = groups.id')
					->where('group_tenants.tenant_id', (int) $tenant_id);
			})
			->orderBy('name')
			->get(['groups.id', 'groups.name', 'groups.description']);

		//prendo $_GET &alert=
		if (!empty($alert_id)) {
			//se è alert=1
			if ($alert_id == '1') {
				//mostra messaggio di warning per tasto add premuto senza valori required
				$data['alerts'][] = ['message' => '<h4><i class="icon bi bi-exclamation-triangle-fill"></i> Warning!</h4>Select an element to add...', 'type' => 'warning'];
			}
		}

		//add tenant form
		$data['forms'] = [];
		$data['forms'][] = ['label' => trans('crudbooster.adm_group_label'), 'name' => 'name', 'type' => 'tenant_group_datamodal', 'width' => 'col-sm-6', 'datamodal_table' => 'groups', 'datamodal_where' => '', 'datamodal_columns' => 'name', 'datamodal_columns_alias' => 'Name', 'datamodal_select_to' => $tenant_id, 'required' => true];
		$data['forms'][] = ['label' => trans('crudbooster.description'), 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => '', 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($tenant_id . "/add_group");
		$data['return_url'] = CRUDBooster::mainpath('group/' . $tenant_id);

		$data['command'] = 'add';
		$data['button_addmore'] = false;

		$this->cbView('tenants.group', $data);
	}

	public function add_group($tenant_id)
	{

		if (!CRUDBooster::isSuperadmin()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
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
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if tenant_id and user_id are int
		if (!MyHelper::is_int($group_id) or !MyHelper::is_int($tenant_id)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['delete'] = GroupTenants::where('group_id', $group_id)
			->where('tenant_id', $tenant_id)
			->delete();

		return redirect('admin/tenants/group/' . $tenant_id);
	}
}
