# 268 - Export/Import di un modulo allineati al wizard

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Module generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php` (`getExport`, nuovo `exportTableColumns`, `createImportedTable`, `writeImportedColumns`)

## Contesto

Export/Import JSON di un modulo (`getExport`/`postImport`) erano fermi a prima
dei passi Lista e Campi del wizard v2. Nessun cliente li usa in produzione,
quindi il formato del JSON si puo' estendere liberamente.

## Situazione prima

- L'import scartava le chiavi di colonna lista scritte oggi dal wizard
  (`col_width`, `str_limit`, `format`, `badge`, `currency`, `decimals`, `calc`,
  `nl2br`, `color`, `style`, `join_where`, `join_id`, `callback`).
- La struttura tabella usava `getTableStructure()`, che riduce tutto a
  text/number/boolean: decimal, date, datetime, time, text e longtext
  diventavano VARCHAR(255) alla ricreazione.

## Situazione dopo

- Whitelist delle colonne lista allineata alle chiavi di `ModuleGeneratorList`.
- Export della struttura con i tipi del generatore (`plaintext`, `longtext`,
  `decimal` con "precisione,scala", `date`, `datetime`, `time`); l'import li
  ricrea. I JSON vecchi (solo text/number/boolean) restano importabili.
- Config e form/layout erano gia' a posto (nessuna chiave nuova nel blocco
  CONFIGURATION oltre a quelle in whitelist).

## Motivazione

Un modulo esportato e reimportato deve risultare equivalente all'originale.

## Test

`php -l`. Non provato un giro export/import reale.

## Rischi e note

`getTableStructure()` non e' stato toccato (lo usa il wizard). L'import non
crea ancora menu/privilegi (scelta storica, invariata).

## Rollback

Ripristinare `ModulsController.php`.
