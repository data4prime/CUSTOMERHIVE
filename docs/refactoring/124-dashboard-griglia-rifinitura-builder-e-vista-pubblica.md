# 124 - Dashboard a griglia libera: rifinitura builder e vista pubblica

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/show_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartbar_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartarea_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/panelcustom.blade.php`

## Contesto

Richiesta unica dell'utente con una lista di rifiniture UX dopo aver
usato il builder a griglia con dati reali. Trattate tutte insieme in
questo intervento perché strettamente collegate (stessa pagina, stessa
sessione di test).

## 1. Bug reale trovato: i grafici non si disegnavano dopo un ricaricamento

**Situazione prima**: `addExistingWidget()` (usata da `loadComponents()`,
quindi a ogni caricamento/ricaricamento della pagina) passava l'HTML del
widget come opzione `content` di `grid.addWidget()`. Gridstack lo
inserisce con `innerHTML` diretto, che **non esegue** gli `<script>`
incorporati — i grafici ApexCharts (il cui rendering dipende da uno
`<script>` che chiama `new ApexCharts(...)`) restavano quindi vuoti a
ogni ricaricamento, anche se configurati correttamente. Funzionava solo
per i widget il cui contenuto è testo/HTML puro (KPI, tabella), non per
i grafici.

**Situazione dopo**: il widget viene creato vuoto con
`grid.addWidget({x,y,w,h})`, poi il contenuto vero viene iniettato con
`$(el).find('.grid-stack-item-content').html(content)` (jQuery, che
esegue correttamente gli script - stesso motivo per cui la sidebar già
funzionava).

**Test**: creato un widget `chartline_v2` con una query SQL reale,
salvato, **ricaricata la pagina** (non bastava verificarlo appena
aggiunto: il bug si manifestava solo al reload) → il grafico ora si
disegna.

## 2. Bug reale trovato: il widget KPI era illeggibile nel builder

**Situazione prima**: `builder_grid.blade.php` è una pagina standalone
(non carica AdminLTE, mai incluso lì) - `.small-box`/`.card` (le classi
usate dai widget) non avevano alcuno stile proprio: un KPI configurato
appariva come un blocco di colore pieno senza contenimento, invece del
box con numero grande + etichetta + icona d'angolo del builder legacy.

**Situazione dopo**: aggiunto un CSS minimo per `.small-box`/`.card`
(non un porting di AdminLTE, solo l'essenziale per la leggibilità) sia
nel builder sia nella vista pubblica (con qualche rifinitura tipografica
in più lì, vedi punto 8).

## 3. Elenco moduli di "Modulo incorporato": licenza e Token API

Prima: la select mostrava **tutti** i moduli con `getIndex`/`getAdd`,
incluso "Token API" (pagina di generazione token, non ha senso
incorporarla in un widget) e i moduli Qlik/Chat AI **anche quando la
licenza corrente non li include** (si sarebbe potuto scegliere un
modulo che poi non funziona). Corretto in `panelcustom.blade.php`:
`ApiTokensController` escluso sempre; i controller con "Qlik" nel nome
esclusi salvo `LicenseHelper::isActiveQlik()`; `AdminChatAIController`
escluso salvo `LicenseHelper::isActiveChatAI()`.

**Test**: in questo ambiente (licenza dev senza Qlik/Chat AI attivi) la
select è passata da 42 a 34 opzioni; verificato che "Token API" non
compare mai, e che le uniche voci con "api" nel nome sono "Generatore
API" (modulo diverso, legittimo).

## 4. Popup di conferma ed indicatori di caricamento

- Eliminazione widget: `confirm()` nativo del browser sostituito con un
  popup coerente con lo stile della pagina (`#ch-confirm-modal`).
- Eliminazione: durante l'intervallo tra conferma e risposta del server,
  il widget si attenua e la sua toolbar mostra uno spinner, invece di
  restare visivamente invariato (dava l'impressione che il click non
  avesse avuto effetto).
- Salvataggio configurazione: il bottone "Salva" mostra uno spinner ed è
  disabilitato per la durata della richiesta.

**Test**: verificato in browser che il popup compare al posto
dell'alert nativo, e che il flusso completo (conferma → spinner →
scomparsa widget) funziona.

## 5. Rimossa la modalità "Query guidata" (per ora)

Su indicazione esplicita dell'utente ("prima bisogna pensarci bene"):
tolto il toggle "SQL libera / Query guidata" e il pannello guidato
(dataset/metrica/filtro/anteprima) da tutti i widget che lo avevano
(`smallbox`, `table`, `chartline_v2`, `chartbar_v2`, `chartarea_v2`) -
tornano tutti alla sola SQL libera, come nel builder legacy. **I file
`_query_mode_toggle.blade.php`/`_query_builder_fields.blade.php` e il
backend (`DashboardDatasetRegistry`, endpoint `dataset-options`/
`dataset-preview`, il ramo `mode === 'builder'` in
`renderComponentPayload()`) restano nel codice, semplicemente non più
inclusi/raggiungibili da nessuna UI**: la progettazione di
un'esperienza "query guidata" più chiara è rimandata, non il lavoro
già fatto.

## 6. Stile dei campi del form di configurazione

Aggiunta select con freccia personalizzata (coerente con gli altri
campi) e normalizzato l'aspetto di select2 (l'icona Ionicons) e
dell'input colore nativo, così tutti i campi della sidebar hanno bordo/
angoli/altezza coerenti tra loro.

## 7. Vista pubblica (menu): stile ispirato al mockup, senza modifica

`show_grid.blade.php`: aggiunto il font Manrope e lo stesso CSS minimo
di `.small-box`/`.card` del builder (con qualche rifinitura tipografica
in più), scoped a `.ch-grid-view` per non toccare il resto del tema
admin. **Nessuna funzionalità di modifica aggiunta** (niente maniglie,
niente palette, niente pulsanti edit/elimina - già assenti da
[121](121-dashboard-griglia-fix-vista-pubblica-pulsanti-e-stile.md)):
solo l'aspetto dei widget reali si avvicina a quello del mockup
"Idea 1", non il contenuto (che resta quello vero, dati reali).

**Test**: creata una dashboard di prova con 2 KPI colorati e una
tabella, verificato visivamente che l'aspetto è vicino al mockup
approvato (card pulite, tipografia Manrope, colori pieni sui KPI).
Dashboard di prova poi rimossa.

## Rischi e note

- Il "porting" di `.small-box`/`.card` è minimo e mirato: non copre
  ogni variante AdminLTE (es. box colorati "bg-aqua" ecc. del builder
  legacy), solo quanto serve ai widget effettivamente raggiungibili
  dalla nuova griglia.
- L'icona Ionicons dei widget KPI resta soggetta alla discrepanza già
  nota (118): il nome scelto nel picker (Ionicons v2) potrebbe non
  corrispondere a un'icona v7 valida per `<ion-icon>`.
- "Query guidata" resta nel codice ma non raggiungibile: chi riprende
  il lavoro deve solo re-includere i due partial nei widget e la
  modalità torna disponibile, senza dover riscrivere il backend.

## Rollback

`git diff` di questi file per tornare allo stato precedente (drag/drop
con la stessa griglia, ma con i bug/comportamenti descritti sopra).
