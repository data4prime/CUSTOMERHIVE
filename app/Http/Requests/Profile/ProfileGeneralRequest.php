<?php

namespace App\Http\Requests\Profile;

use App\Helpers\UserHelper;
use App\Http\Requests\Profile\Concerns\RespondsWithProfileJson;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sezione "Generale" del profilo (AdminCmsUsersController::postProfileGeneral,
 * piano 334). Stato e data di scadenza si validano solo per superadmin/tenant admin.
 */
class ProfileGeneralRequest extends FormRequest
{
    use RespondsWithProfileJson;

    public function authorize(): bool
    {
        return UserHelper::me() !== null;
    }

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|alpha_spaces|min:3',
            'photo' => 'nullable|image|max:1000',
        ];

        if (UserHelper::isSuperAdmin() || UserHelper::isTenantAdmin()) {
            $rules['status'] = 'required|in:Active,Inactive';
            $rules['data_scadenza'] = 'nullable|date';
        }

        return $rules;
    }
}
