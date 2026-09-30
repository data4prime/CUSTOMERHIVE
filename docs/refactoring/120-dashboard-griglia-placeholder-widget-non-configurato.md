# 120 - Dashboard a griglia libera: placeholder "widget non configurato" + selezione automatica

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_empty_widget_state.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/table.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/chartbar_v2.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`

## Contesto

Richiesto dall'utente: un widget appena aggiunto (non ancora
configurato) mostrava i segnaposto grezzi `[sql]`/`[name]`/ecc. non
sostituiti - poco intuitivo. Proposta discussa e confermata prima di
implementare (vedi conversazione): un placeholder "non configurato" +
selezione automatica del widget appena creato.

## Situazione prima

Un widget con `config` vuoto (appena creato, mai salvato) mostrava
letteralmente il testo tra parentesi quadre del suo template `layout`
(`[sql]`, `[name]`), mai sostituito perché il loop di sostituzione in
`renderComponentPayload()` itera solo le chiavi presenti in `config`
(vuoto per un widget nuovo).

## Situazione dopo

- Nuovo partial condiviso `_empty_widget_state.blade.php` (icona breve +
  titolo + sottotitolo, compatto apposta per stare senza scroll anche
  in un widget KPI alto solo 2 righe di griglia).
- Ogni widget raggiungibile dalla nuova griglia (`smallbox`, `table`,
  `chartline_v2`, `chartbar_v2`) lo mostra al posto del proprio markup
  normale quando `empty($config->name)` (segnale già esistente: `name`
  è sempre valorizzato una volta che l'utente salva dal form).
- `builder_grid.blade.php`: dopo l'aggiunta di un widget dalla libreria,
  viene selezionato automaticamente (sidebar di configurazione già
  aperta), invece di richiedere un secondo click.

## Motivazione

Messaggio ("Widget non configurato" + azione suggerita) invece di
segnaposto grezzi che non comunicano nulla a chi non conosce
l'implementazione interna; selezione automatica riduce un passaggio
manuale ripetitivo (aggiungi → poi seleziona → poi configura, diventa
aggiungi → configura).

## Test

Verificato in browser: aggiunto un widget KPI dalla libreria → mostra
subito "Widget non configurato" / "Seleziona per impostare nome, dati e
icona" leggibile senza scroll, ed è già selezionato con la sidebar di
configurazione aperta (form completo, non il pannello vuoto di
default). Widget di prova poi rimosso.

## Rischi e note

Il placeholder compatto (font 10-11px) è pensato per il caso più
stretto (KPI, 2 righe): in widget più alti (grafici/tabelle, 5 righe)
resta comunque leggibile, solo con più spazio vuoto intorno.

## Rollback

`git diff` di questi file per tornare ai segnaposto grezzi e alla
selezione manuale.
