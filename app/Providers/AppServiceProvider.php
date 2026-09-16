<?php

namespace App\Providers;

use App\QlikItem;
use App\Observers\QlikItemObserver;
use App\Helpers\PasswordPolicy;
use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        Validator::extend('alpha_spaces', function ($attribute, $value) {
            // This will only accept alpha and spaces.
            // If you want to accept hyphens use: /^[\pL\s-]+$/u.
            return preg_match('/^[\pL\s]+$/u', $value);
        }, 'The :attribute should be letters and spaces only');

        Validator::extend('alpha_num_spaces', function ($attribute, $value) {
            // This will only accept alphanumeric and spaces.
            return preg_match('/^[a-zA-Z0-9\s]+$/', $value);
        }, 'The :attribute should be alphanumeric characters and spaces only');

        // Regole "in stile NIST 800-63B" per le password: niente requisiti di
        // composizione (maiuscola/simbolo obbligatori), ma blocco di password
        // comuni, sequenze/ripetizioni e parole legate al contesto utente
        // (email, nome). Vedi App\Helpers\PasswordPolicy.
        Validator::extend('not_common_password', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return false;
            }

            if (PasswordPolicy::isCommon($value) || PasswordPolicy::hasSequentialOrRepeatedChars($value)) {
                return false;
            }

            $data = $validator->getData();

            return !PasswordPolicy::containsContextWord($value, [$data['email'] ?? null, $data['name'] ?? null]);
        }, 'The :attribute is too common or too easy to guess. Please choose a different one.');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        require app_path('Helpers/functions.php');
    }
}
