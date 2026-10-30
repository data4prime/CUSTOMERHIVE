<?php

namespace App\Actions\ApiTokens;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\NewAccessToken;

class GenerateApiToken
{
    /**
     * Crea un token Sanctum con tutte le abilita' per l'utente. Mappa la scelta
     * di scadenza del form: 30/90/365 giorni da adesso, `custom` a fine
     * giornata della data indicata, qualunque altro valore (es. `never`) senza
     * scadenza. $customDate e' valorizzata dalla validazione solo con `custom`.
     */
    public function handle(User $user, string $name, string $expiryChoice, ?string $customDate = null): NewAccessToken
    {
        $expiresAt = match ($expiryChoice) {
            '30' => now()->addDays(30),
            '90' => now()->addDays(90),
            '365' => now()->addDays(365),
            'custom' => Carbon::parse($customDate)->endOfDay(),
            default => null,
        };

        return $user->createToken($name, ['*'], $expiresAt);
    }
}
