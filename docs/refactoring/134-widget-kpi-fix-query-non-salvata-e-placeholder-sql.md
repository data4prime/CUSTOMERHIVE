# 134 - Widget Indicatore KPI: fix query non salvata + placeholder "[sql]" grezzo

- **Data**: 2026-09-29
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/lang/en/crudbooster.php`
  - `resources/lang/it/crudbooster.php`

## Contesto

Segnalato dall'utente: nel widget Indicatore KPI, se non si imposta una
query il valore mostra letteralmente il testo "[sql]" (poco intuitivo);
inoltre il pannello di configurazione non salva né la SQL libera né la
query guidata (dataset/metrica).

## Situazione prima

**Bug 1 - placeholder grezzo**: `renderComponentPayload()`
(`StatisticBuilderController.php`) sostituisce `[chiave]` nel layout solo
per le chiavi di `$config` con un valore truthy; se `sql` e' vuoto (SQL
libera mai scritta) o `dataset` non e' selezionato (query guidata), il
token `[sql]` scritto a mano nel layout di `smallbox.blade.php` restava
non sostituito e finiva visibile cosi' com'e' nell'HTML finale.

**Bug 2 - query non salvata (causa reale, piu' grave)**: dal refactoring
132 (`132-widget-kpi-sezioni-apribili-e-popup-sorgente.md`), i campi
veri della sezione "Sorgente dati" (`config[sql]`, `config[mode]`,
`config[dataset]`, `config[metric]`, ...) vivono in
`#ch-source-fields-{id}`, dentro il `<form>` del pannello di
configurazione. Uno script li sposta via JS (`$fields.show()`) dentro il
popup `#ch-source-modal-body-{id}` per mostrarli nella modale dedicata -
ma quel popup (`.ch-source-modal-overlay`) era dichiarato **dopo**
`</form>`, cioe' come fratello del form e non suo discendente. Lo
spostamento via JS quindi toglieva quei campi dal sottoalbero DOM del
`<form>`: al Salva generale (`$form.serialize()` in
`builder_grid.blade.php`) quei campi non venivano piu' raccolti - name,
descrizione, colore, icona e link (rimasti dentro il form) si salvavano
regolarmente, ma sql/mode/dataset/metric no, silenziosamente (nessun
errore visibile, il salvataggio "riusciva" con quei campi assenti dal
payload).

## Situazione dopo

- `smallbox.blade.php`: il div `.ch-source-modal-overlay` e' ora
  l'ultimo figlio del `<form>` (chiuso subito prima di `</form>`), cosi'
  i campi spostati al suo interno restano discendenti del form
  indipendentemente da dove la modale li mostra visivamente.
- Il valore del widget calcola `$hasQuery` prima di scrivere `[sql]` nel
  layout (SQL libera non vuota, oppure dataset selezionato in modalita'
  builder): se non c'e' query configurata, il layout non contiene proprio
  il token `[sql]` (niente da sostituire lato controller) e mostra invece
  un messaggio tradotto ("Nessuna query impostata" / "No query set"),
  stesso stile del messaggio di errore SQL gia' esistente.
- Nuova chiave di traduzione `statistic_builder_smallbox_no_query` in
  EN/IT.

## Motivazione

Bug 2 e' un difetto di comportamento reale (dati che l'utente crede di
aver salvato vengono silenziosamente scartati) causato da un dettaglio
di markup introdotto in 132 quando il popup fu aggiunto; la correzione e'
strutturale (nesting del DOM) e non tocca la logica JS di spostamento dei
campi, gia' corretta nel presupposto (sbagliato) che il popup fosse
dentro il form. Bug 1 e' un miglioramento UX minore, scoperto insieme al
bug 2 durante la stessa indagine.

## Test

Verificato in browser (dashboard "test2", `/admin/statistic_builder/builder/7`):
aggiunto un widget KPI, impostato nome/icona, SQL libera `select 42 as
value` -> Salva -> reload pagina -> il widget mostra `42` (persistito).
Riaperto lo stesso widget, passato a Query guidata con dataset "Utenti
amministratori" -> Salva -> reload -> il widget mostra `5` (conteggio
righe reale) e il pannello conferma "Modalità attuale: Query guidata"
(persistito anche il cambio di modalità/dataset). Verificato anche il
widget preesistente "Numero fatture" (mai configurato con query): mostra
"Nessuna query impostata" invece del vecchio `[sql]` grezzo. Widget di
prova poi eliminato.

## Rischi e note

Nessun altro widget (`table`/`chartline_v2`/`chartbar_v2`/`chartarea_v2`)
usa questo pattern di popup dedicato per la sorgente dati (solo
`smallbox.blade.php` lo introduce in 132/133) - fix scoperto e applicato
solo li'.

## Rollback

`git diff` di `smallbox.blade.php` e dei due file di lingua per tornare
alla situazione precedente.
