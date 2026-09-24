<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use CRUDBooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\User;

/**
 * Gestione dei token Sanctum del binario api2 (vedi docs/refactoring/086 e
 * 088). Personal Access Token: generabile per QUALUNQUE utente esistente
 * (eredita i suoi permessi/privilegi, esattamente come un PAT di GitHub/
 * GitLab) - nessuna categoria di utente dedicata. Solo lettura/generazione/
 * revoca: i token non sono modificabili (getEdit/postEditSave negati
 * esplicitamente), il valore in chiaro del token si vede una sola volta,
 * subito dopo la generazione (a DB resta solo l'hash).
 */
class ApiTokensController extends CBController
{
    public function cbInit()
    {
        # START CONFIGURATION DO NOT REMOVE THIS LINE
        $this->title_field = "name";
        $this->limit = "20";
        $this->orderby = "id,desc";
        $this->global_privilege = false;
        $this->button_table_action = true;
        $this->button_bulk_action = false;
        // Stile "icone" ma che rispetta button_edit/button_detail=false
        // anche per il superadmin (vedi resources/views/crudbooster/
        // components/action.blade.php) - con lo stile di default
        // ('button_icon') edit/dettaglio restavano visibili al superadmin
        // nonostante disattivati qui sotto, perché ModuleHelper::can_edit()/
        // can_view() lo bypassano sempre per il superadmin.
        $this->button_action_style = "button_icon_strict";
        $this->button_add = true;
        $this->button_edit = false;
        $this->button_delete = true;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "personal_access_tokens";
        # END CONFIGURATION DO NOT REMOVE THIS LINE

        # START COLUMNS DO NOT REMOVE THIS LINE
        $this->col = [];
        $this->col[] = ["label" => trans('crudbooster.api_tokens_col_name'), "name" => "name"];
        $this->col[] = [
            "label" => trans('crudbooster.api_tokens_col_user'),
            "name" => "tokenable_id",
            "callback_php" => "DB::table('cms_users')->where('id', \$row->tokenable_id)->value('name') ?: '-'",
        ];
        $this->col[] = ["label" => trans('crudbooster.api_tokens_col_created'), "name" => "created_at"];
        $this->col[] = [
            "label" => trans('crudbooster.api_tokens_col_expires'),
            "name" => "expires_at",
            "callback_php" => "\$row->expires_at ?: trans('crudbooster.api_tokens_never')",
        ];
        $this->col[] = [
            "label" => trans('crudbooster.api_tokens_col_last_used'),
            "name" => "last_used_at",
            "callback_php" => "\$row->last_used_at ?: trans('crudbooster.api_tokens_never')",
        ];
        # END COLUMNS DO NOT REMOVE THIS LINE

        # START FORM DO NOT REMOVE THIS LINE
        // Non usato per la creazione: getAdd()/postAddSave() sono
        // overridati con un form custom (utente + nome + scadenza) che
        // chiama User::createToken(), l'unico modo corretto di generare un
        // token Sanctum (non un semplice insert di riga).
        $this->form = [];
        # END FORM DO NOT REMOVE THIS LINE
    }

    /**
     * Utenti selezionabili per un nuovo token: qualunque utente esistente
     * (modello Personal Access Token - vedi docs/refactoring/088).
     */
    private function selectableUsers()
    {
        return DB::table('cms_users')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getAdd()
    {
        $this->cbLoader();

        if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
            return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
        }

        $data['page_title'] = trans('crudbooster.api_tokens_add_page_title');
        $data['api_clients'] = $this->selectableUsers();
        $data['action'] = CRUDBooster::mainpath('add-save');

        return $this->cbView('api_tokens.add', $data);
    }

    public function postAddSave()
    {
        $this->cbLoader();

        if (!CRUDBooster::isCreate() && $this->global_privilege == false) {
            return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'));
        }

        $validator = Validator::make(Request::all(), [
            'tokenable_id' => 'required|integer|exists:cms_users,id',
            'name' => 'required|string|max:255',
            'expiry_choice' => 'required|in:30,90,365,never,custom',
            'expiry_custom_date' => 'required_if:expiry_choice,custom|nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::find(Request::input('tokenable_id'));
        if (!$user) {
            return redirect()->back()->withErrors(['tokenable_id' => trans('crudbooster.api_tokens_invalid_user')])->withInput();
        }

        $expiryChoice = Request::input('expiry_choice');
        $expiresAt = match ($expiryChoice) {
            '30' => now()->addDays(30),
            '90' => now()->addDays(90),
            '365' => now()->addDays(365),
            'custom' => Carbon::parse(Request::input('expiry_custom_date'))->endOfDay(),
            default => null,
        };

        $tokenName = Request::input('name');
        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        CRUDBooster::insertLog(trans('crudbooster.api_tokens_log_generated', ['name' => $tokenName, 'user' => $user->name]));

        $data['page_title'] = trans('crudbooster.api_tokens_reveal_page_title');
        $data['plain_text_token'] = $token->plainTextToken;
        $data['token_name'] = $tokenName;

        return $this->cbView('api_tokens.reveal', $data);
    }

    public function getEdit($id)
    {
        return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.api_tokens_not_editable'));
    }

    public function postEditSave($id, $validate = null)
    {
        return CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.api_tokens_not_editable'));
    }
}
