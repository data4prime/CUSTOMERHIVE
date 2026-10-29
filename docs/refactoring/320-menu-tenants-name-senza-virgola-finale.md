# 320 - `Menu::tenants_name()` senza virgola finale

- **Data**: 2026-10-29
- **Stato**: Completato (test verdi nel container, non verificato nel browser)
- **Area**: Architettura / Modelli (batch 2b del piano 317)
- **File/aree di codice coinvolte**:
  - `app/Menu.php` (`tenants_name()`)
  - `tests/Feature/MenuTenantsCharacterizationTest.php` (test rovesciato)
  - `tests/Feature/ModelRelationsTest.php` (test rovesciato + uno nuovo)

## Contesto

Batch 2b del piano `docs/refactoring/piani/317-models-standard-laravel.md`.
Il [317](317-modelli-eloquent-caratterizzazione-e-igiene.md) aveva fissato
con test di caratterizzazione anche un difetto noto di `Menu::tenants_name()`
(virgola finale), lasciandolo volutamente com'era; il
[319](319-modelli-eloquent-relazioni-additive.md) (batch 2) aveva già tolto
l'N+1 mantenendo il comportamento. Come il [318](318-chatai-autorizzazioni-su-tabelle-dedicate.md),
questo è un fix puntuale, tenuto in un documento a sé (uno per fix, come
previsto dal piano).

## Situazione prima

`tenants_name()` concatenava i nomi dei tenant della pivot `menu_tenants`
aggiungendo `", "` quando `$key < count($tenants) - 1`. Se l'ultimo tenant
della pivot era soft-deleted o mancante, il nome non veniva aggiunto ma la
virgola del precedente sì: risultato `"Alfa, "`.

## Situazione dopo

Costruisce l'elenco dei nomi effettivamente presenti e li unisce con
`implode(', ', ...)`. Invariati: la singola query `whereIn` + `keyBy('id')`
del batch 2, l'ordine degli id della pivot, l'esclusione dei soft-deleted
(come con `find()`), lista vuota → `""`.

**Cambio visibile**, limitato al solo caso "ultimo tenant mancante o
soft-deleted": `"Alfa, "` → `"Alfa"`. Tutti gli altri casi restituiscono lo
stesso valore di prima. Dove si vede: la riga "meta" di una voce in Menu
Management (`MenuHelper::menu_to_html`, `MenuHelper.php:152` e `:208`),
solo per il superadmin; il valore è usato solo come `e($tenants_name)`,
nessun confronto o JS dipende dalla virgola.

## Motivazione

Il vecchio risultato era un difetto, non un comportamento su cui contare.
`implode` elimina la classe di errore alla radice (niente più logica
sull'indice dell'ultimo elemento).

## Test

Eseguiti nel container su `customerhive_testing` (su richiesta del
chiamante, non in autonomia) i file `ModelRelationsTest`,
`MenuTenantsCharacterizationTest`, `UserModelLookupsTest`,
`ModuleStep1MenuDefaultsTest`, `QlikItemVisibilityTest`,
`QlikItemPublicAccessTest`, `MenusCrudTest`, `GroupsCrudTest`,
`CrossModuleRelationsTest`, `ChatAIAuthorizationTest`: **100 passati, 254
assert, nessun fallimento** (insieme al fix del [321](321-user-primary-group-name-gruppo-orfano.md)).

- Rovesciato di proposito (non adattato per passare, con commento nel
  codice) `MenuTenantsCharacterizationTest`:
  `test_tenants_name_con_ultimo_tenant_soft_deleted_non_lascia_la_virgola_finale`
  ora si aspetta `"Alfa"` (prima fotografava la virgola finale).
- Rovesciato in `ModelRelationsTest`:
  `test_menu_tenants_name_senza_virgola_finale_con_ultimo_tenant_soft_deleted`.
- Aggiunto `test_menu_tenants_name_con_soft_deleted_in_testa_e_in_mezzo_resta_identico`
  (atteso `"Alfa, Gamma"`).
- Un test sugli id duplicati nella pivot era stato scritto e poi **tolto**
  su rilievo del reviewer: `menu_tenants` ha `unique(menu_id, tenant_id)`
  (migrazione `2020_08_20_090341_menu_tenants.php:20`), il caso non è
  raggiungibile.

Non verificato: rendering in Menu Management nel browser.

## Rischi e note

- Review (ch-reviewer): nessun rilievo bloccante; il cambio visibile è
  esattamente quello dichiarato. Unico rilievo (test sui duplicati)
  risolto togliendo il test. Pint segnala solo violazioni storiche su righe
  non toccate.
- I file di test sono untracked, quindi il reviewer non ha potuto
  confrontarli con una versione precedente: il "rovesciamento" è
  documentato qui e nei commenti nel codice.
- Il cambio riguarda solo un caso oggi sbagliato (virgola finale) ed è
  visibile solo al superadmin.

## Rollback

`git revert` del singolo fix (è indipendente dal [321](321-user-primary-group-name-gruppo-orfano.md)):
ripristina `app/Menu.php` e i due file di test. Nessuna migrazione, nessun
dato toccato. Nota: il file `app/Menu.php` contiene anche le modifiche del
batch 2 (319); per tornare indietro solo su questo fix, ripristinare il
corpo di `tenants_name()` alla versione del 319 (concatenazione con
`", "` in base a `$key`).
