# 272 - Import dati: tenant e group per id o per nome

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Lista / Import
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`postDoImportChunk`, nuovo `resolveImportSystemRef`)
  - `resources/lang/{en,it}/crudbooster.php` (`import_err_ref_not_found`, `import_err_ref_ambiguous`)

## Contesto

Nel file da importare le colonne `tenant` e `group` possono contenere l'id
oppure il nome del tenant/gruppo.

## Situazione prima

Il valore del file veniva scritto cosi' com'e': un nome finiva nella colonna
numerica (errore o dato sbagliato).

## Situazione dopo

Per `tenant` e `group` si cerca prima per id (se il valore e' un intero e il
record esiste in `tenants`/`groups`, non eliminato), poi per nome. Nessun
risultato o nome presente piu' volte: l'import si ferma con riga e colonna
(vedi 271); i record non si creano. Cella vuota: colonna non valorizzata.

## Motivazione

Dati esportati/compilati a mano usano spesso il nome.

## Test

`php -l`. Non provato con un file reale.

## Rischi e note

Un nome numerico uguale all'id di un altro record viene interpretato come id
(l'id ha la precedenza). Nessuna verifica che il tenant/gruppo sia consentito
all'utente che importa (come per l'inserimento prima di questo intervento).

## Rollback

Ripristinare i due file.
