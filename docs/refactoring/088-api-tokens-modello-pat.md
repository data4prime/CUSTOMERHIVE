# 088 - `api_tokens`: da flag "client API" dedicato a modello Personal Access Token

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza / API
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminController.php` (`postLogin()`, revert)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (rimosso campo)
  - `app/Http/Controllers/System/ApiTokensController.php`
  - `app/Providers/AppServiceProvider.php` (nuovo controllo Sanctum)
  - `database/migrations/` (rimossa la migration `is_api_client`, mai
    arrivata a un commit)
  - `resources/lang/{en,it}/crudbooster.php`
  - `tests/Feature/Api2SanctumAuthTest.php`

## Contesto

Seguito diretto di [086](086-api2-sanctum.md). Discutendo l'impatto sulla
licenza (`LicenseHelper::canAddUser()`/`UserHelper::countUsers()` contano
**tutte** le righe di `cms_users` senza distinzione), è emerso che un
utente "client API" (il design di 086) avrebbe consumato un posto
licenza come un utente vero, creando confusione ("perché questo account
che non fa nulla in dashboard occupa un posto?"). Valutate due
alternative (escludere il flag dal conteggio; un model/tabella dedicata
separata da `cms_users`) prima di arrivare, su proposta dell'utente, a
una terza via più semplice: **niente categoria di utente dedicata** -
un token è generabile per **qualunque utente esistente** e ne eredita i
permessi, esattamente come un Personal Access Token di GitHub/GitLab.

## Situazione prima

- `cms_users.is_api_client` (boolean, aggiunto in 086): un utente così
  flaggato non poteva fare login web (`AdminController::postLogin()`) ed
  era l'unico selezionabile nel form "Genera token" di
  `ApiTokensController`.
- Nessun controllo che leghi la validità di un token allo stato
  dell'utente collegato: un token restava valido anche se l'utente
  veniva disattivato.
- La licenza contava comunque questi utenti come chiunque altro
  (`UserHelper::countUsers()` non fa distinzioni) - motivo per cui si è
  rivisto il design.

## Situazione dopo

- **Rimosso interamente** il concetto di "utente client API": colonna
  `is_api_client` (migration cancellata, mai committata - drop diretto
  della colonna in locale), blocco in `postLogin()`, campo/colonna nel
  form Users, 4 chiavi di traduzione dedicate.
- `ApiTokensController::selectableUsers()` (ex `apiClientOptions()`)
  elenca ora **tutti** gli utenti di `cms_users`, non un sottoinsieme.
- **Nuovo controllo in `AppServiceProvider::boot()`**:
  `Sanctum::authenticateAccessTokensUsing()` verifica, oltre a
  scadenza/revoca già gestite da Sanctum, che l'utente collegato al
  token abbia `status === 'Active'`. Un utente disattivato (a mano, o da
  `CheckUserExpiry` per scadenza) perde immediatamente l'uso di ogni suo
  token, senza doverli revocare uno per uno.
- Testi UI aggiornati per riflettere il modello PAT ("il token erediterà
  i permessi di questo utente").
- Test riscritti: via i test legati al flag, aggiunto
  `test_api2_con_token_di_utente_disattivato_dopo_l_emissione_viene_rifiutato`.

**Comportamento visibile che cambia rispetto a 086** (mai arrivato a un
commit, quindi nessun utente reale ha visto la versione precedente):
qualunque utente può ora avere un token, non solo quelli marcati;
disattivare un utente ora invalida anche i suoi token.

## Motivazione

Più semplice del design precedente e di entrambe le alternative
valutate: nessun flag/tabella da mantenere sincronizzata con la
licenza, nessuna nuova "categoria" di utente da spiegare a chi usa il
pannello. Il problema di licenza si dissolve da solo (un PAT è legato a
un posto già pagato, non un'entità nuova). Il controllo di stato
sull'utente è stato aggiunto perché con questo modello un token è, a
tutti gli effetti, un'altra via d'accesso allo stesso account - e un
account disattivato non deve lasciare comunque aperta quella via.

## Test

Manuali sull'ambiente Docker locale (sessione superadmin reale, vedi
memoria di sessione):
- `php -l` su tutti i file toccati.
- Rimossa la colonna `is_api_client` (drop diretto + pulizia riga
  `migrations`, dato che la migration non era mai stata committata).
- Form "Genera token": il menu utenti ora elenca tutti gli utenti
  (verificato il conteggio delle `<option>`).
- Generato un token via UI per un utente normale (nessun flag) →
  funziona su `api2/hive` (stesso comportamento di sempre, arriva fino
  al bug preesistente e scollegato di `execute_api()`, segno che l'auth
  passa).
- Disattivato lo stesso utente (`status='Inactive'`) **dopo** aver
  emesso il token → stessa chiamata a `api2/hive` ora dà **401**,
  conferma diretta del nuovo controllo.
- Utente/token di prova poi eliminati.
- Scritti (non eseguiti, regola del progetto) i test aggiornati in
  `Api2SanctumAuthTest.php`.

## Rischi e note

- Il controllo di stato si basa su `status === 'Active'`, non
  direttamente su `data_scadenza`: coerente con come funziona già il
  login web (`postLogin()` controlla solo `status`), dato che
  `data_scadenza` viene tradotto in `status='Inactive'` da un comando
  schedulato (`CheckUserExpiry`), non in tempo reale a ogni richiesta.
- Non c'è modo, nella UI attuale, di limitare chi può generare un token
  per **quale** utente (un superadmin può generarne uno per qualunque
  account, incluso un altro superadmin) - stesso livello di fiducia già
  richiesto per l'accesso alla schermata (superadmin-only).
- Non toccato il gap di licenza per gli utenti "veri": resta comunque
  vero che qualunque utente reale generi un token, quel token non
  costituisce un posto aggiuntivo (è la stessa persona) - punto ormai
  assodato, non serve più azione.

## Rollback

`git revert` del commit. Il controllo Sanctum in `AppServiceProvider` è
isolato e rimovibile da solo (basta togliere la chiamata a
`Sanctum::authenticateAccessTokensUsing()`) senza impatto sul resto.
