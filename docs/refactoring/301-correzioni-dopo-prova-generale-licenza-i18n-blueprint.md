# 301 - Correzioni dopo la prova generale: licenza non cancellata su errori del server, messaggio i18n, blueprint

- **Data**: 2026-10-09
- **Stato**: Completato (non committato). Test mirato `ConnectorServiceTest` eseguito (21 passati); verificato a mano con l'istanza di prova; suite completa non lanciata
- **Area**: Licensing / Docker / Blueprint
- **File/aree di codice coinvolte**:
  - `app/Services/ConnectorService.php`, `tests/Unit/Services/ConnectorServiceTest.php`
  - `app/Http/Controllers/System/AdminController.php`, `resources/lang/{en,it}/crudbooster.php`
  - `customerhive-provisioning-istanze-docker.html` (blueprint)
  - Fuori repo: template compose/env in `prove-chive-cp/acme-test/`
  - LICENSES: vedi `LICENSES/docs/refactoring/014`

## Contesto

La prova generale ([300](300-prova-generale-istanza-docker-end-to-end.md)) ha trovato 10 problemi. Qui si
correggono tutti quelli che sono difetti veri; 8, 9 e 10 no (vedi "Rischi e note").

## Situazione prima

Vedi tabella dei problemi in 300. In sintesi: (2) una risposta del server di licenza senza licenza, 5xx
inclusi, cancellava `license.json` e poi la riga `license` dell'istanza → dopo un rinnovo l'accesso non
tornava; (6) messaggio `License is missing or not valid` hardcoded in inglese; (3, 4, 5, 7) il blueprint
aveva comando del worker sbagliato, ripristino incompleto, un solo `.env` con la password di root anche
all'app, nessuna nota su tinker.

## Situazione dopo

- **(2) `ConnectorService`**: nuova proprietà `serverUnavailable`, impostata quando la richiesta di
  licenza fallisce per timeout/irraggiungibilità/eccezione o con **5xx**. `writeLicense()` su 5xx **non
  cancella** `license.json` (resta l'eventuale copia obsoleta) e ritorna false; `validateLicense()` **non
  cancella la riga `license`** se `serverUnavailable`. Le risposte *definitive* (200 senza `data`, 4xx)
  si comportano come prima (test esistente invariato). Con la correzione 014 in LICENSES la licenza scaduta
  ora risponde 200, quindi non passa più di qui. Due test nuovi (5xx non cancella file; 5xx non cancella riga).
- **(6)** `trans('crudbooster.license_missing_or_not_valid')` in `AdminController` (2 punti), chiave
  aggiunta in en ("License is missing, expired or not valid") e it ("La licenza è mancante, scaduta o non
  valida"). **Cambio visibile**: il testo mostrato è ora tradotto e menziona la scadenza.
- **(3, 4, 5, 7) Blueprint e template**: worker `queue:work qlik_sync --queue=qlik_sync,mail ...` (con
  nota); backup con `mysqldump --add-drop-database --databases <slug>_db` (il dump contiene DROP/CREATE DATABASE, quindi il reimport ricrea il database senza passo separato; GRANT non serve, i permessi restano) e verifica di integrità (`-- Dump completed`, `tar -tz`) prima di toccare qualcosa nel ripristino; `-pass env:` al posto di
  `-k` nei comandi openssl; attesa di salute 300 s e `start_period` 240 s con nota sul fail-stop;
  `stop_signal: SIGTERM`; `db.env` separato (l'app non riceve `MYSQL_ROOT_PASSWORD`); nota "niente tinker";
  esempio `license.json` con `license_key` e modulo `Qlik`; frase sulla licenza scaduta riscritta.
  Template di prova aggiornati (`env.template`, `docker-compose.template.yml`, `restore.sh` ricrea il
  database di default).

## Motivazione

Correzioni minime e behavior-preserving per i casi che funzionavano; la chiave di licenza è
configurazione, non cache: perderla per un guasto temporaneo del server blocca un'istanza in produzione.

## Test

- `php artisan test tests/Unit/Services/ConnectorServiceTest.php` → 21 passati (include i 2 nuovi).
- Istanza di prova con immagine `prova-3`: licenza scaduta → login bloccato, riga `license` e
  `license.json` **conservate**; `renew` → login riuscito senza re-registrare la chiave. Messaggio i18n:
  chiave presente e `php -l` OK (non visto a vista nella schermata).
- `db.env`: `printenv` nel container app senza variabili `MYSQL_*`; backup e ripristino con dump `--add-drop-database` (variante B: tabella orfana eliminata, stato identico in 18 s; un dump troncato viene rifiutato prima di toccare il database); ripristino (default con
  DROP) ripetuti: tabella creata dopo il backup eliminata, stato identico.
- Non verificato: un vero 5xx dal server (coperto solo da unit test con `Http::fake`).

## Rischi e note

- Non corretti di proposito: **(8)** login lento (4 chiamate al server di licenza) — è un'ottimizzazione
  con rischio sul gate di login in produzione, da fare a parte; **(9)** `created_at` in ora di Roma e
  `updated_at` in UTC — da verificare prima se è identico in sviluppo, probabile comportamento
  preesistente del DB; **(10)** `activate-existing-license` pubblica — è il percorso di recupero voluto
  della schermata licenza, serve conoscere la chiave.
- Il blueprint HTML è un file non tracciato nella radice: le modifiche non appaiono in `git diff`.
- Se il server restituisce 5xx *per sempre*, l'istanza resta con la copia obsoleta di `license.json`
  (comportamento già esistente per il server irraggiungibile).

## Rollback

Ripristinare `ConnectorService.php` (togliere `serverUnavailable` e i due controlli), le due righe in
`AdminController`, le chiavi lang e i due test; per il blueprint basta annullare le modifiche al file.

## Decisioni sui punti aperti (2026-10-09)

- **`start_period` dell'healthcheck: resta 240 s.** Accorciarlo darebbe `unhealthy` durante la prima migrazione
  (35-76 s oggi, di più con database grandi) e `depends_on: service_healthy` farebbe fallire `up`. Il costo
  (un fail-stop resta `starting` fino a 4 min 35 s) si compensa lato Chive CP: attesa massima 300 s e lettura
  dell'ultima riga di `docker logs` (`[entrypoint] ERRORE`) per accorgersi prima del fallimento.
- **Comando `instance:license-key`: non si crea.** Con il bug 2 corretto il rinnovo basta e la chiave non si
  perde più per errori del server; resta solo il caso raro di licenza ricreata (chiave nuova), coperto dalla
  rotta esistente `POST /admin/activate-existing-license` o da un `UPDATE` su `license`. Se in futuro serve
  automatizzarlo con più garanzie, si aggiunge allora (nota nel blueprint).
