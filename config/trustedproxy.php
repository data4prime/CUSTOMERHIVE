<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxy fidati
    |--------------------------------------------------------------------------
    |
    | Lista separata da virgole di IP/CIDR dei reverse proxy (es. Traefik) di
    | cui fidarsi per X-Forwarded-For/Proto/Host/Port/Prefix, oppure "*" per
    | fidarsi di chiunque chiami l'app (solo se il container non e' raggiungibile
    | se non attraverso il proxy). Vuota/non impostata = nessun proxy fidato,
    | cioe' il comportamento di sempre.
    |
    | Letta da qui (non con env() nel middleware) per compatibilita' con
    | config:cache.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
