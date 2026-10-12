# 291 - Avvisi di scadenza: miglioramenti e test automatici

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Email / Test
- **File/aree di codice coinvolte**:
  - `app/Console/Commands/UserExpiryNotification.php`
  - `app/Helpers/TenantHelper.php` (`build_utenti_scadenza_tenantadmin`, `getTenantAdmins`)
  - `resources/lang/{en,it}/crudbooster.php`
  - `tests/Feature/UserExpiryNotificationTest.php`, `tests/Feature/QueuedEmailTest.php` (nuovi)

## Contesto

Seguito di [289](289-email-ritardate-con-job-in-coda.md) e
[290](290-avvisi-scadenza-utenti-su-job-in-coda.md): revisione del comando
`user:expiry-notification`, che non aveva alcun test.

## Situazione prima

- `UserHelper::tenant()` (una query per utente) pur avendo già `cms_users.tenant`.
- `getTenantAdmins()` leggeva lo stesso ruolo (privilegio 2) una volta per admin
  e andava in errore se il ruolo non esisteva.
- La tabella degli utenti in scadenza viveva nel comando, senza escape di
  nome/email, con intestazioni italiane scritte nel codice.
- Rilanciare il comando a mano lo stesso giorno duplicava tutte le mail.
- Giorni di preavviso e privilegi 1/2 erano numeri sparsi nel codice.

## Situazione dopo

- Il tenant si legge dalla riga già caricata; gli utenti senza tenant vengono
  avvisati (e finiscono nell'elenco dei superadmin) ma non cercano admin.
- `getTenantAdmins()` legge il ruolo una sola volta e ritorna `[]` se il ruolo
  2 non esiste o non è tenant admin (prima: errore).
- Query delle scadenze con `whereIn` su 3 date; costanti `DAYS_BEFORE_EXPIRY`,
  `PRIVILEGES_HIDDEN_FROM_TENANT_ADMINS`.
- `build_utenti_scadenza_tenantadmin` spostato in `TenantHelper` (coerente col
  segnaposto del template), con `e()` su nome/email/data e intestazioni
  tradotte (`user_expiry_col_*`, EN e IT). La lingua di default è `it`, quindi
  nelle mail le intestazioni restano "Nome / Email / Data scadenza".
- Protezione contro il doppio invio: ogni coppia destinatario/template si
  accoda una sola volta al giorno (chiave in cache `user_expiry_notified:*`,
  scritta dopo l'accodamento, scade in 2 giorni). Opzione `--force` per
  reinviare.

## Motivazione

Meno query, mail HTML sicure, nessun doppione, logica verificabile.

## Test

Nuovi (19), da lanciare su richiesta: `php artisan test
--filter="UserExpiryNotificationTest|QueuedEmailTest"` dentro il container.
- `UserExpiryNotificationTest`: finestre 30/7/1 giorni (e giorni fuori
  finestra), solo utenti Active, email disattivata, elenco per tenant,
  esclusione superadmin/tenant admin dall'elenco ai tenant admin, superadmin
  con tutti i tenant, nessuna mail senza scadenze, payload senza credenziali,
  deduplica con `--force`, coda `mail` e connessione configurata.
- `QueuedEmailTest`: `send_at` accoda `SendDelayedEmailJob` con delay (e non
  invia), `content_replace` senza toccare il template nel DB,
  `SendTemplateEmailJob`, `enqueue()`, tabella (righe, colori, escape,
  vuota), `getTenantAdmins()`.
- **Non eseguiti** (la suite parte solo su richiesta): verificati solo `php
  -l` e uno script usa-e-getta in transazione con rollback (4 job al primo
  giro, 4 al secondo, 8 con `--force`; intestazioni tradotte).

## Rischi e note

- Cambi visibili: intestazioni identiche in italiano; nome con HTML ora
  appare come testo; rilancio manuale lo stesso giorno non reinvia (usare
  `--force`). Con cache `file` la protezione sopravvive tra esecuzioni; se la
  cache viene svuotata a metà giornata il rilancio reinvia.
- Rimasto fuori: i template `notifica_scadenza_utente_*` stanno solo nel DB e
  il superadmin riceve il template pensato per i tenant admin.

## Rollback

Ripristinare i file dal commit precedente; le chiavi di traduzione e i test
possono restare.
