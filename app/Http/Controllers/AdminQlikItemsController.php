<?php namespace App\Http\Controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;
	use GroupHelper;
	use Illuminate\Support\Facades\Route;

	class AdminQlikItemsController extends \crocodicstudio\crudbooster\controllers\CBController {

	    public function cbInit() {
				# START CONFIGURATION DO NOT REMOVE THIS LINE
				$this->title_field = "title";
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
				$this->table = "qlik_items";
				# END CONFIGURATION DO NOT REMOVE THIS LINE

				# START COLUMNS DO NOT REMOVE THIS LINE
				$this->col = [];
				$this->col[] = ["label"=>"Title","name"=>"title"];
				$this->col[] = ["label"=>"Subtitle","name"=>"subtitle"];
				$this->col[] = ["label"=>"Help","name"=>"description"];
				// $this->col[] = ["label"=>"Url","name"=>"url"];
				$this->col[] = ["label"=>"Width","name"=>"frame_width"];
				$this->col[] = ["label"=>"Height","name"=>"frame_height"];
				# END COLUMNS DO NOT REMOVE THIS LINE

				# START FORM DO NOT REMOVE THIS LINE
				$this->form = [];
				$this->form[] = ['label'=>'Title','name'=>'title','type'=>'text','validation'=>'required|string|min:1|max:70','width'=>'col-sm-10','placeholder'=>'Item title'];
				$this->form[] = ['label'=>'Url','name'=>'url','type'=>'text','validation'=>'required|min:1|max:255','width'=>'col-sm-10','placeholder'=>'Path to embed item'];
				$this->form[] = ['label'=>'Subtitle','name'=>'subtitle','type'=>'text','validation'=>'string|min:1|max:70','width'=>'col-sm-10','placeholder'=>'Item subtitle'];
				$this->form[] = ['label'=>'Help','name'=>'description','type'=>'textarea','validation'=>'string|min:1|max:200','width'=>'col-sm-10','placeholder'=>'Item description'];
				$this->form[] = ['label'=>'Full Page','name'=>'frame_full_page','type'=>'checkbox','width'=>'col-sm-1','dataenum'=>' '];
				$this->form[] = ['label'=>'Width','name'=>'frame_width','type'=>'number','validation'=>'required|int|min:1|max:10000','width'=>'col-sm-1','value'=>'100'];
				$this->form[] = ['label'=>'','name'=>'frame_width_unit','type'=>'select','validation'=>'','width'=>'col-sm-1','dataenum'=>'px','default'=>'%'];
				$this->form[] = ['label'=>'Height','name'=>'frame_height','type'=>'number','validation'=>'required|int|min:1|max:10000','width'=>'col-sm-1','value'=>'100'];
				$this->form[] = ['label'=>'','name'=>'frame_height_unit','type'=>'select','validation'=>'','width'=>'col-sm-1','dataenum'=>'px','default'=>'%'];
				# END FORM DO NOT REMOVE THIS LINE

				# OLD START FORM
				//$this->form = [];
				//$this->form[] = ['label'=>'Title','name'=>'title','type'=>'text','validation'=>'required|string|min:3|max:70','width'=>'col-sm-10','placeholder'=>'You can only enter the letter only'];
				//$this->form[] = ['label'=>'Description','name'=>'description','type'=>'text','validation'=>'required|min:1|max:255','width'=>'col-sm-10'];
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
				$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('content/[id]'),'icon'=>'fa fa-search','color'=>'info','title'=>'View item'];
				$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('access/[id]'),'icon'=>'fa fa-users','color'=>'warning','title'=>'Set auth'];


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
        $this->script_js =
				  "
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

				  });";


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
	        $this->style_css = "
					.box{
						padding:8px
					}
					#table_dashboard a.btn-detail{
						display:none;
					}
					#table_dashboard tbody tr td:last-child .button_action{
						text-align:center !important;
					}
					";



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
	        //Your code here
					//TODO filtrare permesso gruppi
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
					$postdata['frame_width'] .= $postdata['frame_width_unit'];
					$postdata['frame_height'] .= $postdata['frame_height_unit'];
					unset($postdata['frame_full_page']);
					unset($postdata['frame_width_unit']);
					unset($postdata['frame_height_unit']);
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
					$postdata['frame_width'] .= $postdata['frame_width_unit'];
					$postdata['frame_height'] .= $postdata['frame_height_unit'];
					unset($postdata['frame_full_page']);
					unset($postdata['frame_width_unit']);
					unset($postdata['frame_height_unit']);
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
	    public function hook_before_delete($id) {
	        //Your code here

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



	    // #RAMA custom methods

			//look at qlik item's content
			public function content_view($qlik_item_id) {
				//check auth
			  if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				$allowed = GroupHelper::can_see_item($qlik_item_id);
				//check if at least one of item allowed groups is in user groups
			  if(!$allowed) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//produce qlik ticket
				//get user data
				$current_user_id = CRUDBooster::myId();
				$current_user = \App\User::find($current_user_id);

				//UserId
				$qlik_login = $current_user->qlik_login;
				//User Directory
				$user_directory = $current_user->user_directory;

				if(empty($qlik_login) OR empty($user_directory)){
					$data['error'] = 'User credentials missing. Ask an admin to set your qlik id and user directory';
				  $this->cbView('qlik_items.no_credentials',$data);
					exit;
				}

				//base path to call
				$QRSurl = 'https://platformq.dasycloud.com:4243';
				$xrfkey = "0123456789abcdef";
				//Path to call (with xrfkey parameter added)
				$endpoint = "/qps/ticket?xrfkey=".$xrfkey;
				//Location of QRS client certificate and certificate key, assuming key is included separately
				$base_path = '/var/www/customerhive/storage/app/';
				$QRSCertfile = $base_path."certificates/client.pem";
				$QRSCertkeyfile = $base_path."certificates/client_key.pem";
				// #RAMA no password
				// $QRSCertkeyfilePassword = "Passw0rd!";

				//Set up the required headers
				$headers = array(
					'Accept: application/json',
					'Content-Type: application/json',
					'x-qlik-xrfkey: '.$xrfkey,
					'X-Qlik-User: UserDirectory='.$user_directory.';UserId='.$qlik_login
				);

				//Create Connection using Curl
				$ch = curl_init($QRSurl.$endpoint);

				curl_setopt($ch, CURLOPT_VERBOSE, true);
				// curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, '{
					"UserId":"'.$qlik_login.'",
					"UserDirectory":"'.$user_directory.'"
				}');
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSLCERT, $QRSCertfile);
				curl_setopt($ch, CURLOPT_SSLKEY, $QRSCertkeyfile);
				// #RAMA no password
				// curl_setopt($ch, CURLOPT_KEYPASSWD, $QRSCertkeyfilePassword);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

				//Execute and get response
				$raw_response = curl_exec($ch);
				$response = json_decode($raw_response);
				$qlik_ticket = $response->Ticket;
				//end of get qlik ticket

			  $data = [];
			  $data['row'] = \App\QlikItem::find($qlik_item_id);
				$data['page_icon'] = '';
			  $data['page_title'] = $data['row']->title;
				$data['help'] = $data['row']->description;
			  $data['subtitle'] = $data['row']->subtitle;

				// smontare URL per inserire app id anzichè URL per immissione qlik item
				// $qlik_sense_app_base_path = 'https://platformq.dasycloud.com';
				// $app_id = 'a92f56de-351f-4b67-a38c-8ca7147a450a';
				// $sheet_id = '1ff88551-9c4d-41e0-b790-37f4c11d3df8';
				// $item_url = $qlik_sense_app_base_path.'/single/?appid='.$app_id.'&sheet='.$sheet_id.'&opt=ctxmenu,currsel&qlikTicket='.$qlik_ticket;

				$data['item_url'] = $data['row']->url.'&qlikTicket='.$qlik_ticket;

			  $this->cbView('qlik_items.view',$data);
			}

			public function access($item_id, $alert_id = null){
				//check auth
			  if(!CRUDBooster::isRead() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }
				$data['item_id'] = $item_id;
				$data['qlik_item'] = \App\QlikItem::find($item_id);
				$data['groups'] = \App\ItemsAllowed::where('item_id',$item_id)
																							->join('groups','groups.id','=','items_allowed.group_id')
																							->get();
				$data['page_title'] = 'Authorize Access';

				//prendo $_GET &alert=
				if(!empty($alert_id)){
					//se è alert=1
					if($alert_id=='1'){
						//mostra messaggio di warning per tasto add premuto senza valori required
						$data['alerts'][] = ['message'=>'<h4><i class="icon fa fa-warning"></i> Warning!</h4>Select an element to add...','type'=>'warning'];
					}
				}
				//add member form
				$data['forms'] = [];
				$data['forms'][] = ['label'=>'Name','name'=>'name','type'=>'item_access_datamodal','width'=>'col-sm-6','datamodal_table'=>'groups','datamodal_where'=>'','datamodal_columns'=>'name','datamodal_columns_alias'=>'Name','datamodal_select_to'=>$item_id,'required'=>true];
				$data['forms'][] = ['label'=>'Description','name'=>'description','type'=>'text','validation'=>'min:1|max:255','width'=>'col-sm-6','placeholder'=>'Group description','readonly'=>true];
				$data['action'] = CRUDBooster::mainpath($item_id."/auth");
				$data['return_url'] = CRUDBooster::mainpath('access/'.$item_id);

			  $this->cbView('qlik_items.access',$data);
			}

			public function add_authorization($item_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per item access?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }
				$group_id = $_POST['name'];
				$return_url = $_POST['return_url'];
				$ref_mainpath = $_POST['ref_mainpath'];

			  if(empty($group_id)) {
					return redirect($return_url.'/alert/1');
			  }
				//check if user is already in group
				$access = \App\ItemsAllowed::where('item_id',$item_id)
																		->where('group_id',$group_id)
																		->count();

				if($access == 0){
					$add_authorization = new \App\ItemsAllowed;
					$add_authorization->item_id = $item_id;
					$add_authorization->group_id = $group_id;
					$add_authorization->save();
				}

				//redirect
				if(empty($return_url)){
						$return_url = $ref_mainpath;
				}
				return redirect($return_url);
			}

			public function remove_authorization($item_id, $group_id){
				//check auth update su groups
				//TODO creaiamo permesso specifico da autorizzare e controllare per item access?
			  if(!CRUDBooster::isUpdate() && $this->global_privilege==FALSE || $this->button_edit==FALSE) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

				//check if group_id and user_id are int
				if(filter_var($group_id, FILTER_VALIDATE_INT) === false OR filter_var($item_id, FILTER_VALIDATE_INT) === false) {
			    CRUDBooster::redirect(CRUDBooster::adminPath(),trans("crudbooster.denied_access"));
			  }

			  $data['delete'] = \App\ItemsAllowed::where('item_id',$item_id)
														->where('group_id',$group_id)
														->delete();

				return redirect('admin/qlik_items/access/'.$item_id);
			}

			//overwrite default method
	    public function getEdit($id)
	    {
				//load edit page
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        if (! CRUDBooster::isRead() && $this->global_privilege == false || $this->button_edit == false) {
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

        return view('qlik_items.form', compact('id', 'row', 'page_menu', 'page_title', 'command'));
	    }

			//overwrite default method
	    public function getDetail($id)
	    {
				//load detail page
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        if (! CRUDBooster::isRead() && $this->global_privilege == false || $this->button_detail == false) {
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
	}
