<?php

namespace App\Http\Requests\Profile;

use App\Helpers\LicenseHelper;
use App\Helpers\UserHelper;
use App\Http\Requests\Profile\Concerns\RespondsWithProfileJson;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sezione "Qlik" del profilo (AdminCmsUsersController::postProfileQlik, piano 334).
 * Permesso e regole che prima stavano in linea nel controller.
 */
class ProfileQlikRequest extends FormRequest
{
    use RespondsWithProfileJson;

    public function authorize(): bool
    {
        $user = UserHelper::me();

        return $user && (UserHelper::isSuperAdmin() || UserHelper::isTenantAdmin()) && LicenseHelper::isActiveQlik();
    }

    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'rows' => 'nullable|array',
            'rows.*.qlik_conf_id' => 'required|integer|distinct|exists:qlik_confs,id',
            'rows.*.qlik_login' => 'nullable|string|max:255',
            'rows.*.user_directory' => 'nullable|string|max:255',
        ];
    }
}
