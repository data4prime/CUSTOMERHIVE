# 339 - Ultimi modelli di sistema spostati in `App\Models`

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti dall'implementatore: 351 verdi su 2 gruppi; Larastan pulito; regressione completa da lanciare)
- **Area**: Architettura / Modelli
- **File/aree di codice coinvolte**:
  - `app/Models/` (7 file nuovi: `Menu`, `ChatAIConf`, `QlikItem`, `QlikSyncRun`, `QlikSyncRunRecord`,
    `Log`, `AccessLog`); i 7 vecchi `app/<Nome>.php` eliminati
  - `app/Support/legacy_crudbooster_aliases.php` (7 alias)
  - riferimenti in `Helpers/` (`MenuHelper`, `UserHelper`, `ChatAIHelper`, `QlikHelper`, `functions.php`),
    `Actions/{ChatAI,Menus}/`, `Services/QlikSync/{QlikSyncService,QlikSyncRollback}`, 10 controller di
    `Controllers/System/` (incluso `CBController`), `Providers/AppServiceProvider`, 2 Blade
  - `tests/Feature/RemainingModelMoveTest.php` (nuovo)
  - Piano: `piani/339-modelli-in-app-models-ultimi-menu-chatai-qlik-log.md`

## Contesto

Quinto e ultimo batch di spostamento dei modelli di sistema in `App\Models\` (ADR A17), dopo `User` (336),
`Tenant`/`Group`/`Role`/`UsersGroup` (337) e le pivot più `Modules` (338). Esclusi per scelta della persona
`Clienti`/`Ordini`/`Righe` (non di sistema); `DynamicTable`/`DynamicColumn` non sono modelli Eloquent.

## Situazione prima

I 7 modelli stavano in `app/` con namespace `App`. `Menu` era il più esposto (13 `use`, 4 inline attivi in
`CBController`, inline in `MenuHelper` e `UserHelper`) e il controller per cliente `AdminOrdiniController`
(righe 280/307) lo cita per nome. `Menu`, `ChatAIConf` e `QlikItem` avevano `use App\Models\...` espliciti
(per `Group`, `Tenant` e le pivot dei batch 337/338). `QlikSyncRun`/`QlikSyncService` non avevano test.

## Situazione dopo

I 7 modelli sono in `App\Models\` (solo namespace), con `class_alias` sui vecchi nomi. Rimossi i `use App\Models\...`
ridondanti nei tre modelli (stesso namespace) e `use App\QlikItem;` mai usato in `AppServiceProvider`
(l'import morto `App\Observers\QlikItemObserver`, di una classe inesistente, resta). `new App\Log` in
`functions.php` → `new App\Models\Log`. Aggiornati gli inline (`CBController` 4 punti, helper, 2 Blade
`form_body` riga 36). **Nessun cambio visibile.** Nessuna migration. `app/` non contiene più modelli di
sistema: restano `Clienti`, `Ordini`, `Righe`, `DynamicTable`, `DynamicColumn`.

## Motivazione

Completare l'allineamento dei modelli alla convenzione Laravel, in un unico batch (deroga consapevole alla
regola "un modello per batch") con ordine interno che isola i rischi, e senza rompere i controller dei
clienti (alias, prova concreta: `AdminOrdiniController`).

## Test

- Nuovo `RemainingModelMoveTest`: alias dei 7, tabelle/timestamp, soft delete (`ChatAIConf`, `QlikItem`) e
  assenza (`Menu`, `QlikSyncRun*`), costanti e `activeStatuses()`/`isActive()`/cast/`records()` di
  `QlikSyncRun`, `add_log_ch()` (riga in `log`, troncamento a 65000), `AccessLog::count()`, `target_layout`
  da `CBController` su index/add/edit/detail (positivo con `m` valido, negativo con `m=999999` → `default`),
  render delle due Blade `form_body`, e `QlikSyncService::requestCancel()` con `cancel_delete` (percorso senza
  rete che esercita `QlikSyncRollback`, `QlikSyncRun`, `QlikSyncRunRecord`, `Menu::qlikItemContent`;
  positivo e negativo) e `markFailed`. Sul codice vecchio: 7 alias rossi, 24 verdi.
- Dopo: tutto verde. Regressione in due gruppi (condividono il DB di test): 206 + 145 = **351 test verdi,
  0 falliti**, senza suite completa.
- Verifiche statiche: `php -l`, `composer dump-autoload`, Larastan da cache vuota senza errori. Ricerca
  finale a testo fisso rifatta indipendentemente dalla sessione principale: il vecchio nome resta solo negli
  alias, nel test di alias, nel commento `CBController:1659` e in `AdminOrdiniController` (serve l'alias).
- Pint `--test`: segnala 5 dei modelli spostati (stile del contenuto originale: tab, `const` senza
  visibilità); non riformattati per non gonfiare il diff.
- **Lacune dichiarate**: `step()`, `upsertApp`, `upsertItem` di `QlikSyncService` (30+ usi dei modelli Qlik)
  richiedono un driver Qlik e quindi rete: non testati; un `use` sbagliato lì lo vedrebbero solo `php -l` e
  Larastan. Il test delle due Blade passa anche con il nome vecchio (lo risolve l'alias). Per `add_log_ch()` non
  esiste un caso negativo (scrive sempre). `AccessLog` solo sul modello, non via dashboard. Nessun reviewer
  lanciato (solo namespace).

## Rischi e note

- Un riferimento al vecchio nome funziona per alias e non si vede: la ricerca finale è la rete di sicurezza.
- `AdminOrdiniController` (controller per cliente) cita ancora `\App\Menu`: va bene finché esiste l'alias;
  l'alias non va tolto prima che tutti i clienti siano migrati.
- In produzione serve `composer dump-autoload` dopo l'update. Da riconfermare sul DB di un cliente reale la
  query `*able_type` (nessun nome di classe salvato per questi modelli nel DB locale; i job non serializzano
  modelli).
- `use App\Observers\QlikItemObserver;` in `AppServiceProvider` è un import morto (la cartella non esiste),
  lasciato volutamente.

## Rollback

Rimettere i 7 file in `app/` con namespace `App`, togliere i 7 alias, ripristinare riferimenti, `use`
rimossi e `new App\Log`. Nessun dato toccato, nessuna migration.
