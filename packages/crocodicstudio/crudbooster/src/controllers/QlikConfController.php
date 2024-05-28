<?php

namespace crocodicstudio\crudbooster\controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use \crocodicstudio\crudbooster\helpers\UserHelper;


class QlikConfController extends CBController
{

	public function cbInit()
	{

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->title_field = "confname";
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
		$this->table = "qlik_confs";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Configuration Name", "name" => "confname"];
		$this->col[] = ["label" => "Type", "name" => "type"];
        //$this->col[] = ["label" => "qrsurl", "name" => "QRS Url"];
        //$this->col[] = ["label" => "Endpoint", "name" => "endpoint"];
        //$this->col[] = ["label" => "QRSCertfile", "name" => "QRSCertfile"];
        //$this->col[] = ["label" => "QRSCertkeyfile", "name" => "QRSCertkeyfile"];
        //$this->col[] = ["label" => "QRSCertkeyfilePassword", "name" => "QRSCertkeyfilePassword"];
        //$this->col[] = ["label" => "url", "name" => "url"];
        //$this->col[] = ["label" => "keyid", "name" => "keyid"];
        //$this->col[] = ["label" => "issuer", "name" => "issuer"];
        //$this->col[] = ["label" => "web_int_id", "name" => "web_int_id"];
        //$this->col[] = ["label" => "private_key", "name" => "private_key"];
        $this->col[] = ["label" => "debug", "name" => "debug"];

		# END COLUMNS DO NOT REMOVE THIS LINE


		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Configuration Name', 'name' => 'confname', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Configuration Name'];
        $this->form[] = ['label' => 'Type', 'name' => 'type', 'type' => 'select', 'width' => 'col-sm-10', 'dataenum' => 'On-Premise;SAAS'];
        $this->form[] = ['label' => 'QRS Url', 'name' => 'qrsurl', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRS Url'];
        $this->form[] = ['label' => 'URL', 'name' => 'url', 'type' => 'text',  'width' => 'col-sm-10', 'placeholder' => 'Enter URL'];

        $this->form[] = ['label' => 'Port', 'name' => 'port', 'type' => 'text',  'width' => 'col-sm-10', 'placeholder' => 'Port'];

        $this->form[] = ['label' => 'Endpoint', 'name' => 'endpoint', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Endpoint'];
        $this->form[] = ['label' => 'QRSCertfile', 'name' => 'QRSCertfile', 'type' => 'upload', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertfile'];
        $this->form[] = ['label' => 'QRSCertkeyfile', 'name' => 'QRSCertkeyfile', 'type' => 'upload', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertkeyfile'];
        $this->form[] = ['label' => 'QRSCertkeyfilePassword', 'name' => 'QRSCertkeyfilePassword', 'type' => 'password', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertkeyfilePassword'];

        $this->form[] = ['label' => 'Key ID', 'name' => 'keyid', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Key ID'];
        $this->form[] = ['label' => 'Issuer', 'name' => 'issuer', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Issuer'];
        $this->form[] = ['label' => 'Web Int ID', 'name' => 'web_int_id', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Web Int ID'];
        $this->form[] = ['label' => 'Private Key', 'name' => 'private_key', 'type' => 'upload', 'width' => 'col-sm-10', 'placeholder' => 'Enter Private Key'];

        $this->form[] = ['label' => 'Debug', 'name' => 'debug', 'type' => 'select', 'width' => 'col-sm-10', 'dataenum' => 'Inactive;Active'];

		//only superadmin can edit tenant
    if (CRUDBooster::isSuperadmin()) {
      $this->form[] = [
        'label' => 'Tenant',
        'name' => 'qlikconfs_tenants',
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "tenants,name",
        "relationship_table" => "qlikconfs_tenants",
        'required' => true,
        'validation' => 'required',
        'value' => UserHelper::current_user_tenant() //default value per creazione nuovo record
      ];
      //superadmin vede i gruppi come cascading dropdown in base al tenant
      $this->form[] = [
        "label" => "Group",
        "name" => "qlikconfs_groups",
        "type" => "select2",
        "select2_multiple" => true,
        "datatable" => "groups,name",
        "relationship_table" => "qlikconfs_groups",
        "required" => true,
        'parent_select' => 'qlikconfs_tenants',
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
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('items/[id]'), 'icon' => 'fa fa-shield', 'color' => 'warning', 'title' => 'Items'];
		if (CRUDBooster::isSuperadmin()) {
            $this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseHub/[id]'), 'icon' => 'fa fa-desktop', 'color' => 'primary', 'title' => 'Qlik Sense Hub'];
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseQMC/[id]'), 'icon' => 'fa fa-code', 'color' => 'primary', 'title' => 'Qlik Sense QMC'];
            //$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('tenant/[id]'), 'icon' => 'fa fa-industry', 'color' => 'primary', 'title' => 'Tenants'];
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
		$this->script_js = "

$(document).ready(function () {

    var type = $('[name=\"type\"]').first();
    var type_val = type.val();
    var on_premise = ['qrsurl', /*'endpoint',*/ 'QRSCertfile', 'QRSCertkeyfile', 'QRSCertkeyfilePassword'];

    var saas = ['url', 'keyid', 'issuer', 'web_int_id', 'private_key'];
    if (type_val == 'On-Premise') {

        saas.forEach(element => {

            to_hide = document.getElementsByName(element);

            to_hide.forEach(hide => {
                hide.parentNode.parentNode.style.display = 'none';

            });


        });

        on_premise.forEach(element => {

            to_show = document.getElementsByName(element);

            to_show.forEach(show => {
                show.parentNode.parentNode.style.display = '';

            });


        });


    } else if (type_val == 'SAAS') {
        on_premise.forEach(element => {

            to_hide = document.getElementsByName(element);

            to_hide.forEach(hide => {
                hide.parentNode.parentNode.style.display = 'none';

            });


        });

        saas.forEach(element => {

            to_show = document.getElementsByName(element);

            to_show.forEach(show => {
                show.parentNode.parentNode.style.display = '';

            });


        });

    }

    type.change(function () {
        // Code to be executed when the value of the select changes
        var selectedValue = $(this).val();
        var on_premise = ['qrsurl', /*'endpoint',*/ 'QRSCertfile', 'QRSCertkeyfile', 'QRSCertkeyfilePassword'];

        var saas = ['url', 'keyid', 'issuer', 'web_int_id', 'private_key'];

        //console.log(document.getElementsByName('type')[0]);

        //var type = document.getElementsByName('type')[0].value;

        var type = $('[name=\"type\"]').first().val();

        if (type == 'On-Premise') {

            saas.forEach(element => {

                to_hide = document.getElementsByName(element);

                to_hide.forEach(hide => {
                    hide.parentNode.parentNode.style.display = 'none';

                });


            });

            on_premise.forEach(element => {

                to_show = document.getElementsByName(element);

                to_show.forEach(show => {
                    show.parentNode.parentNode.style.display = '';

                });


            });


        } else if (type == 'SAAS') {
            on_premise.forEach(element => {

                to_hide = document.getElementsByName(element);
				console.log(to_hide);

                to_hide.forEach(hide => {
                    hide.parentNode.parentNode.style.display = 'none';

                });


            });

            saas.forEach(element => {

                to_show = document.getElementsByName(element);

                to_show.forEach(show => {
                    show.parentNode.parentNode.style.display = '';

                });


            });

        }

    });
});


";


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
