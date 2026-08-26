# Come è stata costruita la pipeline CI/CD (cronologia)

Log dei passaggi fatti per arrivare alla pipeline descritta in
[`cicd-pipeline.md`](cicd-pipeline.md). Utile come riferimento se in futuro
si deve rifare lo stesso percorso per staging o per un altro ambiente.

## 1. Branch `dev`

Creato dal branch `main` esistente e pushato su `origin/dev`. Da quel
momento il lavoro locale/quotidiano avviene su `dev`, `main` resta il
branch verso cui si promuoverà in futuro (staging).

## 2. Provisioning e primo deploy manuale del server

Server `dev.thecustomerhive.com` provisionato (già esistente su un host
condiviso insieme a staging, al license server e ad altri siti cliente).
Primo deploy fatto **a mano** seguendo
[`deploy-manuale-server.md`](deploy-manuale-server.md): clone del branch
`dev`, `.env`, `composer install`, migration, seed. Login verificato
funzionante prima di passare all'automazione.

## 3. Sistemata la CI (prima non eseguiva mai i test)

Il workflow esistente (`.github/workflows/deploy.yml`, nome "Run tests") si
fermava dopo `composer install`/`.env`/`key:generate`: non lanciava mai
`phpunit`. Prima di collegarci un deploy automatico, sistemato:

- Aggiunto lo step `php artisan test`.
- Aggiunto `dev` tra i branch che triggerano il workflow (prima solo `main`).
- Verificati i test esistenti: **nessuno era mai stato eseguito davvero**.
  Due non venivano nemmeno raccolti da PHPUnit (nome file/posizione non
  conformi alla configurazione in `phpunit.xml`), due fallivano per bug
  propri (un `use` con namespace sbagliato, un mock non più coerente col
  codice reale). Rimossi tutti, sostituiti con un test minimo
  (`tests/Unit/ExampleTest.php`) senza dipendenze da database, solo per
  validare che la pipeline funzioni.
- Fix successivo: rimuovendo l'unico file in `tests/Feature/`, la cartella
  (vuota) spariva dal checkout in CI (git non traccia cartelle vuote) e
  PHPUnit falliva perché `phpunit.xml` la referenzia ancora. Aggiunto un
  `.gitkeep`.

## 4. Chiave SSH per il deploy automatico (GitHub Actions → server)

Generata una coppia di chiavi dedicata (non quella personale). Primo
tentativo di connessione fallito (`Permission denied`) perché la chiave
pubblica non era ancora installata sul server per l'utente giusto —
scoperto durante il test che l'utente da usare era `ubuntu`, non `deploy`
come da guida originale (nessun utente dedicato creato, per velocità — vedi
TODO in [`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md)). Una volta
installata la chiave pubblica in `authorized_keys` di `ubuntu`, connessione
verificata. Aggiunti i GitHub Secrets: `DEV_SSH_PRIVATE_KEY`, `DEV_SSH_HOST`,
`DEV_SSH_USER`, `DEV_SSH_PORT`, `DEV_DEPLOY_PATH`.

Durante questo passaggio: un tentativo di generare la chiave direttamente
sul server per errore, poi ripulito; e un file di chiave privata rimasto
per sbaglio dentro la cartella del progetto sul server, anch'esso ripulito.

## 5. Autenticazione del server verso GitHub (per il `git pull`)

Il repo sul server era clonato via HTTPS. Tentativo di usare una **Deploy
Key** (approccio consigliato, sola lettura, legata al singolo repo): non
disponibile perché il repo (`data4prime/CUSTOMERHIVE`) è di
un'organizzazione e non c'era accesso admin per aggiungerla. Anche i
fine-grained Personal Access Token non funzionavano per lo stesso motivo
(l'organizzazione non ha abilitato l'accesso via fine-grained token).
Soluzione adottata: un **Personal Access Token classic** (scope `repo`),
salvato **non** nell'URL del remote (finirebbe in chiaro in `.git/config`,
dentro una cartella con permessi troppo aperti su questo server condiviso)
ma tramite il credential store di git, in un file nella home dell'utente
`ubuntu` con permessi `600`. Verificato con `git fetch`. TODO: sostituire
con una Deploy Key appena disponibile accesso admin — vedi
[`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md).

## 6. Permessi e ownership sul server

Vedi il dettaglio completo in
[`permessi-dev-server.md`](permessi-dev-server.md). In sintesi: verificato
che il webserver gira come `www-data` (`ps aux`), sistemato l'ownership
(`ubuntu:www-data`) e i permessi (`755`/`644` di base, `775`+setgid su
`storage/`/`bootstrap/cache/`, `640` su `.env`) prima di scrivere il job di
deploy, così i file scritti dal deploy automatico restano leggibili/
scrivibili dal webserver.

## 7. Scritto il job di deploy

Aggiunto `deploy-dev` a `.github/workflows/deploy.yml`: dipende dal job
`tests`, gira solo sui push reali a `dev` (non sulle PR), si collega via
SSH (`appleboy/ssh-action`) usando i secret del punto 4, esegue
`git fetch`/`git reset --hard`/`composer install --no-dev`/`migrate --force`/
`optimize:clear`, poi uno smoke test (`curl` su `/admin` con retry).
Decisioni prese in fase di progettazione:
- `git reset --hard` invece di `git pull` (deploy riproducibile, niente
  patch manuali accumulate sul server).
- `composer install --no-dev` (niente pacchetti di sviluppo sul server).
- Nessuno step di build asset: `public/css`/`public/js` sono già compilati
  e committati nel repo.
- Nessuno step di permessi nel workflow: non serve, il setgid applicato al
  punto 6 fa sì che i nuovi file ereditino automaticamente il gruppo
  corretto.

## 8. Primo run completo

Pushato il workflow finale su `dev`. Risultato: **tutto verde** — job
`tests` passato, job `deploy-dev` passato (deploy + smoke test). Pipeline
CI/CD per `dev.thecustomerhive.com` considerata funzionante.

## Cosa NON è stato ancora fatto

Vedi la sezione "Limiti noti" in [`cicd-pipeline.md`](cicd-pipeline.md) per
l'elenco completo (staging, utente dedicato, Deploy Key, HTTPS, rollback,
backup DB).
