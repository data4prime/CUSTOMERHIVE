# 317 - Modelli Eloquent: test di caratterizzazione (batch 0) e igiene sul posto (batch 1)

- **Data**: 2026-10-29
- **Stato**: Completato (batch 0 e 1; test del batch 0 eseguiti prima delle modifiche del batch 1, da rilanciare dopo)
- **Area**: Architettura / Modelli Eloquent
- **File/aree di codice coinvolte**:
  - `tests/Feature/MenuTenantsCharacterizationTest.php` (nuovo)
  - `tests/Feature/ModuleStep1MenuDefaultsTest.php` (nuovo)
  - `tests/Feature/QlikItemVisibilityTest.php` (nuovo)
  - `tests/Feature/UserModelLookupsTest.php` (nuovo)
  - `tests/Feature/QlikItemPublicAccessTest.php` (nuovo)
  - `app/ChatAIConf.php`, `app/Group.php`, `app/Menu.php`, `app/MenuGroups.php`,
    `app/MenuTenants.php`, `app/ModuleTenants.php`, `app/Modules.php`,
    `app/Tenant.php`, `app/UsersGroup.php`, `app/User.php`, `app/QlikItem.php`,
    `app/QlikSyncRun.php`, `app/QlikSyncRunRecord.php`
  - Piano di riferimento: `docs/refactoring/piani/317-models-standard-laravel.md`

## Contesto

Primo intervento del piano 317 (allineare i modelli Eloquent di `app/` agli
standard Laravel, vedi flusso `/architect` in
[`315`](315-flusso-architect-senior-dev-refactoring-laravel.md)). Il piano è
diviso in batch: qui si documentano solo il **batch 0** (test di
caratterizzazione, perché i modelli toccati non avevano copertura) e il
**batch 1** (igiene sul posto, senza cambi di comportamento). I batch 2, 2b e 3
**non sono ancora fatti**.

CustomerHive è in produzione presso clienti reali: ogni modifica deve essere
behavior-preserving, e prima di rifattorizzare codice senza test serve un test
che fissi il comportamento attuale.

## Situazione prima

- Nessun test copriva `Menu::tenants_name()`, `UserHelper::can_menu` (via
  `Menu::tenants()`), il salvataggio dei default tenant/gruppo in
  `ModulsController::postStep1`, `QlikHelper::can_see_item`,
  `User::tenant()/primary_group()` con
  `UserHelper::current_user_primary_group_name()`, né
  `QlikItem::enablePublicAccess()/disablePublicAccess()`.
- I 13 modelli avevano debito di stile: `use` inutili o ridondanti
  (`QlikHelper` in `ChatAIConf`, `\App\Group`/`\App\Tenant` in `User`),
  `$hidden = []` vuoti, un `//dd` morto in `Menu`, `$casts` con il solo
  `deleted_at` in `QlikItem`/`ChatAIConf` (già coperto da `SoftDeletes`),
  `$casts` come proprietà invece del metodo `casts()`, trait e costanti
  sparsi dopo le proprietà, metodi senza tipo di ritorno.

## Situazione dopo

**Batch 0 - 5 nuovi file di test in `tests/Feature/`** che fotografano il
comportamento attuale:

| File | Cosa fotografa |
|------|----------------|
| `MenuTenantsCharacterizationTest` | `Menu::tenants_name()` e `UserHelper::can_menu` via `Menu::tenants()` |
| `ModuleStep1MenuDefaultsTest` | `ModulsController::postStep1` con `assign_default_tenant/group` |
| `QlikItemVisibilityTest` | `QlikHelper::can_see_item` |
| `UserModelLookupsTest` | `User::tenant()/primary_group()` e `UserHelper::current_user_primary_group_name()` |
| `QlikItemPublicAccessTest` | `QlikItem::enablePublicAccess()/disablePublicAccess()` |

Due test fotografano comportamenti **oggi scorretti** che il futuro batch 2b
cambierà di proposito (il test andrà aggiornato in quel momento):

- `tenants_name()` con l'ultimo tenant soft-deleted lascia la virgola finale
  (`"Alfa, "`);
- `current_user_primary_group_name()` con gruppo primario soft-deleted o
  cancellato solleva `ErrorException` ("on null").

Nessun test per ChatAI: vedi "Rischi e note".

**Batch 1 - igiene in 13 modelli** (13 file, 45 inserzioni, 74 cancellazioni):

- rimossi `use` inutili/ridondanti, `$hidden = []` vuoti, `//dd` morto in
  `Menu`, `$casts` solo-`deleted_at` in `QlikItem`/`ChatAIConf`;
- `$casts` -> `casts(): array` con array identico in `User` e `QlikSyncRun`;
- trait in cima alla classe e costanti prima delle proprietà;
- tipi di ritorno aggiunti: `Menu::tenants(): array`, `tenants_name(): string`,
  `QlikItem::allowedGroups()/allowedTenants(): array`,
  `enablePublicAccess()/disablePublicAccess()/isPublic(): bool`,
  `QlikSyncRun::records(): HasMany`.

Non toccati: `Clienti`, `Ordini`, `Righe`, `DynamicTable`, e i corpi di
`ChatAIConf::allowedGroups()/allowedTenants()`. Non aggiunti
`$fillable`/`$guarded`: nel codice indicizzato non c'è mass assignment sui
modelli senza protezione.

**Comportamento visibile: nessun cambiamento.** Nessun testo UI (i18n n/a),
nessuna migrazione.

## Motivazione

- Il batch 0 rende i refactoring successivi verificabili: senza una
  fotografia del comportamento attuale non si distingue una regressione da un
  cambio voluto.
- Il batch 1 riduce il rumore e dà ai modelli una forma coerente (tipi di
  ritorno, `casts()` come in Laravel 11+) prima di toccare la logica, con
  rischio minimo.
- Scartato: aggiungere `$fillable`/`$guarded` adesso, perché non servono ai
  modelli attuali e aumenterebbero il rischio sui controller dei clienti.

## Test

Verificato davvero:

- Batch 0: i 5 file eseguiti nel container su `customerhive_testing`: **34
  passati, 71 assert, nessun fallimento** (eseguiti sul codice prima delle
  modifiche del batch 1).
- Batch 1: review di `ch-reviewer` sul diff, che ha confermato
  punto per punto il behavior-preserving (firme, `casts()` identici,
  `SoftDeletes` aggiunge comunque il cast di `deleted_at` anche con
  `$timestamps = false`, tutti i return path rispettano i nuovi tipi,
  chiamanti verificati). Pint `--test` segnala solo violazioni storiche su
  righe non toccate.

NON verificato: **nessun test è stato eseguito dopo le modifiche del
batch 1**. I test del batch 0 vanno rilanciati dalla persona
(`/refactor-verify`).

## Rischi e note

Rilievi del reviewer: nessuno bloccante o strutturale. Note:

1. I controller dei clienti (gitignored) non sono verificabili rispetto ai
   nuovi tipi di ritorno; rischio basso (i modelli sono usati, non estesi).
2. Gap di test: `QlikSyncRun::records()`, `User::casts()` e
   `QlikSyncRun::casts()` non hanno un test dedicato.
3. La ricerca di mass assignment non copre i 52 file `.php` non indicizzati da
   tokensave.
4. Avviso git "LF will be replaced by CRLF" su `QlikSyncRun.php` e
   `QlikSyncRunRecord.php` (autocrlf, innocuo).

Scoperte emerse durante il batch 0:

- **ChatAI**: `ChatAIConf::allowedGroups()/allowedTenants()` filtrano su
  `chataiconf_id`, colonna inesistente in ogni migrazione e in entrambi i DB
  locali. ChatAI scrive in `items_allowed`/`tenants_allowed` di Qlik con
  `item_id` = id della conf (collisione di id con i Qlik items). Nessun
  cliente usa ChatAI. Non è gestito qui: se ne occupa il piano
  `docs/refactoring/piani/318-chatai-autorizzazioni-come-qlik.md` (decisione
  A12 in `docs/architettura/adr.md`). Per questo nessun test ChatAI.
- `cms_users.primary_group` e `cms_users.tenant` sono NOT NULL: l'orfano reale
  è un id che punta a un gruppo/tenant soft-deleted o inesistente, non un
  valore nullo.
- `can_menu` per un non superadmin dipende dal path della richiesta, quindi il
  suo test passa da HTTP.

Residuo: batch 2, 2b (cambio intenzionale dei due comportamenti scorretti
sopra) e 3 ancora da fare.

## Rollback

`git revert` dei commit del batch (solo codice e test). Nessuna migrazione da
annullare, nessun dato toccato. In alternativa, ripristinare i 13 file di
`app/` elencati sopra e cancellare i 5 file di test nuovi.
