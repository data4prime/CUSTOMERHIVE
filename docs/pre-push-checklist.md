# Checklist pre-push

Cose da controllare/ripristinare prima di pushare su `main` (o comunque prima
che il codice arrivi in staging/produzione). Aggiungere qui ogni volta che si
introduce una modifica "temporanea" per lo sviluppo locale.

## ✅ Riattivare i controlli di licenza — fatto (2026-08-26)

Erano stati disattivati temporaneamente per poter lavorare in locale senza
una licenza valida. Riattivati in `LicenseHelper.php` (rimossi i 4 return
anticipati `LICENSE-CHECK-DISABLED-DEV`), con l'aggiunta di un guard
"nessuna licenza ancora" su `canAddTenant()`, `canAddUser()` e
`getLicenseInfo()` (che ne erano privi e sarebbero andati in errore
fatale a tabella `license` vuota). Dettaglio completo in
[`refactoring/003-licensing-hardening.md`](refactoring/003-licensing-hardening.md).

Il flusso di attivazione (trial e "ho già una licenza") resta bloccato da
un bug lato server di licenza remoto, non da questo repository — vedi lo
stesso documento.

---

## ⚠️ Verificare che il license server di produzione parli già la nuova busta {success, data}

`ConnectorService.php` (`getAccessToken`, `writeLicense`) e
`AdminController::postActivateLicense()` sono stati aggiornati per leggere
le risposte del license server nella nuova busta `{success, message, data}`
invece del formato piatto precedente (`status`/`result` a livello radice).
Verificato solo contro un'istanza Docker locale di LICENSES già aggiornata.

**Prima di pushare su `main`**: confermare che `license.thecustomerhive.com`
(produzione) risponda già con la nuova busta su `/auth/login` e
`/license-server/license`/`/licenses` — altrimenti login e attivazione
licenza si rompono immediatamente in produzione. Dettaglio in
[`refactoring/004-licensing-envelope-success-data.md`](refactoring/004-licensing-envelope-success-data.md).

---

## ⚠️ Verificare che nessun ruolo non superadmin usi il wizard di Module Generator

Il wizard (`ModulsController`, step 1/2/4) è ora riservato al superadmin
— [`refactoring/080-module-generator-wizard-solo-superadmin.md`](refactoring/080-module-generator-wizard-solo-superadmin.md).
Prima di aggiornare un cliente in produzione, verificare che nessun suo
ruolo non-superadmin avesse in uso quel wizard (in tal caso va reintrodotto
un permesso dedicato per quel cliente, non tolto il controllo globale).

---

## ⚠️ Profilo a sezioni: migration `session_version` + email configurata + test

Il profilo utente ora cambia email/password con password attuale + codice
OTP e chiude le altre sessioni tramite la nuova colonna
`cms_users.session_version` — [`refactoring/170-profilo-utente-a-sezioni-con-verifica-otp.md`](refactoring/170-profilo-utente-a-sezioni-con-verifica-otp.md).
Prima di aggiornare un cliente: eseguire `php artisan migrate`; verificare
che la posta (SMTP) sia configurata, perché senza SMTP (e senza TOTP) email e
password non sono modificabili dal profilo; avvisare chi usa integrazioni API
con header `X-User` (è l'email dell'utente). I test in
`tests/Feature/UserProfileSectionsTest.php` sono scritti ma non ancora
eseguiti: lanciarli prima del push.

---

<!-- Aggiungere qui le prossime voci della checklist -->

---

## ⚠️ Sincronizzazione Qlik: `composer install`, `migrate` e worker della coda su ogni installazione

Nuova funzionalità "Sincronizza da Qlik" (app e item, vedi
[`piano-qlik-sync-app-items.md`](piano-qlik-sync-app-items.md) e
[`refactoring/176-qlik-sync-coda-e-modello-dati.md`](refactoring/176-qlik-sync-coda-e-modello-dati.md)):

- **`composer install`**: nuova dipendenza `textalk/websocket` (fogli SaaS).
- **`php artisan migrate`**: tabelle `jobs`/`failed_jobs`, colonne additive su
  `qlik_apps`/`qlik_items`, tabelle `qlik_sync_runs`/`qlik_sync_run_records`.
- **Worker della coda** sempre attivo (supervisor/systemd):
  `php artisan queue:work qlik_sync --queue=qlik_sync --sleep=3 --tries=1 --timeout=3600`.
  Senza worker le sincronizzazioni restano "in coda" (la pagina di monitoraggio
  mostra un avviso). Dopo ogni aggiornamento del codice: `php artisan queue:restart`.
- **Non provato contro un Qlik reale** (SaaS/on-premise): fare lo spike su un
  tenant di test prima di proporlo ai clienti (elenco fogli SaaS via WebSocket
  e QRS on-premise, formato URL degli item).

---

## ⚠️ Standard UI (Bootstrap 5 + Bootstrap Icons, senza AdminLTE): giro manuale prima del rilascio

Interventi [206](refactoring/206-ui-standard-librerie-locali-bootstrap-5-3-8.md)–[209](refactoring/209-ui-standard-token-componenti-compatibilita.md).
Il lavoro è stato verificato solo da codice, server e jsdom: **nessuno l'ha visto nel
browser**.

- **Giro manuale con 3 ruoli diversi** (accento): lista/form/dettaglio di 2 moduli,
  filtro avanzato, esportazione/importazione, wizard del module generator (tutti i
  passi), dashboard (builder + visualizzazione + Qlik), utenti/profilo/MFA,
  impostazioni, privilegi, login/licenza/404, pagine pubbliche Qlik e Chat AI,
  popup datamodal. Sidebar aperta/chiusa e albero del menu, notifiche, barra di
  controllo del builder legacy.
- **Rilascio dei file statici**: `public/vendor/{bootstrap,bootstrap-icons,jquery,
  plugins,libs}/`, `public/css/ch-*.css`, `public/js/ch-shell.js` devono arrivare su
  ogni installazione; la cartella `public/vendor/crudbooster/assets/adminlte/`,
  `public/vendor/lucide/` e `public/vendor/crudbooster/ionic/` non esistono più
  (cancellarle dalle installazioni se il deploy non le rimuove).
- **Cache dei browser/CDN**: i CSS/JS nuovi hanno `?v=<filemtime>`; verificare che il
  web server non serva copie vecchie di `theme.css`/`main.js`.
- **Clienti**: confronto dei loro moduli custom dopo l'aggiornamento (`ch-compat.css`
  copre le classi Bootstrap 3; `local-scripts/migrate-*.php` migrano i loro file).
  Aggiornare i test che asserissero classi/testi HTML cambiati (nessuno trovato nel
  repo).
- **Test automatici**: non lanciati dopo questo intervento.
