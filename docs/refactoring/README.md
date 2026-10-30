# Tracciamento del refactoring

Questa cartella traccia, passo per passo, il percorso di modernizzazione del
progetto: aggiornamento di Laravel, miglioramento dell'architettura,
refactoring per stabilità/manutenibilità, semplificazione di
installazione/setup, rinnovo della UI/UX.

Obiettivo: chiunque (oggi o tra un anno) deve poter capire **cosa è cambiato,
perché, e come funzionava prima**, senza dover ricostruire il contesto da
zero leggendo i diff di git.

**Questi file servono solo a documentare**: non vanno letti per eseguire una
richiesta normale (bug fix, feature, domanda su come funziona qualcosa) — per
quello usa il codice/tokensave. Leggili solo quando il compito è
esplicitamente documentare un intervento o consultarne la storia passata.

## Struttura

- **`glossario.md`** — termini/concetti ricorrenti nel progetto (CRUDBooster,
  tenant, licenza, guard, ecc.), definiti una volta sola e richiamati dagli
  altri documenti.
- **`_template.md`** — modello da copiare per ogni intervento di refactoring.
- **`NNN-titolo-breve.md`** — un file per ogni intervento, con la situazione
  prima/dopo. Numerati in ordine cronologico (`001-`, `002-`, ...).
- **`archivio-NNN-MMM.md`** — voci più vecchie tolte dall'indice qui sotto per
  tenerlo leggero (vedi [`archivio-001-068.md`](archivio-001-068.md) e
  [`archivio-069-289.md`](archivio-069-289.md)).
- **`backlog-risolto.md`** — voci del backlog già chiuse, tolte da qui sotto.

## Come aggiungere un intervento

1. Copia `_template.md` in un nuovo file `NNN-titolo-breve.md` (`NNN` =
   prossimo numero libero: leggi la riga "Prossimo numero libero" qui sotto,
   non serve scorrere l'indice; `titolo-breve` in kebab-case).
2. Compila le sezioni **prima di iniziare a modificare il codice** (situazione
   "prima" e motivazione) e completa il resto a lavoro fatto.
3. Se introduci un termine nuovo o non ovvio, aggiungilo a `glossario.md`.
4. Aggiungi una riga nell'indice qui sotto e aggiorna "Prossimo numero
   libero". Se l'indice supera ~60 righe, sposta le voci più vecchie in un
   nuovo `archivio-NNN-MMM.md` (stesso formato di
   [`archivio-001-068.md`](archivio-001-068.md)) e aggiorna il link nella
   sezione "Struttura" sopra.
5. Se l'intervento richiede di riattivare/rimuovere qualcosa prima del prossimo
   push, aggiungilo anche a [`../pre-push-checklist.md`](../pre-push-checklist.md).

## Indice degli interventi

**Prossimo numero libero: 340** (nota: `piani/335-module-generator-import-export-service.md` è il piano del doc 334, `piani/334-profilo-utente-form-request.md` è il piano del doc 335, `piani/336-modelli-in-app-models-user-per-primo.md` è il piano del doc 336 e `piani/337-modelli-in-app-models-cluster-tenant-group-role.md` è il piano del doc 337; prima di numerare un piano guardare anche `docs/refactoring/piani/`)

Voci 001–068 (26 agosto – 3 settembre 2026, uscita di CRUDBooster da
`packages/` + prime correzioni UI/bug) archiviate in
[`archivio-001-068.md`](archivio-001-068.md).

Voci 069–289 (3 settembre – 8 ottobre 2026) archiviate in
[`archivio-069-289.md`](archivio-069-289.md).

| N.  | Titolo | Area | Stato | Data |
|-----|--------|------|-------|------|
| [339](339-ultimi-modelli-in-app-models.md) | **Ultimi 7 modelli di sistema (`Menu`, `ChatAIConf`, `QlikItem`, `QlikSyncRun`, `QlikSyncRunRecord`, `Log`, `AccessLog`) spostati in `App\Models` (batch B5 del piano 339): 7 alias (`AdminOrdiniController` di un cliente cita `\App\Menu`), nessuna migration, `use` ridondanti tolti da `Menu`/`ChatAIConf`/`QlikItem`, inline di `CBController`/helper/2 Blade aggiornati; 24 test nuovi (anche `QlikSyncRun` e `requestCancel`, prima senza test); `app/` senza più modelli di sistema; nessun cambio visibile** | Architettura / Modelli | Completato (351 test verdi dall'implementatore, Larastan pulito; suite completa da lanciare) | 2026-10-30 |
| [338](338-modelli-pivot-e-modules-in-app-models.md) | **8 pivot/autorizzazioni (`GroupTenants`, `MenuTenants`, `MenuGroups`, `ModuleTenants`, `TenantsAllowed`, `ItemsAllowed`, `ChatAIGroupAllowed`, `ChatAITenantAllowed`) e `Modules` spostati in `App\Models` (batch B4 del piano 338, tutti insieme per scelta della persona): 9 alias, nessuna migration, `use` espliciti in `Menu`/`QlikItem`/`ChatAIConf`, 4 import mai usati rimossi, 2 inline e 1 Blade aggiornati; 22 test nuovi; nessun cambio visibile** | Architettura / Modelli | Completato (156 test verdi dall'implementatore, Larastan pulito; regressione completa da lanciare) | 2026-10-30 |
| [337](337-modelli-cluster-tenant-group-role-usersgroup-in-app-models.md) | **`Tenant`, `Group`, `Role`, `UsersGroup` spostati in `App\Models` (batch B3 del piano 337, cluster insieme per scelta della persona): 4 alias per i controller dei clienti, nessuna migration (nessun `*able_type` oltre ai token), `use` espliciti in `ChatAIConf`/`Menu`/`QlikItem`, 4 viste Blade aggiornate; 8 test nuovi (alias, relazioni, `add_tenant`); fotografati due comportamenti esistenti (`Tenant::delete()` fallirebbe senza `updated_at`, `add_tenant` non idempotente); nessun cambio visibile** | Architettura / Modelli | Completato (8 test nuovi verdi, Larastan pulito; regressione da lanciare) | 2026-10-30 |
| [336](336-user-in-app-models-con-alias-e-migration.md) | **`User` spostato in `App\Models\User` (batch B1 del piano 336): alias `App\User` per i controller dei clienti, migration che porta `personal_access_tokens.tokenable_type` al nome nuovo (con `down()`), riferimenti del repo e `config/auth.php`/`services.php` aggiornati; 7 test nuovi; sostituisce A11 (ADR A17); incidente di processo: `sed` inline ha corrotto 35 file, ripristinato; nessun cambio visibile** | Architettura / Modelli | Completato (test eseguiti: 109 verdi) | 2026-10-30 |
| [335](335-profilo-utente-form-request.md) | **Profilo utente: 4 Form Request (`ProfileQlikRequest`, `ProfileGeneralRequest`, `ProfileSystemRequest`, `ProfilePreferencesRequest`) per i `postProfile*` di `AdminCmsUsersController` con trait `RespondsWithProfileJson` (stesso 403/422 JSON di prima); `EmailStart`/`PasswordStart` restano fuori (il 429 precede la validazione); 16 test di caratterizzazione nuovi, prima senza test su Qlik; nessun cambio visibile (piano 334)** | Architettura / Form Request + Profilo utente | Completato (test eseguiti: 47 verdi) | 2026-10-30 |
| [334](334-module-generator-import-export-service.md) | **Module Generator: import/export dei moduli estratti da `ModulsController` in `ModuleExporter`, `ModuleImporter` e `ModuleTransferException` (piano 335, il numero del piano non coincide con quello del doc); `ModuleGeneratorLayout::placeableFields()` additivo; cambio visibile D1: 10 messaggi ora `trans()` (inglese per l'interfaccia in inglese, italiano identico); import ancora non transazionale; 22 test nuovi** | Architettura / Service + Module Generator | Completato (test eseguiti: 106 verdi) | 2026-10-30 |
| [333](333-chatai-form-request-send-message.md) | **ChatAI (batch 2 del piano 332): Form Request `SendChatMessageRequest` e `SendAgentMessageRequest` per `send_message`/`send_message_agent` (iniezione dalla firma, `can_see_item` in `authorize()`); cambi visibili D2: `message` mancante/vuoto/non stringa e `agent_id` mancante (superadmin) -> JSON `chatai_invalid_request` con 422, `denied_access` ora `application/json` (stesso corpo e stato 200); corretto l'helper di `ChatAISendMessageAgentTest`; test nuovi non eseguiti** | Architettura / Form Request + Chat AI | Completato (test eseguiti: 58 verdi) | 2026-10-30 |
| [332](332-chatai-controller-pulizia-i18n-access-tenant-content-view.md) | **`AdminChatAIController` (batch 1 del piano 332): `use` inutilizzato, `normalizePrimary()`, `access()`/`tenant()` su un metodo privato comune, `content_view()` senza `$_GET` con `frameData()`; testi di access/tenant e tooltip tradotti (8 chiavi `chatai_*` in en e it, cambio visibile: pagine in italiano); test nuovi non eseguiti, batch 2 (Form Request) rimandato; fissato come probabilmente scorretto il comportamento dei gruppi/tenant soft-deleted in elenco** | Architettura / Chat AI | Completato (test eseguiti: 70 verdi) | 2026-10-30 |
| [331](331-chatai-client-service.md) | **ChatAI (batch B): `ChatAIClient` in `app/Services/ChatAI/` (payload, cURL, lettura risposta con `interpret()` testabile senza rete) e metodo privato `relayToAgent()` nel controller; due cambi visibili dichiarati: D1 (`send_message()` con risposta anomala dell'agente non dà più, ipoteticamente, 500) e D3 (testo di errore di rete tradotto, chiave `chatai_request_error`, nuovo in inglese); nessun timeout (D2, follow-up)** | Architettura / Service + Chat AI | Completato (test eseguiti: 54 verdi) | 2026-10-30 |
| [330](330-chatai-controller-action-primary-e-autorizzazioni.md) | **`AdminChatAIController` (batch A): Parte 1 refactoring behavior-preserving, 5 Action in `app/Actions/ChatAI/` (regola primary, allow/revoke tenant e gruppo); Parte 2 correzione con cambio visibile: la chat ignora le conf soft-deleted (`send_message` senza primary ora `missing_item` invece di 500, `send_message_agent` esclude le cancellate); parte 2 dedotta dal codice, non provata a runtime** | Architettura / Action + Chat AI | Completato (test eseguiti: 58 verdi) | 2026-10-30 |
| [329](329-chatai-send-message-agent-controllo-autorizzazione.md) | **ChatAI: `send_message_agent` controlla `can_see_item($agent_id)` (batch 0, correzione di sicurezza): prima un utente loggato poteva usare URL/token di una conf non autorizzata chiamando l'endpoint a mano; id inesistente (superadmin) ora `missing_item` invece di 500; cambio visibile solo fuori dall'interfaccia, nessun testo UI nuovo** | Sicurezza / Chat AI | Completato (test eseguiti: 36 verdi) | 2026-10-30 |
| [328](328-api-tokens-action-generate-api-token.md) | **Action `GenerateApiToken` (B5, secondo passo): mappatura `expiry_choice` → `expires_at` e `createToken()` spostate da `ApiTokensController::postAddSave()` in `app/Actions/ApiTokens/`; controller con validazione, log e vista; behavior-preserving, nessun testo UI nuovo** | Architettura / Action | Completato (test eseguiti: 29 verdi) | 2026-10-30 |
| [327](327-primo-form-request-store-api-token-request-b5.md) | **Primo Form Request del repo (B5): `StoreApiTokenRequest` per `ApiTokensController::postAddSave` (permesso + 4 regole), costruito dentro il metodo con `validateCurrent()` perché la firma di `CBController::postAddSave()` non lo permette; lezione Larastan su `app(Classe::class)`; 22 test di caratterizzazione; nessun cambio visibile per i form HTML (solo JSON non valido darebbe 422)** | Architettura / Form Request | Completato (22 test verdi prima e dopo, suite non lanciata) | 2026-10-30 |
| [326](326-open-redirect-add-groups-users-tenants-safe-redirect-target.md) | **Open redirect negli `add_*` di Groups/Users/Tenants (B2): `return_url`/`ref_mainpath` filtrati con `SafeRedirectTarget` in 5 metodi, trait reso più rigido su tab/spazi e URL non parsabili (`http:///host`); cambio visibile solo per input costruiti a mano; residuo `CRUDBooster::redirect`/`hook_before_add` non affrontato** | Sicurezza / Controller di sistema | Completato (144 test verdi, suite non lanciata) | 2026-10-30 |
| [325](325-uploads-rotta-non-serve-file-reali-500-diventa-404.md) | **`uploads/*` (B1b, seguito del 324): la rotta non serve file reali (i file escono da `/storage/uploads/...`), `getPreview` risponde 404 invece di 500 quando il file esiste solo in `storage/app/public`; nessuna nuova esposizione, `/storage/...` resta pubblico (opzioni B/C aperte)** | Sicurezza / Rotte | Completato (41 test verdi nel file, suite non lanciata) | 2026-10-30 |
| [324](324-rotte-custom-sotto-login.md) | **Rotte custom di sistema sotto login (B1): 28 rotte in `web.php` + `api-documentation`/postman con `CBBackend`, `saveEnable` solo superadmin (prima un ospite poteva svuotare `module_tenants`), fix `$row` in `enable()`; cambio visibile per ospiti e non superadmin, mashup non più pubbliche (ribalta il 076)** | Sicurezza / Rotte | Completato (42 test nuovi verdi, suite non lanciata) | 2026-10-29 |
| [323](323-audit-compatibilita-php-85-curl-close-blade-immagini-worker.md) | **Audit compatibilità PHP 8.5: rimosse 10 `curl_close()` deprecate, corrette due viste Blade non compilabili (`modules/index`, `menus/form_body`), ricostruite le immagini worker/scheduler (ferme a PHP 8.3); cambio visibile solo in `form_body` (valore del join)** | PHP 8.5 / Qualità / Docker | Completato (verifiche statiche, suite non lanciata) | 2026-10-29 |
| [322](322-prime-action-e-local-scope-batch-3.md) | **Prime Action del repo (`app/Actions/`: `AssignDefaultMenuTenant`, `AssignDefaultMenuGroup`, `AddTenantToGroup`, wrapper nei modelli) e local scope su `Menu`/`UsersGroup` (batch 3 del piano 317); nessun cambio di comportamento** | Architettura / Modelli | Completato (123 test verdi nel container) | 2026-10-29 |
| [321](321-user-primary-group-name-gruppo-orfano.md) | **`UserHelper::current_user_primary_group_name()` con gruppo primario soft-deleted/inesistente: stringa vuota invece di `ErrorException` (batch 2b del piano 317); cambio visibile solo nel caso orfano** | Architettura / Modelli | Completato (100 test verdi nel container con il 320) | 2026-10-29 |
| [320](320-menu-tenants-name-senza-virgola-finale.md) | **`Menu::tenants_name()` senza virgola finale quando l'ultimo tenant è soft-deleted/mancante (`"Alfa, "` → `"Alfa"`, batch 2b del piano 317); visibile solo al superadmin in Menu Management** | Architettura / Modelli | Completato (100 test verdi nel container) | 2026-10-29 |
| [319](319-modelli-eloquent-relazioni-additive.md) | **Modelli Eloquent: relazioni additive (batch 2 del piano 317) su `Menu`, `Group`, `User`, `QlikItem` con nomi nuovi (`assignedTenants`, `memberGroups`, ecc.); `Menu::tenants_name()` senza N+1; wrapper esistenti intatti, nessun cambio di comportamento** | Architettura / Modelli | Completato (98 test verdi nel container) | 2026-10-29 |
| [318](318-chatai-autorizzazioni-su-tabelle-dedicate.md) | **Autorizzazioni ChatAI (Agent AI) su tabelle dedicate (`chatai_groups_allowed`, `chatai_tenants_allowed`) come per i Qlik items: fix `QueryException` per i non superadmin, niente più condivisione con Qlik, controller allineato a quello Qlik (trait `SafeRedirectTarget`)** | Chat AI / Autorizzazioni | Completato (28 test nuovi + 62 di regressione verdi) | 2026-10-29 |
| [317](317-modelli-eloquent-caratterizzazione-e-igiene.md) | **Modelli Eloquent: test di caratterizzazione (batch 0, 34 test su 5 file) e igiene sul posto in 13 modelli (batch 1: `use`/`$hidden` inutili, `casts()`, tipi di ritorno); nessun cambio di comportamento** | Architettura / Modelli | Completato (test da rilanciare dopo il batch 1) | 2026-10-29 |
| [316](316-conoscenza-e-memoria-dell-architect.md) | **Conoscenza e memoria dell'architect: `docs/architettura/` (mappa, pattern, ADR, lezioni), più tool di misura, review del piano, post-mortem e test di caratterizzazione per rischio; tutti gli agent su `sonnet`** | Tooling / Claude Code | Completato (non verificato in sessione reale) | 2026-10-29 |
| [315](315-flusso-architect-senior-dev-refactoring-laravel.md) | **Flusso architect → senior developer per il refactoring verso gli standard Laravel: agent `ch-architect-analyst`/`ch-senior-dev`, comandi `/architect` e `/refactor-verify`, nuovo ordine di review e doc** | Tooling / Claude Code | Completato (non verificato in sessione reale) | 2026-10-29 |
| [314](314-prova-larastan-helpers-e-system.md) | **Prova di Larastan su `app/Helpers` e `Controllers/System`: 44 segnalazioni al livello 0, alcuni bug latenti trovati (`QlikHelper::getTicket`, `Log` senza `use`)** | Tooling / Qualità | Completato (tenuto, livello 0 con baseline) | 2026-10-29 |
| [313](313-hook-pint-informativo-dopo-modifica-php.md) | **Hook informativo (PostToolUse) che lancia `pint --test` sul file PHP appena modificato; esclusi i controller dei clienti, inclusi `System/` e `Auth/`** | Tooling / Claude Code | Completato (provato a mano, non in sessione reale) | 2026-10-29 |
| [312](312-pint-psr12-controllo-stile-file-toccati.md) | **Laravel Pint (preset PSR-12) in `require-dev`, `pint.json` e rule: controllo di stile solo con `--test` sui file toccati** | Tooling / Dipendenze | Completato | 2026-10-29 |
| [311](311-claude-md-rules-con-paths.md) | **CLAUDE.md alleggerito: standard UI, gotcha Blade/JS, SMTP e CSRF/licenza spostati in `.claude/rules/` con scope per percorso** | Tooling / Claude Code | Completato (non verificato in nuova sessione) | 2026-10-29 |
| [310](310-chat-ai-finestra-flottante-ridimensionabile.md) | **Chat AI (finestra flottante) ridimensionabile: stili degli handle jQuery UI che mancavano nelle pagine admin** | Frontend / Chat AI | Completato (solo compilazione Blade) | 2026-10-28 |
| [309](309-menu-qlik-agent-ai-end-explode-php85.md) | **Voci di menu Qlik / Agent AI non cliccabili con PHP 8.5: `end(explode())` lanciava `ErrorException` e la voce diventava "rotta"** | Frontend / Menu, PHP 8.5 | Completato (lint + prova funzione) | 2026-10-28 |
| [308](308-chat-ai-finestra-flottante-fix-interfaccia.md) | **Chat AI (finestra flottante): testi tradotti, messaggi coerenti prima/dopo il reload, no invio vuoto, scroll all'apertura, errori visibili, tema scuro** | Frontend / Chat AI | Completato (solo lint/compilazione) | 2026-10-28 |
| [307](307-migrazione-personal-access-tokens-guardia-tabella-esistente.md) | **Migrazione `personal_access_tokens`: non fallisce se la tabella esiste già (trovato nella prova di aggiornamento su un dump reale)** | Database / Aggiornamento clienti | Completato | 2026-10-28 |
| [306](306-aggiornamento-laravel-13-30.md) | **Aggiornamento di `laravel/framework` 13.29.0 → 13.30.1 (solo quel pacchetto, Symfony resta 7.4): `composer audit` senza advisory** | Dipendenze / Sicurezza | Completato | 2026-10-16 |
| [305](305-header-x-app-version-nel-check-in-della-licenza.md) | **Header `x-app-version` nel check-in della licenza (versione in uso per Chive CP, solo informativa)** | Licensing / Chive CP | Completato (provato con Docker vero) | 2026-10-16 |
| [304](304-aggiornamento-patch-dipendenze-advisory-sicurezza.md) | **Aggiornamento di patch di `symfony/http-foundation` e `league/commonmark` (advisory `composer audit` da 4 a 1; Laravel resta, serve Symfony 8)** | Dipendenze / Sicurezza | Completato | 2026-10-16 |
| [303](303-passaggio-a-php-8-5.md) | **Passaggio a PHP 8.5: composer.json `^8.5`, immagini Docker dev/prod e CI su 8.5** | Dipendenze / Docker / CI | Completato | 2026-10-12 |
| [302](302-pipeline-build-e-push-immagine-docker-hub.md) | **Pipeline: build e push dell'immagine di produzione su Docker Hub (job build-and-push, tag vX.Y.Z)** | CI / Docker | Pronto, non eseguito | 2026-10-12 |
| [301](301-correzioni-dopo-prova-generale-licenza-i18n-blueprint.md) | **Correzioni dopo la prova generale: licenza non cancellata su errori del server, messaggio di licenza i18n, correzioni al blueprint** | Licensing / Docker | Completato | 2026-10-09 |
| [300](300-prova-generale-istanza-docker-end-to-end.md) | **Prova generale end-to-end di un'istanza Docker con LICENSES reale (solo prova, nessun codice modificato): 2 bug sul ciclo di vita della licenza, correzioni al blueprint** | Docker / Licensing | Completato | 2026-10-09 |
| [299](299-bootstrap-istanza-docker-entrypoint-e-comandi.md) | **Istanza Docker di produzione: entrypoint con migrazioni (fail-stop), `instance:bootstrap`, `instance:password-link`** | Docker / Provisioning | Completato | 2026-10-08 |
| [298](298-dockerfile-produzione.md) | **Dockerfile di produzione (docker/Dockerfile.prod, 3 stadi, www-data su 8080) e .dockerignore a lista bianca** | Docker | Completato | 2026-10-08 |
| [297](297-docker-prod-versione-proxy-fidati-health.md) | **Immagine di produzione, parte codice: APP_VERSION, proxy fidati (TRUSTED_PROXIES), GET /health** | Docker / Config | Completato | 2026-10-08 |
| [296](296-connessione-coda-mail-dedicata.md) | **Connessione di coda `mail` dedicata (MAIL_QUEUE_CONNECTION=mail al posto di qlik_sync)** | Code / Email | Completato | 2026-10-08 |
| [295](295-servizio-scheduler-docker-locale.md) | **Servizio scheduler (schedule:work) nell'ambiente Docker locale** | Docker | Completato | 2026-10-08 |
| [294](294-smtp-solo-da-settings-config-mail-rimosso.md) | **SMTP solo dai Settings (MailSettings, TLS/SSL e mittente inclusi), config/mail.php rimosso, Mail::fake() nei test** | Email / Config | Completato | 2026-10-08 |
| [293](293-scadenza-account-al-login-e-protezione-superadmin.md) | **Scadenza account anche al login e sui token api2, protezione dei superadmin, rimosso MigrateLegacyCrudboosterExtends** | Auth / Utenti | Completato | 2026-10-08 |
| [292](292-check-user-expiry-pulizia-e-test.md) | **user:check-expiry: un solo UPDATE, log e conteggio, 8 test (comportamento di scadenza invariato)** | Utenti / Console | Completato | 2026-10-08 |
| [291](291-avvisi-scadenza-miglioramenti-e-test.md) | **Avvisi di scadenza: meno query, escape HTML, deduplica giornaliera, builder in TenantHelper, 19 test** | Email / Test | Completato | 2026-10-08 |
| [290](290-avvisi-scadenza-utenti-su-job-in-coda.md) | **Avvisi di scadenza utenti su job in coda (MAIL_QUEUE_CONNECTION, default sync)** | Email / Code | Completato | 2026-10-08 |

**Stato**: `Pianificato` → `In corso` → `Completato` (o `Annullato` se si
decide di non procedere, motivando il perché nel file stesso).

## Roadmap refactoring auth (strategia additiva)

Percorso concordato per sostituire l'auth custom di CRUDBooster con i guard
Laravel nativi (vedi [001](001-auth-guard-additivo-fase-1.md) per il
dettaglio della strategia):

- ✅ Fase 0 — Ricognizione
- ✅ Fase 1 — Guard additivo su `postLogin()`
- 🔶 Fase 3 — Migrazione dei 42 file individuati in Fase 0, uno alla volta.
  **In corso, non come sprint dedicato**: si migra un file quando lo si
  tocca comunque per un altro motivo. `CBBackend` migrato in
  [002](002-cbbackend-guard-fase-3.md), **41 file ancora sul meccanismo
  legacy**.
- ⏸️ Fase 4 — Rimozione della scrittura della sessione legacy (solo quando
  la Fase 3 sarà sostanzialmente completa). **Messa in pausa esplicitamente
  dall'utente**, da riprendere più avanti — per ora si passa ad altre
  sezioni del progetto da refactorare.

## Roadmap uscita da CRUDBooster (packages/)

Percorso per portare `packages/crocodicstudio/crudbooster` a standard
Laravel. Dettaglio completo passo-passo (già concluso, salvo il punto
aperto sotto) in [`archivio-001-068.md`](archivio-001-068.md#roadmap-uscita-da-crudbooster-packages--dettaglio-completo).

- ✅ Controller "di sistema", le 5 classi "motore", i 14 helper, i
  middleware, le route, il service provider, le view, gli assets, il
  database e le config del pacchetto — tutti spostati fuori da
  `packages/crocodicstudio/crudbooster/src/`, che oggi contiene solo
  `assets/`, `fonts/`, `views/`.
- ⬜ **Rimozione definitiva degli alias** (`app/Support/legacy_crudbooster_aliases.php`
  e l'entry in `composer.json`): possibile solo dopo che *ogni* cliente
  attivo è stato aggiornato almeno una volta con
  `php artisan crudbooster:migrate-legacy-extends --apply` sui propri
  controller custom (comando pronto —
  [016](016-comando-migrazione-extends-legacy-clienti.md) — ma mai ancora
  eseguito su un ambiente reale). Finché anche un solo cliente non è
  passato da questo comando, gli alias restano necessari.

## Backlog — emerso ma non ancora assegnato a un intervento numerato

Cose notate durante altri lavori, non ancora trasformate in un intervento
vero e proprio. Le voci già risolte sono in
[`backlog-risolto.md`](backlog-risolto.md).

- **Autenticazione delle API custom (`CRUDBooster::authAPI()`, modulo API
  Generator)**: schema "fatto in casa" (chiave condivisa in `cms_apikey` +
  timestamp + user agent → `md5()`, confrontato con l'header
  `X-Authorization-Token`). Problemi noti: nessuna scadenza reale sul
  timestamp (solo l'uguaglianza dell'hash), MD5 invece di un HMAC standard,
  autenticazione **disattivata di default** a meno che il setting
  `api_debug_mode` non sia impostato esplicitamente a `'false'`, chiavi che
  non scadono/ruotano mai. **Non risolto per `api/` esistente** (nessuno
  di questi problemi è stato toccato lì) — **risolto per i nuovi client**
  con un binario parallelo e additivo, `api2` (Bearer token via Laravel
  Sanctum, scadenza per-token scelta alla generazione), vedi
  [086](086-api2-sanctum.md). Migrazione volontaria, per cliente, non uno
  switch globale: `api/` resta con lo schema debole finché un cliente non
  passa a `api2`. **Deciso 2026-09-24**: si accetta così per ora; quando
  i client saranno migrati su `api2`, `api/` verrà disattivato del
  tutto (nessuna data ancora).
- **`ModuleHelper` non ha un ramo generico per `global_privilege=true`** su
  tabelle diverse da quelle già coperte (Module Generator, `cms_users`/
  `groups`) — un modulo di sistema con `global_privilege=true` ma senza
  scoping tenant/gruppo resta comunque negato per un non superadmin
  (trovato risolvendo [093](093-execute-api-cbinit-privilegi.md)). Non
  corretto: tocca `ModuleHelper`, usato da ogni pagina CRUD admin — serve
  una valutazione a parte.
- `assets/adminlte/plugins/` (11 MB) non ancora sottoposto al censimento
  JS fatto in [094](094-pulizia-librerie-js-morte-public-vendor.md) —
  fuori scope in quell'intervento.
- **Ionicons v2 morto** (`vendor/crudbooster/ionic/css/ionicons.min.css`),
  caricato globalmente in `admin_template.blade.php`/`chat_ai/public.blade.php`
  per un solo uso reale (`home.blade.php`) — notato in
  [128](128-icone-lucide-reskin-font-awesome.md), non ancora rimosso.
- ~~Ionicons v7 (`<ion-icon>`, widget dashboard a griglia) caricato da CDN
  esterno `unpkg.com`~~ — **risolto in
  [129](129-widget-kpi-icona-lucide.md)** (2026-09-28): portato sullo
  stesso set Lucide vendorizzato del resto dell'interfaccia, script CDN
  rimossi.
- **Pulsanti Modifica/Elimina di ogni widget visibili anche in vista
  pubblica della dashboard a griglia** — lo script che dovrebbe
  nasconderli lancia `ReferenceError: $ is not defined` (jQuery non
  caricato in quel contesto), trovato verificando
  [129](129-widget-kpi-icona-lucide.md), preesistente e scollegato,
  non corretto.
- Font Awesome non ancora rimosso dopo il reskin [128](128-icone-lucide-reskin-font-awesome.md):
  resta vendorizzato/caricato perché i controller custom dei singoli
  clienti (fuori da questo repo) possono referenziare icone FA non
  presenti nella mappatura. Rimozione possibile solo dopo aver verificato
  per ogni cliente attivo (stesso vincolo già noto per gli alias
  CRUDBooster).
- **Duplicazione residua in `public/vendor/crudbooster/assets/assets/`**
  (15 MB, file live/tracciati su git) — notata in
  [036](036-rimozione-assets-legacy.md), non ancora ripulita.

- **Bug lato server di licenza remoto** (`license.thecustomerhive.com`,
  progetto separato gestito dall'utente): il trial di attivazione
  restituisce `LicenseService::getLicenseByDomain(): Argument #1 ($domain)
  must be of type string, null given` — vedi
  [003](003-licensing-hardening.md#rischi-e-note). Da correggere in
  quel progetto, non qui.

## Documenti correlati

- [`../docker-local-dev.md`](../docker-local-dev.md) — ambiente di sviluppo locale
- [`../login-e-licensing.md`](../login-e-licensing.md) — sistema di login e licensing attuale
- [`../pre-push-checklist.md`](../pre-push-checklist.md) — cose da ripristinare/verificare prima di un push
- [`../test-coverage.md`](../test-coverage.md) — catalogo dei test automatici esistenti
- [`../ui-ux-annotazioni.md`](../ui-ux-annotazioni.md) — cose notate sull'interfaccia da tenere a mente per il rinnovo UI/UX
- [`archivio-001-068.md`](archivio-001-068.md) — interventi 001–068 archiviati
- [`archivio-069-289.md`](archivio-069-289.md) — interventi 069–289 archiviati
- [`backlog-risolto.md`](backlog-risolto.md) — voci di backlog già risolte
