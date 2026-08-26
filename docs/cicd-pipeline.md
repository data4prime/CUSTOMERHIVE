# Pipeline CI/CD

Descrizione di come funziona oggi la pipeline (`.github/workflows/deploy.yml`,
nome interno del workflow "Run tests"). Per la cronologia di come è stata
costruita vedi [`cicd-passaggi-setup.md`](cicd-passaggi-setup.md).

## Cosa fa, in sintesi

```
push/PR su dev o main
        │
        ▼
   job "tests"  ──(fallisce)──> STOP, niente deploy
        │ (passa)
        ▼
push reale su dev? ──(no, es. e' una PR)──> STOP qui
        │ (si)
        ▼
   job "deploy-dev"
   ├─ SSH sul server, aggiorna il codice, dipendenze, migration
   └─ smoke test (curl su /admin)
```

Oggi la pipeline copre **solo il branch `dev` → `dev.thecustomerhive.com`**.
Il branch `main` fa girare i test ma non deploya ancora nulla (staging è
tuttora manuale — vedi "Limiti noti" sotto).

## Job "tests"

Gira su ogni push e pull request verso `dev` o `main`.

1. Checkout del codice
2. Setup PHP 8.1 con le estensioni richieste dal progetto
3. `composer install`
4. Prepara `.env` (da `.env.example`) e genera `APP_KEY`
5. `php artisan test`

Il test runner usa `.env.testing` (SQLite in-memory) quando `APP_ENV=testing`
è impostato — non serve un database MySQL nella CI.

## Job "deploy-dev"

Gira **solo** quando: evento è un push (non una PR) *e* il branch è `dev` *e*
il job `tests` è passato (`needs: tests`).

1. **Connessione SSH** al server (action `appleboy/ssh-action`), con le
   credenziali nei GitHub Secrets:
   - `DEV_SSH_HOST`, `DEV_SSH_USER`, `DEV_SSH_PORT`, `DEV_SSH_PRIVATE_KEY`
   - `DEV_DEPLOY_PATH` (percorso del progetto sul server)
2. Sul server, in sequenza:
   ```
   cd $DEV_DEPLOY_PATH
   git fetch origin dev
   git reset --hard origin/dev
   composer install --no-dev --optimize-autoloader --no-interaction
   php artisan migrate --force
   php artisan optimize:clear
   ```
3. **Smoke test**: il runner GitHub (non il server) fa una `curl` su
   `http://<DEV_SSH_HOST>/admin`, con qualche tentativo/retry. Se non
   risponde, il job fallisce.

## Autenticazione: due chiavi diverse, due direzioni

| | Direzione | Dove vive il segreto | Dove è documentata |
|---|---|---|---|
| Chiave SSH di deploy | GitHub Actions → server | GitHub Secrets (`DEV_SSH_PRIVATE_KEY`) | [`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md) punti 1-7 |
| Autenticazione git | server → GitHub (per il `git pull`) | Solo sul server (credential store, home di `ubuntu`) | [`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md) punto 8 |

## Cosa NON fa (limiti noti / rimandato volutamente)

- **Niente staging**: `main` non deploya da nessuna parte per ora. Quando si
  affronterà, si aggiungerà un job `deploy-staging` analogo con secret
  `STAGING_*`.
- **Nessun rollback automatico**: se lo smoke test fallisce, il codice è
  **già stato deployato** (il `git reset --hard` è già avvenuto) — il job
  fallito segnala il problema, ma non riporta indietro il server allo stato
  precedente. Un rollback andrebbe fatto a mano (`git reset --hard <commit
  precedente>` sul server).
- **Nessun backup del DB prima delle migration**: `migrate --force` gira
  direttamente. Accettabile per un ambiente di sviluppo condiviso come dev,
  da rivalutare per staging/produzione.
- **`git reset --hard` è distruttivo per design**: qualunque modifica fatta
  a mano dentro la cartella del progetto sul server (script di prova, file
  temporanei tracciati per errore, ecc. — non `.env`, che è gitignored)
  viene persa al deploy successivo. Voluto: evita che il server accumuli
  patch manuali fuori da git.
- **Utente di deploy condiviso (`ubuntu`) e autenticazione verso GitHub via
  token invece che Deploy Key**: scorciatoie prese per sbloccare dev
  velocemente — vedi i TODO in
  [`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md).
- **HTTPS non attivo** su dev — lo smoke test e i link nella documentazione
  usano `http://`, da aggiornare quando verrà attivato.
- **Cron/scheduler Laravel non configurato** su dev (i job schedulati in
  `app/Console/Kernel.php`, es. controllo scadenza utenti, refresh licenza
  ogni ora, non girano). Non necessario per l'uso attuale di dev, ma da
  configurare se in futuro servisse — vedi il punto dedicato in
  [`deploy-manuale-server.md`](deploy-manuale-server.md).
