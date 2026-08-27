<?php

namespace App\Http\Controllers\System;

use CRUDBooster;
use Illuminate\Support\Facades\DB;

class EmailTemplatesController extends \crocodicstudio\crudbooster\controllers\CBController
{
    public function cbInit()
    {
        $this->table = "cms_email_templates";
        $this->primary_key = "id";
        $this->title_field = "name";
        $this->limit = 20;
        $this->orderby = ["id" => "desc"];
        $this->global_privilege = false;

        $this->button_table_action = true;
        $this->button_action_style = "button_icon";
        $this->button_add = true;
        $this->button_delete = true;
        $this->button_edit = true;
        $this->button_detail = true;
        $this->button_show = false;
        $this->button_filter = true;
        $this->button_export = false;
        $this->button_import = false;

        $this->col = [];
        $this->col[] = ["label" => "Template Name", "name" => "name"];
        $this->col[] = ["label" => "Slug", "name" => "slug"];

        $this->form = [];
        $this->form[] = [
            "label" => "Template Name",
            "name" => "name",
            "type" => "text",
            "required" => true,
            "validation" => "required|min:3|max:255|alpha_spaces",
            "placeholder" => "You can only enter the letter only",
        ];
        // Lo slug non e' scrivibile: viene calcolato dal Template Name in
        // hook_before_add e in modifica non viene mai toccato. Niente "required"
        // ne' "validation": il valore che arriva dal form non viene usato, quindi
        // validarlo sarebbe fuorviante (e un campo readonly vuoto farebbe
        // fallire la creazione se il javascript di anteprima non gira).
        $this->form[] = [
            "label" => "Slug",
            "name" => "slug",
            "type" => "text",
            "readonly" => true,
            "help" => "Generated from the Template Name when the template is created, and never changed afterwards: it is the key the code uses to reference this template, e.g. CRUDBooster::sendEmail(['template' => 'forgot_password_backend']).",
        ];
        $this->form[] = ["label" => "Subject", "name" => "subject", "type" => "text", "required" => true, "validation" => "required|min:3|max:255"];
        $this->form[] = ["label" => "Content", "name" => "content", "type" => "tinymce", "required" => true, "validation" => "required"];
        //$this->form[] = ["label" => "Content", "name" => "content", "type" => "ckeditor", "required" => true, "validation" => "required"];
        $this->form[] = ["label" => "Description", "name" => "description", "type" => "text", "required" => true, "validation" => "required|min:3|max:255"];

        $this->form[] = [
            "label" => "From Name",
            "name" => "from_name",
            "type" => "text",
            "required" => false,
            "width" => "col-sm-6",
            'placeholder' => 'Optional',
        ];
        $this->form[] = [
            "label" => "From Email",
            "name" => "from_email",
            "type" => "email",
            "required" => false,
            "validation" => "email",
            "width" => "col-sm-6",
            'placeholder' => 'Optional',
        ];

        $this->form[] = [
            "label" => "Cc Email",
            "name" => "cc_email",
            "type" => "email",
            "required" => false,
            "validation" => "email",
            'placeholder' => 'Optional',
        ];

        // Anteprima dello slug mentre si scrive il nome: e' solo un'anteprima,
        // il valore autoritativo lo calcola hook_before_add lato server, quindi
        // non importa se il javascript non gira. Si attiva solo a slug vuoto,
        // cioe' in creazione: in modifica il campo non va toccato.
        // Nowdoc perche' il JS e' pieno di $ e non deve essere interpolato.
        // Nota: la chiave "jquery" dei campi form non e' supportata dal form di
        // add/edit (solo da form_detail), per questo si passa da script_js.
        $this->script_js = <<<'JS'
$(function () {
    var name = $('#name'), slug = $('#slug');
    if (!name.length || !slug.length || slug.val()) { return; }
    name.on('keyup change', function () {
        slug.val(
            $(this).val()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '')
        );
    });
});
JS;
    }

    /**
     * Lo slug e' la chiave con cui il codice recupera il template
     * (CRUDBooster::sendEmail(['template' => ...]) -> first('cms_email_templates',
     * ['slug' => ...])), non un'etichetta. Per questo viene generato una volta
     * alla creazione e non e' modificabile.
     */
    public function hook_before_add(&$arr)
    {
        $arr['slug'] = str_slug($arr['name'], '_');

        if (!$arr['slug']) {
            CRUDBooster::redirect(
                CRUDBooster::mainpath('add'),
                'The Template Name does not produce a valid slug. Please use letters and spaces.'
            );
        }

        // Due template con lo stesso slug renderebbero ambiguo quale viene
        // recuperato dagli invii (first() prende il primo per id).
        if (DB::table($this->table)->where('slug', $arr['slug'])->exists()) {
            CRUDBooster::redirect(
                CRUDBooster::mainpath('add'),
                'A template with slug "' . $arr['slug'] . '" already exists. Please choose a different Template Name.'
            );
        }
    }

    /**
     * In modifica lo slug non si tocca, nemmeno se il nome cambia:
     * ricalcolarlo romperebbe silenziosamente gli invii che lo referenziano.
     * Il caso e' concreto - il template "Email Template Forgot Password
     * Backend" ha slug "forgot_password_backend", non derivabile dal nome.
     * Togliendolo da $arr, la UPDATE non include la colonna.
     */
    public function hook_before_edit(&$arr, $id)
    {
        unset($arr['slug']);
    }
    //By the way, you can still create your own method in here... :)

}
