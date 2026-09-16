# 072 - Password dimenticata: link di reset invece di password in chiaro via email

- **Data**: 2026-09-16
- **Stato**: Completato
- **Area**: Auth / Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminController.php` (`postForgot()`, nuovi
    `getResetPassword()`/`postResetPassword()`)
  - `routes/crudbooster.php` (nuove rotte `reset-password`)
  - `resources/views/crudbooster/reset_password.blade.php` (nuova view)
  - `config/auth.php` (aggiunto `throttle`)
  - `database/migrations/2026_09_16_000000_create_password_resets_table.php`
  - `database/migrations/2026_09_16_000001_update_forgot_password_email_template.php`
  - `database/seeders/CmsEmailTemplatesSeeder.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Emerso durante l'analisi/implementazione della policy password stile NIST
800-63B su `cms_users` (vedi memoria di progetto): `AdminController::
postForgot()` generava una password casuale di 5 caratteri e la mandava **in
chiaro via email**, scrivendola direttamente su `cms_users.password` con un
`DB::table(...)->update()` che bypassava completamente il form (e quindi
anche le nuove regole di policy). Segnalato all'utente, che ha poi chiesto
esplicitamente di sistemarlo.

## Situazione prima

```php
$rand_string = str_random(5);
$password = \Hash::make($rand_string);
DB::table(...)->where('email', ...)->update(['password' => $password]);
CRUDBooster::sendEmail([..., 'template' => 'forgot_password_backend']); // manda $rand_string in chiaro
```

Problemi: password di 5 caratteri (ben sotto qualunque policy ragionevole),
inviata in chiaro via email, nessuna scadenza/uso singolo, nessun
rate-limit sulle richieste ripetute, bypass totale delle regole di
validazione del form utenti.

## Situazione dopo

`postForgot()` usa il broker nativo di Laravel (`Illuminate\Auth\Passwords`,
gia' referenziato in `config/auth.php` ma la tabella `password_resets` non
era mai stata creata) per generare un token con scadenza (60 minuti) e
uso singolo, legato a `App\User` (gia' mappato su `cms_users`). L'invio vero
dell'email resta pero' sul sistema di template esistente
(`CRUDBooster::sendEmail()` / `cms_email_templates`), passando una closure
esplicita a `Password::sendResetLink()` invece di lasciar partire la
notifica Laravel di default - cosi' l'email si può ancora personalizzare dal
pannello Settings come prima.

Nuove pagine/rotte:
- `GET admin/reset-password/{token}?email=...` — form per scegliere la
  nuova password (stessa view-style di login/forgot).
- `POST admin/reset-password` — valida (`min:12|max:72|confirmed|
  not_common_password`, stessa regola del form utenti) e chiama
  `Password::reset()`, che verifica il token e invoca una callback che fa
  `Hash::make()` + `save()` sull'utente.

`config/auth.php`: aggiunto `'throttle' => 60` a `passwords.users` (mancava,
default Laravel e' 0 = nessun limite) — senza, ogni richiesta ripetuta
invalida silenziosamente il link precedente (`DatabaseTokenRepository::
create()` cancella sempre il token precedente prima di crearne uno nuovo)
senza nessun freno sulla frequenza delle richieste.

Template email (`cms_email_templates`, slug `forgot_password_backend`):
placeholder `[password]` sostituito con `[reset_url]` (un link), sia nella
riga gia' seedata (migration dati, condizionata al contenuto esatto di
default - non tocca personalizzazioni admin) sia nel seeder per le
installazioni nuove.

## Motivazione

Mandare password in chiaro via email è un anti-pattern noto (l'email non è
un canale sicuro, resta nella casella di posta indefinitamente, spesso
finisce anche in log/backup del server di posta). Il pattern "link con
token a scadenza, l'utente sceglie lui la password" è lo standard corrente
ed è quello che il broker nativo di Laravel implementa già, quindi non
serve codice custom per token/scadenza/uso-singolo/rate-limit: bastava
creare la tabella mancante e collegare l'invio email al sistema esistente.

## Test

Verificato manualmente end-to-end sull'ambiente Docker locale (non è stata
lanciata la suite automatica, ne' sono stati aggiunti test automatici - solo
verifica manuale, in attesa di indicazione esplicita per i test):
- `Password::sendResetLink()` genera un token, crea la riga in
  `password_resets`, e una richiesta ripetuta immediata viene ignorata
  (`passwords.throttled`) dopo l'aggiunta di `throttle` in config/auth.php.
- `GET admin/reset-password/{token}?email=...` risponde 200 e renderizza la
  pagina con il token in un campo hidden.
- `POST admin/reset-password` con una password che viola la policy (es.
  `abc123`) viene rifiutato (redirect indietro, nessuna modifica al DB).
- `POST admin/reset-password` con una password conforme aggiorna
  effettivamente l'hash su `cms_users` (verificato con `Hash::check()`),
  consuma il token (la riga in `password_resets` viene eliminata) e un
  secondo tentativo con lo stesso token viene rifiutato (`passwords.token`).
- Non verificato: invio email reale (l'ambiente dev non ha un MTA
  raggiungibile su `127.0.0.1:25` - stesso limite che aveva gia' il flusso
  precedente, non introdotto da questo intervento).

## Rischi e note

- CSRF resta disabilitato globalmente (`app/Http/Middleware/VerifyCsrfToken`
  commentato in `app/Http/Kernel.php`) - non toccato qui, è un intervento
  separato già tracciato nel piano di refactoring generale.
- Il messaggio di errore su `postForgot()` (email inesistente) rivela se
  l'indirizzo è registrato - comportamento preesistente, non cambiato qui.
- Durante il test manuale la password dell'utente
  `admin@customerhive.local` sul DB di sviluppo locale è stata
  temporaneamente cambiata e infine reimpostata a `password-corretta-123`
  (stessa password usata da `tests/Concerns/SeedsCmsData.php` e
  `LoginTest`), per lasciare un valore noto e documentato invece di uno
  sconosciuto.

## Rollback

Le due nuove migration sono reversibili (`down()` droppa `password_resets`
e ripristina il vecchio contenuto del template email). Riportare
`postForgot()`/rotte/view al comportamento precedente richiede di
ripristinare il commit precedente a questo intervento.
