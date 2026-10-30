<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Piano 336 (batch B1): `App\User` e' stato spostato in `App\Models\User`.
 * Sanctum salva il nome della classe in personal_access_tokens.tokenable_type:
 * i token gia' emessi vengono riallineati al nome nuovo. Il vecchio nome resta
 * un alias (app/Support/legacy_crudbooster_aliases.php), quindi i token non
 * aggiornati continuerebbero a funzionare comunque.
 */
return new class () extends Migration {
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\User')
            ->update(['tokenable_type' => 'App\Models\User']);
    }

    public function down(): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\Models\User')
            ->update(['tokenable_type' => 'App\User']);
    }
};
