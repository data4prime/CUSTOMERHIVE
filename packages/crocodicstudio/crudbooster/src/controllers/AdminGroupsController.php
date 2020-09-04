<?php namespace crocodicstudio\crudbooster\controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;
	use \App\UsersGroup;
	use \App\Group;
	use \App\GroupTenants;
	use \crocodicstudio\crudbooster\helpers\UserHelper;
	use \crocodicstudio\crudbooster\helpers\MyHelper;

	class AdminGroupsController extends CBController {

	    public function cbInit() {

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
				$this->table = "groups";
				# END CONFIGURATION DO NOT REMOVE THIS LINE

				# START COLUMNS DO NOT REMOVE THIS LINE
				$this->col = [];
				$this->col[] = ["label"=>"Name","name"=>"name"];
				$this->col[] = ["label"=>"Help","name"=>"description"];
				# END COLUMNS DO NOT REMOVE THIS LINE


				# START FORM DO NOT REMOVE THIS LINE
				$this->form = [];
				$this->form[] = ['label'=>'Name','name'=>'name','type'=>'text','validation'=>'required|string|min:1|max:70','width'=>'col-sm-10','placeholder'=>'You can only enter the letter only'];
				$this->form[] = ['label'=>'Help','name'=>'description','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-10'];

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
				$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('members/[id]'),'icon'=>'fa fa-user','color'=>'info','title'=>'Members'];
				$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('items/[id]'),'icon'=>'fa fa-shield','color'=>'warning','title'=>'Items'];
				//solo superadmin gestisce i tenant
				if(CRUDBooster::isSuperadmin())
				{
					$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('tenant/[id]'),'icon'=>'fa fa-industry','color'=>'primary','title'=>'Tenants'];
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
	    public function actionButtonSelected($id_selected,$button_name) {
	        //Your code here
	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate query of index result
	    | ----------------------------------------------------------------------
	    | @query = current sql query
	    |
	    */
			public function hook_query_index(&$query) {
				if(UserHelper::isTenantAdmin())
				{
					//Tenantadmin vede nella lista dei gruppi solo quelli del proprio tenant
					$query->join('group_tenants','group_tenants.group_id','groups.id');
					$query->where('group_tenants.tenant_id',UserHelper::current_user_tenant());
				}
			}

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate row of index table html
	    | ----------------------------------------------------------------------
	    |
	    */
	    public function hook_row_index($column_index,&$column_value) {
	    	//Your code here
	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate data input before add data is execute
	    | ----------------------------------------------------------------------
	    | @arr
	    |
	    */
	    public function hook_before_add(&$postdata) {
	        //Your code here
	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after add public static function called
	    | ----------------------------------------------------------------------
	    | @id = last insert id
	    |
	    */
	    public function hook_after_add($id) {
	        //Your code here
					Group::find($id)->add_tenant(UserHelper::current_user_tenant());
	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for manipulate data input before update data is execute
	    | ----------------------------------------------------------------------
	    | @postdata = input post data
	    | @id       = current id
	    |
	    */
	    public function hook_before_edit(&$postdata,$id) {
	        //Your code here
	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after edit public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
	    public function hook_after_edit($id) {
	        //Your code here

	    }

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for execute command before delete public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
			public function hook_before_delete($id){
				$members_count = UsersGroup::where('group_id', $id)->count();
				if($members_count > 0){
					CRUDBooster::redirect(CRUDBooster::adminPath('groups'), trans('crudbooster.delete_not_empty_group'));
				}
			}

	    /*
	    | ----------------------------------------------------------------------
	    | Hook for execute command after delete public static function called
	    | ----------------------------------------------------------------------
	    | @id       = current id
	    |
	    */
	    public function hook_after_delete($id) {
	        //Your code here

	    }

			public function members($group_id, $alert_id = null){
				//check auth
			  if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

			  $data = [];
			  $data['members'] = DB::table('users_groups')
														->where('users_groups.group_id',$group_id)
														->where('users_groups.deleted_at',null)
														->join('cms_users', 'cms_users.id', '=', 'users_groups.user_id')
														->join('cms_privileges', 'cms_privileges.id', '=', 'cms_users.id_cms_privileges')
														->select('cms_users.id', 'cms_users.name', 'cms_users.email', 'cms_users.photo', 'cms_privileges.name as privilege', 'cms_users.primary_group')
														->get();

			  $data['group'] = \App\Group::find($group_id);
				$data['group_id'] = $group_id;
				//prendo $_GET &alert=
				if(!empty($alert_id)){
					//se è alert=1
					if($alert_id=='1'){
						//mostra messaggio di warning per tasto add premuto senza valori required
						$data['alerts'][] = ['message'=>'<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...','type'=>'warning'];
					}
				}

				$data['page_title'] = $data['group']->name.' members';

				//add member form
				$data['forms'] = [];
				$data['forms'][] = ['label'=>'Name','name'=>'name','type'=>'group_members_datamodal','width'=>'col-sm-6','datamodal_table'=>'cms_users','datamodal_where'=>'','datamodal_columns'=>'name','datamodal_columns_alias'=>'Name','datamodal_select_to'=>$group_id,'required'=>true];
				$data['forms'][] = ['label'=>'Email','name'=>'email','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-6','placeholder'=>'User email','readonly'=>true,'required'=>true];
				$data['action'] = CRUDBooster::mainpath($group_id."/add_member");
				$data['return_url'] = CRUDBooster::mainpath('members/'.$group_id);

			  $this->cbView('groups.members',$data);
			}

			public function add_member($group_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per group membership?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				$user_id = $_POST['name'];
				$return_url = $_POST['return_url'];
				$ref_mainpath = $_POST['ref_mainpath'];

			  if(empty($user_id)) {
					return redirect($return_url.'/alert/1');
			  }

				//check if user is already in group
				$member = \App\UsersGroup::where('group_id',$group_id)
																		->where('user_id',$user_id)
																		->count();

				if($member == 0){
					$add_member = new \App\UsersGroup;
					$add_member->group_id = $group_id;
					$add_member->user_id = $user_id;
					$add_member->save();
				}

				//redirect
				if(empty($return_url)){
						$return_url = $ref_mainpath;
				}
				return redirect($return_url);
			}

			public function remove_member($group_id, $user_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per group membership?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//check if group_id and user_id are int
				if(!MyHelper::is_int($group_id) OR !MyHelper::is_int($user_id)) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//check if it's user's primary group
				if(UserHelper::primary_group($user_id) == $group_id)
				{
					CRUDBooster::redirectBack(trans("crudbooster.cant_delete_primary_group"));
				}

			  $data['delete'] = UsersGroup::where('group_id',$group_id)
														->where('user_id',$user_id)
														->delete();

				return redirect('admin/groups/members/'.$group_id);
			}

			public function items($group_id, $alert_id = null){
				//check auth
			  if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

			  $data = [];
			  $data['items'] = DB::table('items_allowed')
														->where('items_allowed.group_id',$group_id)
														->join('qlik_items', 'qlik_items.id', '=', 'items_allowed.item_id')
														->get();

			  $data['group'] = \App\Group::find($group_id);
				$data['group_id'] = $group_id;

				//prendo $_GET &alert=
				if(!empty($alert_id)){
					//se è alert=1
					if($alert_id=='1'){
						//mostra messaggio di warning per tasto add premuto senza valori required
						$data['alerts'][] = ['message'=>'<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...','type'=>'warning'];
					}
				}
				$data['page_title'] = $data['group']->name.' Allowed Items';

				//add member form
				$data['forms'] = [];
				$data['forms'][] = ['label'=>'Title','name'=>'title','type'=>'group_items_datamodal','width'=>'col-sm-6','datamodal_table'=>'qlik_items','datamodal_where'=>'','datamodal_columns'=>'title','datamodal_columns_alias'=>'Item','datamodal_select_to'=>$group_id,'required'=>true];
				$data['forms'][] = ['label'=>'Subtitle','name'=>'subtitle','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-6','placeholder'=>'Subtitle','readonly'=>true];
				$data['action'] = CRUDBooster::mainpath($group_id."/add_item");
				$data['return_url'] = CRUDBooster::mainpath('items/'.$group_id);

			  $this->cbView('groups.items',$data);
			}

			public function add_item($group_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per group allowed item?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }
				$item_id = $_POST['title'];
				$return_url = $_POST['return_url'];
				$ref_mainpath = $_POST['ref_mainpath'];

				//check if item is already allowed in group
				$item = \App\ItemsAllowed::where('group_id',$group_id)
																		->where('item_id',$item_id)
																		->count();

			  if(empty($item_id)) {
					return redirect($return_url.'/alert/1');
			  }

				if($item == 0){
					$add_item = new \App\ItemsAllowed;
					$add_item->group_id = $group_id;
					$add_item->item_id = $item_id;
					$add_item->save();
				}

				//redirect
				if(empty($return_url)){
						$return_url = $ref_mainpath;
				}
				return redirect($return_url);
			}

			public function remove_item($group_id, $item_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per group membership?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//check if group_id and user_id are int
				if(!MyHelper::is_int($group_id) OR !MyHelper::is_int($item_id))
				{
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

			  $data['delete'] = DB::table('items_allowed')
														->where('group_id',$group_id)
														->where('item_id',$item_id)
														->delete();

				return redirect('admin/groups/items/'.$group_id);
			}

			public function tenant($group_id, $alert_id = null)
			{
				//check auth
			  if(!CRUDBooster::isSuperadmin())
				{
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				$data['group_id'] = $group_id;
				$data['group'] = Group::find($group_id);
				$data['tenants'] = GroupTenants::where('group_id',$group_id)
																				->join('tenants','tenants.id','=','group_tenants.tenant_id')
																				->get();
				$data['page_title'] = 'Group Tenants';

				//prendo $_GET &alert=
				if(!empty($alert_id)){
					//se è alert=1
					if($alert_id=='1'){
						//mostra messaggio di warning per tasto add premuto senza valori required
						$data['alerts'][] = ['message'=>'<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...','type'=>'warning'];
					}
				}

				//add tenant form
				$data['forms'] = [];
				$data['forms'][] = ['label'=>'Name','name'=>'name','type'=>'group_tenant_datamodal','width'=>'col-sm-6','datamodal_table'=>'tenants','datamodal_where'=>'','datamodal_columns'=>'name','datamodal_columns_alias'=>'Name','datamodal_select_to'=>$group_id,'required'=>true];
				$data['forms'][] = ['label'=>'Description','name'=>'description','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-6','placeholder'=>'Tenant description','readonly'=>true];
				$data['action'] = CRUDBooster::mainpath($group_id."/add_tenant");
				$data['return_url'] = CRUDBooster::mainpath('tenant/'.$group_id);

			  $this->cbView('groups.tenant',$data);
			}

			public function add_tenant($group_id) {

			  if(!CRUDBooster::isSuperadmin()) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }
				$tenant_id = $_POST['name'];
				$return_url = $_POST['return_url'];
				$ref_mainpath = $_POST['ref_mainpath'];

			  if(empty($tenant_id)) {
					return redirect($return_url.'/alert/1');
			  }
				//check if tenant is already allowed
				$allowed = GroupTenants::where('group_id',$group_id)
																->where('tenant_id',$tenant_id)
																->count();

				if($allowed == 0){
					$add_tenant = new GroupTenants;
					$add_tenant->group_id = $group_id;
					$add_tenant->tenant_id = $tenant_id;
					$add_tenant->save();
				}

				//redirect
				if(empty($return_url)){
						$return_url = $ref_mainpath;
				}
				return redirect($return_url);
			}

			public function remove_tenant($group_id, $tenant_id) {
			  if(!CRUDBooster::isSuperadmin()) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//check if tenant_id and user_id are int
				if(!MyHelper::is_int($tenant_id) OR !MyHelper::is_int($group_id))
				{
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

			  $data['delete'] = GroupTenants::where('group_id',$group_id)
														->where('tenant_id',$tenant_id)
														->delete();

				return redirect('admin/groups/tenant/'.$group_id);
			}

	}
