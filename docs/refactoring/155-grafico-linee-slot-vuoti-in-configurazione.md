# 155 - Grafico a linee: linee vuote che riappaiono nella configurazione

- **Data**: 2026-09-29
- **Stato**: Completato
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartline_v2.blade.php`

## Contesto

Segnalato dall'utente sul widget 18: nel pannello di configurazione del
Grafico a linee comparivano linee extra vuote. Seguito di 152 (piu' linee).

## Situazione prima

Il form pre-renderizza 4 blocchi "Altre linee", nascosti con `display:none`
tranne i primi `count($existingLines)`. I campi dei blocchi nascosti vengono
comunque inviati: `postSaveComponent()` salva `config[lines]` con tutti e 4
gli slot, anche vuoti (verificato sul componente 29: 1 linea compilata + 3
vuote con `mode: sql`). Alla riapertura `count($existingLines)` era 4, quindi
tutti i blocchi risultavano visibili. Il rendering del grafico ignorava gia'
gli slot vuoti (`renderComponentPayload()`), quindi il grafico era corretto.

## Situazione dopo

`$existingLines` contiene solo le linee compilate (nome, dataset o sql non
vuoti), reindicizzate: i blocchi visibili sono tanti quante le linee reali.
Comportamento visibile: nessuno slot vuoto all'apertura; "+ Aggiungi linea"
li mostra uno alla volta come prima.

## Motivazione

Correzione minima solo lato vista, senza toccare il salvataggio condiviso da
tutti i widget. Scartato: filtrare gli slot vuoti in `postSaveComponent()`
(tocca un punto comune a ogni componente).

## Test

`php -l` non applicabile a Blade; da verificare riaprendo la configurazione
del widget 18. Non testato in browser.

## Rischi e note

- Il JSON salvato continua a contenere gli slot vuoti (il form li reinvia
  a ogni salvataggio): sono innocui, non hanno effetto sul grafico.
- Se si svuota una linea in mezzo, le successive scalano di una posizione
  al salvataggio successivo.

## Rollback

Ripristinare la riga `$existingLines = (array) ($config->lines ?? []);`.
