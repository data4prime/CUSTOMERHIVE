<?php namespace App\Http\Controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;
	use Illuminate\Support\Facades\Route;
	use ModuleHelper;
	use PDF;
	use \App\Righe;

	class AdminOrdiniController extends \crocodicstudio\crudbooster\controllers\CBController {

	    public function cbInit() {

			# START CONFIGURATION DO NOT REMOVE THIS LINE
			$this->title_field = "id";
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
			$this->table = "mg_ordini";
			# END CONFIGURATION DO NOT REMOVE THIS LINE

			# START COLUMNS DO NOT REMOVE THIS LINE
			$this->col = [];
			$this->col[] = ["label"=>"Data","name"=>"data"];
			$this->col[] = ["label"=>"Cliente","name"=>"cliente"];
			$this->col[] = ["label"=>"Numero","name"=>"numero"];
			$this->col[] = ["label"=>"Intestatario","name"=>"intestatario"];
			$this->col[] = ["label"=>"Tassa","name"=>"tassa"];
			$this->col[] = ["label"=>"Descrizione","name"=>"descrizione"];
			$this->col[] = ["label"=>"Data Consegna","name"=>"data_consegna"];
			$this->col[] = ["label"=>"Termini Di Pagamento","name"=>"termini_di_pagamento"];
			$this->col[] = ["label"=>"Stato","name"=>"stato"];
			# END COLUMNS DO NOT REMOVE THIS LINE

			# START FORM DO NOT REMOVE THIS LINE
			$this->form = [];
			$this->form[] = ['label'=>'Numero','name'=>'numero','type'=>'number','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Data','name'=>'data','type'=>'datetime','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Cliente','name'=>'cliente','type'=>'datamodal','datamodal_table'=>'clienti_details','datamodal_columns'=>'codice,ragione_sociale,indirizzo,piva','datamodal_select_to'=>'ragione_sociale:intestatario,termini_pagamento:termini_di_pagamento','datamodal_where'=>'','datamodal_size'=>'large'];
			// $this->form[] = ['label'=>'Codice cliente','name'=>'codice','type'=>'text','required'=>true];
			// $this->form[] = ['label'=>'Ragione sociale','name'=>'ragione_sociale','type'=>'text','required'=>true];
			// $columns[] = ['label'=>'Indirizzo','name'=>'indirizzo','type'=>'text','required'=>true];
			// $columns[] = ['label'=>'P.IVA','name'=>'piva','type'=>'text','formula'=>"[qta] * [prezzo]","readonly"=>true,'required'=>true];
			// $this->form[] = ['label'=>'Cliente','name'=>'mg_cliente','type'=>'child','columns'=>$columns,'table'=>'mg_cliente','foreign_key'=>'cliente'];

			// $this->form[] = ['label'=>'Cliente','name'=>'cliente','type'=>'text','validation'=>'required','width'=>'col-sm-9'];

			$this->form[] = ['label'=>'Ragione sociale','name'=>'intestatario','type'=>'text','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Tassa','name'=>'tassa','type'=>'percent','validation'=>'required|min:1|max:100','width'=>'col-sm-1'];
			$this->form[] = ['label'=>'Note','name'=>'descrizione','type'=>'textarea','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Data Consegna','name'=>'data_consegna','type'=>'datetime','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Termini di pagamento','name'=>'termini_di_pagamento','type'=>'text','width'=>'col-sm-9', 'readonly'=>true];
			$this->form[] = ['label'=>'Stato','name'=>'stato','type'=>'select2','validation'=>'required','width'=>'col-sm-9','dataenum'=>'Bozza;Inviato;In preparazione;Spedito;Chiuso'];

			$columns[] = ['label'=>'Articolo','name'=>'articolo_id','type'=>'datamodal','datamodal_table'=>'mg_articolo','datamodal_columns'=>'codice,descrizione,prezzo','datamodal_select_to'=>'prezzo:prezzo,codice:codice,descrizione:descrizione,um:unita_misura','datamodal_where'=>'','datamodal_size'=>'large'];
			$columns[] = ['label'=>'Codice','name'=>'codice','type'=>'text','required'=>true];
			$columns[] = ['label'=>'Descrizione','name'=>'descrizione','type'=>'text','required'=>true];
			$columns[] = ['label'=>'Unità di misura','name'=>'unita_misura','type'=>'text','required'=>true];
			$columns[] = ['label'=>'Prezzo','name'=>'prezzo','type'=>'number','required'=>true];
			$columns[] = ['label'=>'Quantità','name'=>'qta','type'=>'number','validation'=>'min:1','required'=>true];
			$columns[] = ['label'=>'Sub Total','name'=>'subtotal','type'=>'number','formula'=>"[qta] * [prezzo]","readonly"=>true,'required'=>true];
			$this->form[] = ['label'=>'Righe d\'ordine','name'=>'mg_righe','type'=>'child','columns'=>$columns,'table'=>'mg_righe','foreign_key'=>'ordine_id'];
			# END FORM DO NOT REMOVE THIS LINE

			# OLD START FORM
			//$this->form = [];
			//$this->form[] = ['label'=>'Data','name'=>'data','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Cliente','name'=>'cliente','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Numero','name'=>'numero','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Intestatario','name'=>'intestatario','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Tassa','name'=>'tassa','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Descrizione','name'=>'descrizione','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Data Consegna','name'=>'data_consegna','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Termini di pagamento','name'=>'termini_di_pagamento','validation'=>'required','width'=>'col-sm-9'];
			//$this->form[] = ['label'=>'Stato','name'=>'stato','validation'=>'required','width'=>'col-sm-9'];
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
					// $this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('[id]/righe/add'),'icon'=>'fa fa-plus','color'=>'success','title'=>'Aggiungi Riga'];
					// $this->addaction[] = ['label'=>'','url'=>CRUDBooster::adminpath('[id]/righe/index'),'icon'=>'fa fa-list','color'=>'info','title'=>'Righe'];
					$this->addaction[] = ['label'=>'','url'=>CRUDBooster::mainpath('print/[id]'),'icon'=>'fa fa-print','color'=>'info','title'=>'Stampa'];

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

	    public function getAdd2()
	    {
	        $this->cbLoader();
	        if (! CRUDBooster::isCreate() && $this->global_privilege == false || $this->button_add == false) {
	            CRUDBooster::insertLog(trans('crudbooster.log_try_add', ['module' => CRUDBooster::getCurrentModule()->name]));
	            CRUDBooster::redirect(CRUDBooster::adminPath(), trans("crudbooster.denied_access"));
	        }

	        $page_title = trans("crudbooster.add_data_page_title", ['module' => CRUDBooster::getCurrentModule()->name]);
	        $page_menu = Route::getCurrentRoute()->getActionName();
	        $command = 'add';

	        $target_layout = \App\Menu::find(Request::get('m'))->target_layout;

	        return view('crudbooster::ordini.form', compact('page_title', 'page_menu', 'command', 'target_layout'));
	    }


			public function getEdit2($id)
			{
					$this->cbLoader();
					$row = DB::table($this->table)->where($this->primary_key, $id)->first();

					//kicks out if user shouldn't view the record $row
					if(!ModuleHelper::can_edit($this, $row)) {
						//log denied access
						CRUDBooster::insertLog(trans("crudbooster.log_try_add", [
							'name' => $module->{$this->title_field},
							'module' => CRUDBooster::getCurrentModule()->name
						]));
						//kick out
						CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
					}

					$page_menu = Route::getCurrentRoute()->getActionName();
	        $page_title = trans("Ordine ".$row->numero);
					$command = 'edit';
					Session::put('current_row_id', $id);

					$target_layout = \App\Menu::find(Request::get('m'))->target_layout;

					return view('crudbooster::ordini.form', compact('id', 'row', 'page_menu', 'page_title', 'command', 'target_layout'));
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
	        //Add order_id to order's righe with null order_id
					$righe = Righe::whereNull('ordine_id')->update(['ordine_id' => $id]);
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

			public function getPrint($id) {
				// Fetch all order data from database
				$data['order'] = \App\Ordini::findOrFail($id);
				$data['cliente'] = \App\Clienti::where('codice', $data['order']->cliente)->first();
				$data['righe'] = \App\Righe::where('ordine_id',$id)->get();
				$data['total'] = 0;
				foreach ($data['righe'] as $riga) {
					$data['total'] += $riga->subtotal;
				}

				$datatopdf = PDF::loadView('crudbooster::ordini.pdf', $data);
				// If you want to store the generated pdf to the server then you can use the store function
				# $pdf->save(storage_path().'_filename.pdf');
				// Finally, you can download the file using download function
				return $datatopdf->download('ordine_' . $id . '.pdf');
			}



	    //By the way, you can still create your own method in here... :)


	}
