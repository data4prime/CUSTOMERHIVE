# 292 - user:check-expiry: pulizia e test

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Utenti / Console
- **File/aree di codice coinvolte**:
  - `app/Console/Commands/CheckUserExpiry.php`
  - `tests/Feature/CheckUserExpiryTest.php` (nuovo)

## Contesto

Revisione dei comandi schedulati, dopo [290](290-avvisi-scadenza-utenti-su-job-in-coda.md)
e [291](291-avvisi-scadenza-miglioramenti-e-test.md). `user:check-expiry`
(00:00) è l'**unico** punto in cui la scadenza degli account viene applicata:
nel codice non c'è alcun controllo di `data_scadenza` al login o sulle
richieste.

## Situazione prima

- Caricava tutti gli utenti scaduti (anche già Inactive) e li aggiornava uno
  a uno in un ciclo; blocco di codice commentato con sintassi rotta; filtro
  inutile `!= ''` su una colonna `date`; nessun output, nessun log, nessun
  `updated_at`, nessun codice di uscita esplicito.

## Situazione dopo

- Un solo `UPDATE ... WHERE id IN (...)` sui soli utenti `Active` con
  `data_scadenza` valorizzata, diversa da `0000-00-00` e `< now()`.
- Imposta `updated_at`; scrive in log (`user:check-expiry: utenti disattivati
  per scadenza: ids`) e in output il numero di utenti; `return SUCCESS`.
- **Comportamento di scadenza invariato**: `data_scadenza` è `date`
  confrontata con `now()`, quindi l'utente è disattivato alla mezzanotte del
  giorno di scadenza (non alla fine). Fissato da un test, da aggiornare se si
  decide di renderlo inclusivo.

## Motivazione

Meno query, tracciabilità di chi viene bloccato, comando verificabile.

## Test

- `CheckUserExpiryTest` (8): scaduto da giorni → Inactive; scade domani o
  fra 30 giorni → Active; scade oggi → Inactive (comportamento attuale);
  NULL e data zero legacy → Active; già Inactive non riscritto
  (`updated_at` invariato); solo gli scaduti e conteggio in output; messaggio
  quando non c'è nessuno. **Non eseguiti** (suite solo su richiesta).
- Verificato con script usa-e-getta in transazione con rollback: scaduto →
  Inactive, futuro → Active, oggi → Inactive, già Inactive invariato, secondo
  giro "Nessun utente scaduto da disattivare."

## Rischi e note

- Controllo al login, protezione dei superadmin e rimozione di
  `users.sql`/`users.json`: fatti nell'intervento [293](293-scadenza-account-al-login-e-protezione-superadmin.md).

## Rollback

Ripristinare `CheckUserExpiry.php` dal commit precedente; il test può restare.
