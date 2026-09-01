<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use CRUDBooster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends CBController
{
    public function cbInit()
    {
        $this->module_name = "Settings";
        $this->table = 'cms_settings';
        $this->primary_key = 'id';
        $this->title_field = "name";
        $this->index_orderby = ['name' => 'asc'];
        $this->button_delete = true;
        $this->button_show = false;
        $this->button_cancel = false;
        $this->button_import = false;
        $this->button_export = false;

        $this->col = [];
        // "%field%" non e' un placeholder riconosciuto da CBController::
        // getIndex() (che sostituisce solo token "[nomecampo]", o si puo'
        // referenziare $row direttamente essendo gia' in scope nell'eval())
        // - restava cosi' com'e' dentro eval(), causando un ParseError su
        // ogni caricamento di questa pagina (bug preesistente, non legato
        // all'upgrade Laravel).
        $this->col[] = ["label" => "Nama", "name" => "name", "callback_php" => "ucwords(str_replace('_',' ',\$row->name))"];
        $this->col[] = ["label" => "Setting", "name" => "content"];

        $this->form = [];

        if (Request::get('group_setting')) {
            $value = Request::get('group_setting');
        } else {
            $value = 'General Setting';
        }

        $this->form[] = ['label' => 'Group', 'name' => 'group_setting', 'value' => $value];
        $this->form[] = ['label' => 'Label', 'name' => 'label'];

        $this->form[] = [
            "label" => "Type",
            "name" => "content_input_type",
            "type" => "select",
            // Allineata ai case gestiti da setting.blade.php: "upload_document"
            // era offerto qui ma la view non lo renderizzava (nessun campo a
            // schermo, e valore azzerato ad ogni salvataggio del gruppo), mentre
            // "upload_file" - usato da righe reali - non era selezionabile.
            // "password" e' nuovo: serve per non mostrare in chiaro le password.
            "dataenum" => ["text", "password", "number", "email", "textarea", "wysiwyg", "upload_image", "upload_file", "datepicker", "radio", "select"],
        ];
        $this->form[] = [
            "label" => "Radio / Select Data",
            "name" => "dataenum",
            "placeholder" => "Example : abc,def,ghi",
            "jquery" => "
			function show_radio_data() {
				var cit = $('#content_input_type').val();
				if(cit == 'radio' || cit == 'select') {
					$('#form-group-dataenum').show();
				}else{
					$('#form-group-dataenum').hide();
				}
			}
			$('#content_input_type').change(show_radio_data);
			show_radio_data();
			",
        ];
        $this->form[] = ["label" => "Helper Text", "name" => "helper", "type" => "text"];
    }

    function getShow()
    {
        $this->cbLoader();

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Setting', 'module' => 'Setting']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $group = urldecode(Request::get('group'));
        $data['page_title'] = $group;

        // La SELECT dei setting del gruppo stava nella blade, insieme alla
        // UPDATE che riempie le label vuote: una scrittura eseguita durante il
        // render di una GET. La scrittura resta (comportamento invariato, la
        // label si auto-ripara) ma vive qui, non nel template.
        $settings = DB::table('cms_settings')->where('group_setting', $group)->get();
        foreach ($settings as $s) {
            if (!$s->label) {
                $s->label = ucwords(str_replace('_', ' ', $s->name));
                DB::table('cms_settings')->where('id', $s->id)->update(['label' => $s->label]);
            }
        }
        $data['settings'] = $settings;

        return view('crudbooster::setting', $data);
    }

    function hook_before_edit(&$posdata, $id)
    {
        $this->return_url = CRUDBooster::mainpath("show") . "?group=" . urlencode($posdata['group_setting']);
    }

    function getDeleteFileSetting()
    {
        // Mancava qualunque controllo: la rotta e' protetta solo da CBBackend,
        // che verifica soltanto "utente autenticato" - quindi era raggiungibile
        // da qualsiasi utente con qualsiasi privilegio, e azzerava il contenuto
        // di un setting cancellandone il file. Gli altri due metodi custom di
        // questo controller (getShow, postSaveSetting) fanno lo stesso check.
        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Setting', 'module' => 'Setting']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $id = g('id');
        $row = CRUDBooster::first('cms_settings', $id);
        if (!$row) {
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }
        Cache::forget('setting_' . $row->name);

        // $row->content e' un path relativo alla public root (es.
        // "/storage/uploads/...", oppure un vecchio URL assoluto), non un path
        // relativo al disco 'local' - la cui root e' storage/app/public. Per
        // questo Storage::exists() non lo trovava mai e il file restava orfano
        // ad ogni cancellazione. Si risolve con public_path(), come fa il
        // componente upload (vedi docs/refactoring/007-upload-path-relativo.md).
        // La guardia sul contenuto non vuoto e' necessaria: public_path('') e'
        // la cartella public stessa.
        if ($row->content) {
            $file = public_path($row->content);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        DB::table('cms_settings')->where('id', $id)->update(['content' => null]);
        CRUDBooster::redirect(Request::server('HTTP_REFERER'), trans('alert_delete_data_success'), 'success');
    }

    function postSaveSetting()
    {

        if (!CRUDBooster::isSuperadmin()) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", ['name' => 'Setting', 'module' => 'Setting']));
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $group = Request::get('group_setting');
        $setting = DB::table('cms_settings')->where('group_setting', $group)->get();
        $upload_failed = [];
        foreach ($setting as $set) {

            $name = $set->name;

            // Se il campo non e' arrivato nella richiesta il setting non si
            // tocca: prima veniva messo a NULL, quindi qualunque riga il cui
            // input non venga renderizzato (o non venga inviato) si azzerava
            // ad ogni salvataggio del gruppo. Un input di testo svuotato
            // arriva comunque come stringa vuota, quindi resta cancellabile.
            if (!Request::has($name) && !Request::hasFile($name)) {
                continue;
            }

            $content = Request::get($name);

            // Le password si renderizzano sempre vuote: campo vuoto significa
            // "non modificare", altrimenti salvare il gruppo le cancellerebbe.
            if ($set->content_input_type == 'password' && (string) $content === '') {
                continue;
            }

            if (Request::hasFile($name)) {


                if ($set->content_input_type == 'upload_image') {
                    CRUDBooster::valid([$name => 'image|max:10000'], 'view');
                } else {
                    CRUDBooster::valid([$name => 'mimes:pem,doc,docx,xls,xlsx,ppt,pptx,pdf,zip,rar|max:20000'], 'view');
                }

                $file = Request::file($name);
                $ext = $file->getClientOriginalExtension();

                //Create Directory Monthly
                $directory = 'uploads/' . date('Y-m');
                Storage::makeDirectory($directory, 0777, true);

                //Move file to storage
                $filename = md5(str_random(5)) . '.' . $ext;
                $storeFile = Storage::putFileAs($directory, $file, $filename);
                if ($storeFile) {
                    // Path relativo alla public root, non URL assoluto: un URL
                    // costruito da HTTP_HOST si congela sull'host visto durante
                    // l'upload e si rompe al primo cambio di dominio, protocollo
                    // o porta. Stessa convenzione di CRUDBooster::uploadFile()
                    // (vedi docs/refactoring/007-upload-path-relativo.md).
                    $content = '/storage/' . $directory . '/' . $filename;
                } else {
                    // Storage::putFileAs ritorna false SENZA eccezione (caso
                    // tipico: directory non scrivibile dall'utente del web
                    // server). Prima si finiva nell'UPDATE con $content a null,
                    // quindi il setting veniva azzerato e la pagina mostrava
                    // comunque "Your setting has been saved !": un upload
                    // fallito era indistinguibile da uno riuscito.
                    Log::error(
                        'Settings: scrittura del file per "' . $name . '" fallita in '
                        . storage_path('app/public/' . $directory)
                        . ' - controllare i permessi di scrittura.'
                    );
                    $upload_failed[] = $set->label ?: $name;

                    continue;
                }
            }



            DB::table('cms_settings')->where('name', $set->name)->update(['content' => $content]);

            Cache::forget('setting_' . $set->name);
        }

        if ($upload_failed) {
            return redirect()->back()->with([
                'message' => 'Upload failed for: ' . implode(', ', $upload_failed)
                    . '. The previous value has been kept. Check the write permissions on '
                    . 'storage/app/public/uploads (details in the application log).',
                'message_type' => 'warning',
            ]);
        }

        return redirect()->back()->with(['message' => 'Your setting has been saved !', 'message_type' => 'success']);
    }

    function hook_before_add(&$arr)
    {
        $arr['name'] = str_slug($arr['label'], '_');

        // "name" e' la chiave logica di un setting: la usano getSetting(), la
        // cache ("setting_".$name), l'UPDATE di postSaveSetting e la dedup dei
        // seeder (che tiene l'id piu' basso e cancella il resto). Un nome
        // duplicato - anche in un gruppo diverso - farebbe scrivere due righe a
        // vicenda e ne farebbe cancellare una al primo db:seed. C'e' anche un
        // indice unique a DB: questo check evita di arrivarci con un errore SQL.
        if (DB::table('cms_settings')->where('name', $arr['name'])->exists()) {
            CRUDBooster::redirect(
                CRUDBooster::mainpath('add') . '?group_setting=' . urlencode($arr['group_setting']),
                'A setting named "' . $arr['name'] . '" already exists. Please choose a different label.'
            );
        }

        $this->return_url = CRUDBooster::mainpath("show") . "?group=" . urlencode($arr['group_setting']);
    }

    function hook_after_edit($id)
    {
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        /* REMOVE CACHE */
        Cache::forget('setting_' . $row->name);
    }
}
