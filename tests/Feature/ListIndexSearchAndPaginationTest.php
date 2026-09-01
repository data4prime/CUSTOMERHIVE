<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per la ricerca (?q=) e il numero di record per
 * pagina (?limit=) delle liste admin (CBController::getIndex()) - due
 * parametri GET semplici, comuni a tutti i moduli CRUD, non specifici di
 * uno in particolare. Verificati sul modulo Tenants (il piu' semplice tra
 * quelli gia' coperti da *CrudTest.php: nessun hook/gruppo/privilege da
 * seminare per popolare la lista).
 *
 * - ?q=testo: LIKE "%testo%" in OR su tutte le colonne visibili in
 *   tabella (non sui campi calcolati/subquery) - vedi getIndex().
 * - ?limit=N: sostituisce $this->limit (per Tenants: default 20,
 *   ordinamento "id,desc" esplicito in cbInit()) - il valore piu' recente
 *   creato (id piu' alto) compare per primo.
 * - ?filter_column[<colonna>][sorting]=asc|desc (click sull'header di
 *   colonna) e ?filter_column[<colonna>][type]=<operatore>&[value]=...
 *   (il modale "filtro avanzato"): stessa chiave annidata, gestita da
 *   getIndex() PRIMA del ramo ?limit/orderby di default. Piu' colonne
 *   filtrate insieme sono combinate in AND (un singolo
 *   $result->where(function($w){...}) con $w->where() ripetuto, non
 *   orWhere). Il "sorting" da solo (senza type/value) non aggiunge
 *   nessun WHERE, solo l'orderby.
 */
class ListIndexSearchAndPaginationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    public function test_ricerca_q_filtra_per_nome(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Da Trovare Con La Ricerca',
            'domain_name' => 'tenant-da-trovare-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Tenant Che Non Deve Comparire',
            'domain_name' => 'tenant-altro-' . uniqid(),
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/tenants?q=Da+Trovare');

        $response->assertStatus(200);
        $response->assertSee('Tenant Da Trovare Con La Ricerca');
        $response->assertDontSee('Tenant Che Non Deve Comparire');
    }

    public function test_ricerca_q_senza_corrispondenze_non_mostra_righe(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Presente',
            'domain_name' => 'tenant-presente-' . uniqid(),
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/tenants?q=StringaCheNonCombaciaConNulla');

        $response->assertStatus(200);
        $response->assertDontSee('Tenant Presente');
    }

    public function test_limit_riduce_i_record_mostrati_alla_pagina(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Piu Vecchio',
            'domain_name' => 'tenant-vecchio-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Tenant Piu Recente',
            'domain_name' => 'tenant-recente-' . uniqid(),
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/tenants?limit=1');

        $response->assertStatus(200);
        // AdminTenantsController::cbInit() ordina esplicitamente per
        // "id,desc": con limit=1 resta solo l'ultimo creato (id piu' alto).
        $response->assertSee('Tenant Piu Recente');
        $response->assertDontSee('Tenant Piu Vecchio');
    }

    public function test_limit_piu_alto_del_numero_di_record_li_mostra_tutti(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Primo Tenant Per Limit Alto',
            'domain_name' => 'tenant-limit-1-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Secondo Tenant Per Limit Alto',
            'domain_name' => 'tenant-limit-2-' . uniqid(),
            'created_at' => now(),
        ]);

        $response = $this->get('http://localhost/admin/tenants?limit=200');

        $response->assertStatus(200);
        $response->assertSee('Primo Tenant Per Limit Alto');
        $response->assertSee('Secondo Tenant Per Limit Alto');
    }

    public function test_sort_ascendente_ordina_i_risultati_per_colonna(): void
    {
        $this->actingAsSuperadmin();
        // Inserito PRIMA (id piu' basso): con l'ordinamento di default
        // (id,desc) comparirebbe DOPO Zeta - se il test passa comunque con
        // Alfa prima, e' merito del sort per nome, non una coincidenza
        // dell'ordinamento di default.
        DB::table('tenants')->insert([
            'name' => 'Alfa Tenant Per Sort Asc',
            'domain_name' => 'tenant-sort-asc-alfa-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Zeta Tenant Per Sort Asc',
            'domain_name' => 'tenant-sort-asc-zeta-' . uniqid(),
            'created_at' => now(),
        ]);

        $url = 'http://localhost/admin/tenants?' . http_build_query([
            'filter_column' => ['name' => ['sorting' => 'asc']],
        ]);
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Alfa Tenant Per Sort Asc', 'Zeta Tenant Per Sort Asc']);
    }

    public function test_sort_discendente_ordina_i_risultati_per_colonna(): void
    {
        $this->actingAsSuperadmin();
        // Qui invertito rispetto al test sopra: Zeta inserito prima (id
        // piu' basso, quindi DOPO Alfa nell'ordinamento di default
        // id,desc) - se compare comunque prima, e' il sort desc per nome
        // a deciderlo, non l'ordinamento di default.
        DB::table('tenants')->insert([
            'name' => 'Zeta Tenant Per Sort Desc',
            'domain_name' => 'tenant-sort-desc-zeta-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Alfa Tenant Per Sort Desc',
            'domain_name' => 'tenant-sort-desc-alfa-' . uniqid(),
            'created_at' => now(),
        ]);

        $url = 'http://localhost/admin/tenants?' . http_build_query([
            'filter_column' => ['name' => ['sorting' => 'desc']],
        ]);
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Zeta Tenant Per Sort Desc', 'Alfa Tenant Per Sort Desc']);
    }

    public function test_filter_like_su_una_colonna_specifica(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Da Trovare Col Filtro',
            'domain_name' => 'tenant-filtro-like-1-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Tenant Che Il Filtro Non Deve Prendere',
            'domain_name' => 'tenant-filtro-like-2-' . uniqid(),
            'created_at' => now(),
        ]);

        $url = 'http://localhost/admin/tenants?' . http_build_query([
            'filter_column' => ['name' => ['type' => 'like', 'value' => 'Da Trovare Col Filtro']],
        ]);
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSee('Tenant Da Trovare Col Filtro');
        $response->assertDontSee('Tenant Che Il Filtro Non Deve Prendere');
    }

    /**
     * A differenza di 'like', l'operatore '=' richiede una corrispondenza
     * ESATTA: un valore che e' un prefisso/sottostringa del nome non deve
     * bastare a far comparire la riga.
     */
    public function test_filter_uguale_richiede_corrispondenza_esatta(): void
    {
        $this->actingAsSuperadmin();
        DB::table('tenants')->insert([
            'name' => 'Tenant Nome Esatto',
            'domain_name' => 'tenant-filtro-eq-1-' . uniqid(),
            'created_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Tenant Nome Esatto Con Testo In Piu',
            'domain_name' => 'tenant-filtro-eq-2-' . uniqid(),
            'created_at' => now(),
        ]);

        $url = 'http://localhost/admin/tenants?' . http_build_query([
            'filter_column' => ['name' => ['type' => '=', 'value' => 'Tenant Nome Esatto']],
        ]);
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSee('Tenant Nome Esatto');
        $response->assertDontSee('Tenant Nome Esatto Con Testo In Piu');
    }

    /**
     * Piu' colonne filtrate insieme sono in AND: una riga che soddisfa
     * solo una delle due condizioni non deve comparire.
     */
    public function test_filter_su_piu_colonne_applica_and(): void
    {
        $this->actingAsSuperadmin();
        // Riga che soddisfa ENTRAMBE le condizioni del filtro.
        DB::table('tenants')->insert([
            'name' => 'Combo Match Alpha',
            'description' => 'Right Description Alpha',
            'domain_name' => 'tenant-filtro-and-alpha-' . uniqid(),
            'created_at' => now(),
        ]);
        // Soddisfa solo il filtro sul nome.
        DB::table('tenants')->insert([
            'name' => 'Combo Match Beta',
            'description' => 'Wrong Description Beta',
            'domain_name' => 'tenant-filtro-and-beta-' . uniqid(),
            'created_at' => now(),
        ]);
        // Soddisfa solo il filtro sulla descrizione.
        DB::table('tenants')->insert([
            'name' => 'Different Name Gamma',
            'description' => 'Right Description Gamma',
            'domain_name' => 'tenant-filtro-and-gamma-' . uniqid(),
            'created_at' => now(),
        ]);

        $url = 'http://localhost/admin/tenants?' . http_build_query([
            'filter_column' => [
                'name' => ['type' => 'like', 'value' => 'Combo Match'],
                'description' => ['type' => 'like', 'value' => 'Right Description'],
            ],
        ]);
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSee('Combo Match Alpha');
        $response->assertSee('Right Description Alpha');
        $response->assertDontSee('Combo Match Beta');
        $response->assertDontSee('Different Name Gamma');
    }
}
