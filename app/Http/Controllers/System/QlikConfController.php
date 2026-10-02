<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Session;
use Request;
use DB;
use CRUDBooster;
use \App\Helpers\UserHelper;


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
		$this->button_show = false;
		$this->button_filter = true;
		$this->button_import = true;
		$this->button_export = true;
		$this->table = "qlik_confs";
		# END CONFIGURATION DO NOT REMOVE THIS LINE

		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = [];
		$this->col[] = ["label" => "Configuration Name", "name" => "confname"];
		$this->col[] = ["label" => "Type", "name" => "type"];
        $this->col[] = ["label" => "Auth", "name" => "auth"];
        $this->col[] = ["label" => "debug", "name" => "debug"];

		# END COLUMNS DO NOT REMOVE THIS LINE


		# START FORM DO NOT REMOVE THIS LINE
		$this->form = [];
		$this->form[] = ['label' => 'Configuration Name', 'name' => 'confname', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Configuration Name'];
        $this->form[] = ['label' => 'Type', 'name' => 'type', 'type' => 'select', 'width' => 'col-sm-10', 'dataenum' => 'On-Premise;SAAS'];
        $this->form[] = ['label' => 'Auth', 'name' => 'auth', 'type' => 'select', 'width' => 'col-sm-10', 'dataenum' => 'JWT'];
        //$this->form[] = ['label' => 'QRS Url', 'name' => 'url', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRS Url'];
        $this->form[] = ['label' => 'URL', 'name' => 'url', 'type' => 'text',  'width' => 'col-sm-10', 'placeholder' => 'Enter URL'];

        $this->form[] = ['label' => 'Port', 'name' => 'port', 'type' => 'text',  'width' => 'col-sm-10', 'placeholder' => 'Port'];

        $this->form[] = ['label' => 'Endpoint', 'name' => 'endpoint', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Endpoint'];
        //$this->form[] = ['label' => 'QRSCertfile', 'name' => 'QRSCertfile', 'type' => 'upload', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertfile'];
        //$this->form[] = ['label' => 'QRSCertkeyfile', 'name' => 'QRSCertkeyfile', 'type' => 'upload', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertkeyfile'];
        //$this->form[] = ['label' => 'QRSCertkeyfilePassword', 'name' => 'QRSCertkeyfilePassword', 'type' => 'password', 'width' => 'col-sm-10', 'placeholder' => 'Enter QRSCertkeyfilePassword'];

        $this->form[] = ['label' => 'Key ID', 'name' => 'keyid', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Key ID'];
        $this->form[] = ['label' => 'Issuer', 'name' => 'issuer', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Issuer'];
        $this->form[] = ['label' => 'Web Int ID', 'name' => 'web_int_id', 'type' => 'text', 'width' => 'col-sm-10', 'placeholder' => 'Enter Web Int ID'];
        $this->form[] = ['label' => 'Private Key', 'name' => 'private_key', 'type' => 'upload', 'validation' => 'extensions:pem','width' => 'col-sm-10', 'placeholder' => 'Enter Private Key'];

        $this->form[] = ['label' => 'Debug', 'name' => 'debug', 'type' => 'select', 'width' => 'col-sm-10', 'dataenum' => 'Inactive;Active'];
	$this->form[] = ['label' => 'Tenant Path', 'name' => 'tenant_path', 'type' => 'hidden', 'width' => 'col-sm-10', 'value' => env('APP_URL')];

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
        "relationship_table" => "qlikconfs_groups",
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
        | @icon        = Font awesome class icon. e.g : bi bi-list
        | @color 	   = Default is primary. (primary, warning, succecss, info)
        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
        |
        */
		$this->addaction = array();
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('members/[id]'), 'icon' => 'bi bi-person-fill', 'color' => 'info', 'title' => 'Members'];
		//$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('items/[id]'), 'icon' => 'bi bi-shield-fill', 'color' => 'info', 'title' => 'Items'];
		if (CRUDBooster::isSuperadmin()) {
            // Titoli diversi per On-Premise (Qlik Sense Hub / QMC) e SaaS (Qlik Cloud Hub / Management Console); showIf valuta il campo type della riga
            $this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseHub/[id]'), 'icon' => 'bi bi-display', 'color' => 'primary', 'title' => trans('crudbooster.qlik_action_hub_onpremise'), 'showIf' => '[type] != "SAAS"'];
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseQMC/[id]'), 'icon' => 'bi bi-code-slash', 'color' => 'primary', 'title' => trans('crudbooster.qlik_action_qmc_onpremise'), 'showIf' => '[type] != "SAAS"'];
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseHub/[id]'), 'icon' => 'bi bi-display', 'color' => 'primary', 'title' => trans('crudbooster.qlik_action_hub_saas'), 'showIf' => '[type] == "SAAS"'];
			$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('QlikServerSenseQMC/[id]'), 'icon' => 'bi bi-code-slash', 'color' => 'primary', 'title' => trans('crudbooster.qlik_action_console_saas'), 'showIf' => '[type] == "SAAS"'];
            //$this->addaction[] = ['label' => '', 'url' => CRUDBooster::mainpath('tenant/[id]'), 'icon' => 'bi bi-buildings-fill', 'color' => 'primary', 'title' => 'Tenants'];
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
        document.addEventListener('DOMContentLoaded', function() {

            const typeSelect = document.getElementsByName('type')[0];
            //console.log(document.getElementsByName('type'));
            const authSelect = document.getElementsByName('auth')[0];
            //console.log(document.getElementsByName('auth'));

            const fields = {
                'On-Premise_JWT': ['confname', 'type', 'auth', 'url', 'port', 'endpoint', 'private_key'],
                'SAAS_JWT': ['confname', 'type', 'auth', 'url', 'port', 'endpoint', 'keyid', 'issuer', 'web_int_id', 'private_key']
            };

            function updateVisibility() {
                // Hide all fields initially
                Object.keys(fields).forEach(key => {
                    fields[key].forEach(fieldId => {

                        if (document.getElementsByName(fieldId)[0]) {
                            if (fieldId != 'confname' && fieldId != 'type' && fieldId != 'auth') {
                                document.getElementsByName(fieldId)[0].parentNode.parentNode.classList.add('hidden');
                            }
                        }
                    });
                });

                // Determine the selected options
                const selectedType = typeSelect.value;
                const selectedAuth = authSelect.value;

                //console.log(selectedType);
                //console.log(selectedAuth);


                // Construct the key for the fields object
                const fieldKey = selectedType + '_' + selectedAuth;

                // If the key exists in the fields object, show the corresponding fields
                if (fields[fieldKey]) {
                    fields[fieldKey].forEach(fieldId => {
                        //console.log('fieldID '+fieldId);

                        if (document.getElementsByName(fieldId)[0]) {
                            document.getElementsByName(fieldId)[0].parentNode.parentNode.classList.remove('hidden');
                        }

                        
                    });
                }
            }

            //console.log(typeSelect);
            updateVisibility();

            // Add event listeners to update visibility on change
            typeSelect.addEventListener('change', updateVisibility);
            authSelect.addEventListener('change', updateVisibility);
        });
            
            ";

		// Pulsante "Prova connessione" (form add/edit/detail). Testi passati gia'
		// tradotti da Blade/PHP, mai scritti in chiaro nel JS.
		// L'utente corrente deve avere un utente Qlik associato a QUESTA
		// configurazione (qlik_users): il token del test e' costruito su
		// quell'identita'. Nel form di creazione non esiste ancora id, quindi
		// il pulsante e' disattivato con un suggerimento.
		$canTestConnection = false;
		$testBlockHint = trans('crudbooster.qlik_test_add_hint');
		$testConfId = in_array(Request::segment(3), ['edit', 'detail']) ? (int) Request::segment(4) : 0;
		if ($testConfId > 0) {
			$canTestConnection = DB::table('qlik_users')->where('user_id', CRUDBooster::myId())->where('qlik_conf_id', $testConfId)->exists();
			$testBlockHint = trans('crudbooster.qlik_test_no_user_hint');
		}

		$testConnectionJs = <<<'JS'
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('form');
            if (!form) { return; }
            var footerCol = form.querySelector('.box-footer .col-sm-10');
            if (!footerCol) { return; }

            var I18N = __I18N__;
            var TEST_URL = __URL__;
            var CAN_TEST = __CAN_TEST__;
            var BLOCK_HINT = __BLOCK_HINT__;
            var COLORS = { ok: '#198754', warn: '#b58105', fail: '#dc3545', skip: '#6c757d' };
            var m = (form.getAttribute('action') || '').match(/edit-save\/(\d+)/);
            var confId = m ? m[1] : '';
            var lastReport = '';

            function el(tag, text, style) {
                var e = document.createElement(tag);
                if (text !== undefined && text !== null) { e.textContent = text; }
                if (style) { e.style.cssText = style; }
                return e;
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-info';
            var icon = document.createElement('i');
            icon.className = 'bi bi-plug-fill';
            btn.appendChild(icon);
            btn.appendChild(document.createTextNode(' ' + I18N.button));
            footerCol.appendChild(document.createTextNode(' '));
            footerCol.appendChild(btn);

            if (!CAN_TEST) {
                btn.disabled = true;
                footerCol.appendChild(el('p', BLOCK_HINT, 'margin:8px 0 0;color:#6c757d;font-size:13px'));
                return;
            }

            function setLoading(on) {
                btn.disabled = on;
                icon.className = on ? 'bi bi-arrow-repeat ch-spin' : 'bi bi-plug-fill';
            }

            // ---- popup (overlay autonomo: non dipende dal modal di Bootstrap) ----
            var overlay = null;

            function closePopup() {
                if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
                overlay = null;
                document.removeEventListener('keydown', onKey);
            }

            function onKey(e) { if (e.key === 'Escape') { closePopup(); } }

            function openPopup(headBg, headText, bodyBuilder, withCopy) {
                closePopup();
                overlay = el('div', null, 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:100000;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px');
                overlay.addEventListener('click', function (e) { if (e.target === overlay) { closePopup(); } });

                var dlg = el('div', null, 'background:#fff;border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.35);width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden');
                dlg.setAttribute('role', 'dialog');
                dlg.setAttribute('aria-modal', 'true');

                var head = el('div', null, 'padding:12px 16px;color:#fff;display:flex;align-items:center;justify-content:space-between;background:' + headBg);
                var titles = el('div', null, 'min-width:0');
                titles.appendChild(el('div', I18N.title, 'font-size:12px;opacity:.85'));
                titles.appendChild(el('div', headText, 'font-weight:600;font-size:15px'));
                head.appendChild(titles);
                var x = el('button', '×', 'background:none;border:0;color:#fff;font-size:26px;line-height:1;cursor:pointer;padding:0 4px');
                x.type = 'button';
                x.setAttribute('aria-label', I18N.close);
                x.addEventListener('click', closePopup);
                head.appendChild(x);
                dlg.appendChild(head);

                var body = el('div', null, 'padding:12px 16px;overflow-y:auto;flex:1 1 auto');
                bodyBuilder(body);
                dlg.appendChild(body);

                var foot = el('div', null, 'padding:10px 16px;border-top:1px solid #ddd;text-align:right;background:#f5f5f5');
                if (withCopy) {
                    var copy = el('button', I18N.copy, 'margin-right:8px');
                    copy.type = 'button';
                    copy.className = 'btn btn-secondary btn-sm';
                    copy.addEventListener('click', function () {
                        var done = function () { copy.textContent = I18N.copied; };
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(lastReport).then(done);
                        } else {
                            var ta = document.createElement('textarea');
                            ta.value = lastReport;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            done();
                        }
                    });
                    foot.appendChild(copy);
                }
                var close = el('button', I18N.close);
                close.type = 'button';
                close.className = 'btn btn-secondary btn-sm';
                close.addEventListener('click', closePopup);
                foot.appendChild(close);
                dlg.appendChild(foot);

                overlay.appendChild(dlg);
                document.body.appendChild(overlay);
                document.addEventListener('keydown', onKey);
                close.focus();
            }

            function showReport(data) {
                var text = [data.summary];
                openPopup(data.ok ? COLORS.ok : COLORS.fail, data.summary, function (body) {
                    (data.steps || []).forEach(function (s) {
                        var color = COLORS[s.status] || COLORS.skip;
                        var box = el('details', null, 'border:1px solid #ddd;border-left:4px solid ' + color + ';border-radius:3px;background:#fff;margin-bottom:8px;padding:6px 10px');
                        if (s.status !== 'ok') { box.open = true; }

                        var sum = el('summary', null, 'cursor:pointer;font-weight:600');
                        sum.appendChild(el('span', I18N['status_' + s.status] || s.status, 'display:inline-block;min-width:80px;color:' + color));
                        sum.appendChild(document.createTextNode(s.title));
                        sum.appendChild(el('span', ' — ' + s.duration_ms + ' ms', 'font-weight:400;color:#6c757d'));
                        box.appendChild(sum);

                        text.push('', '[' + (I18N['status_' + s.status] || s.status) + '] ' + s.title + ' (' + s.duration_ms + ' ms)');

                        (s.hints || []).forEach(function (h) {
                            box.appendChild(el('div', h, 'margin:6px 0;padding:6px 8px;background:#fff8e1;border-radius:3px'));
                            text.push('  > ' + h);
                        });

                        var details = s.details || {};
                        Object.keys(details).forEach(function (k) {
                            var row = el('div', null, 'margin-top:6px');
                            row.appendChild(el('div', k, 'font-size:12px;color:#6c757d'));
                            row.appendChild(el('pre', details[k], 'margin:0;white-space:pre-wrap;word-break:break-all;background:#f7f7f9;border:0;padding:6px 8px;font-size:12px'));
                            box.appendChild(row);
                            text.push('  ' + k + ': ' + String(details[k]).replace(/\n/g, '\n    '));
                        });

                        body.appendChild(box);
                    });
                    lastReport = text.join('\n');
                }, true);
            }

            function showError(message, raw) {
                openPopup(COLORS.fail, message, function (body) {
                    if (raw) {
                        body.appendChild(el('pre', raw.substring(0, 2000), 'white-space:pre-wrap;font-size:12px;margin:0'));
                    }
                }, false);
            }

            btn.addEventListener('click', function () {
                var fd = new FormData(form);
                fd.set('conf_id', confId);
                setLoading(true);

                fetch(TEST_URL, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function (r) {
                    return r.text().then(function (t) { return { status: r.status, text: t }; });
                }).then(function (res) {
                    var data = null;
                    try { data = JSON.parse(res.text); } catch (e) { data = null; }
                    if (data && data.steps && data.steps.length) {
                        showReport(data);
                    } else if (data && data.summary) {
                        showError(data.summary);
                    } else {
                        showError(I18N.bad_response + ' (HTTP ' + res.status + ')', res.text);
                    }
                }).catch(function (err) {
                    showError(I18N.network_error + ': ' + err.message);
                }).then(function () {
                    setLoading(false);
                });
            });
        });
JS;
		$this->script_js .= strtr($testConnectionJs, [
			'__I18N__' => json_encode([
				'button' => trans('crudbooster.qlik_test_button'),
				'title' => trans('crudbooster.qlik_test_modal_title'),
				'close' => trans('crudbooster.qlik_test_close'),
				'copy' => trans('crudbooster.qlik_test_copy'),
				'copied' => trans('crudbooster.qlik_test_copied'),
				'bad_response' => trans('crudbooster.qlik_test_bad_response'),
				'network_error' => trans('crudbooster.qlik_test_network_error'),
				'status_ok' => trans('crudbooster.qlik_test_status_ok'),
				'status_warn' => trans('crudbooster.qlik_test_status_warn'),
				'status_fail' => trans('crudbooster.qlik_test_status_fail'),
				'status_skip' => trans('crudbooster.qlik_test_status_skip'),
			]),
			'__URL__' => json_encode(CRUDBooster::mainpath('test-connection')),
			'__CAN_TEST__' => $canTestConnection ? 'true' : 'false',
			'__BLOCK_HINT__' => json_encode($testBlockHint),
		]);


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


	/**
	 * "Prova connessione" nel form della configurazione Qlik: risposta JSON con
	 * il rapporto passo-passo di App\Services\QlikConnectionTester. Funziona sia
	 * sui valori del form non ancora salvati sia su una configurazione gia'
	 * salvata (campo conf_id: la chiave privata gia' caricata si legge dal DB,
	 * mai da un path inviato dal client). Route auto-instradata per
	 * riflessione: POST admin/qlik_confs/test-connection.
	 */
	public function postTestConnection()
	{
		$this->cbLoader();

		if (!CRUDBooster::isRead() && !CRUDBooster::isCreate() && !CRUDBooster::isUpdate() && $this->global_privilege == false) {
			return response()->json(['ok' => false, 'summary' => trans('crudbooster.denied_access'), 'steps' => []], 403);
		}

		$savedConf = null;
		$confId = (int) Request::input('conf_id');
		if ($confId > 0) {
			$savedConf = DB::table('qlik_confs')->where('id', $confId)->first();
			if (!$savedConf) {
				return response()->json(['ok' => false, 'summary' => trans('crudbooster.qlik_test_conf_not_found'), 'steps' => []], 404);
			}
			if (!CRUDBooster::isSuperadmin()
				&& !DB::table('qlikconfs_tenants')->where('qlik_confs_id', $confId)->where('tenant_id', UserHelper::current_user_tenant())->exists()) {
				return response()->json(['ok' => false, 'summary' => trans('crudbooster.denied_access'), 'steps' => []], 403);
			}
		}

		// Serve un utente Qlik associato all'utente corrente per questa
		// configurazione: il token del test e' costruito su quell'identita'.
		if (!$savedConf) {
			return response()->json(['ok' => false, 'summary' => trans('crudbooster.qlik_test_add_hint'), 'steps' => []], 422);
		}
		if (!DB::table('qlik_users')->where('user_id', CRUDBooster::myId())->where('qlik_conf_id', $confId)->exists()) {
			return response()->json(['ok' => false, 'summary' => trans('crudbooster.qlik_test_no_user_hint'), 'steps' => []], 422);
		}

		$uploadedKey = null;
		$file = Request::file('private_key');
		if ($file && $file->isValid()) {
			$uploadedKey = (string) file_get_contents($file->getRealPath());
		}

		$input = Request::only(['type', 'auth', 'url', 'port', 'endpoint', 'keyid', 'issuer', 'web_int_id']);

		$report = (new \App\Services\QlikConnectionTester())->run($input, $uploadedKey, $savedConf, \App\User::find(CRUDBooster::myId()));

		return response()->json($report);
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
