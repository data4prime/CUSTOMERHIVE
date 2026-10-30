<?php

namespace App\Http\Requests\ChatAI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Messaggio della chat flottante (AdminChatAIController::send_message, piano 332).
 * Il metodo non e' un override di CBController e la rotta e' esplicita, quindi il
 * Form Request si inietta dalla firma (niente validateCurrent()).
 */
class SendChatMessageRequest extends FormRequest
{
    /**
     * L'accesso e' gia' filtrato dal gruppo di rotte (login e licenza): qui non
     * c'e' un permesso per-risorsa da controllare.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string',
        ];
    }

    /**
     * Il chiamante e' un fetch: risposta JSON con stato 422 invece del redirect
     * con errori in sessione.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['message' => trans('crudbooster.chatai_invalid_request')], 422)
        );
    }
}
