# 218 - Cambio password: stesse modalità del cambio email (totp / email / none)

- **Data**: 2026-10-05
- **Stato**: Completato
- **Area**: Auth / Profilo utente
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`postProfilePasswordStart`, `postProfilePasswordConfirm`, nuovo `applyProfilePasswordChange`)
  - `resources/views/users/profile.blade.php` (etichetta pulsante e JS del passo 1)
  - `resources/lang/{en,it}/crudbooster.php` (`log_profile_password_changed_unverified`)

## Contesto

Il cambio email (intervento 192) sceglie la verifica in base a
`MfaHelper::emailChangeMode()`: TOTP se attivo, altrimenti codice via email
se l'SMTP è configurato, altrimenti basta la password attuale. Il cambio
password non seguiva la stessa logica.

## Situazione prima

`postProfilePasswordStart` usava `hasActiveTotp ? 'totp' : 'email'`: senza
TOTP e senza SMTP il codice non poteva partire (503 `profile_otp_send_failed`)
e l'utente non poteva cambiare la password dal profilo.

## Situazione dopo

Il metodo è `MfaHelper::emailChangeMode($user)`:

- `totp`: invariato, serve il codice dell'app (nessuna email, anche con SMTP);
- `email`: invariato, codice all'email attuale dell'utente;
- `none` (niente TOTP e SMTP non configurato): la password viene salvata
  subito dal passo 1, la sola password attuale è la prova. Il log usa la
  nuova chiave `log_profile_password_changed_unverified`.

Il salvataggio (hash, bump versione sessione, log) è estratto in
`applyProfilePasswordChange`, usato dal passo 2 e dal ramo `none`. Nella UI il
pulsante del passo 1 in modalità `none` è "Aggiorna password" invece di
"Continua", e la risposta con `changed: true` resetta il form senza passo 2.

## Motivazione

Coerenza con il cambio email e nessun blocco su istanze senza SMTP/TOTP.

## Test

Solo `php -l` sul controller (OK). Suite non lanciata. Da provare a mano i tre
casi: TOTP attivo, senza TOTP con SMTP, senza TOTP e senza SMTP.

## Rischi e note

Cambio di comportamento visibile: nel caso `none` il cambio password non
richiede più un codice (prima falliva). Come per l'email, la sola password
attuale protegge l'operazione.

## Rollback

Ripristinare `$method = MfaHelper::hasActiveTotp($user) ? 'totp' : 'email'`,
ricollocare il corpo di `applyProfilePasswordChange` in
`postProfilePasswordConfirm` e togliere il ramo `changed` dal JS.
