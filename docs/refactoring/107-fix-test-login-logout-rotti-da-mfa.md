# 107 - Fix: LoginTest/LogoutTest rotti dal gate MFA

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Auth / Test
- **File/aree di codice coinvolte**:
  - `tests/Concerns/LogsInAdmin.php`
  - `tests/Feature/LoginTest.php`
  - `tests/Feature/LogoutTest.php`

## Contesto

L'introduzione del gate MFA in `AdminController::postLogin()` (vedi
`docs/refactoring/097-*`) ha rotto 4 test di caratterizzazione scritti
prima dell'MFA: eseguendo la suite completa su richiesta esplicita
dell'utente sono risultati 4 fallimenti, tutti riconducibili allo stesso
motivo. L'aggiornamento di questi test era stato esplicitamente
rimandato durante l'implementazione dell'MFA ("rimandiamo la parte di
test, ci penseremo in futuro").

## Situazione prima

I test di login/logout autenticano un utente "pulito" (nessun TOTP
attivo, nessun dispositivo mai riconosciuto). Con il gate MFA attivo,
`postLogin()` per un utente del genere tenta sempre lo step-up email OTP
(stile GitHub) perche' il dispositivo del test non risulta mai fidato -
il login si ferma su un redirect a `getMfaVerify()` invece di popolare
subito `Session::put('admin_id', ...)`/`Auth::login()`, facendo fallire
le asserzioni su `assertSessionHas('admin_id', ...)`.

Fallivano: `test_login_con_credenziali_corrette_riesce`,
`test_superadmin_bypassa_il_controllo_tenant`,
`test_dopo_il_login_si_accede_a_una_pagina_protetta` (LoginTest),
`test_logout_invalida_sia_la_sessione_legacy_che_il_guard` (LogoutTest).

## Situazione dopo

Aggiunto un helper `trustDeviceFor(array $user)` in
`tests/Concerns/LogsInAdmin.php` che riproduce esattamente cosa succede
in produzione al primo step-up riuscito: inserisce una riga in
`mfa_trusted_devices` per l'utente (stesso schema selector/validator di
`MfaHelper::issueTrustedDeviceCookie()`) e allega il cookie
corrispondente alla richiesta di test via `$this->withCookie(...)`
(cifrato automaticamente dal framework di test, decifrato lato server
dal middleware `EncryptCookies` come un cookie reale). I 4 test
chiamano questo helper prima di `postLoginFrom()`, cosi'
`MfaHelper::hasTrustedDevice()` trova gia' un dispositivo valido e
`postLogin()` salta lo step-up email OTP - il resto del test (login
"di base": sessione/guard popolati) resta identico a prima, invariato
nelle asserzioni.

Nessuna logica di produzione toccata: il gate MFA resta esattamente
com'era, i test ora simulano solo la precondizione "dispositivo gia'
riconosciuto" invece di quella implicita "MFA non esiste" che avevano
prima. Il gate MFA vero e proprio (redirect, verifica codice, rate
limit) resta verificato manualmente, come per il resto del piano MFA -
non e' stata aggiunta copertura automatica dedicata in questo
intervento (scope limitato alla sola riparazione, su richiesta
esplicita dell'utente).

## Test

- `php -l` sui 3 file modificati.
- `php artisan test --filter="LoginTest|LogoutTest"`: 8/8 passati (prima:
  4 falliti su 8).
- Suite completa gia' eseguita subito prima (su richiesta esplicita):
  186 passati, 4 falliti - tutti e soli quelli qui risolti.

## Rischi e note

L'helper conosce il meccanismo interno selector/validator del cookie
fidato (`MfaHelper::TRUSTED_DEVICE_COOKIE`/`TRUSTED_DEVICE_DAYS`): se in
futuro quel meccanismo cambia, va aggiornato anche questo helper, non
solo il codice di produzione.

## Rollback

Rimuovere le 3 chiamate a `trustDeviceFor()` e il metodo stesso da
`LogsInAdmin.php` - i test tornano a fallire come prima finche' l'MFA
resta attivo sul gate di login.
