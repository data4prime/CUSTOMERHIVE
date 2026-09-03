# Installazione e deploy manuale su server (senza Docker)

Guida per installare/aggiornare CustomerHive su un server "nudo" (VM con
PHP/Apache/MySQL installati direttamente sull'host, **non** containerizzato).
Vale sia per `dev.thecustomerhive.com` (nuovo server, da fare per la prima
volta) sia per `staging.thecustomerhive.com` (server già esistente, da
riallineare a questo processo). Per lo sviluppo **locale** invece si usa
Docker: vedi [`docker-local-dev.md`](docker-local-dev.md).

Usa questo documento come **checklist**: spunta ogni passo man mano. È anche
la base da cui poi scriveremo lo script della pipeline CI/CD automatica — se
un passo qui è ambiguo/manuale, va chiarito ora, non quando sarà uno script.

Nei comandi sotto, sostituisci `<dominio>` con `dev.thecustomerhive.com` o
`staging.thecustomerhive.com` a seconda dell'ambiente che stai installando.

---

## 0. Prerequisiti one-time (solo la primissima volta su un server nuovo)

- [ ] Server raggiungibile via SSH, con un utente dedicato al deploy (non
      root per le operazioni quotidiane).
- [ ] DNS: `<dominio>` punta all'IP del server.
- [ ] Chiave SSH di deploy generata e aggiunta come **Deploy Key** (sola
      lettura) sul repository GitHub, così il server può fare `git clone`/
      `git pull` senza usare credenziali personali:
      ```
      ssh-keygen -t ed25519 -C "deploy@<dominio>" -f ~/.ssh/customerhive_deploy
      cat ~/.ssh/customerhive_deploy.pub   # da incollare in GitHub > repo > Settings > Deploy keys
      ```

## 1. Requisiti software sul server

- [ ] **PHP 8.3** (richiesto da `composer.json` dopo l'upgrade a Laravel 13,
      stessa versione usata in locale via Docker, per coerenza) con le
      estensioni:
      `pdo_mysql mbstring exif pcntl bcmath gd zip intl curl xml fileinfo openssl ctype tokenizer json`
      (`gd` compilata con supporto freetype/jpeg per le immagini). Su Ubuntu,
      se i repo di sistema non hanno PHP 8.3, aggiungere la PPA
      `ppa:ondrej/php` (o, se il mirror Launchpad dà errori 404 temporanei,
      il repository diretto `packages.sury.org`).
- [ ] **Composer 2**.
- [ ] **MySQL 8** (o accesso a un'istanza MySQL 8 già esistente).
- [ ] **Apache** con `mod_rewrite` e `mod_headers` abilitati (oppure Nginx +
      PHP-FPM, vedi variante sotto).
- [ ] **git**.
- [ ] Non serve Node/npm sul server: gli asset compilati (`public/css`,
      `public/js`) sono già committati nel repo (build fatta in locale con
      `npm run prod`), non c'è build step da eseguire in produzione.

## 2. Recupero del codice

- [ ] Clonare il branch corretto (`dev` per l'ambiente dev, `main` per
      staging) in una directory dedicata, es. `/var/www/customerhive`:
      ```
      git clone --branch dev git@github.com:<org>/<repo>.git /var/www/customerhive
      ```
- [ ] **Verificare che non ci siano bypass di sviluppo residui**: il tag
      `LICENSE-CHECK-DISABLED-DEV` (vedi
      [`pre-push-checklist.md`](pre-push-checklist.md)) non deve essere
      presente nel codice che arriva qui.
      ```
      grep -rn "LICENSE-CHECK-DISABLED-DEV" packages/ && echo "STOP: rimuovere il bypass prima di procedere"
      ```

## 3. Configurazione `.env`

- [ ] Copiare `.env.example` in `.env` e compilarlo. Variabili **critiche**
      per questo progetto (oltre alle solite `DB_*`):

  | Variabile | Valore |
  |---|---|
  | `APP_ENV` | `dev` o `staging` (mai `local`) |
  | `APP_DEBUG` | `true` su dev, **`false`** su staging |
  | `APP_URL` | `https://<dominio>` |
  | `APP_PATH` | valore stabile per questo ambiente, es. `dev` / `staging`. **Attenzione**: il valore che metti qui è quello che verrà registrato nella licenza (vedi punto 7) e da quel momento non va più cambiato, altrimenti il login si blocca — vedi [`login-e-licensing.md`](login-e-licensing.md). |
  | `APP_DOMAIN` | `<dominio>` (il codice ne estrae automaticamente solo la prima parte prima del punto, es. `dev`) |
  | `APP_KEY` | generata al punto 5, non scriverla a mano |

- [ ] Permessi del file `.env`: leggibile solo dall'utente dell'applicazione
      (`chmod 640 .env`), **mai committato in git**.

## 4. Dipendenze PHP

- [ ] Installare le dipendenze in modalità produzione (niente pacchetti di
      sviluppo, autoloader ottimizzato):
      ```
      composer install --no-dev --optimize-autoloader --no-interaction
      ```

## 5. Chiave applicazione e cache

- [ ] ```
      php artisan key:generate --force
      ```
- [ ] (facoltativo, valutare per staging una volta stabile) cache di
      config/route per performance — **da NON usare finché si sta ancora
      cambiando spesso `.env`**, perché nasconde le modifiche fatte a mano:
      ```
      php artisan config:cache
      php artisan route:cache
      ```

## 6. Permessi cartelle

- [ ] Le cartelle che Laravel deve poter scrivere devono appartenere/essere
      scrivibili dall'utente con cui gira il webserver (es. `www-data`):
      ```
      mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
      chown -R www-data:www-data storage bootstrap/cache
      chmod -R 775 storage bootstrap/cache
      ```

## 7. Database

- [ ] Creare database e utente dedicato (se non già presente):
      ```sql
      CREATE DATABASE customerhive_dev CHARACTER SET utf8mb4;
      CREATE USER 'chive_user'@'%' IDENTIFIED BY '<password forte>';
      GRANT ALL PRIVILEGES ON customerhive_dev.* TO 'chive_user'@'%';
      FLUSH PRIVILEGES;
      ```
- [ ] Eseguire le migration:
      ```
      php artisan migrate --force
      ```
- [ ] Se è un ambiente nuovo/vuoto, popolare i dati di base. Il seeder utenti
      chiede l'email admin in modo interattivo — passarla via stdin se non
      c'è un terminale interattivo:
      ```
      echo "admin@<dominio>" | php artisan db:seed --force
      ```
      (password di default creata dal seeder: `123456` — **cambiarla subito**
      dopo il primo login).

## 8. Storage pubblico

- [ ] ```
      php artisan storage:link
      ```

## 9. Configurazione web server

**Apache** — vhost con `DocumentRoot` sulla cartella `public/`:
```apache
<VirtualHost *:80>
    ServerName <dominio>
    DocumentRoot /var/www/customerhive/public

    <Directory /var/www/customerhive/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/customerhive-<dominio>-error.log
    CustomLog ${APACHE_LOG_DIR}/customerhive-<dominio>-access.log combined
</VirtualHost>
```
- [ ] `a2enmod rewrite headers` e riavviare Apache.

**Nginx + PHP-FPM** (alternativa, se il server usa questo stack):
```nginx
server {
    listen 80;
    server_name <dominio>;
    root /var/www/customerhive/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 10. HTTPS

- [ ] Certificato (es. Let's Encrypt via `certbot`) per `<dominio>`, e
      redirect automatico HTTP→HTTPS.
      ```
      certbot --apache -d <dominio>     # o --nginx, a seconda dello stack
      ```

## 11. Scheduler Laravel (cron di sistema)

A differenza dell'ambiente Docker locale (dove non gira), su un server
persistente va configurato il cron di sistema per far funzionare i job
schedulati dell'app (controllo scadenza utenti, notifiche, refresh della
licenza ogni ora — vedi `app/Console/Kernel.php`):
- [ ] Aggiungere al crontab dell'utente applicativo:
      ```
      * * * * * cd /var/www/customerhive && php artisan schedule:run >> /dev/null 2>&1
      ```

## 12. Licenza

- [ ] Visitare `https://<dominio>/admin/register-license` e compilare il
      form (email, dominio precompilato, numero utenti/tenant previsti).
      **Nota**: in fase di test abbiamo riscontrato che il license server
      esterno (`license.thecustomerhive.com`) può rispondere "Some
      parameters are missing" (di solito per `APP_PATH` non valorizzato —
      controllare il punto 3) o dare un proprio errore 500 — vedi
      [`login-e-licensing.md`](login-e-licensing.md). Se succede, non è un
      problema di questo server: va segnalato a chi gestisce il license
      server.

## 13. Verifica finale

- [ ] `https://<dominio>/admin` risponde ed è raggiungibile in HTTPS.
- [ ] Login con l'utente admin funziona.
- [ ] Dashboard carica senza errori 500.
- [ ] `storage/logs/laravel.log` non mostra errori ripetuti dopo un giro
      manuale dell'app.
- [ ] Cambiata la password di default dell'admin (se creato dal seeder).

---

## Checklist rapida — primo deploy su `dev.thecustomerhive.com`

Riepilogo puntato dei passi sopra, da spuntare in ordine per il primo deploy:

- [ ] 0. Server provisionato, DNS puntato, deploy key GitHub configurata
- [ ] 1. Stack software installato (PHP 8.3 + estensioni, Composer, MySQL, Apache/Nginx, git)
- [ ] 2. `git clone` branch `dev` + verifica nessun bypass licenza residuo
- [ ] 3. `.env` compilato (`APP_PATH=dev`, `APP_DOMAIN=dev.thecustomerhive.com`, `APP_DEBUG=true`)
- [ ] 4. `composer install --no-dev`
- [ ] 5. `key:generate`
- [ ] 6. Permessi `storage/` e `bootstrap/cache/`
- [ ] 7. DB creato, `migrate --force`, `db:seed --force`
- [ ] 8. `storage:link`
- [ ] 9. Vhost configurato e attivo
- [ ] 10. HTTPS attivo
- [ ] 11. Cron `schedule:run` configurato
- [ ] 12. Licenza registrata per il dominio (o problema noto segnalato)
- [ ] 13. Verifica finale (login + dashboard + log puliti)

Una volta completata questa checklist a mano con successo, questi stessi
passi diventano la base per lo script di deploy automatico della pipeline
CI/CD (push su `dev` → questi step sul server dev; push su `main` → gli
stessi step sul server staging).
