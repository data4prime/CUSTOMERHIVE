# 116 - Dashboard a griglia libera: fix, l'aggiunta widget da drag diventa click

- **Data**: 2026-09-28
- **Stato**: Completato e verificato con click reale in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Segnalato di nuovo dall'utente su una dashboard reale
(`/admin/statistic_builder/builder/7`) dopo il fix del
[115](115-dashboard-griglia-fix-drop-area-vuota.md) (area di drop
vuota): il trascinamento continuava a non fare nulla anche con l'area
corretta.

## Situazione prima

L'aggiunta di un widget usava `GridStack.setupDragIn()` per rendere le
card della libreria trascinabili dentro la griglia (drag da un elemento
ESTERNO alla griglia, "cross-container").

## Diagnosi

Investigazione approfondita (eventi mouse sintetici passo-passo,
patch temporanee sui metodi interni di gridstack, confronto con lo
spostamento di un widget già in griglia):
- Lo **spostamento/ridimensionamento di un widget già presente in
  griglia** (drag "interno", stesso contenitore) funziona
  perfettamente: verificato che l'evento pubblico `grid.on('change')`
  scatta e la posizione cambia davvero, con una sequenza di eventi
  mouse sintetici.
- Il **drag da un elemento esterno** (`GridStack.setupDragIn`) non
  completa mai il drop, nemmeno forzando manualmente l'evento
  `mouseenter` sul contenitore della griglia mentre il trascinamento
  è attivo: la classe draggable usata internamente per gli elementi
  "esterni" non imposta lo stato condiviso che il contenitore
  droppable controlla per accettare il drop (comportamento interno di
  gridstack@9, non del codice di questo progetto) — il meccanismo di
  "drag da fuori" di questa libreria, in questo setup, non è
  affidabile.

## Situazione dopo

L'aggiunta di un widget è ora un **click** sulla card della libreria
invece di un trascinamento: aggiunge subito il widget in griglia (in
prima posizione libera, gridstack sceglie da solo grazie a `float:
true`), poi lo si sposta/ridimensiona con lo stesso drag interno già
verificato funzionante. `GridStack.setupDragIn()`/l'evento `dropped`
sono stati rimossi.

## Motivazione

Il drag interno (spostare/ridimensionare) è il cuore dell'editor e
funziona in modo affidabile; il drag esterno serviva solo per il primo
inserimento di un widget, un'azione che un click risolve in modo più
semplice e - soprattutto - **verificato funzionante**, invece di
insistere su un meccanismo di libreria dimostratosi inaffidabile in
questo setup.

## Test

Verificato con **click reale** (non sintetico) in browser su una
dashboard di prova: click su "Indicatore KPI" → widget aggiunto subito
in griglia; click su "Tabella" → secondo widget posizionato
automaticamente accanto al primo senza sovrapposizioni; ricaricata la
pagina → entrambi persistiti nella posizione corretta. Widget di prova
poi rimossi.

## Rischi e note

- Cambia il comportamento atteso rispetto al mockup originale (che
  mostrava un trascinamento dalla libreria) — cambiamento di
  comportamento visibile, fatto qui perché il drag esterno è risultato
  inaffidabile, non per preferenza estetica.
- Il drag interno resta l'unico meccanismo di trascinamento nell'editor:
  se in futuro emergessero problemi anche lì, andrebbe approfondito
  separatamente (qui verificato funzionante).

## Rollback

`git diff` di questo file per tornare al drag esterno (sconsigliato:
riporta il bug segnalato dall'utente).
