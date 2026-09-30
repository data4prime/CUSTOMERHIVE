# Piano — provisioning di istanze CustomerHive via Docker

Obiettivo finale: poter creare rapidamente un'istanza CustomerHive isolata
(container app + DB dedicati) raggiungibile su un proprio sottodominio
(`istanzaX.thecustomerhive.com`), sia per demo a potenziali clienti sia per
deploy diretti decisi da noi, con la possibilità di farla poi evolvere in
un ambiente di produzione vero e proprio. Il controllo di tutto il ciclo di
vita (creazione, aggiornamento, chiusura) vive dentro l'applicativo
**LICENSES**.

**Stato**: piano discusso e concordato (16-17/09/2026), nessuna
implementazione ancora iniziata. Le decisioni sotto sono il risultato di
un giro di brainstorming, non sono ancora state validate col codice reale.

## Ispirazione: architettura n8n multitenant

Il 17/09/2026 è stato analizzato un documento tecnico esterno (non in
questo repo) che descrive una piattaforma n8n multitenant su AWS
Lightsail, con una separazione netta tra **control plane** (ha le chiavi),
**data plane** (esegue i container dei tenant) e **portale self-service**
(pubblico, zero chiavi). Diverse idee sono state adottate qui, altre
scartate perché pensate per un rischio che CustomerHive non ha (n8n esegue
codice arbitrario dei tenant per design; CustomerHive esegue solo codice
nostro):

- **Adottato**: separazione control plane/data plane su due macchine,
  canale SSH dedicato a chiave singola, coda job idempotente con recupero
  degli orfani, backup cifrati con chiave che non lascia il control plane
  più copia offsite, limiti di risorse per istanza con margine di
  capacità, conferma testuale sulle eliminazioni, registro unico
  job+audit, pin esplicito della versione di Docker Engine.
- **Scartato/rimandato**: cifratura del volume dati a livello di sistema
  operativo, 2FA obbligatoria e IAM a privilegio minimo su servizi cloud
  (non usiamo AWS), instradamento con alias personalizzato per cliente —
  nessuno di questi risolve un rischio che abbiamo oggi.

## Decisioni architetturali prese

| Aspetto | Decisione |
|---|---|
| Isolamento istanze | container app + DB dedicati per ciascuna istanza |
| Scala v1 | poche decine di istanze |
| **Topologia** | **due macchine separate per livello di fiducia**: il server attuale resta il *control plane* (solo LICENSES), un **nuovo host dedicato** è il *data plane* (Docker, Traefik, tutte le istanze incluse dev/staging) |
| **Canale control plane → data plane** | **SSH dedicata**: chiave ed25519 generata su LICENSES e mai copiata altrove; `docker compose`/Docker API instradati sul data plane tramite quel canale (`DOCKER_HOST=ssh://...` o `docker context`); nessun altro percorso di rete tra le due macchine |
| Lifecycle demo | nessuna scadenza automatica — chiusura sempre manuale |
| Chiusura istanza | due passi separati: "chiudi" (reversibile, ferma i container, tiene i volumi) ed "elimina definitivamente" (irreversibile, rimuove anche i volumi, **richiede di digitare lo slug per confermare**) |
| Aggiornamenti | manuali, **per singola istanza** — niente rollout automatico o in blocco per ora (eccetto `dev`, vedi sotto) |
| Reverse proxy/TLS | **Traefik**, in un **container Docker sul data plane**, unico punto pubblico su 80/443 |
| Instradamento verso le istanze | **provider Docker nativo di Traefik**: ogni istanza porta le proprie label (`traefik.enable=true`, `traefik.http.routers.<slug>.rule=Host(...)`) già nel `docker-compose.<slug>.yml` generato in fase di creazione — Traefik scopre/rimuove la rotta da solo quando il container nasce/sparisce, **nessuna chiamata esplicita di LICENSES per registrare una rotta** |
| Control-plane | dentro **LICENSES** (stesso stack Laravel/PHP), non un servizio a sé |
| LICENSES | resta l'unico componente **nativo su Apache**, sul server originale — è il pezzo con i privilegi più alti (chiavi, SSH verso il data plane), quindi non containerizzato |
| Dev e staging | migrano anche loro su Docker **sul data plane**, usando la stessa immagine/meccanismo delle istanze cliente — non è lavoro a parte |
| **Aggiornamento di `dev`** | l'unico automatizzato: la pipeline CI, dopo aver pubblicato una nuova immagine, chiama un endpoint interno di LICENSES (token segreto, non allowlist IP — i runner GitHub Actions non hanno IP fissi) che accoda lo stesso job di aggiornamento usato per qualunque istanza — nessuna chiave SSH separata data alla CI |
| Migrazione dev/staging | cutover esplicito per dominio: build e test su rotta temporanea, poi si sposta la rotta Caddy del dominio reale solo quando verificato; la versione Apache nativa (sul *control plane*, non più coinvolta come host del container) resta dormiente come rete di sicurezza solo per un periodo di transizione, poi va rimossa |
| Trigger provisioning (creazione nuova istanza) | manuale/interno (bottone in LICENSES) — non esiste ancora una landing page pubblica |
| Registry immagini | **DockerHub** |
| Strategia versioni | **dev continuo** (ogni push su `dev` = nuova immagine + aggiornamento automatico) — **staging e istanze cliente/demo solo su versioni taggate esplicitamente** (rilascio deciso, non ogni commit) |
| Coda job | tabella `jobs` in LICENSES, worker che preleva un job alla volta (`FOR UPDATE SKIP LOCKED`), operazioni idempotenti, job "in esecuzione" da troppo tempo considerati orfani e riaccodati, email agli admin se un job fallisce in modo definitivo |
| Limiti di risorse per istanza | memoria/CPU limitati per container (default da definire in Fase 5), nuove istanze rifiutate se farebbero superare una soglia di margine sulla capacità del data plane |
| Backup | dump cifrato (chiave gestita da LICENSES, mai lasciare il control plane) + copia locale + **copia offsite** su uno storage esterno (provider da scegliere, vedi Fase 5) — **solo per le istanze hosted**: quelle on-premise sono responsabilità di backup del cliente |
| **Modalità di deployment** | due modalità distinte: **hosted** (sul nostro data plane, gestita da LICENSES via SSH, come già descritto) e **on-premise** (su una macchina del cliente, fuori dal nostro controllo) |
| **Comunicazione per le istanze on-premise** | **si inverte**: non SSH in ingresso (impossibile, non è una nostra macchina), ma **chiamata in uscita dall'istanza verso LICENSES** — estensione dello stesso meccanismo già esistente per la verifica licenza (`GetLicense`, orario) |
| **Aggiornamento delle istanze on-premise** | il check-in **segnala solo** che è disponibile una versione più recente (confrontando il tag corrente con l'ultimo rilasciato) — **non lo esegue da solo**: l'aggiornamento vero resta un processo manuale coordinato col cliente, come già oggi per ogni cliente in produzione |

## Perché queste scelte (riferimento rapido)

- **Container+DB dedicato per istanza** invece di riuso del multi-tenant
  applicativo già esistente: isolamento reale, coerente con l'idea di
  sottodomini indipendenti (`istanzaX.thecustomerhive.com`).
- **Traefik**: provider Docker nativo e maturo (a differenza di Caddy, dove
  la stessa cosa richiederebbe un plugin di terze parti o un'Admin API
  pilotata da LICENSES) — è anche lo strumento usato dal riferimento n8n
  per lo stesso identico scenario. Le rotte nascono/muoiono con i
  container stessi (via label), senza una chiamata separata da LICENSES
  per registrarle o rimuoverle — un passo in meno nel flusso di
  creazione/chiusura di un'istanza.
- **Aggiornamenti solo manuali per ora** (tranne `dev`): coerente con la
  regola già seguita per CustomerHive in produzione (ogni modifica va
  verificata, non spinta automaticamente) — si potrà introdurre un
  rollout in blocco più avanti se il numero di istanze lo giustifica.
- **LICENSES nativo**: è il pezzo con i privilegi più alti (le chiavi, la
  SSH verso il data plane), quindi è quello a cui conviene *non*
  aggiungere un ulteriore livello di indirizione (container dentro
  container) solo per uniformità.
- **Due macchine separate per livello di fiducia**: CustomerHive non
  esegue codice arbitrario di terzi come n8n, quindi il rischio di base è
  più basso — ma un'istanza compromessa (bug applicativo, modulo custom di
  un cliente) potrebbe comunque tentare movimento laterale verso
  licensing/staging se condividessero macchina e Docker daemon. Separare
  ora costa un server in più, ma evita di dover migrare tutto più avanti
  sotto pressione quando il numero di istanze sarà già alto.
- **Un solo canale (SSH) tra le due macchine**, usato sia per Docker sia
  per raggiungere l'Admin API di Caddy: meno superfici di rete da
  proteggere, un solo posto dove revocare l'accesso in caso di problemi.
- **Backup cifrati e offsite fin da subito**: il dump lascia il data plane
  in chiaro solo per il tempo di transito sul canale SSH già fidato; viene
  cifrato non appena arriva su LICENSES, prima di toccare qualunque
  storage — così anche una copia offsite compromessa non espone dati.
- **On-premise: check-in che segnala, non che esegue**: un aggiornamento
  automatico e non sorvegliato su un server di produzione che non
  controlliamo è esattamente il tipo di rischio che questo progetto evita
  già per i clienti hosted (ogni modifica in produzione va verificata, non
  spinta da sola) — vale ancora di più quando la macchina è del cliente e
  un errore non è nemmeno ispezionabile da remoto. Il check-in resta quindi
  di sola lettura: ci dice quando è il momento di contattare il cliente per
  programmare un aggiornamento, riusando il processo già esistente
  (duplica DB, copia file custom, vedi [project_client_update_process]).

## Fasi

### Fase 0 — Il nuovo host "data plane" e il canale di controllo

- [ ] Provisionare un nuovo server (Ubuntu, IP proprio), dimensionato in
      base al numero di istanze attese e ai loro limiti di risorse (vedi
      Fase 5)
- [ ] Installare Docker Engine + plugin Compose, **pinnando la versione**
      deliberatamente (documentare quale e perché — non affidarsi a
      "l'ultima disponibile": un aggiornamento di Docker può rompere la
      discovery dei container da parte del proxy, come già successo ad
      altri con questo stesso stack)
- [ ] Generare una chiave SSH dedicata **su LICENSES** (mai copiata
      altrove) per il canale control-plane → data-plane
- [ ] Sul data plane, creare un utente dedicato limitato al necessario per
      gestire Docker (niente accesso root/sudo) e autorizzare quella
      chiave
- [ ] Firewall del data plane: 80/443 pubbliche (Traefik), 22 raggiungibile
      **solo dall'IP del control plane** (+ IP amministrativi), nessun'altra
      porta esposta (DB e servizi interni restano sulla rete Docker interna)
- [ ] Configurare LICENSES per parlare al Docker del data plane attraverso
      quel canale (`DOCKER_HOST=ssh://...` o `docker context create`)
- [ ] **Verifica**: da LICENSES, un comando Docker di prova (es.
      `docker context ls`, `docker ps`) raggiunge correttamente il data
      plane

### Fase 1 — Traefik e rete condivisa sul data plane

- [ ] Creare la rete Docker condivisa (`web`) sul data plane
- [ ] Far girare Traefik come container: porte 80/443 pubblicate, provider
      Docker abilitato (accesso in lettura al socket Docker locale del
      data plane — nessuna esposizione di rete nuova, resta sullo stesso
      host), dashboard/API di Traefik **non esposta pubblicamente**
- [ ] Volumi persistenti per lo storage ACME (certificati Let's Encrypt) —
      altrimenti un redeploy di Traefik richiederebbe di riemettere tutti
      i certificati da zero
- [ ] Template delle label da applicare a ogni istanza (dominio, entrypoint
      HTTPS, resolver del certificato) — usato dal generatore di
      `docker-compose.<slug>.yml` in Fase 5
- [ ] **Verifica**: un container di prova con le label giuste viene
      instradato automaticamente non appena parte, senza alcuna azione
      manuale su Traefik

### Fase 2 — Immagine Docker di CustomerHive e pipeline

- [ ] Scrivere il `Dockerfile` di produzione, a partire dal setup Docker
      locale già esistente ma reso immutabile/distribuibile (non pensato
      solo per sviluppo) — `composer install --no-dev` gira **durante la
      build**, non più sul server; l'immagine non contiene `.env`, le
      variabili arrivano da fuori al momento dell'avvio del container
- [ ] Decidere esplicitamente cosa **non** entra nell'immagine e resta su
      volumi esterni: `storage/`, eventuali controller/moduli custom
      cliente — altrimenti un aggiornamento immagine li cancellerebbe
- [ ] Creare l'account/organizzazione DockerHub e i secret necessari in
      GitHub Actions (`DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`)
- [ ] Aggiungere alla pipeline (`.github/workflows/deploy.yml`) un nuovo
      job `build-and-push`, dopo `tests` e prima di qualunque deploy:
      builda l'immagine e la pubblica su DockerHub con due tag — uno
      "rolling" (es. `dev-latest`, sempre sovrascritto) e uno immutabile
      (es. `dev-<sha>`) per poter risalire a una build precisa
  - su push a `dev`: tag `dev-latest` + `dev-<sha>` (ogni push, automatico)
  - su un tag/release git: tag semver (es. `1.6.0`) — solo queste vengono
    usate da staging e dalle istanze cliente/demo, mai i build continui di
    `dev`
- [ ] Aggiungere in LICENSES l'endpoint interno che la pipeline chiama per
      accodare l'aggiornamento di `dev` dopo ogni build (token segreto in
      un header, confrontato a tempo costante, rate limit dedicato — non
      allowlist IP, i runner GitHub Actions non hanno IP fissi)
- [ ] Le migration **non girano mai durante la build** — solo dopo, a
      container già avviato (vale per ogni ambiente, dev incluso)
- [ ] **Verifica**: un container avviato da questa immagine con un DB
      vuoto si comporta come un'installazione pulita (login, attivazione
      licenza, dashboard)

### Fase 3 — Migrazione di dev su Docker (pilota a basso rischio)

- [ ] Comporre il `docker-compose` per dev usando l'immagine della Fase 2,
      pensato per girare **sul data plane**
- [ ] Importare DB e storage esistenti di dev nel nuovo container (stesso
      procedimento già usato per aggiornare un cliente: duplica DB, copia
      file)
- [ ] Farlo girare su una rotta di test temporanea, **non** ancora
      `dev.thecustomerhive.com`
- [ ] Giro di verifica manuale completo (login, moduli, licensing, tutto
      quello che gira su dev oggi)
- [ ] Collegare il job `deploy-dev` della pipeline all'endpoint interno di
      LICENSES creato in Fase 2, invece di SSH diretta da GitHub Actions al
      server: LICENSES accoda lo stesso job usato per aggiornare
      qualunque istanza (pull immagine, restart, migration, smoke test)
- [ ] Cutover: spostare `dev.thecustomerhive.com` dal backend Apache al
      container Docker sul data plane (basta applicare la label Traefik
      con quel dominio e far partire il container — nessuna configurazione
      di rotta separata)
- [ ] Lasciare l'Apache nativo di dev (sul *control plane*) dormiente
      (non cancellarlo subito) come rete di sicurezza per un periodo di
      transizione
- [ ] **Decommissioning**: una volta consolidata la fiducia nella versione
      Docker (nessun problema emerso dopo un periodo di uso reale),
      rimuovere il vhost Apache nativo di dev — non deve restare lì
      indefinitamente "perché non si sa mai"

### Fase 4 — Migrazione di staging

Stesso schema della Fase 3 (incluso il decommissioning finale del vhost
Apache nativo), con più cautela essendo un ambiente più delicato — deploy
oggi ancora manuale via FTP secondo [project_cicd_plan]. Anche staging
gira sul data plane, aggiornato però solo su versioni taggate (mai
`dev-latest`).

### Fase 4b — Il control plane serve solo LICENSES

- [ ] Verificare che, una volta completato il decommissioning di dev e
      staging (Fasi 3 e 4), l'unico servizio rimasto sul server originale
      sia LICENSES stesso
- [ ] Ripulire configurazione Apache residua (vhost, certificati, cron)
      relativa a dev/staging non più in uso su quella macchina

### Fase 5 — Le funzionalità di provisioning in LICENSES

- [ ] Migration per le nuove tabelle: inventario istanze (slug, dominio,
      tag immagine corrente, stato demo/chiusa/eliminata, flag
      auto_update, license_key, timestamp), `jobs` (tipo, stato, tentativi,
      errore, autore, origine), `audit_events` (azione, autore, tenant, IP)
- [ ] Worker della coda job: preleva un job alla volta (`FOR UPDATE SKIP
      LOCKED`), job "in esecuzione" da più di N minuti considerati orfani
      e riaccodati, operazioni idempotenti, email agli admin quando un job
      esaurisce i tentativi
- [ ] Definire i limiti di risorse per container (memoria/CPU, default
      ragionevole) e una soglia di margine sulla capacità del data plane:
      richieste di nuove istanze oltre soglia vengono rifiutate con un
      messaggio esplicito, non silenziosamente accodate
- [ ] **Creazione istanza**: form → job in coda → genera
      `docker-compose.<slug>.yml` **con le label Traefik già incluse** →
      `docker compose up -d` (sul data plane, via SSH) → Traefik instrada
      il dominio da solo, non appena il container è su → attende health
      check → migration nel container nuovo → genera licenza demo per il
      dominio → stato `attiva`
- [ ] **Stop istanza** (reversibile): dump di sicurezza del DB (via SSH,
      streaming) → `docker compose down` (senza rimuovere i volumi — la
      rotta Traefik sparisce da sola insieme al container) → disattiva la
      licenza → stato `chiusa`
- [ ] **Eliminazione definitiva** (irreversibile, solo su istanze già
      `chiusa`, **richiede di digitare lo slug per confermare**):
      `docker compose down -v` → rimozione file compose generato → libera
      lo spazio disco
- [ ] **Aggiornamento manuale per singola istanza**: pull nuovo tag
      immagine → stop vecchio container → start nuovo → migration → smoke
      test → aggiorna versione registrata nell'inventario (se fallisce,
      stato `update_failed`, nessun tentativo di auto-fix)
- [ ] **Backup cifrati**: il dump (`docker compose exec db mysqldump ...`
      sul data plane) viene incanalato direttamente in `openssl enc` sul
      lato LICENSES — mai scritto in chiaro su disco — con una chiave
      simmetrica tenuta in `.env` di LICENSES (`BACKUP_ENCRYPTION_KEY`,
      generata con `openssl rand`, **rotazione non prevista
      deliberatamente**: romperebbe la lettura dei backup già cifrati,
      stesso principio adottato dal riferimento n8n)
- [ ] **Copia offsite**: push del file già cifrato verso uno storage
      esterno — **provider ancora da scegliere** (non usiamo AWS oggi;
      opzioni: un bucket S3-compatibile di terzi, un secondo server
      dedicato solo ai backup) — decisione da prendere prima di
      implementare questo punto, non blocca il resto della fase

### Fase 6 — Prima istanza reale end-to-end

- [ ] Creare una prima istanza di prova con l'intero meccanismo (non una
      demo vera per un prospect) e verificarla a fondo prima di usarla sul
      serio

### Fase 7 — Istanze on-premise (macchine del cliente)

Per le istanze che vivono su infrastruttura del cliente, LICENSES non ha
SSH né alcun accesso diretto — il modello si inverte: è l'istanza a
chiamare fuori, non il contrario.

- [ ] Aggiungere `deployment_type` (`hosted`/`on_premise`) all'inventario
      istanze
- [ ] **Generazione pacchetto** invece di provisioning remoto: LICENSES
      crea la licenza come oggi, poi genera uno `docker-compose.yml` +
      `.env` precompilati (tag immagine di release, licenza già inserita)
      scaricabili — il cliente (o chi fa l'installazione per suo conto)
      esegue `docker compose up -d` sulla propria macchina
- [ ] **Estendere il comando schedulato esistente** (`GetLicense`, oggi
      solo verifica licenza) per riportare anche la versione dell'immagine
      in uso, letta da una variabile d'ambiente impostata alla creazione
      del container
- [ ] LICENSES risponde al check-in con: esito validazione licenza (come
      oggi) + se è disponibile una versione più recente rilasciata — **non
      innesca alcun aggiornamento automatico**, solo lo segnala nel
      pannello (es. "istanza X: 2 versioni indietro, ultimo check-in 3h fa")
- [ ] L'endpoint di check-in resta **pubblico per necessità** (qualunque
      installazione, ovunque, deve poterlo raggiungere) ma distinto e più
      ristretto del pannello admin (solo licenza + versione, nessuna delle
      azioni distruttive disponibili da lì)
- [ ] Documentare esplicitamente il confine di responsabilità: i backup
      delle istanze on-premise **restano a carico del cliente** — LICENSES
      non vi ha accesso, quindi non può farli
- [ ] **Verifica**: un'istanza on-premise di prova (anche solo in una VM
      locale) si registra, passa la verifica licenza, e il pannello mostra
      correttamente il suo stato di aggiornamento

## Rimandato apposta (non bloccante per la v1)

- **Nessuna scadenza automatica delle demo** — chiusura sempre a comando
  manuale.
- **Nessun rollout in blocco automatico** per le istanze cliente/demo —
  solo aggiornamento manuale per singola istanza (l'unica eccezione è
  `dev`, automatizzata perché non ha dati di clienti reali); da
  riconsiderare se il numero di istanze cresce abbastanza da rendere
  insostenibile farlo una per una.
- **Provider di storage offsite per i backup** — deciso "sì" al
  principio, non ancora al fornitore: vedi Fase 5.
- **Landing page pubblica** — non esiste ancora; se/quando arriverà, il
  trigger di provisioning (oggi manuale/interno) andrà rivisto con
  attenzione alla sicurezza (endpoint pubblico che può creare risorse) —
  sul modello del "portale" del riferimento n8n: il portale chiede, non
  possiede chiavi, LICENSES esegue da una whitelist chiusa.
- **Test CI su MySQL vero invece di SQLite**: la CI oggi usa SQLite
  in-memory per velocità, mentre l'ambiente Docker locale usa
  deliberatamente MySQL vero per fedeltà alla produzione — disallineamento
  preesistente, non causato da questo piano. Potrebbe valere la pena
  chiuderlo (servizio MySQL in GitHub Actions) ora che ci si avvicina di
  più a "build fedele alla produzione", ma non è un requisito di questo
  piano.
- **Personalizzazioni cliente su un'istanza diventata cliente vero**: se
  un'istanza demo/deploy diretto evolve in cliente con moduli custom, quei
  file vanno su volumi separati dall'immagine — il meccanismo va disegnato
  quando ci si arriva concretamente, non è bloccante per la v1 (le demo
  nascono "pulite", senza personalizzazioni).
- **Cifratura del volume dati a livello di sistema operativo** sul data
  plane — presente nel riferimento n8n come rischio residuo accettato
  anche lì; per noi ancora meno prioritario dato il rischio di base più
  basso.

## Riferimenti

- [`cicd-pipeline.md`](cicd-pipeline.md) — pipeline attuale (solo
  `git pull` su dev, nessuna immagine Docker ancora pubblicata)
- [`docker-local-dev.md`](docker-local-dev.md) — setup Docker locale da
  cui partire per il `Dockerfile` di produzione
- [`login-e-licensing.md`](login-e-licensing.md) — come funziona oggi
  l'attivazione licenza (flusso pensato per un umano via UI, da rendere
  programmatico per la creazione automatica di licenze demo)
- Documento tecnico esterno "Piattaforma n8n multitenant" (17/09/2026,
  non in questo repo) — architettura di riferimento per la separazione
  control plane/data plane, la coda job e il modello di backup cifrati
