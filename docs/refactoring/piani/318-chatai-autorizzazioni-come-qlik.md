# Piano 318 — Autorizzazioni ChatAI (Agent AI) come per i Qlik items

Stato: **scritto, in attesa di approvazione del testo**. Branch: `refactor/models`
(commit separati dal piano 317). Autore: `/architect`, 2026-10-29. Destinatario:
`ch-senior-dev` (non vede la conversazione: questo file è autosufficiente).

## Obiettivo

Far funzionare per i non superadmin l'accesso alle conf ChatAI con la stessa
logica dei Qlik items (gruppi + tenant, solo superadmin gestisce le
autorizzazioni), senza toccare né condividere dati con Qlik.

## Perché serve (fatti verificati 2026-10-29)

- `ChatAIConf::allowedGroups()/allowedTenants()` (`app/ChatAIConf.php:23,36`)
  filtrano su `chataiconf_id`, colonna assente da ogni migrazione e da entrambi
  i DB locali (`customerhive`, `customerhive_testing`). Per un non superadmin
  `ChatAIHelper::can_see_item` (`app/Helpers/ChatAIHelper.php:98,123`) dà una
  `QueryException`; `AdminChatAIController::content_view` (`:742`) non la
  cattura; la sidebar (`CRUDBooster.php:719`) la cattura ma lascia la voce con
  `url "#"` e `is_broken`.
- Tutto il resto di ChatAI scrive/legge `item_id` = id della conf nelle tabelle
  **di Qlik** `items_allowed` / `tenants_allowed`: controller
  (`AdminChatAIController.php:413-445, 524-573`), selettore menu
  (`MenusController.php:530-531`), popup `item_access_datamodal` /
  `item_tenant_datamodal`. Conseguenza: conf ChatAI N e item Qlik N
  condividono le autorizzazioni.
- Il controller ChatAI è una copia più vecchia di quello Qlik, senza i suoi
  hardening: usa `$_POST` grezzo, nessun controllo di tipo/esistenza,
  `redirect($return_url)` da input senza filtro, nessuna cascata alla
  cancellazione (`hook_before_delete` assente).
- Nessun cliente usa ChatAI; nel DB locale `chatai_confs` è vuota e le righe di
  `items_allowed`/`tenants_allowed` appartengono a item Qlik (id 11-149).

## Decisione

**Tabelle dedicate** `chatai_groups_allowed` e `chatai_tenants_allowed`, create
da una migrazione nuova. Scartato `chataiconf_id` sulle tabelle esistenti:
obbligherebbe a rendere nullable `item_id` su tabelle Qlik già in produzione.
Le righe che ChatAI ha scritto finora in `items_allowed`/`tenants_allowed` non
si migrano né si cancellano (nessun cliente le ha; non si può distinguerle da
quelle Qlik): restano dove sono. Registrare come ADR A12.

## Perimetro

Dentro: migrazione, modello `ChatAIConf`, `AdminChatAIController` (metodi
`access`, `tenant`, `add_tenant`, `remove_tenant`, `add_authorization`,
`remove_authorization`, `content_view`, hook di cancellazione),
`ChatAIHelper::can_see_item`, selettore ChatAI in `MenusController.php:~523-535`,
popup `item_access_datamodal` / `item_tenant_datamodal` (solo la sorgente dati,
con default invariato per Qlik), viste `chat_ai.access` / `chat_ai.tenant` se
passano la chiave.

Fuori: tutto Qlik (tabelle, query, controller, helper) — invariato; migrazione
di dati; funzionalità del chatbot (`send_message*`); `CBController` oltre alla
whitelist del popup se serve.

## Passi

1. **Test prima** (nuova funzionalità: test del comportamento *obiettivo*,
   scritti a mano da `ch-senior-dev`, non lanciati da lui): vedi sotto.
2. **Migrazione** nuova in `database/migrations/` (create-only, `down()` =
   `dropIfExists`): `chatai_groups_allowed` (`id`, `chatai_conf_id` unsigned,
   `group_id` unsigned, colonne di audit come `items_allowed`: `created_by`,
   `created_at`, `modified_by`, `modified_at`, `deleted_by`, `deleted_at`,
   unique `(chatai_conf_id, group_id)`) e `chatai_tenants_allowed` (stesso
   schema con `tenant_id`). Nessuna FK, coerente con le tabelle esistenti
   (verificare e allinearsi allo stile delle migrazioni 2020 di `items_allowed`).
3. **Modello**: `ChatAIConf::allowedGroups()/allowedTenants()` leggono le nuove
   tabelle e restano `array` di id nello stesso ordine naturale; aggiungere le
   relazioni `belongsToMany` (`allowedGroupRecords()`, `allowedTenantRecords()`)
   come prima applicazione del pattern del batch 2 del piano 317. Se serve un
   modello pivot (per `->save()` come in `ItemsAllowed`) crearlo in `app/` con
   nome coerente (`ChatAIGroupAllowed`, `ChatAITenantAllowed`).
4. **Controller**: i sei metodi di gestione passano alle nuove tabelle e
   ricevono gli stessi controlli di `AdminQlikItemsController`
   (`:756-864`): `MyHelper::is_int`, esistenza di conf/gruppo/tenant,
   `request()->input()` al posto di `$_POST`, redirect filtrato.
   `safeRedirectTarget()` oggi è un metodo privato del controller Qlik
   (`:520`): estrarlo in un trait `Controllers/System/Concerns/` (come
   `HandlesQlikSyncStart`) usato da entrambi **senza cambiarne il
   comportamento** (il controller Qlik può solo sostituire il metodo privato
   col trait). Aggiungere `hook_before_delete($id)` che cancella le righe
   delle nuove tabelle per quella conf. Aggiornare nomi/commenti ereditati da
   Qlik (`$qlik_item_id` in `content_view`).
5. **`ChatAIHelper::can_see_item`**: spostare il controllo `is_int` prima del
   `find` (come `QlikHelper`); resto invariato. Nessun concetto di "pubblico".
6. **Selettore menu** (`MenusController.php:530-531`): la join passa a
   `chatai_tenants_allowed` (`chatai_conf_id = chatai_confs.id`).
7. **Popup**: oggi `item_access_datamodal/browser.blade.php` e
   `item_tenant_datamodal/browser.blade.php` leggono `items_allowed` /
   `tenants_allowed` fissi, con corpo comune in
   `datamodal_relation/component.blade.php`. Scelta preferita: una chiave
   opzionale del form (es. `datamodal_allowed_scope => 'chatai'`) che il
   componente comune aggiunge all'URL del popup; i due `browser.blade.php`
   la mappano su una **whitelist fissa** (`qlik` default → tabelle attuali,
   `chatai` → nuove tabelle), mai il nome tabella dalla query string.
   `ChatAI` la passa nei due `$data['forms'][]` (`AdminChatAIController.php:428,463`).
   Se leggendo `datamodal_relation/component.blade.php` risulta più semplice e
   meno rischioso per Qlik, alternativa accettabile: due nuovi tipi
   (`chatai_access_datamodal`, `chatai_tenant_datamodal`) — in tal caso
   aggiornare whitelist `CBController.php:~1042`, `ModuleGeneratorFields`
   (NO_COLUMN/MODAL) e le etichette en+it in `resources/lang/*/crudbooster.php`.
   Scegliere e dichiarare nel report. **Qualunque testo nuovo va in en e it.**

## Test (nuovo comportamento, per rischio decrescente)

Riusare `tests/Concerns/SeedsCmsData.php` e lo stile di
`QlikItemVisibilityTest.php` (stessa matrice). Leggere le migrazioni
`*_chatai_confs_table.php` per le colonne obbligatorie di `chatai_confs`.

1. `ChatAIHelper::can_see_item`: superadmin sempre; id non intero/inesistente →
   false; tenantadmin con tenant abilitato → true; tenant non abilitato →
   false; utente standard con tenant e gruppo abilitati → true; gruppo non
   abilitato → false; tenant non abilitato con gruppo ok → false.
2. **Isolamento da Qlik**: item Qlik N abilitato al tenant/gruppo dell'utente
   e conf ChatAI con lo stesso id N non abilitata → `QlikHelper::can_see_item`
   true e `ChatAIHelper::can_see_item` false (e viceversa).
3. Controller (HTTP, come superadmin): `add_authorization`/`add_tenant` creano
   la riga nelle nuove tabelle una sola volta (doppio POST → una riga);
   id non intero o gruppo/tenant/conf inesistente → nessuna riga; `return_url`
   verso host esterno → redirect al fallback; `remove_*` cancella; da non
   superadmin → negato.
4. Cancellazione di una conf → righe nelle nuove tabelle rimosse, righe Qlik
   con lo stesso `item_id` **intatte**.
5. Selettore menu "Agent AI" (`MenusController`): mostra solo le conf il cui
   tenant è abilitato per il tenant dell'utente.
6. Regressione Qlik: `QlikItemVisibilityTest` e le pagine
   `admin/qlik_items/access|tenant` invariate (si rilanciano i test del
   batch 0, non si modificano).

## Impatto sui clienti

- ChatAI comincia a funzionare per i non superadmin (nessun cliente lo usa).
- Qlik invariato: stesse tabelle e query. Un solo tocco condiviso: il trait
  `safeRedirectTarget` e, per la scelta del popup, un parametro opzionale con
  default invariato.
- La migrazione crea due tabelle vuote sui DB dei clienti al prossimo
  aggiornamento. Nessun dato spostato.
- Se un cliente avesse già conf ChatAI con autorizzazioni (non risulta), dopo
  l'aggiornamento le autorizzazioni vecchie non varrebbero più per ChatAI e
  vanno reimpostate dal superadmin: da dichiarare in "Rischi e note".

## Rischio

Medio-basso: tabelle nuove, Qlik non toccato; i punti sensibili sono il popup
condiviso e la whitelist della tabella. Da chiarire con la persona se
conviene far girare la migrazione sul DB locale e su uno staging prima del
rilascio.

## Rollback

`git revert` dei commit del batch + `php artisan migrate:rollback` della sola
migrazione nuova (droppa solo le due tabelle nuove).

## Chiusura

`ch-reviewer` (qui i18n può non essere "n/a": controllare i popup e le
etichette) → correzioni → `ch-doc-writer` → post-mortem in
`docs/architettura/lezioni.md`; aggiornare `mappa.md` (le nuove tabelle) e
`pattern-nel-repo.md` (trait `Concerns` per i controller di sistema).
