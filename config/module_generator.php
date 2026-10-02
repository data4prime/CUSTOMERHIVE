<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nuovo module generator (wizard v2)
    |--------------------------------------------------------------------------
    |
    | Quando e' true il wizard usa le nuove interfacce (per ora: passo Lista).
    | Spento di default: il formato salvato nei controller dei moduli e' lo
    | stesso, quindi il flag si puo' accendere e spegnere senza migrazioni.
    | Vedi docs/refactoring/193 e 194.
    |
    */

    'wizard_v2' => (bool) env('MODULE_GENERATOR_WIZARD_V2', false),

];
