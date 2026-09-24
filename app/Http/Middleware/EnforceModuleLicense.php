<?php

namespace App\Http\Middleware;

use App\Helpers\LicenseHelper;
use Closure;
use CRUDBooster;

/**
 * Blocca le route dei moduli a licenza (Qlik, ChatAI) quando il modulo non
 * e' incluso nella licenza attiva - anche via URL diretto, non solo
 * nascondendo pulsanti/voci di menu. Vedi
 * docs/refactoring/076-guard-licenza-e-login-moduli-qlik-chatai.md.
 *
 * Il controllo scatta solo per i path sotto i prefissi mappati qui sotto
 * (confronto a segmento intero: "qlik_items" non copre "qlik_items_x"),
 * per ogni altra richiesta costa un confronto di stringhe.
 */
class EnforceModuleLicense
{
    /**
     * Prefisso di path (relativo ad ADMIN_PATH) => metodo di LicenseHelper
     * che dice se il modulo e' in licenza.
     */
    protected const LICENSED_PREFIXES = [
        'qlik_items' => 'isActiveQlik',
        'qlik_confs' => 'isActiveQlik',
        'qlik/user' => 'isActiveQlik',
        'chat_ai' => 'isActiveChatAI',
    ];

    public function handle($request, Closure $next)
    {
        $admin_path = trim(config('crudbooster.ADMIN_PATH') ?: 'admin', '/');
        $path = trim($request->path(), '/');

        foreach (self::LICENSED_PREFIXES as $prefix => $check) {
            $full = $admin_path.'/'.$prefix;
            if ($path === $full || str_starts_with($path, $full.'/')) {
                if (! LicenseHelper::$check()) {
                    // Se la dashboard del ruolo punta proprio a questo modulo
                    // (menu di tipo Qlik/Agent AI, vedi CBBackend), rimandare
                    // alla dashboard creerebbe un loop di redirect: il flag
                    // flashato qui sopravvive al giro /admin (CBBackend fa
                    // reflash) e al secondo passaggio si risponde con un 403.
                    if ($request->session()->get('module_license_denied')) {
                        abort(403, trans('crudbooster.module_not_licensed'));
                    }
                    $request->session()->flash('module_license_denied', true);

                    return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.module_not_licensed'));
                }
                break;
            }
        }

        return $next($request);
    }
}
