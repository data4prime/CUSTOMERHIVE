# 175 - Statistic Builder: "Layout Dashboard" nascosto dal menu e campo Layout facoltativo

- **Data**: 2026-09-30
- **Stato**: Completato (non verificato nel browser)
- **Area**: Statistic Builder / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/sidebar.blade.php`
  - `app/Http/Controllers/System/StatisticBuilderController.php` (`cbInit()`)

## Contesto

Le nuove dashboard nascono sempre in modalita' `grid` (vedi 114/`hook_before_add()`)
e ignorano il layout ad aree fisse (`dashboard_layouts`). Il modulo "Layout
Dashboard" e il campo obbligatorio "Layout" nel form di creazione non hanno
piu' senso per chi crea una dashboard nuova.

## Situazione prima

- Sidebar: sotto "Statistic Builder" c'era una voce "Layout Dashboard"
  (`admin/dashboard_layouts`), sempre visibile ai superadmin.
- Form Statistic Builder: campo "Layout" `required`, `validation => required`:
  in creazione bisognava scegliere un layout che poi veniva ignorato.

## Situazione dopo

- La voce "Layout Dashboard" e' rimossa dalla sidebar (sostituita da un commento
  Blade che spiega come riattivarla). Il modulo e i dati restano: `admin/dashboard_layouts`
  funziona ancora via URL, cosi' chi ha dashboard `legacy_areas` puo' modificare i layout.
- Il campo "Layout" e' facoltativo (`required => false`, `validation => nullable`).
- Il campo e' nascosto con `hide_form` sia in creazione sia in modifica (non
  scritto: nelle nuove `layout` resta NULL; nelle `legacy_areas` esistenti il
  valore gia' salvato resta intatto). Da UI non si puo' piu' cambiare il
  layout di una dashboard `legacy_areas` (serve il DB).

## Motivazione

Meno confusione in creazione. `hide_form` invece di togliere il campo da
`$this->form` (lezione di 103: rimuovere un campo dal form puo' corrompere i dati
al salvataggio; con `hide_form` il campo non viene assegnato).

## Test

Nessun test automatico eseguito. Verificati: `php -l` del controller, compilazione
Blade della sidebar (`Blade::compileString` + `php -l`), lettura di `cbLoader()` ->
`cbInit()` -> `checkHideForm()`. **Non verificato nel browser**: creare una dashboard
nuova (niente campo Layout, salvataggio ok, `layout` NULL, apre il builder a
griglia), modificare una `legacy_areas` (campo nascosto, layout invariato dopo il salvataggio), controllare l'elenco
(colonna Layout vuota per le nuove).

## Rischi e note

- Il modulo Layout Dashboard non e' piu' raggiungibile dal menu: per i clienti
  con dashboard `legacy_areas` serve conoscere l'URL finche' non sono convertite.
- Una dashboard nuova con `layout` NULL che venisse riportata a `legacy_areas` dal DB
  ricade sulla griglia di default a 9 aree (`resolveDashboardCodeLayout()`), ma
  `getBuilder()` legacy con layout NULL mostra `code_layout` vuoto.

## Rollback

Ripristinare i due file (`git checkout` dei path). Nessuna migration, nessun dato toccato.
