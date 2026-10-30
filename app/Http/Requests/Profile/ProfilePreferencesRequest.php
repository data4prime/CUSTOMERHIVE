<?php

namespace App\Http\Requests\Profile;

use App\Helpers\DateFormat;
use App\Helpers\UserHelper;
use App\Http\Requests\Profile\Concerns\RespondsWithProfileJson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sezione "Preferenze" del profilo (AdminCmsUsersController::postProfilePreferences,
 * piano 334): vale per qualunque utente loggato, solo sul proprio profilo.
 */
class ProfilePreferencesRequest extends FormRequest
{
    use RespondsWithProfileJson;

    public function authorize(): bool
    {
        return UserHelper::me() !== null;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'lang' => 'required|in:en,it',
            'decimal_separator' => 'required|in:comma,dot',
            'date_format' => ['required', Rule::in(DateFormat::FORMATS)],
        ];
    }
}
