# 295 - Servizio `scheduler` nell'ambiente Docker locale

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Docker
- **File/aree di codice coinvolte**:
  - `docker-compose.yml` (nuovo servizio `scheduler`)
  - `docs/docker-local-dev.md`, `docs/deploy-manuale-server.md`

## Contesto

Revisione di schedule e job: i comandi di `app/Console/Kernel.php` (scadenza
utenti, avvisi, licenza oraria, pulizia MFA) richiedono un cron
`schedule:run` che sui server si configura a mano, mentre in Docker locale
non girava nulla (c'era solo il `worker` della coda). I comandi andavano
lanciati a mano.

## Situazione prima

- Servizi: `app`, `worker`, `db`. Nessun processo eseguiva `schedule:run`,
  quindi in locale gli account scaduti non venivano disattivati e nessun
  avviso partiva da solo.

## Situazione dopo

- Nuovo servizio `scheduler` (`customerhive_scheduler`): stessa immagine,
  stessi volumi e utente `www-data` del `worker`, comando
  `php artisan schedule:work` (ciclo ogni minuto, senza installare cron
  nell'immagine). `restart: unless-stopped`; dopo aver cambiato il codice:
  `docker compose restart scheduler`.
- Gli orari seguono `app.timezone` (`Europe/Rome`), non l'ora UTC del container.
- Documentazione aggiornata: servizi e comandi utili in `docker-local-dev.md`,
  riferimento nel punto 11 di `deploy-manuale-server.md`.

## Motivazione

Avere in locale lo stesso comportamento dei server per i job schedulati, senza
ricordarsi di lanciarli a mano e senza modificare l'immagine.

## Test

- `docker compose config -q` ok; `docker compose up -d scheduler`: container
  attivo; `php artisan schedule:list` mostra i 4 comandi con le prossime
  esecuzioni (00:00, 00:10, ogni ora, 03:00).
- Non atteso/verificato: l'esecuzione effettiva dei comandi (prima scadenza
  utile: `GetLicense` a inizio ora, i comandi di scadenza a mezzanotte).

## Rischi e note

- `command:GetLicense` ogni ora chiama il license server remoto anche in locale
  (i controlli di licenza sono già attivi in locale).
- `user:check-expiry` disattiva gli utenti locali scaduti; il DB di sviluppo
  ha oggi un utente attivo con scadenza passata che verrà disattivato alla
  prima esecuzione.
- `user:expiry-notification`: in locale `email_enabled` è `no`, quindi non
  parte nessuna mail. Se si attiva l'invio con l'SMTP di test configurato nei
  Settings (Gmail), le mail andrebbero agli indirizzi degli utenti locali.
- Per sospendere tutto: `docker compose stop scheduler`.

## Rollback

Rimuovere il servizio `scheduler` da `docker-compose.yml` e
`docker compose rm -sf scheduler`.
