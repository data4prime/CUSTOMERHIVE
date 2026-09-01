<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Session;
use Request;
use DB;
use CRUDBooster;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use \App\Helpers\UserHelper;
use \App\Helpers\QlikHelper;


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
		$this->button_show = false;
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
        //$this->form[] = ['label' => 'Code Layout', 'name' => 'code_layout', 'type' => 'textarea', 'width' => 'col-sm-10', 'placeholder' => 'Enter Code Layout'];
		$this->form[] = ['label' => 'Code Layout', 'name' => 'code_layout', 'type' => 'tinymce', 'width' => 'col-sm-10', 'placeholder' => 'Enter Code Layout'];
		//wysiwyg




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
	}



	


	/*
	 * Add/Edit del layout sostituiti con un builder visuale a righe/colonne
	 * (invece del vecchio editor TinyMCE in cui l'admin scriveva l'HTML a
	 * mano) - vedi docs/refactoring/. Il formato salvato in code_layout
	 * resta identico (div.statistic-row.row > div#areaN.col-sm-X
	 * .connectedSortable), quindi StatisticBuilderController/index.blade.php
	 * e la tabella cms_statistic_components (area_name) non cambiano.
	 * getDetail() e' overridato per mostrare un preview visivo del layout
	 * (vedi sotto); il resto del CRUD generico (lista, cancellazione) resta
	 * quello standard, non toccato.
	 */

	public function getAdd()
	{
		$this->cbLoader();

		if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
		}

		$data['page_title'] = trans('crudbooster.add_data_page_title', ['module' => 'Dashboard Layout']);
		$data['row'] = null;
		$data['rows_model'] = [[12]];
		$data['action'] = CRUDBooster::mainpath('add-save');

		return $this->cbView('dashboard_layouts.builder', $data);
	}

	public function getEdit($id)
	{
		$this->cbLoader();

		if (!CRUDBooster::isRead() && $this->global_privilege == false) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
		}

		$row = DB::table($this->table)->where($this->primary_key, $id)->first();
		if (!$row) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.missing_item'));
		}

		$html = html_entity_decode((string) $row->code_layout);
		$parsed = $this->parseLayoutToGrid($html);

		$data['page_title'] = trans('crudbooster.edit_data_page_title', ['module' => 'Dashboard Layout', 'name' => $row->layoutname]);
		$data['row'] = $row;
		//se il layout esistente non e' nel formato riconosciuto dal builder
		//(es. scritto a mano/col vecchio TinyMCE con markup diverso), si
		//riparte da una griglia vuota di default: senza modalita' avanzata
		//non c'e' modo di preservare un HTML libero non riconosciuto, e il
		//vecchio HTML resta com'era finche' non si salva davvero
		$data['rows_model'] = $parsed ?: [[12]];
		$data['action'] = CRUDBooster::mainpath("edit-save/$id");

		return $this->cbView('dashboard_layouts.builder', $data);
	}

	public function getDetail($id)
	{
		$this->cbLoader();

		if (!CRUDBooster::isRead() && $this->global_privilege == false) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
		}

		$row = DB::table($this->table)->where($this->primary_key, $id)->first();
		if (!$row) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.missing_item'));
		}

		$data['page_title'] = trans('crudbooster.detail_data_page_title', ['module' => 'Dashboard Layout', 'name' => $row->layoutname]);
		$data['row'] = $row;
		$data['code_layout_html'] = html_entity_decode((string) $row->code_layout);

		return $this->cbView('dashboard_layouts.detail', $data);
	}

	public function postAddSave()
	{
		$this->cbLoader();

		if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
		}

		$validator = Validator::make(Request::all(), [
			'layoutname' => 'required|string|max:255',
		]);
		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}

		$arr = [
			'layoutname' => Request::input('layoutname'),
			'code_layout' => $this->buildCodeLayoutFromRequest(),
		];
		if (Schema::hasColumn($this->table, 'created_at')) {
			$arr['created_at'] = date('Y-m-d H:i:s');
		}

		DB::table($this->table)->insert($arr);

		return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.alert_add_data_success'), 'success');
	}

	public function postEditSave($id, $validate = null)
	{
		$this->cbLoader();

		if (!CRUDBooster::isUpdate() && $this->global_privilege == false) {
			return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
		}

		$validator = Validator::make(Request::all(), [
			'layoutname' => 'required|string|max:255',
		]);
		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}

		$arr = [
			'layoutname' => Request::input('layoutname'),
			'code_layout' => $this->buildCodeLayoutFromRequest(),
		];
		if (Schema::hasColumn($this->table, 'updated_at')) {
			$arr['updated_at'] = date('Y-m-d H:i:s');
		}

		DB::table($this->table)->where($this->primary_key, $id)->update($arr);

		return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.alert_update_data_success', ['module' => 'Dashboard Layout', 'title' => Request::input('layoutname')]), 'success');
	}

	/**
	 * Genera l'HTML del layout dal modello a righe/colonne del builder
	 * visuale, con gli id areaN gia' assegnati in ordine di riga/colonna
	 * (nessuna modalita' avanzata/HTML libero).
	 */
	protected function buildCodeLayoutFromRequest()
	{
		$rows = json_decode((string) Request::input('layout_model', '[]'), true);
		if (!is_array($rows)) {
			$rows = [];
		}

		$n = 0;
		$html = '';
		foreach ($rows as $row) {
			if (!is_array($row) || empty($row)) {
				continue;
			}
			$html .= "<div class='statistic-row row'>\n";
			foreach ($row as $width) {
				$width = max(1, min(12, (int) $width));
				$n++;
				$html .= "    <div id='area{$n}' class='col-sm-{$width} connectedSortable'></div>\n";
			}
			$html .= "</div>\n";
		}

		return $html;
	}

	/**
	 * Riconosce solo la forma div.statistic-row.row > div.col-sm-X
	 * .connectedSortable (quella generata dal builder e dal fallback
	 * hardcoded in StatisticBuilderController::getDashboard()). Qualunque
	 * altra struttura (es. tabelle <td> create con il vecchio TinyMCE, o
	 * markup scritto a mano) ritorna null - niente reverse-engineering
	 * azzardato di HTML libero, si passa alla modalita' avanzata.
	 */
	protected function parseLayoutToGrid($html)
	{
		$html = trim((string) $html);
		if ($html === '') {
			return [];
		}

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		$loaded = $dom->loadHTML('<?xml encoding="utf-8" ?><div id="cb-root">' . $html . '</div>');
		libxml_clear_errors();
		if (!$loaded) {
			return null;
		}

		$root = $dom->getElementById('cb-root');
		if (!$root) {
			return null;
		}

		$rows = [];
		foreach ($root->childNodes as $node) {
			if ($node->nodeType === XML_TEXT_NODE) {
				if (trim($node->textContent) !== '') {
					return null;
				}
				continue;
			}
			if ($node->nodeType !== XML_ELEMENT_NODE || strtolower($node->nodeName) !== 'div' || !$this->hasClass($node, 'statistic-row')) {
				return null;
			}

			$cols = [];
			foreach ($node->childNodes as $cell) {
				if ($cell->nodeType === XML_TEXT_NODE) {
					if (trim($cell->textContent) !== '') {
						return null;
					}
					continue;
				}
				if ($cell->nodeType !== XML_ELEMENT_NODE || strtolower($cell->nodeName) !== 'div') {
					return null;
				}
				if (!preg_match('/col-sm-(\d+)/', $cell->getAttribute('class'), $m)) {
					return null;
				}
				$cols[] = (int) $m[1];
			}

			if (empty($cols)) {
				return null;
			}

			$rows[] = $cols;
		}

		return $rows;
	}

	protected function hasClass($node, $class)
	{
		$classes = preg_split('/\s+/', trim($node->getAttribute('class')));
		return in_array($class, $classes, true);
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
		//Non piu' invocato: postAddSave() e' ora overridato sopra e non
		//chiama piu' questo hook (il builder visuale genera gia' l'HTML
		//con gli id assegnati). Lasciato per riferimento/eventuale rollback.
		$postdata['code_layout'] = $this->aggiungiIdAElemTd($postdata['code_layout']);

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
		//Non piu' invocato: vedi nota su hook_before_add() sopra.
		$postdata['code_layout'] = $this->aggiungiIdAElemTd($postdata['code_layout']);

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

	function aggiungiIdAElemTd($html) {
		$n = 0;

		$html = preg_replace_callback('/<td([^>]*)\s*id\s*=\s*"[^"]*"([^>]*)>/i', function($matches) {
			return $matches[0];
		}, $html);

		// Quantificatore lazy (*?), non greedy: con 2+ <td> senza id di
		// fila, la versione greedy originale consumava dal primo <td>
		// fino all'ULTIMO </td> della stringa in un solo match, collassando
		// piu' celle in una sola (perse). Con *? il match si ferma al primo
		// </td> incontrato, una cella alla volta.
		$html = preg_replace_callback('/<td([^>]*)>(?:(?!id=).)*?<\/td>/i', function($matches) use (&$n) {
			$n++;
			$id = 'area' . $n;
			return '<td id="' . $id . '" class="'."connectedSortable".'"'  . '>'.'&nbsp;'.'</td>';
		}, $html);

		return $html;
	}




}
