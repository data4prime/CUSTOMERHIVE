# 199 - Wizard v2, passo Campi: tipi di colonna estesi

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Backend/Frontend (dietro flag `wizard_v2`)
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleGeneratorFields.php` (`SQL_BY_TYPE`, `TABLE_CHOICE`, `dbTypeFor()`, `defaultSize()`, `build()`)
  - `app/Http/Controllers/System/ModulsController.php` (`save_table()` ramo di creazione, `addTableColumns()`)
  - `resources/views/crudbooster/module_generator/step2_v2.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (rimossa `mg_fld_db_long`)

## Contesto

Il passo Campi (196) creava solo colonne VARCHAR o INT: una "Data" finiva in
un VARCHAR(255), un "Importo" in un INT, i testi lunghi in VARCHAR(1000).
Decisione presa il 2026-10-02: estendere i tipi di colonna.

## Situazione prima

`dbTypeFor()` restituiva solo `text` o `number`; `save_table()` e
`addTableColumns()` conoscevano solo `string`, `integer`, `boolean`.

## Situazione dopo

Per i campi **nuovi** la colonna dipende dal tipo di campo:

| Tipo di campo | Colonna |
|---|---|
| `textarea`, `multitext` | TEXT |
| `ckeditor`, `tinymce`, `wysiwyg`, `json` | LONGTEXT |
| `number` | INT(11) |
| `money` | DECIMAL(12,2) |
| `percent` | DECIMAL(5,2) |
| `date` / `datetime` / `time` | DATE / DATETIME / TIME |
| `datamodal`, `select`/`select2` con sorgente "altra tabella" | INT(11) |
| `color` | VARCHAR(20) |
| tutti gli altri (text, email, password, hidden, select/radio/checkbox con elenco fisso o query, upload, ...) | VARCHAR(255) |

La dimensione scelta in "Avanzate" vale solo per VARCHAR e INT; per gli
altri la modale mostra il tipo di colonna creato. `save_table()` ha solo
casi aggiuntivi (`plaintext`, `longtext`, `decimal`, `date`, `datetime`,
`time`): il vecchio wizard invia solo `text`/`number`/`boolean` e non cambia.
Il confronto "la colonna mantiene il tipo attuale" per colonne esistenti
usa ancora la sola famiglia testo/numero (`dbFamily()` in JS).

## Motivazione

Ordinamenti, filtri e formati per data/importo affidabili nei moduli nuovi,
senza testi lunghi troncati a 1000 caratteri. Colonne esistenti e moduli
esistenti non cambiano: il passo non converte né modifica colonne.

## Test

In Docker: mappatura di tutti i tipi (`dbTypeFor`/`defaultSize`/`build`);
creazione reale su tabelle temporanee (poi eliminate) sia di una tabella
nuova via `save_table()` sia di colonne su tabella esistente via
`addTableColumns()`: `SHOW COLUMNS` conferma i tipi della tabella sopra in
entrambi i casi. Vista del passo 2 verificata in Chrome headless (suggerimenti
`DECIMAL(12,2)` e `DATE`, nessuno per il testo breve). Non verificato: salvataggio
completo dal browser, suite di test (non lanciata).

## Rischi e note

- `datamodal` e select da tabella come INT presuppongono id numerici: con una
  tabella collegata a chiave testuale i valori non entrerebbero.
- `percent` DECIMAL(5,2) arriva a 999,99.
- Se si cambia tipo di campo dopo il primo salvataggio, la colonna già creata
  non viene modificata (limite voluto del passo).

## Rollback

Ripristinare i quattro file da git. Le colonne già create con i nuovi tipi
restano valide anche con il codice precedente.
