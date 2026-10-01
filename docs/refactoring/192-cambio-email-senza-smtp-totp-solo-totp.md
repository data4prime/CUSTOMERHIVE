# 192 - Cambio email dal profilo: verifica in base a TOTP/SMTP

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Auth / Profilo
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`postProfileEmailStart`, `postProfileEmailConfirm`, nuovo `applyProfileEmailChange`)
  - `app/Helpers/MfaHelper.php` (nuovi `isSmtpConfigured`, `emailChangeMode`)
  - `resources/views/users/profile.blade.php` (pannello cambio email + JS)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Il cambio email dal profilo richiedeva sempre un codice inviato al nuovo
indirizzo (piu' il TOTP se attivo). Con SMTP non configurato l'invio falliva
e il cambio era impossibile (risposta 503), a differenza del login che in
quel caso prosegue senza step-up.

## Situazione prima

- Passo 1: password attuale + invio codice email al nuovo indirizzo; se
  l'invio falliva, stop.
- Passo 2: codice email e, se l'utente aveva il TOTP, anche il codice TOTP.

## Situazione dopo

La verifica dipende da `MfaHelper::emailChangeMode()`:

| TOTP attivo | SMTP configurato | Verifica richiesta |
|---|---|---|
| si | si/no | solo codice TOTP (nessuna email inviata) |
| no | si | solo codice email al nuovo indirizzo (come prima) |
| no | no | nessuna, basta la password attuale: email salvata subito al passo 1 |

"SMTP configurato" = `smtp_driver` = `smtp` e `smtp_host` valorizzato nei
Settings (controllo sui setting, non test di raggiungibilita').

La logica di salvataggio (controllo unicita', revoca dispositivi attendibili,
bump versione sessione, log) e' estratta in `applyProfileEmailChange()`, usata
da entrambi i percorsi. Il caso senza verifica viene tracciato nel log con una
voce dedicata (`log_profile_email_changed_unverified`). La UI nasconde il campo
codice email quando non serve ed adatta l'etichetta del bottone del passo 1.

## Motivazione

Permettere di cambiare email su istanze senza SMTP, senza bloccare l'utente.
Per chi ha il TOTP, il TOTP e' gia' un secondo fattore sufficiente.

## Test

Lint (`php -l`) di controller, helper e file lingua; `view:clear`. Nessun test
automatico lanciato ne' prova manuale su browser: da verificare i quattro casi
della tabella.

## Rischi e note

- **Cambio di comportamento visibile**: (1) senza TOTP e senza SMTP chi conosce
  la password puo' cambiare l'email senza altra prova; (2) con TOTP e SMTP il
  codice email al nuovo indirizzo non viene piu' richiesto, quindi non si
  prova piu' che l'utente controlli il nuovo indirizzo (rischio di impostare
  un indirizzo sbagliato e non ricevere piu' reset password/OTP).
- Un SMTP configurato ma irraggiungibile (senza TOTP) continua a bloccare il
  cambio con errore 503, come prima.
- Un driver `sendmail`/`mail` e' considerato "SMTP non configurato".

## Rollback

Ripristinare i file elencati dal commit precedente (nessuna migration, nessun
dato modificato).
