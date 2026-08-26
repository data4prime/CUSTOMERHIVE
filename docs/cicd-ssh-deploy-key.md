# Chiave SSH dedicata al deploy automatico (GitHub Actions)

Guida per creare una chiave SSH separata da quella personale, usata solo
dalla pipeline CI/CD per collegarsi al server e fare il deploy. Vale come
riferimento sia per `dev.thecustomerhive.com` sia, più avanti, per staging.

**Perché una chiave separata e non quella che usi tu**: se la chiave della
pipeline dovesse trapelare (log, secret configurato male, ecc.) il danno
resta limitato a quello che quella chiave può fare — non alla tua identità
personale su quel server o su altri sistemi.

## 1. Genera la coppia di chiavi (in locale, sul tuo PC)

```
ssh-keygen -t ed25519 -C "github-actions-deploy-dev" -f customerhive_deploy_dev -N ""
```

Genera due file nella cartella corrente:
- `customerhive_deploy_dev` — chiave **privata**, andrà in GitHub Secrets (mai in git, mai condivisa)
- `customerhive_deploy_dev.pub` — chiave **pubblica**, va installata sul server

(`-N ""` = nessuna passphrase, necessario perché la pipeline deve poterla
usare senza intervento manuale.)

> ⚠️ **TODO futuro — da sistemare**: per partire velocemente, su
> `dev.thecustomerhive.com` la chiave è stata installata sull'utente
> `ubuntu` esistente invece che su un utente `deploy` dedicato (punto 2
> sotto, saltato). Questo server è **condiviso**: sotto `/var/www` ci sono
> anche `staging.thecustomerhive.com`, `license.thecustomerhive.com` (il
> license server esterno) e diversi altri siti cliente, e l'utente `ubuntu`
> ha accesso in scrittura praticamente ovunque lì dentro — quindi questa
> chiave di deploy, così com'è oggi, potrebbe in teoria scrivere anche
> fuori dalla cartella di `dev.thecustomerhive.com`. Da rivedere prima di
> impostare il deploy automatico anche per staging: creare un utente
> dedicato con permessi limitati alla sola cartella del progetto (vedi
> punti 2-4 sotto), e rigenerare/sostituire questa chiave.

## 2. (Consigliato) Crea un utente dedicato al deploy sul server

Se sul server dev stai già usando un utente non-root per il deploy manuale,
puoi riusarlo. Altrimenti:

```
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy   # cosi' puo' scrivere dove serve, senza essere root
```

## 3. Installa la chiave pubblica sul server

Sul server (connesso con un utente che ha già accesso, es. il tuo):

```
sudo mkdir -p /home/deploy/.ssh
sudo chmod 700 /home/deploy/.ssh
```

Poi incolla il contenuto di `customerhive_deploy_dev.pub` (dal tuo PC) in
`/home/deploy/.ssh/authorized_keys` sul server, es.:

```
# sul tuo PC:
cat customerhive_deploy_dev.pub
# copia l'output, poi sul server:
sudo nano /home/deploy/.ssh/authorized_keys   # incolla la riga, salva
sudo chmod 600 /home/deploy/.ssh/authorized_keys
sudo chown -R deploy:deploy /home/deploy/.ssh
```

## 4. Dai all'utente `deploy` i permessi giusti sulla cartella del progetto

```
sudo chown -R deploy:www-data /var/www/customerhive
sudo chmod -R 775 /var/www/customerhive/storage /var/www/customerhive/bootstrap/cache
```

## 5. Testa la connessione PRIMA di metterla in GitHub

Dal tuo PC:

```
ssh -i customerhive_deploy_dev deploy@dev.thecustomerhive.com "cd /var/www/customerhive && whoami && git status"
```

Deve rispondere senza chiedere password e senza errori di permessi. Se non
funziona, va risolto qui — non nella pipeline (più facile da debuggare in
locale che nei log di GitHub Actions).

## 6. Aggiungi i secrets su GitHub

Repo → **Settings → Secrets and variables → Actions → New repository
secret**. Crea questi secret (prefisso `DEV_` per distinguerli da quelli di
staging che arriveranno dopo):

| Nome secret | Valore |
|---|---|
| `DEV_SSH_PRIVATE_KEY` | contenuto **completo** del file `customerhive_deploy_dev` (chiave privata, incluse le righe `-----BEGIN...-----`/`-----END...-----`) |
| `DEV_SSH_HOST` | `dev.thecustomerhive.com` |
| `DEV_SSH_USER` | `ubuntu` (vedi nota sopra — non è l'utente dedicato consigliato, ma è quello in uso oggi) |
| `DEV_SSH_PORT` | `22` |
| `DEV_DEPLOY_PATH` | `/var/www/dev.thecustomerhive.com` (percorso reale confermato sul server) |

## 7. Pulizia in locale

Una volta caricata la chiave privata su GitHub Secrets:
```
rm customerhive_deploy_dev customerhive_deploy_dev.pub
```
Non serve tenerne una copia locale (GitHub Secrets è già la fonte
autorevole); se in futuro serve rigenerarla, si ripete la procedura.

## 8. Seconda chiave: autenticazione del server verso GitHub (per `git pull`)

Questa è una chiave **diversa e separata** da quella dei punti 1-7. Serve
per la direzione opposta:

| | Chi la usa | Da dove a dove | Dove vive la privata |
|---|---|---|---|
| Chiave dei punti 1-7 (`DEV_SSH_PRIVATE_KEY`) | GitHub Actions | GitHub → server | Solo in GitHub Secrets |
| Chiave di questo punto | il server stesso | server → GitHub | Solo sul server |

Sul server il repo è clonato via HTTPS: per farlo autenticare verso GitHub
senza dover gestire una password/token che scade, l'approccio corretto è
una **Deploy Key** GitHub (chiave SSH legata a questo singolo repository,
sola lettura) — i passi sono qui sotto. Tutti i comandi vanno eseguiti
**tu, sul server** (via la tua sessione SSH normale).

> ⚠️ **TODO futuro — da migrare**: il repo `data4prime/CUSTOMERHIVE` è di
> un'organizzazione e al momento non c'è accesso admin per aggiungere una
> Deploy Key. Per sbloccare `dev.thecustomerhive.com` si è usato
> temporaneamente un **Personal Access Token classic** (scope `repo`) al
> posto della Deploy Key: token salvato via `git config --global
> credential.helper "store --file ~/.git-credentials-customerhive"` (file
> nella home dell'utente `ubuntu`, `chmod 600` — **non** nell'URL del
> remote, che finirebbe in chiaro in `.git/config` dentro `/var/www`, una
> cartella con permessi troppo aperti su questo server condiviso). Non
> appena si ottiene accesso admin al repo (o lo fa chi lo ha), passare alla
> Deploy Key sotto: più sicura (scoped al solo repo, sola lettura, non
> scade, non legata a un account personale).

1. Genera la chiave **direttamente sul server** (qui puoi lasciarla lì,
   la privata non deve muoversi altrove):
   ```
   ssh-keygen -t ed25519 -C "dev-server-pull-customerhive" -f ~/.ssh/customerhive_pull_deploy -N ""
   ```

2. Stampa la chiave pubblica e copiala:
   ```
   cat ~/.ssh/customerhive_pull_deploy.pub
   ```

3. Su GitHub: repo → **Settings → Deploy keys → Add deploy key**. Incolla
   la chiave pubblica, dai un titolo (es. "dev.thecustomerhive.com server"),
   **lascia DESELEZIONATO "Allow write access"** (deve poter solo leggere,
   non pushare).

4. Configura SSH sul server perché usi questa chiave specifica per questo
   repo (utile visto che il server ospita anche altri siti/repo):
   ```
   cat >> ~/.ssh/config <<'EOF'
   Host github-customerhive
       HostName github.com
       User git
       IdentityFile ~/.ssh/customerhive_pull_deploy
       IdentitiesOnly yes
   EOF
   chmod 600 ~/.ssh/config
   ```

5. Cambia il remote del progetto da HTTPS a questo alias SSH:
   ```
   cd /var/www/dev.thecustomerhive.com
   git remote set-url origin git@github-customerhive:data4prime/CUSTOMERHIVE.git
   ```

6. Testa (non modifica nulla, verifica solo che l'autenticazione funzioni):
   ```
   git fetch origin
   ```
   Deve completare senza chiedere username/password e senza errori di
   permessi.

## Note di sicurezza

- La chiave privata **non va mai committata** nel repository, nemmeno per
  errore in un file di config di esempio.
- Valuta di disabilitare il login SSH via password sul server (solo chiavi),
  se non l'hai già fatto: `PasswordAuthentication no` in `/etc/ssh/sshd_config`.
- I runner di GitHub Actions hanno IP dinamici: non è praticabile whitelistare
  IP specifici nel firewall del server per queste connessioni. La difesa
  principale resta l'autenticazione a chiave + un utente `deploy` con
  permessi limitati alla sola cartella del progetto (non root, niente sudo).

---

Una volta completati questi step, i secrets sono pronti per essere usati nel
workflow GitHub Actions (prossimo step del piano — vedi
`docs/deploy-manuale-server.md` per i comandi esatti che il workflow dovrà
eseguire via SSH).
