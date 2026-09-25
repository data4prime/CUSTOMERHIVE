# 105 - Fix: link "Clicca qui" senza contesto su piu' pagine di auth

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/reset_password.blade.php`
  - `resources/views/crudbooster/mfa_verify.blade.php`
  - `resources/views/crudbooster/mfa_recovery.blade.php`
  - `resources/views/crudbooster/mfa_recovery_status.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Segnalato dall'utente su `admin/mfa-verify`: un link "Clicca qui" senza
nessuna indicazione di cosa faccia. Controllato dove altro compare lo
stesso pattern (`trans('crudbooster.click_here')` usato da solo, senza
testo esplicativo prima).

## Situazione prima

Il pattern "testo esplicativo + Clicca qui" e' usato correttamente in
`forgot.blade.php` ("Vuoi provare ad accedere di nuovo? Clicca qui").
Lo stesso link (torna al login) compare pero' **senza** il testo
esplicativo in altri 4 punti: `reset_password.blade.php` (preesistente,
non introdotto in questa sessione) e nelle 3 viste MFA aggiunte oggi
(`mfa_verify`, `mfa_recovery`, `mfa_recovery_status` - copiato il pattern
da `reset_password.blade.php` senza notare che mancava il testo prima).

## Situazione dopo

- `reset_password.blade.php`: aggiunto lo stesso testo esplicativo gia'
  usato in `forgot.blade.php` (`crudbooster.forgot_text_try_again`).
- Le 3 viste MFA: il link stesso e' diventato autoesplicativo
  ("Back to login" / "Torna al login", nuova chiave
  `crudbooster.mfa_link_back_to_login`) invece di aggiungere una frase
  prima - piu' pulito per un link isolato di fondo pagina non legato a un
  form specifico appena compilato (a differenza di forgot/reset password,
  dove "vuoi provare di nuovo?" ha senso dopo un tentativo).

## Test

- `php -l` sui due file di traduzione.
- Verificato via `curl` che il testo cambiato renda: `reset-password/...`
  mostra "Vuoi provare ad accedere di nuovo? Clicca qui",
  `mfa-recovery` mostra "Torna al login".

## Rischi e note

Nessuno - solo testo, nessuna logica toccata.

## Rollback

Ripristinare `{{trans("crudbooster.click_here")}}` da solo (comportamento
poco chiaro, non consigliato).
