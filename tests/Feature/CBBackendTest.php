<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il middleware CBBackend (CRUDBooster),
 * il gate che protegge ogni pagina dell'area admin. Fissano il
 * comportamento ATTUALE, il middleware non è stato modificato.
 *
 * Si usa la rotta vuota 'GET /admin' (Route::get('/', function () {}) in
 * routes.php) come bersaglio minimo protetto da CBBackend, per testare
 * solo la logica del gate senza dipendere da un controller applicativo.
 * Nota: se in futuro venisse configurato un menu con is_dashboard=1, questa
 * stessa rotta farebbe un redirect aggiuntivo (gestito comunque da
 * CBBackend) — qui il DB di test non ne ha nessuno, quindi il gate arriva
 * fino in fondo e passa la richiesta al closure vuoto.
 *
 * Nota su un dettaglio scoperto scrivendo questo test: il middleware
 * globale App\Http\Middleware\SetUserPreferredLanguage (gira su OGNI
 * richiesta web, non solo su quelle autenticate) presume che l'id utente in
 * sessione corrisponda sempre a una riga reale in cms_users, altrimenti va
 * in errore fatale (nessun controllo di null). In produzione non è un
 * problema perché admin_id viene sempre impostato da un login riuscito su
 * un utente reale — per questo qui si semina sempre un utente vero invece
 * di inventare un id a caso.
 */
class CBBackendTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function getAdmin(array $session = [])
    {
        return $this->withSession($session)->get('http://localhost/admin');
    }

    public function test_richiesta_senza_sessione_reindirizza_al_login(): void
    {
        $response = $this->getAdmin();

        $response->assertRedirect(url('admin/login'));
    }

    public function test_richiesta_con_sessione_valida_passa(): void
    {
        $userId = $this->seedUser()['id'];

        $response = $this->getAdmin(['admin_id' => $userId, 'admin_lock' => 0]);

        $response->assertStatus(200);
    }

    public function test_richiesta_con_sessione_bloccata_reindirizza_al_lock_screen(): void
    {
        $userId = $this->seedUser()['id'];

        $response = $this->getAdmin(['admin_id' => $userId, 'admin_lock' => 1]);

        $response->assertRedirect(url('admin/lock-screen'));
    }
}
