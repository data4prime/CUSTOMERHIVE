<?php

namespace App\Http\Requests\Profile\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Rifiuti in JSON dei Form Request del profilo (piano 334): stesso formato degli
 * helper privati profileResponse()/profileValidationError() di
 * AdminCmsUsersController, sempre JSON anche per richieste non AJAX (come prima).
 */
trait RespondsWithProfileJson
{
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json(['ok' => false, 'message' => trans('crudbooster.denied_access')], 403)
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
