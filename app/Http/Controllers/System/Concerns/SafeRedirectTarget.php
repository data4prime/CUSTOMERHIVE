<?php

namespace App\Http\Controllers\System\Concerns;

/**
 * Filtro anti open-redirect per i return_url/ref_mainpath che arrivano via
 * POST nelle pagine di autorizzazione (Qlik items e ChatAI).
 */
trait SafeRedirectTarget
{
    /**
     * Accetta solo URL relativi o dello stesso host (evita open redirect
     * dal return_url/ref_mainpath arrivati via POST); altrimenti $fallback.
     */
    protected function safeRedirectTarget($url, $fallback)
    {
        $url = trim((string) $url);
        if ($url === '' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return $fallback;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== null && $host !== false && $host !== request()->getHost()) {
            return $fallback;
        }
        if ($host === null && parse_url($url, PHP_URL_SCHEME) !== null) {
            return $fallback;
        }
        return $url;
    }
}
