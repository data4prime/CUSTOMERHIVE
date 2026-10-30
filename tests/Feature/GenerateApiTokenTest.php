<?php

namespace Tests\Feature;

use App\Actions\ApiTokens\GenerateApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test dell'Action GenerateApiToken (B5, doc 328): fissano la mappatura della
 * scadenza spostata fuori da ApiTokensController::postAddSave(). Servono il DB
 * (MySQL vero) perche' createToken() scrive su personal_access_tokens.
 */
class GenerateApiTokenTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function user(): User
    {
        return User::findOrFail($this->seedUser()['id']);
    }

    private function storedToken(): object
    {
        return DB::table('personal_access_tokens')->first();
    }

    #[DataProvider('giorniProvider')]
    public function test_scelta_in_giorni_imposta_la_scadenza_da_adesso(string $choice, int $days): void
    {
        $token = (new GenerateApiToken())->handle($this->user(), 'Token', $choice);

        $this->assertNotEmpty($token->plainTextToken);

        $expiresAt = Carbon::parse($this->storedToken()->expires_at);
        $this->assertEqualsWithDelta(now()->addDays($days)->timestamp, $expiresAt->timestamp, 5);
    }

    public static function giorniProvider(): array
    {
        return [
            '30 giorni' => ['30', 30],
            '90 giorni' => ['90', 90],
            '365 giorni' => ['365', 365],
        ];
    }

    public function test_custom_scade_a_fine_giornata_della_data_indicata(): void
    {
        // data relativa: expires_at e' un TIMESTAMP MySQL (arriva al 2038)
        $date = now()->addDays(10)->toDateString();

        (new GenerateApiToken())->handle($this->user(), 'Token', 'custom', $date);

        $this->assertSame($date . ' 23:59:59', Carbon::parse($this->storedToken()->expires_at)->format('Y-m-d H:i:s'));
    }

    public function test_never_non_ha_scadenza(): void
    {
        (new GenerateApiToken())->handle($this->user(), 'Token', 'never');

        $this->assertNull($this->storedToken()->expires_at);
    }

    public function test_scelta_sconosciuta_non_ha_scadenza(): void
    {
        // Preserva il `default => null` di prima (non raggiungibile da HTTP).
        (new GenerateApiToken())->handle($this->user(), 'Token', '7');

        $this->assertNull($this->storedToken()->expires_at);
    }

    public function test_salva_nome_proprietario_e_abilita_complete(): void
    {
        $user = $this->user();

        (new GenerateApiToken())->handle($user, 'Mio token', 'never');

        $this->assertSame(1, DB::table('personal_access_tokens')->count());

        $row = $this->storedToken();
        $this->assertSame('Mio token', $row->name);
        $this->assertEquals($user->id, $row->tokenable_id);
        $this->assertSame(['*'], json_decode($row->abilities, true));
    }
}
