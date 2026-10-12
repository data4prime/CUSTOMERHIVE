# 299 - Istanza Docker di produzione: entrypoint con migrazioni, instance:bootstrap, instance:password-link

- **Data**: 2026-10-08
- **Stato**: Completato (non committato)
- **Area**: Docker / Provisioning
- **File/aree di codice coinvolte**:
  - `docker/entrypoint.prod.sh` (nuovo), `docker/Dockerfile.prod` (ENTRYPOINT + COPY)
  - `app/Console/Commands/InstanceBootstrap.php`, `InstancePasswordLink.php`, `Concerns/InstanceCommandSupport.php` (nuovi)
  - `app/Console/Kernel.php` (registrazione dei due comandi)
  - `database/seeders/InstanceBaseSeeder.php` (nuovo)

## Contesto

Ogni cliente ha un'istanza Docker creata e gestita da un pannello esterno
("Chive CP", via SSH; blueprint `customerhive-provisioning-istanze-docker.html`).
L'immagine di produzione (298) non aveva entrypoint: un primo `migrate`
crea lo schema ma non ci sono admin, dati di base né licenza. Questo
intervento rende **utilizzabile** un'istanza appena nata.

Scelte già prese (non rimesse in discussione): migrazioni a ogni avvio
dell'app con fail-stop (niente crash-loop); stessa immagine per app,
scheduler, worker, il ruolo dipende dal comando; nessun HEALTHCHECK
nell'immagine; il bootstrap lo lancia l'orchestratore una volta con
`docker compose exec` (password e chiave di licenza mai in variabili
d'ambiente); l'email col link la manda Chive CP; TTL del link = 60 minuti
standard (`config/auth.php`), flusso "password dimenticata" intatto.

## Situazione prima

- `docker/Dockerfile.prod`: nessun `ENTRYPOINT`, `CMD apache2-foreground`.
- Nessun comando per il primo avvio. `DatabaseSeeder` è interattivo
  (`Cms_usersSeeder` chiede l'email e crea l'utente con password `123456`),
  duplica moduli/template (vedi sotto) e semina anche cose non necessarie.
- Nessun modo per generare un link di impostazione password da CLI.

## Situazione dopo

### 1. `docker/entrypoint.prod.sh`

Ruolo dal comando: **solo `apache2-foreground` = app**; ogni altro comando
(`schedule:work`, `queue:work`, ...) fa solo `exec "$@"` (nessuna migrazione).
Ruolo app, in ordine:

1. APP_KEY, DB_HOST, DB_DATABASE, DB_USERNAME non vuoti e DB_PASSWORD definita.
2. `mkdir -p` + prova di scrittura di `storage/framework/{sessions,views,cache}`,
   `storage/logs`, `storage/app/public`, `storage/app/custom-controllers`,
   `bootstrap/cache`. Niente `chown`: il messaggio dice la correzione da fare sull'host.
3. Attesa DB con PHP/PDO (`ATTR_TIMEOUT` 3s), `DB_WAIT_TIMEOUT` (default 60s).
4. `php artisan migrate --force` (niente config:cache/route:cache).
5. `exec apache2-foreground`.

Qualsiasi errore → **fail-stop**: messaggio `[entrypoint] ERRORE: ...` su
stderr, il processo resta vivo (loop di `sleep`) senza avviare Apache, quindi
nessun crash-loop e l'healthcheck resta rosso. Trap su **TERM, INT e WINCH**
(vedi sorprese: `docker stop` manda SIGWINCH).

### 2. `php artisan instance:bootstrap --admin-email=<email> --license-key=<uuid>`

Ordine: validazione input → `GET_LOCK` MySQL (no due bootstrap in parallelo) →
**una transazione** con tutto:

1. Controllo "già inizializzata": `cms_users` ha ≥1 riga **oppure** `license`
   ha ≥1 riga → exit 2, nulla modificato. Il segnale non può restare scritto a
   metà: utente e licenza sono nella stessa transazione dei dati di base
   (solo DML, nessun commit implicito di MySQL).
2. `InstanceBaseSeeder::run()` (classificazione sotto).
3. Superadmin: stessi campi di `Cms_usersSeeder` (`name='Super Admin'`,
   `id_cms_privileges` = privilegio con `is_superadmin=1`, `status='Active'`,
   `primary_group`, `tenant`), password = `Hash::make(Str::random(64))`, mai
   stampata né loggata. Poi `InstanceBaseSeeder::linkUsersToGroups()`
   (`Cms_usersGroups`).
4. Licenza: `INSERT` della sola `license_key` in `license`. Nessuna chiamata di
   rete, nessun `license.json` (al primo login lo fa già
   `LicenseHelper::canLicenseLogin()`).
5. Token: `Password::broker()->createToken($user)` (nessuna email, nessuna
   Notification), URL come `AdminController::postForgot()`:
   `CRUDBooster::adminPath('reset-password/'.$token).'?email='.urlencode($email)`.
   Da CLI `url()` usa `APP_URL` (verificato: `http://localhost:18080/admin/...`).

### 3. `php artisan instance:password-link --admin-email=<email>`

Utente esistente e `status='Active'` → nuovo token (il broker cancella i
precedenti), stessa uscita. Non tocca `postForgot`/`postResetPassword`.

### Contratto per chi scrive il Control Plane

**Output**: l'**ultima riga di stdout** è esattamente `LINK=<url>`; tutto il
resto (avanzamento, errori) è su **stderr**. Esempio:

```
LINK=http://localhost:18080/admin/reset-password/dcbc39b5...edb8?email=admin%40example.test
```

In caso di errore stdout è vuoto. Usare `docker compose exec -T` e leggere
l'ultima riga di stdout, non mischiare stderr.

| Exit code | Significato | Comando |
|---|---|---|
| 0 | OK, `LINK=` stampato | entrambi |
| 1 | errore generico (email/UUID non validi, DB, eccezione, bootstrap già in corso) | entrambi |
| 2 | istanza già inizializzata, **nessuna modifica** | bootstrap |
| 3 | nessun utente con quell'email | password-link |
| 4 | utente esistente ma non attivo (aggiunto da me, non previsto dalla specifica) | password-link |

**Sicurezza**: i comandi si eseguono **solo da riga di comando nel
container**, non sono esposti via HTTP. Il token non viene scritto in
`laravel.log`, né in `cms_logs` (nessun `insertLog`), né stampato altrove; i
messaggi d'errore non includono mai l'SQL con i parametri (chiave di licenza,
hash).

### Classificazione dei seeder (`database/seeders`, 21 file)

| Seeder | Classe | Decisione |
|---|---|---|
| `GroupSeeder` | gruppo "IT" (segnaposto strutturale) | **incluso**: l'admin ha `primary_group`, le tabelle ponte lo usano |
| `TenantSeeder` | "Tenant 1" (segnaposto strutturale) | **incluso**: l'admin ha `tenant`; è lo stesso che crea `DatabaseSeeder` |
| `Cms_statistics` | dati di base (Dashboard) | incluso |
| `Cms_menus` | dati di base (menu Dashboard) | incluso |
| `Cms_menusGroups`, `Cms_menusTenants`, `Cms_groupTenants` | tabelle ponte di base | incluse (con 1 menu/gruppo/tenant, tutti id 1, coerenti; NB `Cms_groupTenants` usa l'id del *menu* come group_id e `Cms_menusTenants` l'id del *gruppo* come tenant_id: funziona solo perché sono tutti 1, non l'ho toccato) |
| `Cms_modulsSeeder` | dati di base (20 moduli) | incluso |
| `Cms_privilegesSeeder` | dati di base (Super Administrator) | incluso |
| `Cms_privileges_rolesSeeder` | dati di base | incluso |
| `Cms_settingsSeeder` | dati di base (impostazioni) | incluso |
| `CmsEmailTemplates` | dati di base (3 template) | incluso, **con attenzione**: le migrazioni creano già i 2 template MFA e il seeder li reinserisce senza controllare lo slug (duplicati). `InstanceBaseSeeder` cancella prima i 2 slug MFA (istanza vuota, stesso HTML dopo il restyle) e lascia il seeder |
| `Cms_menusPrivileges` | dati di base | incluso |
| `Cms_usersSeeder` (file `CmsuUsersSeeder.php`) | **utente di default password 123456**, chiede l'email in interattivo | **escluso**: lo sostituisce il comando |
| `Cms_usersGroups` | legame utenti-gruppi | incluso, **dopo** la creazione dell'admin (`linkUsersToGroups()`) |
| `QlikConf` (file `Qlik_Conf.php`) | modulo "Qlik Configuration" | **escluso**: `Cms_modulsSeeder` lo contiene già, `DatabaseSeeder` lo duplica (e senza riga in `cms_privileges_roles`) |
| `CBSeeder` | guscio vuoto (tutto commentato) | escluso |
| `GroupTenantSeeder` | riparazione dati legacy (`groups.tenant`), senza namespace | escluso |
| `MenuParentDefaultSeeder` | riparazione dati legacy (`parent_id`), senza namespace | escluso |
| `ModuleHelperSeeder` | link di aiuto facoltativi (help.thecustomerhive.com), senza namespace, fuori da `DatabaseSeeder` | escluso (nel dubbio); eventuale seguito |
| `DatabaseSeeder` | orchestratore dei clienti/sviluppo | **non toccato** |

Nessun dato demo vero e proprio nei seeder (le "Qlik conf" sono solo il
modulo). Nessun seeder esistente modificato.

## Motivazione

Un'istanza nuova senza intervento umano sui server (regola: nessun accesso
diretto) deve nascere completa con un solo comando, senza password note e
senza segreti in variabili d'ambiente/`docker inspect`. Il fail-stop evita che
la restart policy giri a vuoto su un errore che non si risolve da solo.
Alternativa scartata: bootstrap nell'entrypoint (segreti nell'ambiente).

## Test

Ambiente: immagine `docker/Dockerfile.prod` costruita in locale + compose di
prova **fuori dal repo** (scratchpad): MySQL 8.0 usa-e-getta, app (healthcheck
solo qui), scheduler e worker (`depends_on` app healthy), volume nominato per
storage. Nessuna suite di test lanciata. `php -l` OK su tutti i file PHP;
`shellcheck` (via docker) OK sull'entrypoint.

- **a) Avvio a freddo**: migrazione completa (~140 migrazioni) ~76 s, tutto
  healthy in 145 s da `up` (compreso il DB). Scheduler e worker partono dopo,
  **nessuna migrazione nei loro log**, girano come `www-data`. Restart
  dell'app: healthy in 13 s (`Nothing to migrate`, ~1 s di migrate);
  ricreazione: 12 s. Nessun re-bootstrap.
- **b) Migrazione che fallisce** (file di prova montato in compose): errore
  leggibile in `docker logs`, `RestartCount` 0, Apache non parte (curl su
  `/health` senza risposta), salute `unhealthy`, `docker stop` in 0,8 s (exit 143).
- **c)** DB irraggiungibile (`DB_WAIT_TIMEOUT=8`): attesa di 8 s e fail-stop con
  l'errore PDO; APP_KEY mancante: fail-stop con messaggio chiaro; storage non
  scrivibile (tmpfs root): messaggio con `chown -R 33:33`. Sempre RestartCount 0.
- **d) instance:bootstrap**: exit 0 e `LINK=` ultima riga di stdout; seconda
  esecuzione exit 2, stdout vuoto, conteggi identici. Dopo: `cms_users` 1,
  `license` 1, privilegi 1, ruoli 20, menu 1, moduli 20, impostazioni 17,
  template 3 (nessuno slug duplicato), tenant 1, gruppi 1, `users_groups` 1;
  `Hash::check('123456')` falso. Errore a metà (tabella `license` rinominata):
  exit 1, messaggio senza SQL, **tutte le tabelle a zero** (rollback), e dopo
  il ripristino un nuovo bootstrap riesce. Email e UUID non validi: exit 1.
- **e) Link**: GET della pagina reset 200; POST con password di 12+ caratteri
  non comune → redirect al login e `Hash::check` conferma il cambio. Un token
  già usato non funziona più.
- **f) instance:password-link**: nuovo token, quello precedente non funziona
  (POST: password invariata); utente inesistente exit 3; utente inattivo exit 4.
- **g) Token nei log**: nessuno in `laravel.log`, in `cms_logs`, né nei docker
  logs dei servizi **eccetto** le righe dell'access log di Apache quando il link
  viene davvero aperto (vedi sorprese). I link mai aperti non compaiono da nessuna parte.
- **h)** Un controller in `storage/app/custom-controllers` (volume) resta
  caricabile via autoload PSR-4 dopo `restart` e dopo ricreazione (288).

### NON verificato

- **Login completo** dopo il reset: richiede un license server raggiungibile
  (`LicenseHelper::canLicenseLogin()`); provato solo fino alla password cambiata.
- Un modulo creato davvero dal Module Generator via interfaccia (provato un
  file equivalente messo a mano nel volume).
- Cartelle di storage **esistenti ma non scrivibili** (provato il caso "non
  creabili"); bind mount reale con owner sbagliato (usato tmpfs di root).
- SIGTERM/SIGWINCH *durante* una migrazione lunga (provato su fail-stop e
  Apache sano).
- Invio email (Chive CP), TLS/proxy, Docker Hub, altre architetture di CPU,
  MySQL diverso da 8.0, lingue diverse (i nomi dei moduli/impostazioni si salvano
  tradotti con la lingua dell'app al momento del bootstrap).
- Il test di rollback è stato fatto troncando le tabelle del DB di prova (non
  un DB appena migrato identico: mancavano i 2 template MFA creati dalle migrazioni).
- Esecuzione concorrente di due bootstrap (il lock c'è, non l'ho stressato).

## Rischi e note

Sorprese che toccano il Control Plane / il compose di produzione:

1. **`docker stop` manda SIGWINCH**, non SIGTERM (`STOPSIGNAL` di php:apache).
   Gestito nell'entrypoint (trap WINCH) e Apache lo riconosce. Ma
   **scheduler e worker** ignorano SIGWINCH: nel compose serve
   `stop_signal: SIGTERM` su quei due servizi (senza: 137 dopo il timeout; con:
   exit 0 in 1-2 s).
2. **Healthcheck e avvio a freddo**: la prima migrazione dura ~76 s, più dei
   "es. 60 secondi" del blueprint. Nel compose l'healthcheck dell'app ha bisogno
   di `start_period` ampio (provato 300 s; con 5 s l'app risulta unhealthy durante
   la migrazione e `depends_on: service_healthy` fa fallire `up`). Durante lo
   `start_period` un fail-stop appare come `starting`, non `unhealthy`.
3. **Access log di Apache**: quando l'utente apre il link, la GET con il token
   finisce nell'access log (docker logs). Il token è monouso e scade in 60
   minuti, ma chi legge i log in quella finestra lo vede: stesso comportamento
   del reset "password dimenticata".
4. Comportamento preesistente non toccato: `LicenseHelper::writeLicense()`
   scrive la riga `license` (chiave inclusa) in `laravel.log` con `Log::info`
   al primo login.
5. `DatabaseSeeder` + migrazioni producono duplicati (template MFA, modulo Qlik
   Configuration): non toccato, aggirato in `InstanceBaseSeeder`.
6. Il nome "Tenant 1"/"IT" sono segnaposto uguali a quelli dei clienti esistenti.
7. Dopo aver aggiunto la classe `InstanceBaseSeeder` nell'ambiente di sviluppo
   (vendor su volume) serve `composer dump-autoload` (classmap `database/`); nell'immagine di produzione lo fa già la build.
8. Comportamento per i clienti esistenti invariato: i comandi sono nuovi e non
   chiamati da nessuno; nessun file toccato tra seeder, DatabaseSeeder,
   AdminController, flusso forgot/reset, `docker/Dockerfile`, `docker/entrypoint.sh`,
   `docker-compose.yml`, `deploy.yml`. L'unico file esistente modificato è
   `Kernel.php` (due righe) e `Dockerfile.prod` (entrypoint).

## Rollback

Rimuovere i file nuovi, le due righe in `app/Console/Kernel.php` e le righe
ENTRYPOINT/COPY/RUN sed in `docker/Dockerfile.prod`. Nessuna migrazione, nessuna
modifica ai dati dei clienti. Un'istanza già bootstrappata resta valida.
