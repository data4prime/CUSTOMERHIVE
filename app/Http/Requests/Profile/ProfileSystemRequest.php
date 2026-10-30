<?php

namespace App\Http\Requests\Profile;

use App\Helpers\UserHelper;
use App\Http\Requests\Profile\Concerns\RespondsWithProfileJson;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sezione "Sistema" del profilo (AdminCmsUsersController::postProfileSystem,
 * piano 334): solo superadmin e tenant admin.
 */
class ProfileSystemRequest extends FormRequest
{
    use RespondsWithProfileJson;

    public function authorize(): bool
    {
        $user = UserHelper::me();

        return $user && (UserHelper::isSuperAdmin() || UserHelper::isTenantAdmin());
    }

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'tenant' => 'nullable|integer|exists:tenants,id',
            'primary_group' => 'required|integer|exists:groups,id',
        ];
    }
}
