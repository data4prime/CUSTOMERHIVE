# 270 - Import dati: "aggiorna se esiste, altrimenti inserisci"

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Lista / Import
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`postDoneImport`, `postDoImportChunk`)
  - `resources/views/crudbooster/import.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

L'import dati inseriva sempre nuovi record: reimportare lo stesso file
duplicava tutto.

## Situazione prima

`postDoImportChunk` faceva solo `insert` per ogni riga.

## Situazione dopo

Nella pagina di corrispondenza c'e' la scelta "Solo nuovi record" (default,
comportamento invariato) oppure "Aggiorna se esiste, altrimenti inserisci".
Nella seconda compare la colonna "Chiave": si spuntano una o piu' colonne
abbinate (solo quelle abbinate sono selezionabili, almeno una obbligatoria).
Per ogni riga si cerca il record con gli stessi valori in tutte le colonne
chiave (esclusi i soft-deleted, nel perimetro tenant/gruppo di
`DatasetAccessScope::applyRowScope`): se c'e' si aggiornano le altre colonne
abbinate (+ `updated_at`), altrimenti si inserisce. Riga con valore chiave
vuoto: saltata, con messaggio d'errore nel progresso. Modalita' e chiavi
passano in sessione come gia' `select_column`.

## Motivazione

Reimportare un file aggiornato senza creare duplicati.

## Test

`php -l`. Non provato con un file reale.

## Rischi e note

Se piu' record hanno gli stessi valori chiave, si aggiornano tutti. Le chiavi
FK testuali si risolvono nell'id prima del confronto (come per l'inserimento,
creando il record collegato se manca). Nessun conteggio separato
aggiornati/inseriti: il progresso li somma.

## Rollback

Ripristinare i file (nessuna migrazione).
