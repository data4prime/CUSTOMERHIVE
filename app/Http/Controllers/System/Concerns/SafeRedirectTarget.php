<?php

namespace App\Http\Controllers\System\Concerns;

/**
 * Filtro anti open-redirect per i return_url/ref_mainpath che arrivano via
 * POST nelle pagine di autorizzazione (Qlik items e ChatAI) e negli add_*
 * di Groups, Users e Tenants (member/item/tenant/group, doc 326).
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
        // Spazi e caratteri di controllo dentro l'URL: i browser tolgono tab e
        // a capo, quindi "/\t/host" diventa "//host" (parse_url lo vede come
        // semplice path).
        if (preg_match('/[\x00-\x20\x7f]/', $url)) {
            return $fallback;
        }
        // "http:///host": parse_url() fallisce (false) e i controlli sotto
        // lo darebbero per buono, ma i browser lo aprono come "http://host".
        if (parse_url($url) === false) {
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
