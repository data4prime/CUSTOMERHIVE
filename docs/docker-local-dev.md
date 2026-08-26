# Ambiente di sviluppo locale (Docker)

Questo setup Docker serve **solo per lo sviluppo in locale**. Lo staging gira
su una macchina remota separata, senza Docker.

## Requisiti

- Docker Desktop (con Docker Compose incluso)

## Setup iniziale

1. Clona il repo ed entra nella cartella del progetto.
2. Copia il file di configurazione:
   ```
   cp .env.example .env
   ```
   I valori di default in `.env.example` (DB, utente, password) sono già
   allineati con `docker-compose.yml`: non serve modificarli per far
   funzionare l'ambiente. `MYSQL_ROOT_PASSWORD` e `APP_PORT` sono usati solo
   da Docker Compose.
3. Build e avvio dei container:
   ```
   docker compose up -d --build
   ```
   Al primo avvio l'entrypoint del container `app` installa in automatico
   le dipendenze Composer, genera `APP_KEY` e crea lo storage link.
4. Applica le migration e popola i dati di base:
   ```
   docker compose exec app php artisan migrate
   docker compose exec app php artisan db:seed
   ```
   Il seeder degli utenti chiede l'email dell'admin da terminale (password
   di default: `123456`).
5. Apri l'app su [http://localhost:8080](http://localhost:8080) (porta
   configurabile con `APP_PORT` nel `.env`).

## Come funziona

- Il codice del progetto viene montato nel container `app` (bind mount),
  quindi le modifiche ai file locali sono immediatamente attive: **non
  serve rebuildare l'immagine** se non cambi `docker/Dockerfile`.
- `vendor/`, `storage/framework/` e `bootstrap/cache/` sono su volumi Docker
  nativi (non sul bind mount): su Docker Desktop (Windows/Mac) l'I/O su
  migliaia di piccoli file attraverso il bind mount è molto più lento che su
  un volume nativo, e con `vendor/` (~9000 file) questo da solo rendeva ogni
  richiesta lentissima (anche 10+ secondi). Con questi path spostati su
  volumi, le richieste tornano nell'ordine di qualche centinaio di ms. Se
  cambi le dipendenze Composer devi rilanciare `composer install` dentro il
  container (vedi sotto); questi volumi non sono visibili come file sul
  filesystem host (l'IDE non li vede, ma non serve: sono codice di terze
  parti o cache generata).
- Servizi definiti in `docker-compose.yml`:
  - `app` — PHP 8.1 + Apache
  - `db` — MySQL 8 (dati persistiti nel volume `customerhive-mysql`)
  - `node` — compilazione asset front-end con gulp, facoltativo (vedi sotto)

## Comandi utili

```
docker compose exec app php artisan ...      # comandi artisan
docker compose exec app composer ...         # comandi composer
docker compose logs -f app                   # log dell'applicazione
docker compose down                          # ferma i container (i dati MySQL restano)
docker compose down -v                       # ferma i container e cancella anche il DB
```

Compilazione asset front-end (gulp), facoltativa:
```
docker compose --profile assets run --rm node
```

## Problemi comuni

- **Porta occupata**: se 8080 o 3306 sono già in uso, cambia `APP_PORT` /
  `DB_FORWARD_PORT` nel `.env` prima di fare `docker compose up`.
- **Dipendenze Composer cambiate**: dopo un `git pull` con nuove dipendenze,
  esegui `docker compose exec app composer install`.
- **Reset completo dell'ambiente**: `docker compose down -v` seguito da
  `docker compose up -d --build` e di nuovo migrate + seed.
- **App ancora lenta nonostante i volumi**: se sei su Windows e il repo vive
  su un percorso `C:\...` (bind mount da Windows verso la VM Linux di Docker
  Desktop), resta un overhead di I/O sui file che *restano* sul bind mount
  (`app/`, `resources/`, `routes/`, ecc.). Per la massima velocità, clona il
  repo dentro il filesystem nativo di WSL2 (es. `\\wsl$\<distro>\home\<user>\...`
  o direttamente lavorando da un terminale WSL) ed edita il progetto da lì:
  Docker Desktop lavora nativamente su quel filesystem, senza traduzione
  Windows↔Linux.
