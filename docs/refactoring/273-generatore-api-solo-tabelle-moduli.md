# 273 - Generatore API: nel campo Tabella solo le tabelle dei moduli

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiCustomController.php` (`getGenerator`, `getEditApi`, nuovo `apiTablesList`)

## Contesto

Su `/admin/api_generator/generator` il campo "Tabella" mostrava tutte le
tabelle del database, comprese quelle di sistema (`cms_*`, `migrations`,
`jobs`, ...). Non ha senso esporle come sorgente di una API, e rende la
lista lunga da scorrere.

## Situazione prima

`getGenerator` e `getEditApi` chiamavano `CRUDBooster::listTables()` (modo
`standard`, nessun filtro) e passavano l'elenco intero alla vista.

## Situazione dopo

Le due azioni usano un helper privato `apiTablesList()` che chiama
`CRUDBooster::listTables('mg')`: solo le tabelle col prefisso
`app.module_generator_prefix` (quelle create dal module generator). In
modifica, se l'API esistente punta a una tabella fuori da questo elenco,
quella tabella viene comunque aggiunta, così l'API non perde il valore
selezionato al salvataggio.

## Motivazione

Stesso criterio già usato altrove per i moduli generati (modo `mg`).
Alternativa scartata: blacklist di `cms_*` e tabelle Laravel, più fragile
(ogni nuova tabella di sistema andrebbe aggiunta a mano).

## Test

`php -l` sul controller (OK, nel container). Non verificato a vista in
browser.

## Rischi e note

Cambia il comportamento visibile: in creazione non si può più scegliere una
tabella che non abbia il prefisso dei moduli (es. tabelle custom di un
cliente create a mano). Se serve, si può allargare il filtro.

## Rollback

Ripristinare in `ApiCustomController.php` le due chiamate a
`CRUDBooster::listTables()` senza argomento.
