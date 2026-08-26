# Permessi e ownership su dev.thecustomerhive.com

Passaggi fatti per sistemare proprietario e permessi del progetto sul
server, prima di collegarci il deploy automatico. Riferimento riutilizzabile
per altri ambienti sullo stesso server (es. quando si farà staging).

## Perché

Il server è condiviso con altri siti (staging, il license server, siti
cliente) e, controllando, alcune cartelle sotto `/var/www` avevano permessi
molto aperti (`rwxrwxrwx`, leggibili/scrivibili da chiunque abbia un
account sul server). Non è la pratica standard per un progetto Laravel su
Apache — vedi sotto lo schema corretto.

## Verifica preliminare: con quale utente gira il webserver

```bash
ps aux | grep -E 'apache2|php-fpm'
```
Risultato: processi worker `apache2` in esecuzione come `www-data` (il
processo master gira come `root` solo per potersi legare alla porta 80).
Nessun `php-fpm` — il progetto usa `mod_php` integrato in Apache. Confermato
quindi `www-data` come gruppo/utente del webserver.

## Schema applicato

- Proprietario dei file: **`ubuntu`** (l'utente usato per il deploy)
- Gruppo: **`www-data`** (il webserver)
- Cartelle: `755` — il webserver deve solo leggere il codice
- File: `644`
- Eccezione: `storage/` e `bootstrap/cache/` a `775` (Laravel ci scrive a
  runtime: log, cache, sessioni, view compilate) con gruppo `www-data`
- `.env`: `640` (non leggibile da "altri")
- **Mai `777`**

## Comandi eseguiti

```bash
DEPLOY_PATH=/var/www/dev.thecustomerhive.com
WEBUSER=www-data

# Proprietario: ubuntu (deploy) — gruppo: www-data (webserver)
sudo chown -R ubuntu:$WEBUSER $DEPLOY_PATH

# Permessi base: dirs 755, file 644
sudo find $DEPLOY_PATH -type d -exec chmod 755 {} \;
sudo find $DEPLOY_PATH -type f -exec chmod 644 {} \;

# Le cartelle che Laravel deve scrivere a runtime: 775 + gruppo www-data
sudo chmod -R 775 $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache
sudo chgrp -R $WEBUSER $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache

# setgid: i nuovi file/cartelle creati ereditano il gruppo www-data
sudo chmod g+s $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache
sudo find $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache -type d -exec chmod g+s {} \;

# .env più ristretto
sudo chmod 640 $DEPLOY_PATH/.env
sudo chown ubuntu:$WEBUSER $DEPLOY_PATH/.env

# ubuntu deve appartenere al gruppo www-data per scrivere dove serve
sudo usermod -aG $WEBUSER ubuntu
```

Dopo l'ultimo comando: la nuova appartenenza al gruppo non è attiva nella
sessione SSH corrente — serve disconnettersi/riconnettersi (o `newgrp
www-data`) prima di continuare a lavorarci.

## Verifica finale (eseguita e confermata)

```bash
stat -c '%U:%G %a %n' $DEPLOY_PATH $DEPLOY_PATH/storage $DEPLOY_PATH/bootstrap/cache $DEPLOY_PATH/.env
```

Risultato ottenuto:
```
ubuntu:www-data 2755 /var/www/dev.thecustomerhive.com
ubuntu:www-data 2775 /var/www/dev.thecustomerhive.com/storage
ubuntu:www-data 2775 /var/www/dev.thecustomerhive.com/bootstrap/cache
ubuntu:www-data 640 /var/www/dev.thecustomerhive.com/.env
```

Il `2` iniziale su cartella radice/`storage`/`bootstrap/cache` conferma il
bit setgid attivo (anche sulla cartella radice del progetto, non solo su
`storage`/`bootstrap/cache` come richiesto esplicitamente — presente già
prima, probabilmente impostato a livello di `/var/www` quando il server è
stato preparato. Effetto positivo, non richiede correzioni: qualunque file
nuovo creato in futuro nella cartella del progetto erediterà comunque il
gruppo `www-data`).

## Perché il deploy automatico non deve toccare più nulla di questo

Grazie al setgid, i file creati da operazioni future (`git reset --hard`,
`composer install`, ecc., eseguiti come `ubuntu` dal job `deploy-dev` della
pipeline) erediteranno automaticamente il gruppo `www-data`. `git` inoltre
non modifica i permessi delle cartelle già esistenti. Per questo il job di
deploy (vedi [`cicd-pipeline.md`](cicd-pipeline.md)) non include nessuno
step di `chown`/`chmod`: non ce n'è bisogno, il setup è auto-mantenuto.

## TODO

Vedi [`cicd-ssh-deploy-key.md`](cicd-ssh-deploy-key.md): in futuro si vuole
sostituire `ubuntu` con un utente `deploy` dedicato, con permessi limitati
alla sola cartella del progetto invece che a tutto `/var/www`. Quando si
farà, questo stesso schema di permessi va riapplicato con `deploy` al posto
di `ubuntu` come proprietario.
