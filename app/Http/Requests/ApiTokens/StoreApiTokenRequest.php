<?php

namespace App\Http\Requests\ApiTokens;

use App\Helpers\CRUDBooster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Generazione di un token API (ApiTokensController::postAddSave, doc 327):
 * primo Form Request del repo. Permesso e validazione che prima stavano in
 * linea nel controller, nello stesso ordine: prima `create`, poi le regole.
 */
class StoreApiTokenRequest extends FormRequest
{
    /**
     * CBController::postAddSave() non ha parametri, quindi il Form Request non
     * si puo' iniettare dalla firma (un override con un parametro obbligatorio
     * e' un errore fatale). Costruisce e valida dalla richiesta corrente come
     * fa il container (authorize() poi rules(), con le stesse eccezioni).
     * Niente `app(StoreApiTokenRequest::class)`: Larastan risolve davvero le
     * classi passate ad app() durante l'analisi, eseguendo authorize() senza
     * sessione (warning non ignorabile in Helpers/CRUDBooster.php).
     */
    public static function validateCurrent(): static
    {
        $request = static::createFrom(request());
        $request->setContainer(app())->setRedirector(redirect());
        $request->validateResolved();

        return $request;
    }

    /**
     * Stessa condizione di prima nel controller
     * (`!isCreate() && global_privilege == false`, con global_privilege
     * sempre false in ApiTokensController::cbInit()).
     */
    public function authorize(): bool
    {
        return (bool) CRUDBooster::isCreate();
    }

    /**
     * Il rifiuto resta un redirect al mainpath con `denied_access` (come
     * CRUDBooster::redirect() nel resto del modulo), non il 403 di default.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            CRUDBooster::redirect(CRUDBooster::mainpath(), trans('crudbooster.denied_access'))
        );
    }

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'tokenable_id' => 'required|integer|exists:cms_users,id',
            'name' => 'required|string|max:255',
            'expiry_choice' => 'required|in:30,90,365,never,custom',
            'expiry_custom_date' => 'required_if:expiry_choice,custom|nullable|date|after:today',
        ];
    }
}
