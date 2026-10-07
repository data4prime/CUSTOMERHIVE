# 247 - Module Helpers: campo Url più largo

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Module Helpers
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminModuleHelperController.php`

## Contesto

In `admin/module_helpers/edit/{id}` il campo "Url" (link al manuale, es.
`https://help.thecustomerhive.com/books/manuale-amministratore/page/statistic-builder`)
era largo `col-sm-5` e l'indirizzo risultava troncato, impossibile da leggere per intero.

## Situazione prima

`$this->form`: campo `url` con `'width' => 'col-sm-5'`.

## Situazione dopo

Stesso campo con `'width' => 'col-sm-10'`. Nessun altro cambiamento (validazione,
nome campo, elenco, dettaglio invariati).

## Motivazione

Leggibilità dell'URL in modifica/aggiunta, senza toccare dati né logica.

## Test

A vista su `module_helpers/edit/16`: l'URL del manuale si legge per intero. Non verificato
con URL molto più lunghi (oltre la larghezza del campo scorrono come in qualunque input).

## Rischi e note

Solo larghezza del campo. Il campo Module resta a `col-sm-5`.

## Rollback

Riportare `'width'` a `col-sm-5`.
