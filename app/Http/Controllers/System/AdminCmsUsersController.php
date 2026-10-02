<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Session;
use Request;
//use DB;
use Illuminate\Support\Facades\DB;
//use CRUDbooster;
use \App\Helpers\CRUDBooster;
use Illuminate\Support\Facades\Route;
use \App\Tenant;
use \App\Group;
use \App\UsersGroup;
use \App\Helpers\GroupHelper;
use \App\Helpers\UserHelper;
use \App\Helpers\MyHelper;
use \App\Helpers\ModuleHelper;
use App\Helpers\QlikHelper;
use App\Helpers\LicenseHelper;
use App\Helpers\MfaHelper;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AdminCmsUsersController extends CBController
{

	public function cbInit()
	{

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->table               = 'cms_users';
		$this->primary_key         = 'id';
		$this->title_field         = "name";
		$this->button_action_style = 'button_icon';
		$this->button_import 	   = FALSE;
		$this->button_export 	   = FALSE;
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = array();
		$this->col[] = array("label" => "Name", "name" => "name");
		$this->col[] = array("label" => "Email", "name" => "email");
		$this->col[] = array("label" => "Privilege", "name" => "id_cms_privileges", "join" => "cms_privileges,name");
		if (CRUDBooster::isSuperadmin()) {
			$this->col[] = array("label" => "Tenant", "name" => "tenant", "join" => "tenants,name");
		}
		/*$this->col[] = array("label" => "User directory", "name" => "user_directory");*/


		// Senza il controllo su vuoto, strtotime(null) ritorna 0 e date() lo
		// formatta come 01/01/1970 (epoca Unix) invece di non mostrare nulla.
		$this->col[] = array("label" => "Expiry date", "name" => "data_scadenza", "callback_php" => "empty(\$row->data_scadenza) ? '' : date('d/m/Y',strtotime(\$row->data_scadenza))");
		$this->col[] = array("label" => "Status", "name" => "status");
		$this->col[] = array("label" => "Photo", "name" => "photo", "image" => 1);




		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = array();
		$this->form[] = array("label" => "Name", "name" => "name", 'required' => true, 'validation' => 'required|alpha_spaces|min:3');
		$this->form[] = array("label" => "Email", "name" => "email", 'required' => true, 'type' => 'email', 'validation' => 'required|email|unique:cms_users,email,' . CRUDBooster::getCurrentId());
		if (UserHelper::isSuperAdmin()) {
			$this->form[] = array("label" => "Privilege", "name" => "id_cms_privileges", "type" => "select", "datatable" => "cms_privileges,name", 'required' => true, 'validation' => 'required|int|min:1');
		}
		if (UserHelper::isTenantAdmin()) {
			//tenantadmin can't edit users privileges (can't upgradte to admins and can't demote admins)
			$this->form[] = array(
				"label" => "Privilege",
				"name" => "id_cms_privileges",
				"type" => "select",
				"datatable" => "cms_privileges,name",
				'required' => true,
				'validation' => 'required|int|min:1',
				'disabled' => true
			);
			//aggiungo un campo Privilege hidden perchè con la Privilege select disabled viene salvato uno 0
			$this->form[] = [
				"label" => "Privilege",
				"name" => "id_cms_privileges",
				'type' => 'hidden'
			];
		}

		$this->form[] = array(
			"label" => "Expiry date",
			"type" => "date",
			"name" => "data_scadenza",
			'required' => false,
			'validation' => 'date',
			'disable' => UserHelper::isTenantAdmin() || CRUDBooster::isSuperadmin() ? false : true,
		);



		$this->form[] = [
			"label" => "Status",
			"name" => "status",
			'required' => true,
			'type' => 'select',
			'dataenum' => ['Inactive'],
			'default' => 'Active',
			'disabled' => UserHelper::isTenantAdmin() || CRUDBooster::isSuperadmin() ? false : true,
		];
		$this->form[] = array("label" => "Photo", "name" => "photo", "type" => "upload", "help" => "Recommended resolution is 200x200px", 'required' => false, 'validation' => 'image|max:1000', 'resize_width' => 90, 'resize_height' => 90);
		$this->form[] = array("label" => "Language", "name" => "lang", "type" => "select", "dataenum" => ['en|English', 'it|Italiano'], "value" => "en");



		// Policy password in stile NIST 800-63B: lunghezza minima invece di
		// regole di composizione, blocco password comuni/prevedibili (vedi
		// App\Helpers\PasswordPolicy e AppServiceProvider::boot()). 'nullable'
		// preserva il comportamento "lascia vuoto per non cambiarla" in
		// modifica/profilo; in creazione diventa 'required' cosi' non si
		// possono piu' creare utenti senza password.
		// form_body.blade.php deduce da solo l'asterisco "required" cercando
		// la sottostringa 'required' dentro 'validation', quindi non serve
		// ripeterlo anche nella chiave 'required' dell'array.
		// La password si sceglie solo in creazione: in modifica (e dal
		// profilo) la cambia l'utente stesso con password attuale + codice
		// di verifica (postProfilePasswordStart/Confirm), oppure un
		// superadmin/tenant admin gli manda un link di reset
		// (postUserResetPassword). Senza questi campi in $this->form,
		// CBController::input_assignment() non tocca mai la colonna password
		// in modifica, nemmeno con una POST costruita a mano.
		if (CRUDBooster::isAddPage()) {
			$password_validation = 'required|min:12|max:72|confirmed|not_common_password';
			$this->form[] = array(
				"label" => "Password",
				"name" => "password",
				"type" => "password",
				"validation" => $password_validation,
				"help" => trans('crudbooster.reset_password_policy_hint'),
			);
			$this->form[] = array("label" => "Password Confirmation", "name" => "password_confirmation", "type" => "password", "help" => trans('crudbooster.password_leave_empty_hint'));
		}

		// Tenant/Primary Group spostati qui in fondo (erano subito dopo
		// Email/Privilege): il box collassabile "System Information" che
		// li contiene (form_body.blade.php, si apre su 'tenant' e si
		// chiude su 'primary_group' - vedi docs/refactoring/103-*)
		// interrompeva il flusso dei campi principali del profilo,
		// comparendo "in mezzo" tra Email ed Expiry date. Spostare
		// l'intero blocco non cambia nulla nel salvataggio (l'ordine in
		// $this->form non incide su CBController::input_assignment(), che
		// scrive per nome, non per posizione) - solo dove appare in pagina.
		if (CRUDBooster::isSuperadmin()) {
			$this->form[] = [
				'label' => 'Tenant',
				'name' => 'tenant',
				"type" => "select2",
				"datatable" => "tenants,name",

				'required' => true,
				'validation' => 'required|int|min:1',
				'value' => UserHelper::current_user_tenant() //default value if new user
			];
			//superadmin vede i gruppi come cascading dropdown in base al tenant
			$exploded_request_uri = explode('/', parse_url($_SERVER['REQUEST_URI'])['path']);
			$user_id = $exploded_request_uri[sizeof($exploded_request_uri) - 1];
			if ($user_id !== 'profile') {
				$default = UserHelper::primary_group($user_id);
			} else {
				$default = UserHelper::current_user_primary_group();
			}
			$this->form[] = [
				'label' => 'Primary Group',
				'name' => 'primary_group',
				"type" => "select2",
				"datatable" => "groups,name",
				'required' => true,
				'validation' => 'required|int|min:1',
				'value' => $default, //default value if new user
				'parent_select' => 'tenant',
				'parent_crosstable' => 'group_tenants',
				'fk_name' => 'tenant_id',
				'child_crosstable_fk_name' => 'group_id'
			];
		} elseif (UserHelper::isTenantAdmin()) {
			//Tenantadmin vede tenant in readonly (disabled) ma può modificare il proprio primary group
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
			//aggiungo un campo tenant hidden perchè con la tenant select disabled viene salvato uno 0
			$this->form[] = [
				"label" => "Tenant",
				"name" => "tenant",
				'type' => 'hidden'
			];
			$this->form[] = [
				'label' => 'Primary Group',
				'name' => 'primary_group',
				"type" => "select2",
				"datatable" => "groups,name",
				'required' => true,
				'validation' => 'required|int|min:1',
				'default' => UserHelper::current_user_primary_group_name(),
				'value' => UserHelper::current_user_primary_group(),
				'parent_select' => 'tenant',
				'parent_crosstable' => 'group_tenants',
				'fk_name' => 'tenant_id',
				'child_crosstable_fk_name' => 'group_id'
			];
		} else {
			//per basic tenant e primary group sono campi readonly (disabled)
			$this->form[] = array("label" => "Tenant", "name" => "tenant", 'required' => true, 'type' => 'select', 'datatable' => "tenants,name", 'default' => '', 'disabled' => true);

			$this->form[] = [
				"label" => "Primary group",
				"name" => "primary_group",
				'required' => true,
				'type' => 'select2',
				'datatable' => "groups,name",
				'default' => '',
				'disabled' => true
			];
		}

		if (!CRUDBooster::isAddPage() && !CRUDBooster::isProfilePage() ) {
		//QLIK USERS START
		if(LicenseHelper::isActiveQlik()) {
				$columns[] = ['label'=>'Qlik Conf','name'=>'qlik_conf_id','type'=>'datamodal','datamodal_table'=>'qlik_confs','datamodal_columns'=>'confname,type','datamodal_select_to'=>'confname:confname,type:type','datamodal_where'=>'','datamodal_size'=>'large'];
				$columns[] = array("label" => "Qlik login", "name" => "qlik_login", 'type'=>'text', 'help' => 'Fill in manually if the selected configuration is of type On-Premise.');
				$columns[] = array("label" => "User directory", "name" => "user_directory", 'type'=>'text', 'help' => 'Fill in manually if the selected configuration is of type On-Premise.');
				$columns[] = array("label" => "Qlik Cloud IDP Subject", "name" => "idp_qlik", 'type'=>'text', 'help' => 'If the selected configuration is of type SaaS, the field will be populated after saving the user if left empty. If you want to obtain a different IDP, delete the current value and save the user; the system will automatically create a new IDP.');

				
					$this->form[] = ['label'=>'Qlik Users','name'=>'qlik_users','type'=>'child','columns'=>$columns, 'required' => true,'table'=>'qlik_users','foreign_key'=>'user_id'];
		}
		//QLIK USERS END
		}
				

# END FORM DO NOT REMOVE THIS LINE
		$user_id = CRUDBooster::myId();
		$this->script_js = "
			/*
			var row = document.getElementById('idp_qlik').parentNode;
			row.className = ' col-sm-5';


			var newColumn = document.createElement('div');
			newColumn.className = 'col-sm-5';


			var newButton = document.createElement('a');
			newButton.innerHTML = 'Create Qlik user';
			newButton.className='btn btn-info';
			newButton.href = '/admin/qlik/user/creare/{$user_id}';


			newColumn.appendChild(newButton);


			row.parentNode.insertBefore(newColumn, row.nextSibling);

			*/
			";

		$this->addaction = array();
		//TODO tenantadmin can't update users with superadmin or tenantadmin privilege
		$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('groups/[id]'), 'icon' => 'bi bi-people-fill', 'color' => 'info', 'title' => 'View groups'];
	}

	public function getProfile()
	{

		$this->button_addmore = FALSE;
		$this->button_cancel  = FALSE;
		$this->button_show    = FALSE;
		$this->button_add     = FALSE;
		$this->button_delete  = FALSE;
		$this->hide_form 	  = ['id_cms_privileges'];
		$data['command'] = 'add';
		$data['button_addmore'] = false;

		$data['page_title'] = trans("crudbooster.label_button_profile");
		$data['row']        = CRUDBooster::first('cms_users', CRUDBooster::myId());

		$currentUser = UserHelper::me();
		$data['mfa_trusted_devices'] = $currentUser ? MfaHelper::listTrustedDevices($currentUser) : collect();

		// Pagina a sezioni (Generale / MFA / Sistema / Password), ciascuna
		// con il suo endpoint di salvataggio JSON - vedi i metodi
		// postProfile* qui sotto. Non passa piu' dal form generico
		// CBController (default.form) ne' da edit-save.
		$isSuperadmin = UserHelper::isSuperAdmin();
		$isTenantAdmin = UserHelper::isTenantAdmin();
		$data['canManage'] = $isSuperadmin || $isTenantAdmin;
		$data['canEditTenant'] = $isSuperadmin;
		$data['tenants'] = $isSuperadmin
			? DB::table('tenants')->orderBy('name')->get(['id', 'name'])
			: DB::table('tenants')->where('id', $data['row']->tenant)->get(['id', 'name']);
		// Gruppi raggruppati per tenant (tabella pivot group_tenants), per il
		// menu a cascata Tenant -> Primary Group che il form vecchio faceva
		// con la select2 'parent_select'. Un tenant admin vede solo i gruppi
		// del proprio tenant.
		$groupsQuery = DB::table('group_tenants')
			->join('groups', 'groups.id', '=', 'group_tenants.group_id')
			->whereNull('groups.deleted_at')
			->orderBy('groups.name');
		if (! $isSuperadmin) {
			$groupsQuery->where('group_tenants.tenant_id', $data['row']->tenant);
		}
		$data['groupsByTenant'] = $groupsQuery
			->get(['group_tenants.tenant_id', 'groups.id', 'groups.name'])
			->groupBy('tenant_id')
			->map(function ($rows) {
				return $rows->map(function ($r) {
					return ['id' => (int) $r->id, 'name' => $r->name];
				})->values();
			});

		// Sezione Qlik: solo se la licenza include il modulo.
		$data['qlikEnabled'] = LicenseHelper::isActiveQlik();
		if ($data['qlikEnabled']) {
			$data['qlikConfs'] = DB::table('qlik_confs')->orderBy('confname')->get(['id', 'confname', 'type']);
			$data['qlikUsers'] = DB::table('qlik_users')
				->where('user_id', $data['row']->id)
				->orderBy('id')
				->get(['qlik_conf_id', 'qlik_login', 'user_directory', 'idp_qlik']);
		}

		$this->cbView('users.profile', $data);
	}

	/**
	 * Sezione "Qlik": associazioni utente -> configurazione Qlik (tabella
	 * qlik_users). Solo superadmin/tenant admin: login e user directory
	 * decidono con quale identita' Qlik entra l'utente, quindi un utente base
	 * non deve poterli scegliere da solo. Stessa logica di
	 * prepare_qlik_users() (IDP generato per le conf SaaS se vuoto), ma sul
	 * solo utente del profilo e senza passare dal form generico.
	 */
	public function postProfileQlik()
	{
		$user = UserHelper::me();

		if (! $user || (! UserHelper::isSuperAdmin() && ! UserHelper::isTenantAdmin()) || ! LicenseHelper::isActiveQlik()) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$validator = Validator::make(Request::all(), [
			'rows' => 'nullable|array',
			'rows.*.qlik_conf_id' => 'required|integer|distinct|exists:qlik_confs,id',
			'rows.*.qlik_login' => 'nullable|string|max:255',
			'rows.*.user_directory' => 'nullable|string|max:255',
		]);
		if ($validator->fails()) {
			return $this->profileValidationError($validator);
		}

		// L'IDP non e' modificabile dall'utente: si ignora quello ricevuto e
		// si tiene quello gia' salvato per la stessa configurazione.
		$existingIdp = DB::table('qlik_users')->where('user_id', $user->id)->pluck('idp_qlik', 'qlik_conf_id');

		$rows = [];
		foreach ((array) Request::input('rows', []) as $r) {
			$confId = (int) $r['qlik_conf_id'];
			$idp = trim((string) ($existingIdp[$confId] ?? ''));
			$login = trim((string) ($r['qlik_login'] ?? ''));
			$directory = trim((string) ($r['user_directory'] ?? ''));

			// Come nella UI: SaaS usa solo l'IDP, On-Premise solo login +
			// user directory. I campi dell'altro tipo vengono scartati.
			if (QlikHelper::confIsSAAS($confId)) {
				$login = $directory = '';
				if ($idp === '') {
					$idp = (string) QlikHelper::createUser($user->id, $confId);
					if ($idp === '') {
						return $this->profileResponse(false, trans('crudbooster.profile_qlik_idp_failed'), [], 422);
					}
				}
			} else {
				$idp = '';
			}

			$rows[] = [
				'user_id' => $user->id,
				'qlik_conf_id' => $confId,
				'qlik_login' => $login ?: null,
				'user_directory' => $directory ?: null,
				'idp_qlik' => $idp ?: null,
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		DB::transaction(function () use ($user, $rows) {
			DB::table('qlik_users')->where('user_id', $user->id)->delete();
			if ($rows) {
				DB::table('qlik_users')->insert($rows);
			}
		});

		return $this->profileResponse(true, trans('crudbooster.profile_qlik_saved'), ['reload' => true]);
	}

	private function profileResponse($ok, $message, array $extra = [], $status = 200)
	{
		return response()->json(array_merge(['ok' => $ok, 'message' => $message], $extra), $status);
	}

	private function profileValidationError($validator)
	{
		return $this->profileResponse(false, $validator->errors()->first(), ['errors' => $validator->errors()], 422);
	}

	/**
	 * Rate limit condiviso dai passi "invio codice"/"verifica codice" del
	 * profilo (stesso criterio di postMfaConfirm: senza, un codice a 6 cifre
	 * e' forzabile a forza bruta). $hit = true registra un tentativo.
	 */
	private function profileTooManyAttempts($name, $userId, $hit = false)
	{
		$key = 'profile-' . $name . ':' . $userId;

		if (RateLimiter::tooManyAttempts($key, 5)) {
			return true;
		}

		if ($hit) {
			RateLimiter::hit($key, 900);
		}

		return false;
	}

	/**
	 * Sezione "Generale": solo nome, lingua, foto e - se l'utente e'
	 * superadmin/tenant admin - stato e data di scadenza. L'email ha il
	 * suo flusso (postProfileEmail*) e la password il suo: nessuno dei due
	 * passa da qui, anche se arrivano nella richiesta.
	 */
	public function postProfileGeneral()
	{
		$user = UserHelper::me();
		if (! $user) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$canManage = UserHelper::isSuperAdmin() || UserHelper::isTenantAdmin();

		$rules = [
			'name' => 'required|alpha_spaces|min:3',
			'lang' => 'required|in:en,it',
			'photo' => 'nullable|image|max:1000',
		];
		if ($canManage) {
			$rules['status'] = 'required|in:Active,Inactive';
			$rules['data_scadenza'] = 'nullable|date';
		}

		$validator = Validator::make(Request::all(), $rules);
		if ($validator->fails()) {
			return $this->profileValidationError($validator);
		}

		$update = [
			'name' => Request::input('name'),
			'lang' => Request::input('lang'),
		];
		if ($canManage) {
			$update['status'] = Request::input('status');
			$update['data_scadenza'] = Request::input('data_scadenza') ?: null;
		}

		$photo = CRUDBooster::uploadFile('photo', false, 90, 90, $user->id);
		if ($photo) {
			$update['photo'] = $photo;
		}

		if (\Illuminate\Support\Facades\Schema::hasColumn('cms_users', 'updated_at')) {
			$update['updated_at'] = date('Y-m-d H:i:s');
		}
		if (\Illuminate\Support\Facades\Schema::hasColumn('cms_users', 'updated_by')) {
			$update['updated_by'] = $user->id;
		}

		$langChanged = $user->lang !== $update['lang'];

		DB::table('cms_users')->where('id', $user->id)->update($update);

		// Nome e foto nell'header/sidebar vengono dalla sessione (popolata al
		// login), non dal DB: senza questo restano quelli vecchi fino al
		// prossimo login.
		Session::put('admin_name', $update['name']);
		if ($photo) {
			Session::put('admin_photo', UserHelper::icon($user->id));
		}

		return $this->profileResponse(true, trans('crudbooster.profile_saved'), [
			'reload' => $langChanged,
			'photo_url' => $photo ? asset($photo) : null,
		]);
	}

	/**
	 * Cambio email, passo 1: controlla password attuale e unicita', poi la
	 * verifica dipende da MfaHelper::emailChangeMode():
	 * - 'totp': serve solo il codice dell'app authenticator (passo 2);
	 * - 'email': codice inviato al NUOVO indirizzo (prova che l'utente lo
	 *   controlla); se l'invio fallisce l'operazione si ferma;
	 * - 'none' (niente TOTP e SMTP non configurato): la nuova email viene
	 *   salvata subito, la sola password attuale e' la prova.
	 * Nei primi due casi nulla viene salvato finche' il passo 2 non riesce.
	 */
	public function postProfileEmailStart()
	{
		$user = UserHelper::me();
		if (! $user) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		if ($this->profileTooManyAttempts('email-start', $user->id)) {
			return $this->profileResponse(false, trans('crudbooster.profile_too_many_attempts'), [], 429);
		}

		$validator = Validator::make(Request::all(), [
			'new_email' => 'required|email|max:255|unique:cms_users,email,' . $user->id,
			'current_password' => 'required',
		]);
		if ($validator->fails()) {
			return $this->profileValidationError($validator);
		}

		$newEmail = trim((string) Request::input('new_email'));

		if (strcasecmp($newEmail, (string) $user->email) === 0) {
			return $this->profileResponse(false, trans('crudbooster.profile_email_same'), [], 422);
		}

		if (! \Hash::check((string) Request::input('current_password'), $user->password)) {
			$this->profileTooManyAttempts('email-start', $user->id, true);

			return $this->profileResponse(false, trans('crudbooster.profile_password_wrong_current'), [], 422);
		}

		$this->profileTooManyAttempts('email-start', $user->id, true);

		$mode = MfaHelper::emailChangeMode($user);

		// Ne' TOTP ne' SMTP: nessun codice possibile, basta la password attuale.
		if ($mode === 'none') {
			return $this->applyProfileEmailChange($user, $newEmail, false);
		}

		if ($mode === 'email') {
			MfaHelper::invalidateEmailOtpCodes($user);
			if (! MfaHelper::sendEmailOtp($user, $newEmail)) {
				return $this->profileResponse(false, trans('crudbooster.profile_otp_send_failed'), [], 503);
			}
		}

		// Con il TOTP attivo non si invia nessuna email: basta il codice
		// dell'app (email_ok gia' true), anche se SMTP e' configurato.
		Session::put('profile_pending_email', [
			'email' => $newEmail,
			'expires' => now()->addMinutes(MfaHelper::EMAIL_OTP_MINUTES)->timestamp,
			'email_ok' => $mode === 'totp',
			'totp_ok' => $mode !== 'totp',
		]);

		return $this->profileResponse(true, $mode === 'email'
			? trans('crudbooster.profile_email_code_sent', ['minutes' => MfaHelper::EMAIL_OTP_MINUTES])
			: trans('crudbooster.profile_email_totp_required'), [
			'needs_totp' => $mode === 'totp',
			'needs_email_code' => $mode === 'email',
		]);
	}

	/**
	 * Cambio email, passo 2: codice email (nuovo indirizzo) e, se l'utente ha
	 * il TOTP attivo, anche il codice dell'app authenticator. Ogni codice
	 * giusto viene ricordato in sessione, cosi' se l'altro e' sbagliato non
	 * va reinserito quello gia' consumato (monouso).
	 */
	public function postProfileEmailConfirm()
	{
		$user = UserHelper::me();
		if (! $user) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$pending = Session::get('profile_pending_email');
		if (! $pending || $pending['expires'] < time()) {
			Session::forget('profile_pending_email');

			return $this->profileResponse(false, trans('crudbooster.profile_otp_expired'), ['expired' => true], 422);
		}

		if ($this->profileTooManyAttempts('email-verify', $user->id)) {
			return $this->profileResponse(false, trans('crudbooster.profile_too_many_attempts'), [], 429);
		}

		if (! $pending['email_ok']) {
			if (! MfaHelper::verifyEmailOtp($user, trim((string) Request::input('code')))) {
				$this->profileTooManyAttempts('email-verify', $user->id, true);

				return $this->profileResponse(false, trans('crudbooster.profile_otp_wrong'), [], 422);
			}
			$pending['email_ok'] = true;
			Session::put('profile_pending_email', $pending);
		}

		if (! $pending['totp_ok']) {
			if (! MfaHelper::verifyAndConsume($user, trim((string) Request::input('totp_code')))) {
				$this->profileTooManyAttempts('email-verify', $user->id, true);

				return $this->profileResponse(false, trans('crudbooster.profile_otp_wrong'), ['email_ok' => true], 422);
			}
			$pending['totp_ok'] = true;
			Session::put('profile_pending_email', $pending);
		}

		return $this->applyProfileEmailChange($user, $pending['email'], true);
	}

	/**
	 * Salva la nuova email e chiude sessioni/dispositivi attendibili. Usato
	 * sia a verifica completata (passo 2) sia, senza TOTP ne' SMTP, subito
	 * dal passo 1 ($verified = false, tracciato nel log).
	 */
	private function applyProfileEmailChange($user, string $newEmail, bool $verified)
	{
		// Un altro utente puo' aver preso l'indirizzo tra il passo 1 e il 2.
		$taken = DB::table('cms_users')->where('email', $newEmail)->where('id', '!=', $user->id)->exists();
		if ($taken) {
			Session::forget('profile_pending_email');

			return $this->profileResponse(false, trans('crudbooster.profile_email_taken'), ['expired' => true], 422);
		}

		$oldEmail = $user->email;
		DB::table('cms_users')->where('id', $user->id)->update(['email' => $newEmail]);

		MfaHelper::revokeAllTrustedDevices($user);
		Session::put('admin_session_version', MfaHelper::bumpSessionVersion($user->id));
		Session::forget('profile_pending_email');
		RateLimiter::clear('profile-email-verify:' . $user->id);

		CRUDBooster::insertLog(trans($verified ? 'crudbooster.log_profile_email_changed' : 'crudbooster.log_profile_email_changed_unverified', ['old' => $oldEmail, 'new' => $newEmail, 'ip' => Request::server('REMOTE_ADDR')]));

		return $this->profileResponse(true, trans('crudbooster.profile_email_changed'), ['email' => $newEmail]);
	}

	/**
	 * Sezione "Sistema": Tenant (solo superadmin) e Primary Group
	 * (superadmin e tenant admin, solo gruppi del proprio tenant). Stessa
	 * logica di appartenenza ai gruppi di hook_before_edit(): se il tenant
	 * cambia si tolgono tutti i gruppi, se cambia il primary group si
	 * aggiunge il nuovo e si toglie il vecchio.
	 */
	public function postProfileSystem()
	{
		$user = UserHelper::me();
		$isSuperadmin = UserHelper::isSuperAdmin();

		if (! $user || (! $isSuperadmin && ! UserHelper::isTenantAdmin())) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$validator = Validator::make(Request::all(), [
			'tenant' => 'nullable|integer|exists:tenants,id',
			'primary_group' => 'required|integer|exists:groups,id',
		]);
		if ($validator->fails()) {
			return $this->profileValidationError($validator);
		}

		$oldTenant = (int) $user->tenant;
		$oldGroup = (int) $user->primary_group;
		// Il tenant lo cambia solo il superadmin: per gli altri si ignora
		// il valore ricevuto.
		$newTenant = ($isSuperadmin && Request::input('tenant')) ? (int) Request::input('tenant') : $oldTenant;
		$newGroup = (int) Request::input('primary_group');

		$tenantChanged = $newTenant !== $oldTenant;
		$groupChanged = $newGroup !== $oldGroup;

		if (($tenantChanged || $groupChanged)
			&& ! DB::table('group_tenants')->where('tenant_id', $newTenant)->where('group_id', $newGroup)->exists()) {
			return $this->profileResponse(false, trans('crudbooster.profile_system_invalid_group'), [], 422);
		}

		if ($tenantChanged) {
			UserHelper::remove_all_groups($user->id);
		}

		DB::table('cms_users')->where('id', $user->id)->update(['tenant' => $newTenant, 'primary_group' => $newGroup]);

		if ($tenantChanged || $groupChanged) {
			GroupHelper::add($newGroup, $user->id);
		}
		if ($groupChanged && ! $tenantChanged) {
			GroupHelper::remove($oldGroup, $user->id);
		}

		return $this->profileResponse(true, trans('crudbooster.profile_system_saved'), ['reload' => $tenantChanged || $groupChanged]);
	}

	/**
	 * Cambio password, passo 1: password attuale + nuova (policy NIST, vedi
	 * not_common_password in AppServiceProvider). Poi codice di verifica: il
	 * TOTP se l'utente ce l'ha attivo, altrimenti email OTP al suo indirizzo.
	 * In sessione resta solo l'hash della nuova password, mai il testo.
	 */
	public function postProfilePasswordStart()
	{
		$user = UserHelper::me();
		if (! $user) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		if ($this->profileTooManyAttempts('password-start', $user->id)) {
			return $this->profileResponse(false, trans('crudbooster.profile_too_many_attempts'), [], 429);
		}

		// email/name nei dati validati servono a not_common_password, che
		// rifiuta password contenenti email o nome dell'utente.
		$validator = Validator::make(array_merge(Request::all(), ['email' => $user->email, 'name' => $user->name]), [
			'current_password' => 'required',
			'password' => 'required|min:12|max:72|confirmed|not_common_password',
		]);
		if ($validator->fails()) {
			return $this->profileValidationError($validator);
		}

		if (! \Hash::check((string) Request::input('current_password'), $user->password)) {
			$this->profileTooManyAttempts('password-start', $user->id, true);

			return $this->profileResponse(false, trans('crudbooster.profile_password_wrong_current'), [], 422);
		}

		if (\Hash::check((string) Request::input('password'), $user->password)) {
			return $this->profileResponse(false, trans('crudbooster.profile_password_same_as_current'), [], 422);
		}

		$this->profileTooManyAttempts('password-start', $user->id, true);

		$method = MfaHelper::hasActiveTotp($user) ? 'totp' : 'email';

		if ($method === 'email') {
			MfaHelper::invalidateEmailOtpCodes($user);
			if (! MfaHelper::sendEmailOtp($user)) {
				return $this->profileResponse(false, trans('crudbooster.profile_otp_send_failed'), [], 503);
			}
		}

		Session::put('profile_pending_password', [
			'hash' => \Hash::make((string) Request::input('password')),
			'method' => $method,
			'expires' => now()->addMinutes(MfaHelper::EMAIL_OTP_MINUTES)->timestamp,
		]);

		$message = $method === 'totp'
			? trans('crudbooster.profile_password_code_sent_totp')
			: trans('crudbooster.profile_password_code_sent_email', ['minutes' => MfaHelper::EMAIL_OTP_MINUTES]);

		return $this->profileResponse(true, $message, ['method' => $method]);
	}

	/**
	 * Cambio password, passo 2: verifica il codice, salva l'hash e chiude le
	 * altre sessioni (MfaHelper::bumpSessionVersion); quella corrente resta.
	 */
	public function postProfilePasswordConfirm()
	{
		$user = UserHelper::me();
		if (! $user) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$pending = Session::get('profile_pending_password');
		if (! $pending || $pending['expires'] < time()) {
			Session::forget('profile_pending_password');

			return $this->profileResponse(false, trans('crudbooster.profile_otp_expired'), ['expired' => true], 422);
		}

		if ($this->profileTooManyAttempts('password-verify', $user->id)) {
			return $this->profileResponse(false, trans('crudbooster.profile_too_many_attempts'), [], 429);
		}

		$code = trim((string) Request::input('code'));
		$valid = $pending['method'] === 'totp'
			? MfaHelper::verifyAndConsume($user, $code)
			: MfaHelper::verifyEmailOtp($user, $code);

		if (! $valid) {
			$this->profileTooManyAttempts('password-verify', $user->id, true);

			return $this->profileResponse(false, trans('crudbooster.profile_otp_wrong'), [], 422);
		}

		DB::table('cms_users')->where('id', $user->id)->update(['password' => $pending['hash']]);

		Session::put('admin_session_version', MfaHelper::bumpSessionVersion($user->id));
		Session::forget('profile_pending_password');
		RateLimiter::clear('profile-password-verify:' . $user->id);

		CRUDBooster::insertLog(trans('crudbooster.log_profile_password_changed', ['email' => $user->email, 'ip' => Request::server('REMOTE_ADDR')]));

		return $this->profileResponse(true, trans('crudbooster.profile_password_changed'));
	}

	/**
	 * Pulsante "Resetta password" sulla scheda di un altro utente: manda a
	 * quell'utente il link di reset (stesso flusso e stessa email di "Password
	 * dimenticata", vedi AdminController::postForgot()). Superadmin su tutti;
	 * tenant admin solo sugli utenti del proprio tenant che non sono
	 * superadmin/tenant admin (UserHelper::can_do_on_user). Chi modifica NON
	 * vede ne' imposta mai la password altrui.
	 */
	public function postUserResetPassword($id)
	{
		$target = \App\User::find($id);

		if (! $target || (int) $target->id === (int) CRUDBooster::myId() || ! UserHelper::can_do_on_user('edit', $target->id)) {
			return $this->profileResponse(false, trans('crudbooster.denied_access'), [], 403);
		}

		$email = $target->email;

		try {
			$status = Password::sendResetLink(['email' => $email], function ($user, $token) use ($email) {
				$data = CRUDBooster::first(config('crudbooster.USER_TABLE'), ['email' => $email]);
				$data->reset_url = CRUDBooster::adminPath('reset-password/' . $token) . '?email=' . urlencode($email);

				CRUDBooster::sendEmail(['to' => $email, 'data' => $data, 'template' => 'forgot_password_backend']);
			});
		} catch (\Throwable $e) {
			\Log::warning('Reset password da admin: invio email fallito.', ['user_id' => $target->id, 'error' => $e->getMessage()]);

			return $this->profileResponse(false, trans('crudbooster.user_reset_password_failed'), [], 503);
		}

		if ($status === Password::RESET_THROTTLED) {
			return $this->profileResponse(false, trans('crudbooster.user_reset_password_throttled'), [], 429);
		}

		if ($status !== Password::RESET_LINK_SENT) {
			return $this->profileResponse(false, trans('crudbooster.user_reset_password_failed'), [], 422);
		}

		CRUDBooster::insertLog(trans('crudbooster.log_user_reset_password_link', ['email' => $email, 'by' => CRUDBooster::me()->email, 'ip' => Request::server('REMOTE_ADDR')]));

		return $this->profileResponse(true, trans('crudbooster.user_reset_password_sent'));
	}

	/**
	 * Fase 1 del piano MFA (enrollment self-service), vedi
	 * docs/refactoring/096-*. Il secret NON e' ancora persistito qui: resta
	 * in sessione finche' l'utente non conferma il primo codice
	 * (postMfaConfirm), per non salvare mai un secret che l'utente non ha
	 * mai davvero collegato alla sua app authenticator.
	 */
	public function getMfaSetup()
	{
		$user = UserHelper::me();

		if ($user && $user->two_factor_confirmed_at) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_status_enabled'), 'info');
		}

		$secret = MfaHelper::generateSecret();
		Session::put('mfa_setup_secret', $secret);

		$issuer = CRUDBooster::getSetting('appname') ?: 'CustomerHive';
		$qrSvg = MfaHelper::qrCodeSvg($issuer, $user->email, $secret);

		// cbView() (non view() diretto): carica cbLoader(), che condivide con
		// la view tutte le variabili che admin_template.blade.php si aspetta
		// sempre (button_export, button_import, ecc.) - vedi CBController::cbLoader().
		$this->cbView('crudbooster::mfa_setup', [
			'page_title' => trans('crudbooster.mfa_page_title_setup'),
			'qr_svg' => $qrSvg,
			'secret' => $secret,
		]);
	}

	public function postMfaConfirm()
	{
		$user = UserHelper::me();
		$secret = Session::get('mfa_setup_secret');
		$code = trim((string) Request::input('code'));

		// Rate limit (Fase 4, vedi docs/refactoring/099-*): senza, un
		// codice a 6 cifre e' forzabile a forza bruta in tempi ragionevoli
		// (1 milione di combinazioni, OWASP raccomanda un limite ~5).
		$rateLimitKey = 'mfa-confirm:'.$user->id;

		if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users/mfa-setup'), trans('crudbooster.mfa_error_too_many_attempts'), 'danger');
		}

		$timeslice = $secret ? MfaHelper::verifyNewSecret($secret, $code) : false;

		if ($timeslice === false) {
			RateLimiter::hit($rateLimitKey, 900);

			return CRUDBooster::redirect(CRUDBooster::adminPath('users/mfa-setup'), trans('crudbooster.mfa_error_wrong_confirm_code'), 'danger');
		}

		RateLimiter::clear($rateLimitKey);

		$user->two_factor_secret = MfaHelper::encryptSecret($secret);
		$user->two_factor_confirmed_at = now();
		$user->two_factor_last_timeslice = $timeslice;
		$user->save();

		Session::forget('mfa_setup_secret');

		$codes = MfaHelper::generateRecoveryCodes();
		MfaHelper::storeRecoveryCodes($user, $codes);
		Session::flash('mfa_recovery_codes_plaintext', $codes);

		CRUDBooster::insertLog(trans('crudbooster.log_mfa_enabled', ['email' => $user->email, 'ip' => Request::server('REMOTE_ADDR')]));

		return redirect(CRUDBooster::adminPath('users/mfa-backup-codes'));
	}

	/**
	 * Mostra i backup codes UNA SOLA VOLTA (Session::pull li rimuove subito
	 * dopo averli letti): un refresh o un ritorno su questa pagina non li
	 * ri-mostra piu'.
	 */
	public function getMfaBackupCodes()
	{
		$codes = Session::pull('mfa_recovery_codes_plaintext');

		if (! $codes) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_enabled_success'), 'success');
		}

		$this->cbView('crudbooster::mfa_backup_codes', [
			'page_title' => trans('crudbooster.mfa_backup_codes_title'),
			'codes' => $codes,
		]);
	}

	public function postMfaRegenerateBackupCodes()
	{
		$user = UserHelper::me();

		if (! $user || ! $user->two_factor_confirmed_at) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_status_disabled'), 'warning');
		}

		$codes = MfaHelper::generateRecoveryCodes();
		MfaHelper::storeRecoveryCodes($user, $codes);
		Session::flash('mfa_recovery_codes_plaintext', $codes);

		return redirect(CRUDBooster::adminPath('users/mfa-backup-codes'));
	}

	public function postMfaDisable()
	{
		$user = UserHelper::me();
		$password = (string) Request::input('password');

		if (! $user || ! \Hash::check($password, $user->password)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_disable_wrong_password'), 'danger');
		}

		MfaHelper::disable($user);

		CRUDBooster::insertLog(trans('crudbooster.log_mfa_disabled', ['email' => $user->email, 'ip' => Request::server('REMOTE_ADDR')]));

		return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_disable_success'), 'success');
	}

	public function postMfaRevokeDevices()
	{
		$user = UserHelper::me();

		if ($user) {
			MfaHelper::revokeAllTrustedDevices($user);
		}

		return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_revoke_devices_success'), 'success');
	}

	/**
	 * Revoca un singolo dispositivo fidato (elenco "dispositivi da cui hai
	 * effettuato l'accesso" sul profilo) - $id arriva dal primo wildcard
	 * della route auto-instradata per riflessione
	 * (CRUDBooster::routeController(), stesso meccanismo di tutti gli
	 * altri metodi Mfa* di questo controller).
	 */
	public function postMfaRevokeDevice($id)
	{
		$user = UserHelper::me();

		if ($user) {
			MfaHelper::revokeTrustedDevice($user, (int) $id);
		}

		return CRUDBooster::redirect(CRUDBooster::adminPath('users/profile') . '#mfa', trans('crudbooster.mfa_revoke_device_success'), 'success');
	}

	public function hook_before_edit(&$postdata, $user_id)
	{


		
		unset($postdata['password_confirmation']);

		// Email e password di se' stessi si cambiano solo dal profilo (con
		// password attuale + codice di verifica): una modifica di se' stessi
		// passata da questo form non le deve toccare.
		if ((int) $user_id === (int) CRUDBooster::myId()) {
			unset($postdata['email'], $postdata['password']);
		} elseif (isset($postdata['email'])) {
			// Email di un ALTRO utente cambiata da superadmin/tenant admin:
			// si chiudono le sessioni di quell'utente e si revocano i suoi
			// dispositivi MFA attendibili (le credenziali di accesso sono
			// cambiate sotto i suoi piedi).
			$old_email = DB::table('cms_users')->where('id', $user_id)->value('email');
			if ($old_email !== null && strcasecmp((string) $old_email, (string) $postdata['email']) !== 0) {
				$target = \App\User::find($user_id);
				if ($target) {
					MfaHelper::revokeAllTrustedDevices($target);
					MfaHelper::bumpSessionVersion($target->id);
				}
			}
		}

		//se il tenant è cambiato
		$old_tenant_id = UserHelper::tenant($user_id);
		if ($old_tenant_id !== $postdata['tenant']) {
			//rimuovi vecchi gruppi dai gruppi di appartenenza
			UserHelper::remove_all_groups($user_id);
		}
		//se il primary group è cambiato
		$old_primary_group_id = UserHelper::primary_group($user_id);
		if (isset($postdata['primary_group']) && $old_primary_group_id !== $postdata['primary_group']) {
			//aggiungi nuovo primary group ai gruppi di appartenenza
			GroupHelper::add($postdata['primary_group'], $user_id);
			//rimuovi vecchio primary group dai gruppi di appartenenza
			GroupHelper::remove($old_primary_group_id, $user_id);
		}

		AdminCmsUsersController::prepare_qlik_users();


	}

	public static function prepare_qlik_users() {
		//file_put_contents(__DIR__.'/qlik_users.txt',  "PREPARE QLIK USERS" . PHP_EOL, FILE_APPEND);
		if (isset(Request::all()['qlikusers-qlik_conf_id'])) {
			//file_put_contents(__DIR__.'/qlik_users.txt',  json_encode(Request::all()['qlikusers-qlik_conf_id']) . PHP_EOL, FILE_APPEND);
			$qlik_conf_ids = Request::all()['qlikusers-qlik_conf_id'];
			$qlik_logins = Request::all()['qlikusers-qlik_login'];
			$qlik_user_directory = Request::all()['qlikusers-user_directory'];
			$qlik_idp_qlik = Request::all()['qlikusers-idp_qlik'];

			$id_user = DB::table('cms_users')->select('id')->where('email',Request::all()['email'] )->first()->id;
			$updated_idp_qlik = [];
			//dd(array_unique(array_diff_assoc($qlik_conf_ids,array_unique($qlik_conf_ids))));
			$check_dupicates = array_unique(array_diff_assoc($qlik_conf_ids,array_unique($qlik_conf_ids)));
			if (count($check_dupicates) > 0) {
				CRUDBooster::redirectBack(trans('crudbooster.qlik_user_conf_exists'), 'error');
			}
			
			foreach($qlik_conf_ids as $k => $v) {
				//file_put_contents(__DIR__.'/qlik_users.txt',  $idp . PHP_EOL, FILE_APPEND);

				if (!empty($v)) {

					/*$check_user = DB::table('qlik_users')->where('user_id', $id_user)->where('qlik_conf_id', $v)->first();

					if ($check_user) {
						CRUDBooster::redirectBack(trans('crudbooster.qlik_user_conf_exists'), 'error');
					}*/


					if (QlikHelper::confIsSAAS($v)) {
						if (!empty($qlik_idp_qlik[$k])) {
							$idp = $qlik_idp_qlik[$k];
						} else {
							$idp = QlikHelper::createUser($id_user, $v);
						}


						
						$updated_idp_qlik[$k] = $idp;

						//file_put_contents(__DIR__.'/qlik_users.txt', $id_user . ' ' . $v . ' ' . $idp . PHP_EOL, FILE_APPEND);

						Request::merge(['qlikusers-idp_qlik' => $updated_idp_qlik]);
					}
				}
			}
		}


		

	}

	public function hook_after_edit($id)
	{

		
	}

	public function hook_before_add(&$postdata)
	{

		if (!LicenseHelper::canAddUser()) {

			$message = "The number of users has exceeded the limit allowed by the current license.";
			//$message .= '<br><br>' . "Please contact the administrator to increase the number of tenants allowed.";
			$message_type = 'warning';
			return CRUDBooster::redirect( g('return_url') ?: CRUDBooster::referer() , $message, $message_type);
			//dd("License not valid");
		}


		unset($postdata['password_confirmation']);
		AdminCmsUsersController::prepare_qlik_users();
	}

	public function hook_after_add($id)
	{
		GroupHelper::add($this->arr['primary_group'], $id);
	}

	public function hook_before_validation()
	{
	}

	public function hook_before_delete($id)
	{
		//forbid user to delete himself
		if ($id == CRUDBooster::myId()) {
			return CRUDBooster::redirect(CRUDBooster::adminPath('users'), trans('crudbooster.delete_self'));
		}
		//cascade delete users_groups
		UsersGroup::where('user_id', $id)->delete();
	}

	public function hook_query_index(&$query)
	{
		if (UserHelper::isTenantAdmin()) {
			//Tenantadmin vede nella lista degli utenti solo quelli del proprio tenant
			$query->where('tenant', UserHelper::current_user_tenant());
		}
	}

	public function getEdit($id)
	{
		//il proprio profilo si modifica dalla pagina Profilo (sezioni con
		//verifica per email/password), non da questo form
		if ((int) $id === (int) CRUDBooster::myId()) {
			return redirect(CRUDBooster::adminPath('users/profile'));
		}

		//load edit page
		$this->cbLoader();
		$user = DB::table($this->table)->where($this->primary_key, $id)->first();

		if (!ModuleHelper::can_edit($this, $user)) {
			CRUDBooster::insertLog(trans("crudbooster.log_try_edit", [
				'name' => $user->{$this->title_field},
				'module' => CRUDBooster::getCurrentModule()->name,
			]));
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
		}

		$data = array();
		$data['page_menu'] = Route::getCurrentRoute()->getActionName();
		$data['page_title'] = trans(
			"crudbooster.edit_data_page_title",
			[
				'module' => CRUDBooster::getCurrentModule()->name,
				'name' => $user->{$this->title_field}
			]
		);
		$data['command'] = 'edit';
		Session::put('current_row_id', $id);

		$data['id'] = $id;
		$data['row'] = $user;

		return view('users.form', $data);
	}

	public function groups($user_id, $alert_id = null)
	{
		//check auth
		if (!CRUDBooster::isRead() && $this->global_privilege == FALSE || $this->button_edit == FALSE) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data = [];
		$data['groups'] = DB::table('users_groups')
			->where('users_groups.user_id', $user_id)
			->whereNull('users_groups.deleted_at')
			->join('groups', 'groups.id', '=', 'users_groups.group_id')
			->select('groups.id', 'groups.name', 'groups.description')
			->get();

		$data['user'] = \App\User::find($user_id);
		$data['user_id'] = $user_id;

		//prendo $_GET &alert=
		if (!empty($alert_id)) {
			//se è alert=1
			if ($alert_id == '1') {
				//mostra messaggio di warning per tasto add premuto senza valori required
				$data['alerts'][] = ['message' => '<h4><i class="icon bi bi-exclamation-triangle-fill"></i> Warning!</h4>Select an element to add...', 'type' => 'warning'];
			}
		}
		$data['page_title'] = $data['user']->name . ' groups';

		//add group form
		$data['forms'] = [];
		//il popup di ricerca (getModalData) non applica scoping automatico:
		//senza questo where un tenant admin vede e puo' selezionare gruppi
		//di qualsiasi altro tenant (i gruppi non hanno una colonna tenant
		//diretta, l'appartenenza e' nella tabella pivot group_tenants)
		$datamodal_where = UserHelper::isTenantAdmin() ? 'id in (select group_id from group_tenants where tenant_id = ' . (int) UserHelper::current_user_tenant() . ')' : "";
		$data['forms'][] = ['label' => 'Name', 'name' => 'name', 'type' => 'user_groups_datamodal', 'width' => 'col-sm-6', 'datamodal_table' => 'groups', 'datamodal_where' => $datamodal_where, 'datamodal_columns' => 'name', 'datamodal_columns_alias' => 'Name', 'datamodal_select_to' => $user_id, 'required' => true];
		$data['forms'][] = ['label' => 'Description', 'name' => 'description', 'type' => 'text', 'validation' => 'min:1|max:255', 'width' => 'col-sm-6', 'placeholder' => 'Group description', 'readonly' => true];
		$data['action'] = CRUDBooster::mainpath($user_id . "/add_group");
		$data['return_url'] = CRUDBooster::mainpath('groups/' . $user_id);

		$data['command'] = 'add';
		$data['button_addmore'] = false;

		$this->cbView('users.groups', $data);
	}

	public function add_group($user_id)
	{
		//check auth update su groups
		//TODO creaiamo permesso specifico da autorizzare e controllare per group membership?
		if (!CRUDBooster::isUpdate() && $this->global_privilege == FALSE || $this->button_edit == FALSE) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}
		$group_id = $_POST['name'];
		$return_url = $_POST['return_url'];
		$ref_mainpath = $_POST['ref_mainpath'];

		if (empty($group_id)) {
			return redirect($return_url . '/alert/1');
		}
		//check if user is already in group
		$membership = \App\UsersGroup::where('group_id', $group_id)
			->where('user_id', $user_id)
			->count();

		if ($membership == 0) {
			$add_member = new \App\UsersGroup;
			$add_member->group_id = $group_id;
			$add_member->user_id = $user_id;
			$add_member->save();
		}

		//redirect
		if (empty($return_url)) {
			$return_url = $ref_mainpath;
		}
		return redirect($return_url);
	}

	public function remove_group($user_id, $group_id)
	{
		//check auth update su groups
		//TODO creaiamo permesso specifico da autorizzare e controllare per group membership?
		if (!CRUDBooster::isUpdate() && $this->global_privilege == FALSE || $this->button_edit == FALSE) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		//check if group_id and user_id are int
		if (!MyHelper::is_int($group_id) or !MyHelper::is_int($user_id)) {
			return CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
		}

		$data['delete'] = DB::table('users_groups')
			->where('group_id', $group_id)
			->where('user_id', $user_id)
			->delete();

		return redirect('admin/users/groups/' . $user_id);
	}
}
