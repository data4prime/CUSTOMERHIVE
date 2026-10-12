# 300 - Prova generale end-to-end di un'istanza Docker (immagine + LICENSES), in locale

- **Data**: 2026-10-09
- **Stato**: Completato. La prova non ha modificato codice; i problemi trovati sono stati corretti dopo, vedi [301](301-correzioni-dopo-prova-generale-licenza-i18n-blueprint.md) e LICENSES `014`
- **Area**: Docker / Provisioning / Licensing
- **File/aree di codice coinvolte**: nessuna. File di prova **fuori dai repo**, in
  `C:\Users\marius.donici\Documents\D4P\prove-chive-cp\acme-test\` (vedi "File di prova").

## Contesto

Immagine di produzione (297-299) e servizio `ManagedLicenseService` di LICENSES (ramo `chive-cp`,
010/012/013) erano stati provati **separatamente**. Questa prova fa a mano ciò che Chive CP farà
in automatico: licenza Managed → compose/.env → `up` → `instance:bootstrap` → link → login contro
LICENSES reale → negativi di licenza → scheduler/worker → scritture con codice in sola lettura →
persistenza → backup/ripristino → aggiornamento (anche fallito) → misure → segreti nei log.
Progetto Docker dedicato `acme-test` (container `acme-test-*`, rete e volumi `acme-test_*`):
i container di sviluppo (`customerhive_*`) non sono stati toccati.

## Situazione prima

Nessuna evidenza che l'insieme funzionasse: in 299 il login completo era "NON verificato" (serve un
license server raggiungibile) e il blueprint conteneva ipotesi mai misurate (attesa di salute 60 s,
comando del worker, backup/ripristino, licenza scaduta).

## Situazione dopo

**Esito sintetico: funziona end-to-end** (login, licenza, scheduler, worker, module builder, upload,
persistenza, backup/ripristino, aggiornamento), **ma con 2 bug seri sul ciclo di vita della licenza
(scadenza) e diverse correzioni da fare al blueprint** (elenco sotto). Niente da correggere nel
"golden path" di creazione istanza.

### Tabella dei passi

| Passo | Esito | Note essenziali |
|---|---|---|
| Prep. 1 LICENSES su | OK | `licenses-app-1`/`licenses-db-1`, :8000, 14 migrazioni applicate, rotte `api-license/license-server/*` rispondono (422 su body vuoto). Raggiungibile dal container come `http://host.docker.internal:8000` |
| Prep. 2 build immagine | OK | `customerhive:prova` (APP_VERSION=prova-1). Contiene solo `app artisan bootstrap composer.* config database public resources routes storage vendor`; `app/Http/Controllers` = solo `Auth CBHook.php Controller.php System` (i ~55 controller custom locali **esclusi**); nessun `.env*`. 889 MB su disco (775 MB layer base condivisi), 202 MB di contenuto |
| Prep. 3 licenza Managed | OK | Licenza id 54, `domain=acme-test.thecustomerhive.com`, `path=acme-test`, 3 tenant / 25 utenti, scadenza +1 anno, modulo `Qlik`. **`ChatAI` non esiste** nella tabella `modules` di LICENSES (i 7 moduli esistenti sono n8n, CRM, Fatturazione, Report Avanzati, Multi-tenant, API Gateway e `Qlik`): `isActiveChatAI()` non può essere vera finché non lo si crea |
| Prep. 4 compose/.env/template | OK | vedi "File di prova"; `docker compose config` valido |
| **A** Avvio | **OK** | `up -d` → tutto in esecuzione in **65 s** (db healthy 25 s, migrazione 35 s, Apache+health 4 s). Scheduler/worker partiti dopo l'app, **nessuna migrazione nei loro log**. Nessun errore/Permission denied/read-only in nessun log |
| **B** instance:bootstrap | **OK** | exit 0, ultima riga stdout `LINK=http://localhost:18080/admin/reset-password/<64 hex>?email=referente%40acme-test.example`; stderr = 4 righe di avanzamento. Seconda esecuzione: exit 2, stdout vuoto, nessuna modifica (utenti 1, licenza 1, moduli 20) |
| **C** Password e login | **OK** | GET link 200 (0,14 s); POST reset 302→/admin/login; login 302→/admin→/admin/statistic_builder/dashboard 200. **Login lento: 6–12 s** (4 chiamate al license server da ~0,7 s) |
| **D** Licenza | **OK** | `api_request_logs`: per ogni login 2×`auth/login` (200) + 2×`license` (200, caller `License #54`). `storage/app/license.json` presente (www-data), riga `license` presente. `isActiveQlik()`=true, `isActiveChatAI()`=false (coerente: solo Qlik). **Dominio: ha passato il confronto ESATTO** (`license.domain == APP_DOMAIN`, entrambi `acme-test.thecustomerhive.com`); la prima etichetta (`acme-test`) **non** combacia, quindi il ripiego non è stato usato |
| **E** Negativi | **KO parziale** | scaduta: **BUG** (vedi sotto); revocata, dominio diverso, chiave errata: OK (login bloccato, 401 dal server); renew/ripristino: accesso torna **solo** dopo aver re-registrato la chiave (scaduta) o fatto restore+renew (revocata) |
| **F** Scheduler/worker | **OK con 1 correzione** | fuso Europe/Rome confermato (`schedule:list`, "Next Due" coerente). `command:GetLicense` forzato: `auth/login`+`license` 200 in LICENSES. Worker: due closure (coda `qlik_sync` e `mail`) eseguite entrambe in 8 ms. **La forma del blueprint `queue:work qlik_sync,mail` NON funziona** (vedi problemi). `stop` scheduler+worker 1,3 s, exit 0/0; stop app 2,2 s, exit 0 |
| **G** Scritture, codice RO | **OK** | (1) modulo creato **dal wizard nel browser integrato** (passi 1-5, via DOM/fetch): `AdminProvaAcmeController.php` nato in `storage/app/custom-controllers` (www-data 0644), rotta `/admin/mg_prova_acme` 200 **subito**, insert di un record OK; (2) logo caricato dalle Impostazioni → `storage/app/public/uploads/2026-10/<hash>.png`, `GET /storage/uploads/...` 200 `image/png`; (3) ricerca log "Permission denied / failed to open stream / read-only / ErrorException": **0 occorrenze** in app/scheduler/worker/db |
| **H** Persistenza | **OK** | `stop`+`start` (13 s) e `down`+`up -d` senza `-v` (18 s): conteggi tabelle e checksum dei file **identici** (snap prima/dopo), `Nothing to migrate`, nessun nuovo bootstrap, login ok, modulo custom 200, upload 200 |
| **I** Backup/ripristino | **OK con correzione** | coppia cifrata (`.sql.gz.enc` 11 KB + `.storage.tar.gz.enc` 5,8 KB, stesso prefisso con data/ora) in 3 s; funziona con il DB `acme-test_db` (trattino). Ripristino come da blueprint (21 s): dati e file tornano, **ma resta una tabella orfana** (`mg_seconda_prova`, creata dopo il backup). Con `DROP/CREATE DATABASE` prima del reimport (28 s): stato **identico** al backup (53 tabelle, checksum file uguali) |
| **J** Aggiornamento | **OK** | prova-2 costruita in 29 s (cache). Sequenza del blueprint (backup → stop scheduler/worker → cambio tag → `up -d --no-deps app` → healthy + smoke `/health` e `/admin/login` 200 → `up -d --no-deps scheduler worker`): **22 s** totali, footer `prova-2`, nulla perso. Variante fallita: fail-stop confermato (vedi sotto), ripristino dalla coppia in 28 s, istanza di nuovo sana |
| **K** Misure | **OK** | vedi "Risorse" |
| **L** Segreti nei log | **OK, con rilievi** | vedi "Segreti nei log" |

### Dettaglio dei tempi (per dimensionare le attese di Chive CP)

- Prima partenza da zero (volumi vuoti): **65 s** (questa macchina) — in 299 erano 145 s totali (migrazione 76 s): il range realistico è 65-145 s.
  Sequenza misurata: db healthy dopo ~25 s (init MySQL), `migrate` 35 s (140 migrazioni), Apache pronto e `healthy` dopo ~4 s.
  Il "es. 60 secondi" del blueprint è **insufficiente** (al limite nel caso migliore).
- `docker compose up -d` **blocca** fino a che l'app è healthy (per le `depends_on`): è un comando da 65+ s, il timeout SSH di Chive CP deve coprirlo.
- Riavvio app (stop → healthy): 8 s. `stop`+`start` di tutto: 13 s. `down`+`up`: 18 s.
- Migrazione fallita: container `Up`, `RestartCount 0`, log `[entrypoint] ERRORE: php artisan migrate --force e' fallito`, `curl /health` senza risposta.
  **La salute resta `starting` per tutto lo `start_period` (240 s)**: è diventata `unhealthy` dopo **4 min 35 s** (14:39:19 → 14:43:54).
  `up -d scheduler` (con dipendenze) a quel punto fallisce subito: `dependency failed to start: container acme-test-app-1 is unhealthy`.
  Scheduler e worker, fermati prima, **restano fermi**. La tabella creata a metà dalla migrazione (`prova_parziale`) resta nel DB: il ripristino con `DROP DATABASE` la elimina.

### Risorse a riposo (dopo login e una pagina; `docker stats --no-stream`)

| Container | CPU | RAM | PID |
|---|---|---|---|
| app (Apache prefork, 1 pagina servita) | 8 % (picco appena dopo la richiesta) | 67 MiB | 7 |
| db (MySQL 8.0) | 3 % | 417 MiB | 41 |
| scheduler | 0,2 % | 40 MiB | 1 |
| worker | 0 % | 40 MiB | 1 |
| **Totale** | | **~565 MiB** | |

Volumi: `storage` 412 KB (nuova istanza + 1 modulo + 1 logo), `db-data` 99 MB su disco (dati logici 1,3 MB). Immagine 889 MB (di cui 775 MB condivisi tra istanze sullo stesso host), `mysql:8.0` 223 MB.
La RAM dell'app **cresce con i worker Apache** e `memory_limit=512M` per richiesta: un limite per istanza ragionevole di partenza è ~1 GiB per app, 512-768 MiB per db, 128-256 MiB per scheduler e worker (da verificare sotto carico: non misurato).

### Segreti nei log

- **license_key**: compare **28 volte per ogni login** in `docker logs app` (non nel `laravel.log`: `LOG_CHANNEL=stderr`). Esempi (oscurati):
  `[2026-10-09 14:44:46] production.INFO: {"id":2,"license_key":"<LICENSE_KEY>"}` (da `LicenseHelper::writeLicense`) e, nel dump dell'array di risposta, `'license_key' => '<LICENSE_KEY>',`. Stesso comportamento dell'hourly `GetLicense` nello scheduler. È il punto 4 di 299, ora quantificato.
- **Token del link di reset**: compare **una volta** nell'access log di Apache quando il link viene davvero aperto:
  `172.20.0.1 - - [09/Oct/2026:12:45:37 +0000] "GET /admin/reset-password/<TOKEN>?email=referente%40acme-test.example HTTP/1.1" 200 ...`. Non compare in nessun altro punto, né se il link non viene aperto.
- **Password** (quella impostata dall'utente): 0 occorrenze in tutti i log. L'access log non registra i body POST.
- **access token del license server**: 0 occorrenze nei log dell'istanza.
- `license.json` contiene la `license_key` in chiaro e sta nel volume `storage` (quindi anche nel backup, che è cifrato).
- **Lato LICENSES** (`api_request_logs`): `payload` con `license_key` in chiaro, e la `response` dell'auth/login contiene `access_token` in chiaro (l'header `authorization` è mascherato, il resto no).
- `docker logs` si **perdono ad ogni ricreazione** del container (aggiornamenti, `up` dopo cambio config): se i log devono servire a posteriori, Chive CP deve raccoglierli prima.

## Problemi trovati (causa e correzione minima proposta). Stato: **1-7 corretti** in 014 (LICENSES) e 301; 8, 9, 10 non corretti di proposito (vedi 301)

| # | Gravità | Dove | Sintomo | Causa | Correzione minima proposta |
|---|---|---|---|---|---|
| 1 | **Alta** | LICENSES | Licenza scaduta: `POST .../license` risponde **500** (`Integrity constraint violation 1452 ... ls_licenses_created_by_foreign`, SQL `update ls_licenses set created_by = 54`). Il blueprint dice che "un login con licenza scaduta riesce comunque e l'app legge lo stato": **falso oggi** | `LicenseValidationController::licenseValidate` fa `$l->status='inactive'; $l->save()`; il listener `saving` di `License` (riga 35) imposta `created_by = auth()->id()`, che nella richiesta autenticata con token è l'id **della licenza** (54), non di un utente → violazione della FK. Preesistente: sulle licenze legacy passa solo quando l'id licenza coincide per caso con un id utente | Usare `saveQuietly()` in quel punto (come già in `renew`/`revoke`) oppure far impostare `created_by` al listener solo se `auth()->user() instanceof User` |
| 2 | **Alta** | CUSTOMERHIVE | Dopo un login con licenza scaduta (o con **qualunque risposta del server senza licenza, 5xx incluso**) la riga `license` dell'istanza viene **cancellata**. Dopo `renew` in LICENSES l'accesso **non torna**: schermata `/admin/register-license` finché non si re-inserisce la chiave. `instance:bootstrap` non aiuta (esce 2: ci sono utenti) | `ConnectorService::writeLicense` cancella `license.json` quando la risposta non contiene `data.id`; `validateLicense` poi non trova il file e fa `DB::table('license')->where('license_key', ...)->delete()` | Cancellare `license.json` solo su risposta definitiva (404/`success:false`), **mai su 5xx**; non cancellare mai la riga `license` (la chiave è configurazione, non cache). In subordine: un comando `instance:license-key --key=...` per ri-registrare la chiave dopo un rinnovo (oggi si può solo dalla schermata "Ho già una licenza", **raggiungibile senza login**, o via SQL) |
| 3 | Media | CUSTOMERHIVE | Il worker del blueprint non parte | `queue:work` prende come primo argomento la **connessione**: `queue:work qlik_sync,mail` → `The [qlik_sync,mail] queue connection has not been configured`. Forma corretta (e usata da `docker-compose.yml` di sviluppo): `queue:work qlik_sync --queue=qlik_sync,mail ...` | Correggere il blueprint (e il commento in `config/queue.php` è già giusto) |
| 4 | Media | Blueprint | Il ripristino "come scritto" lascia **tabelle create dopo il backup** (nel test: modulo `mg_seconda_prova`; in un aggiornamento fallito: tabelle create a metà da una migrazione) | `mysqldump` senza `--databases` ricrea le tabelle del dump ma non toglie le altre | Scelta finale (variante B): dump con `--add-drop-database --databases <slug>_db`, reimport senza nome database, verifica di integrità prima del ripristino. Provato (script `restore.sh`), vedi 301 |
| 5 | Media | Blueprint/compose | Il container `app` riceve `MYSQL_ROOT_PASSWORD` (1 variabile in `printenv` e in `docker inspect`) | Un solo `.env` condiviso da db, app, scheduler, worker; il blueprint dice che l'app non si collega mai come root | Due file: `db.env` (solo `MYSQL_*`) per il servizio `db`, `.env` applicativo (senza `MYSQL_ROOT_PASSWORD`) per gli altri tre |
| 6 | Media | CUSTOMERHIVE | Il messaggio mostrato per **tutte** le cause di licenza non valida (scaduta, revocata, dominio, chiave) è lo stesso, **in inglese hardcoded**: `License is missing or not valid` (`AdminController.php:338` e `:489`) e la schermata propone di *creare* una nuova licenza | Stringa non in `trans()` (regola del progetto: en+it) e nessuna distinzione delle cause | Passare da `trans('crudbooster.license_not_valid')` in en+it; (a parte) distinguere almeno "scaduta" da "non valida" |
| 7 | Bassa | Immagine | `php artisan tinker` **non c'è** nell'immagine (dipendenza dev, corretto per produzione) | `composer install --no-dev` | Nel blueprint: per diagnosi usare comandi artisan o script PHP (`php` con `bootstrap/app.php`); niente tinker |
| 8 | Bassa | CUSTOMERHIVE | Login lento: 4 chiamate al license server (2 `auth/login` + 2 `license`) ~0,7 s l'una = 3-6 s; la prima 12 s. Con il Gateway di mezzo sarà peggio | `canLicenseLogin()` forza un nuovo token e poi `isActive*`/`getLicenseInfo` ne richiedono altri | Non urgente; riusare il token nella stessa richiesta |
| 9 | Bassa | Osservazione | Nei moduli generati `created_at` è in ora di Roma (14:32) e `updated_at` in UTC (12:32) | `created_at` scritto dall'app (Europe/Rome), `updated_at` dal default MySQL (container UTC) | Da verificare se è identico in sviluppo (probabile preesistente); non legato a Docker |
| 10 | Bassa | Osservazione | `POST /admin/activate-existing-license` accetta la chiave **senza login** | Rotta pubblica (serve a risolvere la schermata licenza) | Nota di sicurezza; richiede di conoscere la chiave |

## Cosa cambia nel BLUEPRINT dopo questa prova

1. **Worker**: `php artisan queue:work qlik_sync --queue=qlik_sync,mail --sleep=3 --tries=1 --timeout=3600` (sezione "contratto dell'immagine").
2. **Attesa di salute**: non "es. 60 secondi": prima partenza 65-145 s; massimo di attesa **300 s**. `up -d` è bloccante (le `depends_on` aspettano l'app): timeout SSH adeguato.
3. **`start_period`** dell'healthcheck app = **240 s** nel compose (scritto), e annotare che un fail-stop resta `starting` fino a fine `start_period` (4 min 35 s misurati): per riconoscerlo prima, Chive CP deve leggere l'ultima riga di `docker logs <slug>-app-1` (`[entrypoint] ERRORE:`) e non aspettare solo `unhealthy`.
4. **`stop_signal: SIGTERM`** su scheduler e worker: provato (exit 0 in 1,3 s). Va nel compose generato.
5. **Dominio**: confermata la convenzione (domain = host completo = `APP_DOMAIN`, path = slug = `APP_PATH`): passa il confronto **esatto**, il ripiego "prima etichetta" non serve.
6. **Formato `license.json`** (sezione Gateway): la risposta reale contiene anche **`license_key`** (in chiaro) e `modules[]` con `created_at/updated_at/pivot`; non c'è `ip_check`. Aggiornare l'esempio.
7. **Moduli**: la licenza deve usare i nomi che l'app cerca: **`Qlik`** e **`ChatAI`** (maiuscole comprese). In LICENSES esiste `Qlik` ma non `ChatAI`: creare `ChatAI` nella tabella `modules` prima di offrirlo.
8. **Scadenza/rinnovo** (sezione licenza Managed): "Rinnova" **non basta** finché i bug 1 e 2 non sono corretti: dopo la scadenza l'istanza perde la riga `license` e va re-registrata la chiave. Rimuovere/riscrivere la frase "un login con licenza scaduta riesce comunque".
9. **Revoca**: `revoke` blocca i **nuovi** login (401), non le sessioni già aperte, e lascia `license.json` obsoleto nell'istanza; "Ripristina" una licenza revocata = restore + `renew`.
10. **Backup**: i comandi funzionano con il database `<slug>_db` anche con il trattino (nessun backtick necessario nei comandi `mysqldump`/`mysql`, solo in `DROP/CREATE DATABASE`). Differenze locali: volume nominato `<slug>_storage` al posto del percorso host. Aggiungere: la chiave con `-k` finisce nella lista processi → preferire `-pass env:BACKUP_ENCRYPTION_KEY`; verificare la coppia decifrando e controllando l'archivio (`tar -tz`) prima di dichiararla valida.
11. **Ripristino**: aggiungere il passo `DROP DATABASE` + `CREATE DATABASE` + `GRANT` prima del reimport (problema 4); il tag dell'immagine ripristinato è quello precedente (qui il compose è rimasto su `prova-2`: va riscritto dal fascicolo dell'istanza).
12. **Env separati** (problema 5): `db.env` e `.env` applicativo.
13. **Log**: dichiarare che `docker logs` contiene la `license_key` (28 righe per login) e il token di reset (1 riga se aperto), e che i log si perdono alla ricreazione.
14. **Diagnostica**: niente tinker nell'immagine (problema 7).
15. **Compose**: la mappatura `ports` serve solo in prova; in produzione solo rete Traefik. L'immagine/tag va in **un solo punto** (`x-app-common`) grazie all'ancora YAML: l'aggiornamento è un `sed` su una riga.

## File di prova (modello per il codice di Chive CP)

Cartella `C:\Users\marius.donici\Documents\D4P\prove-chive-cp\acme-test\`:

- `docker-compose.template.yml` — **modello** con segnaposto `<slug> <image> <host_port> <storage_source> <db_source>`
- `env.template` — **modello .env** con segnaposto e indicazione di cosa è **generato**, **derivato** o **fisso**
- `docker-compose.yml`, `.env` — l'istanza `acme-test` istanziata (valori finti)
- `backup.sh`, `restore.sh [--drop]`, `snap.sh`, `login.sh`, `ph.sh` — script usati nella prova (backup/ripristino cifrati, istantanea di stato, login curl, PHP dentro il container)
- `docker-compose.fail.yml` + `failing/` — override con la migrazione che fallisce di proposito
- `backups/` — le coppie cifrate create, `backup.key` (chiave finta), `risultati/` (log, istantanee)

**Valori che Chive CP deve GENERARE per istanza**: `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD`/`DB_PASSWORD` (alfanumerici, niente `$`), `APP_KEY` (`artisan key:generate --show` con l'immagine), `BACKUP_ENCRYPTION_KEY` (solo sul record Instance); **DERIVATI**: `APP_DOMAIN`, `APP_PATH`, `APP_URL` (https), nome db/utente db; **per data plane**: `LICENSE_SERVER_URL` (Gateway), `TRUSTED_PROXIES` (CIDR Traefik), sorgenti dei volumi; **FISSI**: tutto il resto della tabella del contratto.

## Motivazione

Scoprire dove si inceppa il sistema **prima** di scrivere Chive CP. La prova ha trovato due bug sul ciclo di vita della licenza (1, 2), un comando del blueprint sbagliato (3) e un ripristino incompleto (4) che altrimenti sarebbero emersi in produzione.

## Test

Eseguiti a mano come in tabella (nessuna suite lanciata, nessun repository modificato). Verifiche a campione dei log di LICENSES (`api_request_logs`) per ogni passo di licenza; il wizard del module builder è stato pilotato nel **browser integrato** (login, passi 1-5, aggiunta record, upload logo via `fetch` multipart). Il passo 5 del wizard e il caricamento del logo sono stati inviati con DOM/`fetch` (non cliccando nella pagina: il pannello del browser era nascosto).

### NON verificato

- **HTTPS/Traefik** (label, redirect, header `X-Forwarded-*` reali, `TRUSTED_PROXIES` con CIDR), **Gateway** (latenza aggiunta, `ip_check` con IP reali, relay `/license-server/*`), **SMTP vero** (nessuna email inviata), **Data Plane** (`docker --context ... ssh://`), pull da Docker Hub privato.
- **Host Linux con bind mount**: proprietario 33:33 di `storage/` (qui volumi nominati su Docker Desktop/Windows), permessi di `db-data`.
- Carico (RAM/CPU sotto traffico), più istanze in parallelo, `ChatAI` (modulo assente in LICENSES), primo `up -d` con fail-stop (il blocco di 4+ minuti è dedotto, non cronometrato), SIGTERM durante una migrazione lunga, concorrenza di due bootstrap.

## Rischi e note

- La licenza di prova (id 54) e l'istanza `acme-test` sono **lasciate in piedi** per ripetere la prova; la licenza ha ricevuto, durante la prova, scadenza nel passato → `renew`, revoca → `restore`+`renew`: oggi è attiva con scadenza +1 anno.
- Immagini rimaste: `customerhive:prova`, `prova-2`, `prova-3` (con le correzioni di 301); il compose punta a `prova-3`.
- `docker-compose.yml` dell'istanza punta ora a `customerhive:prova-2`.

### Comandi per rimuovere tutto

```bash
# istanza (container, rete) e volumi
docker compose -p acme-test -f C:/Users/marius.donici/Documents/D4P/prove-chive-cp/acme-test/docker-compose.yml down -v
# immagini di prova
docker rmi customerhive:prova customerhive:prova-2 customerhive:prova-3
# cartella di prova
rm -rf C:/Users/marius.donici/Documents/D4P/prove-chive-cp
# licenza di prova in LICENSES (revoca = soft delete; per cancellarla davvero: forceDelete)
cd C:/Users/marius.donici/Documents/D4P/LICENSES && docker compose exec -T app php artisan tinker --execute='$l=\App\Models\License::withTrashed()->find(54); \App\Services\ManagedLicenseService::revoke($l); $l->forceDelete();'
```

## Rollback

Nessuna modifica al codice da annullare: basta rimuovere l'istanza e la licenza di prova con i comandi sopra.
