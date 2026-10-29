# Catalogo dei pattern già usati nel repo

> Letto da `/architect` e `ch-architect-analyst`. Serve a proporre struttura
> **coerente con quella esistente**, non da manuale. Quando nasce un nuovo
> pattern di riferimento (es. la prima Action ben fatta), aggiungilo qui.

## Pattern con un esempio di riferimento

| Pattern | Esempio nel repo | Quando imitarlo |
|---|---|---|
| **Service + Strategy + Factory** (integrazione esterna) | `app/Services/QlikSync/` — `QlikDriver` (interfaccia), `OnPremQlikDriver`/`SaasQlikDriver` (strategie), `QlikDriverFactory`, `QlikSyncService` (orchestrazione), `QlikSyncRollback`, `QlikSyncException` | Più varianti di un sistema esterno dietro la stessa operazione. |
| **Job in coda** | `app/Jobs/SyncQlikRunJob.php`, `SendTemplateEmailJob.php` | Lavoro lento o ritentabile; il controller avvia (`HandlesQlikSyncStart`) e non attende. |
| **Trait per comportamento condiviso di controller** | `Controllers/System/Concerns/HandlesQlikSyncStart.php` | Logica comune a 2+ controller di sistema, senza toccare la gerarchia `CBController`. |
| **Action (un'operazione, un solo `handle()`)** | `app/Actions/Menus/AssignDefaultMenuTenant.php`, `AssignDefaultMenuGroup.php`, `app/Actions/Groups/AddTenantToGroup.php` (doc 322) | Operazione di scrittura che oggi vive in un modello o in un controller. Riceve gli id come parametri (niente `UserHelper`/sessione/richiesta dentro), niente stato. Il vecchio metodo del modello resta come wrapper sottile per i controller dei clienti. Prima di sostituire i punti di chiamata, cercare i chiamanti con tokensave e controllare i tipi (un `int` può cambiare il valore salvato). |
| **Local scope sul modello (mai global)** | `Menu::activeDashboard()`, `Menu::qlikItemContent($id)`, `UsersGroup::ofUser($id)`, `UsersGroup::ofGroup($id)` (doc 322) | Stessa condizione identica ripetuta in 2+ punti. Il test confronta `toSql()`/bindings con la query esplicita. Non per `ModuleHelper::can_*` (permessi per tenant) né per il login. |
| **Relazione Eloquent additiva con nome nuovo** | `Menu::assignedTenants()`, `User::tenantRecord()/primaryGroupRecord()/memberGroups()`, `QlikItem::allowedGroupRecords()` (doc 319) | Si aggiunge accanto al metodo esistente senza sostituirlo se il metodo ha una semantica diversa (soft-deleted inclusi, ordine). Il nome non deve coincidere con una colonna. |
| **Trait `Concerns` per i controller di sistema (uso condiviso)** | `Controllers/System/Concerns/SafeRedirectTarget.php` (usato da `AdminQlikItemsController` e `AdminChatAIController`) | Comportamento identico in 2+ controller di sistema: estrarlo invece di copiarlo. |
| **Tabelle di autorizzazione dedicate per tipo di oggetto** | `chatai_groups_allowed` / `chatai_tenants_allowed` (ADR A12), pivot `ChatAIGroupAllowed`/`ChatAITenantAllowed`, relazioni `ChatAIConf::allowedGroupRecords()/allowedTenantRecords()` | Nuovo tipo di oggetto da autorizzare per gruppo/tenant: non riusare `items_allowed`/`tenants_allowed` di Qlik. Prima relazione `belongsToMany` del repo. |
| **Comando Artisan con trait di supporto** | `Console/Commands/Concerns/InstanceCommandSupport.php` | Operazioni di istanza/CLI. |
| **Helper statico di dominio piccolo** | `MailSettings`, `PasswordPolicy`, `TenantHelper` | Logica senza stato. Ok per ciò che esiste già; per codice **nuovo** preferire una classe iniettabile. |
| **Dataset/registry dichiarativo** | `app/Dashboards/DashboardDatasetRegistry` + `DatasetAccessScope` | Catalogo di sorgenti con scope di accesso esplicito. Nota: `DatasetAccessScope` è l'unico oggetto "scope di accesso" del repo, utile come modello per il tenant. |
| **Alias di compatibilità** | `app/Support/legacy_crudbooster_aliases.php` | Mai rimuovere finché ogni cliente non è migrato. |
| **Feature flag** | `MODULE_GENERATOR_WIZARD_V2`, `CH_UI_V2` | Cambi visibili da introdurre e poter spegnere. |

## Pattern assenti (introdurli è un fatto nuovo, da discutere)

Action, Form Request, Policy/Gate, Event+Listener di dominio, Observer,
API Resource, scope Eloquent globale per tenant. Quando se ne propone uno
**per la prima volta**, la proposta deve includere: l'unico esempio pilota,
dove vivrà la cartella (`app/Actions/`, `app/Http/Requests/`,
`app/Policies/`), e una nota qui dopo l'implementazione.

## Anti-pattern da NON proporre in questo repo

- Rinominare/spostare `CBController`, `CRUDBooster::`, `crudbooster::`, tabelle
  `cms_*`, alias legacy, proprietà `$this->form|col|…` e hook `hook_*`.
- Rimuovere campi da `$this->form` (usa `hide_form`).
- "Spezzare" i `cbInit()` lunghi: sono configurazione dichiarativa.
- Repository "per principio" sopra Eloquent.
- Service/Manager "contenitore" generici (`ApplicationService`, `HelperManager`).
- Riscritture ampie o refactoring non behavior-preserving: tutto a batch
  piccoli, strangler fig.
- Pint o Larastan sull'intero repo (solo sui file toccati / sul perimetro
  indicato in `.claude/rules/`).
- Proposte che dipendono da una copertura test che non esiste, senza prima
  indicare il test di caratterizzazione.
