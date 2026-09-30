# 141 - Widget Indicatore KPI: colore di default bianco

- **Data**: 2026-09-29
- **Stato**: Completato (non verificato in browser, su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Richiesta diretta dell'utente: il colore di default del campo "Colore
(opzionale)" (introdotto in 139) era `#00c0ef` (azzurro) - va bianco.

## Situazione prima

`value='{{ @$config->color ?: "#00c0ef" }}'` sul campo `<input
type='color'>` - un widget nuovo, mai toccato il selettore colore,
partiva con l'accent azzurro.

## Situazione dopo

Default cambiato a `#ffffff`, aiuto testuale aggiornato di conseguenza
("Se non lo cambi resta il colore di default (bianco).").

## Motivazione

Richiesta esplicita dell'utente.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato solo `php -l`.

## Rischi e note

Segnalato all'utente in chat (non bloccante, solo un avviso): con
l'accent bianco, se viene comunque scelta un'icona (facoltativa da
139), l'icona rischia di risultare invisibile - `.kpi-indicator-icon`
usa `--kpi-accent` sia per lo sfondo della pastiglia
(`color-mix(... 14%, white)`, che con `--kpi-accent` bianco resta
bianco) sia per il colore dell'icona stessa (`color:
var(--kpi-accent...)`, anch'esso bianco) - icona bianca su sfondo
bianco. Nessun fix applicato: e' il default richiesto esplicitamente,
non un bug: chi vuole un'icona visibile sceglie comunque un colore.

## Rollback

`git diff` di `smallbox.blade.php` per tornare al default azzurro
(`#00c0ef`).
