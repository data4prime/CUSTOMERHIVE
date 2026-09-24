# 079 - Statistic Builder: privilegi su aggiunta/spostamento widget, `config` non esposto

- **Data**: 2026-09-24
- **Stato**: Completato (parziale, vedi Rischi e note)
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `tests/Feature/StatisticBuilderCrudTest.php`

## Contesto

Gap rimandato di proposito in
[067](067-statistic-builder-sql-arbitrario-e-test.md) (che aveva corretto
il caso grave, l'SQL arbitrario via `postSaveComponent()`), poi in backlog.
Ripreso su richiesta.

## Situazione prima

`CBBackend` verifica solo "sei loggato". Nel controller hanno già
`isSuperadmin()`: `getBuilder()`, `getEditComponent()`,
`postSaveComponent()`, `getDeleteComponent()`. Senza controllo:

- `postAddComponent()` — aggiunge un widget a qualunque dashboard.
- `postUpdateAreaComponent()` — sposta un widget (area/ordinamento) di
  qualunque dashboard.
- `getListComponent($id, $area)` — elenca i widget di qualunque dashboard,
  **inclusa la colonna `config`, che contiene la query SQL** dei widget
  Small Box/Table/Chart (e altra configurazione interna).

Nessuno dei tre permette di iniettare SQL eseguibile (il `config` di un
widget appena creato è vuoto), ma manca qualunque controllo su chi agisce e
su quale dashboard (IDOR).

Vincolo importante (già documentato in 067): `getListComponent()` e
`getViewComponent()` **non possono** essere riservate al superadmin, perché
servono anche alla visualizzazione normale delle dashboard
(`show.blade.php` include la stessa vista dell'editor). Il drag&drop che
chiama `add-component`/`update-area-component` è invece attivo solo in
`getBuilder()` (`@if (CRUDBooster::getCurrentMethod() == 'getBuilder')` in
`statistic_builder/index.blade.php`).

## Situazione dopo

- `postAddComponent()` e `postUpdateAreaComponent()`: stesso controllo
  `isSuperadmin()` + log + redirect `denied_access` degli altri metodi
  dell'editor.
- `getListComponent()`: resta accessibile a tutti gli utenti loggati come
  prima, ma per i **non superadmin** la risposta non include più `config`
  (solo `id`, `id_cms_statistics`, `componentID`, `component_name`,
  `area_name`, `sorting`, `name`). Il JS della pagina usa solo
  `componentID` (per poi chiamare `view-component`), verificato in
  `index.blade.php`. Per il superadmin la risposta è invariata.
- 3 test di caratterizzazione del gap convertiti in regressione + 3 nuovi
  (add/update riescono per il superadmin; `list-component` include
  `config` per il superadmin).

**Comportamento visibile che cambia**: nessuno nei flussi dell'interfaccia
(l'editor era già solo superadmin; la visualizzazione delle dashboard usa
solo `componentID`). Cambia solo per chi chiama gli endpoint a mano senza
essere superadmin.

## Motivazione

Allineare i due endpoint di scrittura dell'editor agli altri già
protetti, e smettere di esporre le query SQL dei widget a qualunque utente
loggato senza rompere la visualizzazione delle dashboard.

## Test

- Manuale via curl da superadmin: `list-component/2/area1` → risposta
  completa con `config` (invariata); `show/dashboard-ordini-di-vendita`,
  `builder/2`, `view-component/{id}` → 200.
- Caso non superadmin: coperto dai test automatici (in locale non c'è un
  utente non superadmin con password nota). Eseguiti su richiesta il
  2026-09-24: tutti i 17 test del file verdi (run congiunto con 078/080,
  56 test / 217 asserzioni).
- `php -l` sul controller e sul file di test.

## Rischi e note

**Parziale, di proposito — decisione di prodotto aperta**: la
*visibilità* delle dashboard per ruolo non è mai verificata lato server.
`getShow($slug)` apre qualunque dashboard a qualunque utente loggato che
ne conosca lo slug, e `getListComponent()`/`getViewComponent()` di
conseguenza servono i widget di qualunque dashboard (`view-component`
esegue la query del widget e ne restituisce il risultato). Un commento nel
codice cita i "link condivisi" `/statistic_builder/show/{slug}` come uso
reale: un controllo di appartenenza (es. "il ruolo ha un menu che punta a
questa dashboard") potrebbe rompere link oggi usati dai clienti. Va deciso
prima quale regola di visibilità è quella voluta.

Non toccati (già in backlog da 067): null-safety di `getBuilder()`/
`getEditComponent()`/`getViewComponent()` su id/componentID inesistente.

## Rollback

Rimuovere i due blocchi `isSuperadmin()` aggiunti e il `select()`
condizionale in `getListComponent()`; ripristinare i test dal git history.
