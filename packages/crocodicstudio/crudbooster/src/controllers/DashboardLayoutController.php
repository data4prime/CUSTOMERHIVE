<?php

namespace crocodicstudio\crudbooster\controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use \crocodicstudio\crudbooster\helpers\UserHelper;
use \crocodicstudio\crudbooster\helpers\QlikHelper;


class DashboardLayoutController extends CBController
{

	public function cbInit()
	{

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->title_field = "layoutname";
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
		$this->table = "dashboard_layouts";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Layout Name", "name" => "layoutname"];
		# END COLUMNS DO NOT REMOVE THIS LINE


		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Layout Name', 'name' => 'layoutname', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Layout Name'];
        $this->form[] = ['label' => 'Code Layout', 'name' => 'code_layout', 'type' => 'textarea', 'width' => 'col-sm-10', 'placeholder' => 'Enter Code Layout'];




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

			// Verifica se l'URL contiene 'dashboard_layouts/detail/'
if (window.location.href.includes('dashboard_layouts/detail/')) {
    // Seleziona tutti gli elementi con la classe 'statistic-row'
    var statisticRows = document.querySelectorAll('.statistic-row');
    
    // Aggiungi il bordo agli elementi selezionati e ai loro nodi interni
    statisticRows.forEach(row => {
		// Aggiungi il bordo all'elemento stesso
        row.style.border = '1px solid black';
        
        // Aggiungi il bordo a tutti i nodi interni
        const childNodes = row.querySelectorAll('*');
        childNodes.forEach(child => {
			//console.log(child);
			
            child.style.borderRight = '1px solid black';
			//if child is a br element, delete it
			if (child.tagName == 'BR') {
				child.remove();
			}
        });
    });
var statisticRows = document.querySelectorAll('.statistic-row');
	statisticRows.forEach(row => {
console.log(row.nextElementSibling);
			//if next sibling is a br element, delete it
			//check if exists


			if (row.nextElementSibling != null && row.nextElementSibling.tagName == 'BR') {
				row.nextElementSibling.remove();
				console.log(row.nextElementSibling);
			}
	});
}

/*if (window.location.href.includes('dashboard_layouts/edit/')) {
	//code_layout

	var textarea = document.querySelector('textarea[name=\"code_layout\"]');
    var code = textarea.value;

    function formatHTML(html) {
        let formatted = '';
        const tab = '\t';
        let indentLevel = 1;

        html.split(/>\s*</).forEach(function(element) {
			console.log(indentLevel);
			if (indentLevel == 0) {
				indentLevel = 1;
			}
            if (element.match(/^\/\w/)) {
                // Closing tag
                indentLevel--;
                formatted += '\\n' + tab.repeat(indentLevel) + '\<' + element + '\>';
            } else if (element.match(/^</) && !element.match(/\/$/)) {
                // Opening tag
                formatted += '\\n' + tab.repeat(indentLevel) + '\<' + element + '\>';
                indentLevel++;
            } else {
                // Self-closing tag or text node
                formatted += '\\n' + tab.repeat(indentLevel) + '\<' + element + '\>';
            }
        });

        return formatted.trim();
    }

    var formattedHTML = formatHTML(code);
    textarea.value = formattedHTML;

}
*/
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

		$postdata['code_layout'] = str_replace("\r\n", "", $postdata['code_layout']);
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

		//$postdata['code_layout'] = str_replace("\r\n", "", $postdata['code_layout']);
		
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
