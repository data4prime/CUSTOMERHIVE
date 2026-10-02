# 215 - Consolidamento dei sette `*_datamodal` delle relazioni

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Frontend / manutenibilità
- **File/aree di codice coinvolte**:
  - nuovi: `resources/views/crudbooster/default/datamodal_relation/{component,browser}.blade.php`
  - ridotti a include: `type_components/{group_members,group_items,group_tenant,item_access,item_tenant,tenant_group,user_groups}_datamodal/{component,browser}.blade.php`
  - `app/Http/Controllers/System/CBController.php` (`getModalData`)

## Contesto

Dall'analisi dei `type_components`: i sette tipi `*_datamodal` sono usati solo
dai sottoform "aggiungi relazione" dei controller di sistema
(`AdminGroupsController`, `AdminCmsUsersController`, `AdminQlikItemsController`,
`AdminTenantsController`, `AdminChatAIController`) e sono copie l'uno
dell'altro. Segue 214 (`select_to` intero) sugli stessi file.

## Situazione prima

Per ogni tipo, due file da 83–104 righe quasi identici:

- `component.blade.php`: campo + modale + JS, copiato sette volte con
  differenze solo di stile (`@php` vs `<?php`), `console.log` residui (anche
  `console.log('inside hidemodal')` fuori da ogni funzione), `&type=` ripetuto
  due volte nell'URL in cinque copie, e il solo campo extra che il popup
  rimanda al form padre (description / email / subtitle).
- `browser.blade.php`: intestazione, ricerca, tabella e JSON di ritorno
  identici; cambiava solo la query che esclude le righe già collegate.
- `CBController::getModalData`: uno `switch` con sette `case` identici.

`component_detail.blade.php` e `asset.blade.php` erano già identici a quelli del
tipo `datamodal` (asset vuoti) e non sono stati toccati.

## Situazione dopo

- `default/datamodal_relation/component.blade.php`: corpo comune del campo, con
  il JS che gestisce `datamodal_description`, `datamodal_email` e
  `datamodal_subtitle` (il popup ne manda uno solo, a seconda del tipo).
- `default/datamodal_relation/browser.blade.php`: intestazione, ricerca e tabella;
  il file del tipo imposta `$result` e, se serve, `$datamodal_extra`
  (`['datamodal_email' => 'email']` per `group_members`,
  `['datamodal_subtitle' => 'subtitle']` per `group_items`; default
  `description`).
- Ogni `<tipo>_datamodal/component.blade.php` è un solo `@include`; ogni
  `<tipo>_datamodal/browser.blade.php` tiene soltanto la propria query (resta
  il filtro tenant di `group_members`/`user_groups`, il filtro per
  non-superadmin di `group_items`, ecc.) e include il comune.
- `getModalData`: lo `switch` diventa una whitelist di sette nomi; stessa vista di
  prima per quei tipi, `datamodal` generico per tutto il resto.
- I file comuni stanno **fuori** da `type_components/` perché
  `ModulsController::componentTypeNames()`/`getStep4()` elencano ogni cartella
  lì dentro come tipo di campo selezionabile nel module generator.

Nomi dei tipi, cartelle, `info.json` e riferimenti nei controller non cambiano.

## Motivazione

Circa 1000 righe in meno da tenere allineate; un solo punto dove correggere il
campo, la modale o la tabella. Scartato: un'unica cartella con config per tipo
anche per la query — le query hanno logica propria (tenant, superadmin) e
accorparle avrebbe ridotto la leggibilità senza benefici.

## Test

- Lint di `CBController`; render nel container Docker dei sette
  `component` e dei sette `browser` con dati reali: nessun errore (un solo
  falso errore del banco di prova su `user_groups` dovuto alla sostituzione della
  request, non riproducibile nel primo giro). Verificato il campo con valore
  risolto (`Super Admin`), l'URL del popup con `type=` unico e lo script
  accodato allo stack `bottom`.
- Non verificato nel browser (selezione di una riga nel popup e
  scrittura nei campi `description`/`email`/`subtitle`) e senza suite di test.

## Rischi e note

- **Cambiamenti minori di comportamento**: `console.log` rimossi; `&type=` non
  più duplicato; tutte le copie ora hanno il guard `key != ''` che alcune non
  avevano; il campo `required` sull'input nascosto di `group_members` (privo di
  effetto sugli input hidden) non c'è più.
- Un cliente che ha copiato uno di questi file in
  `resources/views/vendor/crudbooster/type_components/` non è toccato dal
  consolidamento (l'override ha la precedenza), ma non ne beneficia.
- Se in futuro un nuovo tipo di relazione ha un altro campo extra, basta una
  voce in `$datamodal_extra` e un `if` nel JS comune.

## Rollback

Ripristinare da git i quattordici file dei tipi e `getModalData`, ed eliminare
la cartella `default/datamodal_relation/`.
