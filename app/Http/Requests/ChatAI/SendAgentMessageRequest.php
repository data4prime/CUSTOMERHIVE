<?php

namespace App\Http\Requests\ChatAI;

use App\Helpers\ChatAIHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Messaggio a un agente (AdminChatAIController::send_message_agent, piano 332).
 * Controllo di visibilita' (doc 329) e validazione che prima stavano in linea nel
 * controller. Come SendChatMessageRequest si inietta dalla firma.
 */
class SendAgentMessageRequest extends FormRequest
{
    /**
     * Stesso controllo di prima: l'endpoint e' chiamabile a mano con un agent_id
     * qualunque. Viene prima delle regole (ordine standard di Laravel), quindi un
     * utente non autorizzato riceve denied_access anche con dati non validi.
     */
    public function authorize(): bool
    {
        return (bool) ChatAIHelper::can_see_item($this->input('agent_id'));
    }

    /**
     * Stesso JSON e stesso stato 200 di prima (nessun 403): e' un fetch e il
     * client si aspetta {"message": ...}.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json(['message' => trans('crudbooster.denied_access')])
        );
    }

    /**
     * `agent_id` senza `integer`: un valore non numerico deve continuare a dare
     * denied_access da can_see_item() (authorize), non un errore di validazione.
     *
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string',
            'agent_id' => 'required',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['message' => trans('crudbooster.chatai_invalid_request')], 422)
        );
    }
}
