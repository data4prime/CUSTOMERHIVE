<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 336 (docs/refactoring/piani/336-modelli-in-app-models-user-per-primo.md),
 * batch B1: `App\User` si sposta in `App\Models\User` con alias per il vecchio nome
 * e una migration che aggiorna `personal_access_tokens.tokenable_type`.
 *
 * Fissano: il token con tipo `App\User` (emesso prima dello spostamento) autentica
 * ancora grazie all'alias, anche senza migration; un token nuovo salva
 * `App\Models\User`; il vecchio nome e' un alias della stessa classe; il guard `web`
 * usa il modello nuovo; la migration porta i token al nome nuovo (e `down()` li
 * riporta indietro) senza toccare altri tipi.
 */
class UserModelMoveTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private const OLD_TYPE = 'App\User';

    private const NEW_TYPE = 'App\Models\User';

    protected function setUp(): void
    {
        parent::setUp();

        Route::group(['middleware' => ['api', 'auth:sanctum']], function () {
            Route::any('phpunit-test-user-move/{permalink}', fn () => response()->json(['ok' => true]));
        });
    }

    private function seedUserModel(): \App\User
    {
        $tenantId = $this->seedTenant();
        $privilegeId = $this->seedPrivilege();
        $data = $this->seedUser(['tenant' => $tenantId, 'id_cms_privileges' => $privilegeId]);

        // Nome vecchio di proposito: deve funzionare prima (classe vera) e dopo (alias).
        return \App\User::find($data['id']);
    }

    private function callApi(string $plainTextToken)
    {
        return $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->getJson('http://localhost/phpunit-test-user-move/qualcosa');
    }

    /** Carica la migration del piano 336 (anonima: si include una volta sola per processo). */
    private function tokenableTypeMigration(): object
    {
        static $migration = null;

        if ($migration === null) {
            $files = glob(database_path('migrations/*_update_tokenable_type_for_user_model.php'));
            $this->assertNotEmpty($files, 'Migration update_tokenable_type_for_user_model non trovata.');
            $migration = require $files[0];
        }

        return $migration;
    }

    private function insertToken(int $userId, string $type, string $name): void
    {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => $type,
            'tokenable_id' => $userId,
            'name' => $name,
            'token' => hash('sha256', $name),
            'abilities' => '["*"]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function typeOf(string $name): ?string
    {
        return DB::table('personal_access_tokens')->where('name', $name)->value('tokenable_type');
    }

    // ------------------------------------------------- Sanctum e dati esistenti

    public function test_un_token_con_il_tipo_vecchio_autentica_ancora(): void
    {
        $user = $this->seedUserModel();
        $token = $user->createToken('vecchio', ['*'])->plainTextToken;

        // Simula un token emesso prima dello spostamento.
        DB::table('personal_access_tokens')->where('name', 'vecchio')->update(['tokenable_type' => self::OLD_TYPE]);

        $this->callApi($token)->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_un_token_con_il_tipo_nuovo_autentica(): void
    {
        // Positivo del precedente: stesso setup, tipo nuovo.
        $user = $this->seedUserModel();
        $token = $user->createToken('nuovo', ['*'])->plainTextToken;

        DB::table('personal_access_tokens')->where('name', 'nuovo')->update(['tokenable_type' => self::NEW_TYPE]);

        $this->callApi($token)->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_un_token_nuovo_salva_il_tipo_della_classe_nuova(): void
    {
        $user = $this->seedUserModel();
        $user->createToken('appena-creato', ['*']);

        $this->assertSame(self::NEW_TYPE, $this->typeOf('appena-creato'));
    }

    // ------------------------------------------------------------------- Alias

    public function test_il_nome_nuovo_esiste_e_il_vecchio_e_un_alias_della_stessa_classe(): void
    {
        $this->assertTrue(class_exists(self::NEW_TYPE), 'App\Models\User non esiste.');
        $this->assertTrue(class_exists(self::OLD_TYPE), 'App\User non esiste.');

        $this->assertSame(
            (new \ReflectionClass(self::NEW_TYPE))->getName(),
            (new \ReflectionClass(self::OLD_TYPE))->getName()
        );

        $old = new (self::OLD_TYPE)();
        $this->assertInstanceOf(self::NEW_TYPE, $old);
        $this->assertSame(self::NEW_TYPE, $old->getMorphClass());
    }

    public function test_il_guard_web_usa_il_modello_nuovo(): void
    {
        $actor = $this->actingAsSuperadmin();
        $provider = Auth::guard('web')->getProvider();

        $this->assertSame(self::NEW_TYPE, ltrim($provider->getModel(), '\\'));
        $this->assertInstanceOf(self::NEW_TYPE, $provider->retrieveById($actor['userId']));
    }

    // --------------------------------------------------------------- Migration

    public function test_la_migration_porta_i_token_al_tipo_nuovo_e_il_down_li_riporta(): void
    {
        $user = $this->seedUserModel();
        $this->insertToken($user->id, self::OLD_TYPE, 'a-vecchio');
        $this->insertToken($user->id, self::NEW_TYPE, 'b-nuovo');
        $this->insertToken($user->id, 'App\Altro', 'c-altro');

        $migration = $this->tokenableTypeMigration();

        $migration->up();
        $this->assertSame(self::NEW_TYPE, $this->typeOf('a-vecchio'));
        $this->assertSame(self::NEW_TYPE, $this->typeOf('b-nuovo'));
        $this->assertSame('App\Altro', $this->typeOf('c-altro'), 'Un tipo non User non va toccato.');

        $migration->down();
        $this->assertSame(self::OLD_TYPE, $this->typeOf('a-vecchio'));
        $this->assertSame(self::OLD_TYPE, $this->typeOf('b-nuovo'));
        $this->assertSame('App\Altro', $this->typeOf('c-altro'));
    }

    public function test_la_migration_con_la_tabella_vuota_non_fa_nulla(): void
    {
        $migration = $this->tokenableTypeMigration();

        $migration->up();
        $migration->down();

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }
}
