# 101 - Settings "Email Setting": pulsante per testare l'invio prima di salvare

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Settings / Email
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/SettingsController.php` (`postTestEmail()`)
  - `resources/views/crudbooster/setting.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Richiesta esplicita dell'utente, emersa durante il debug della configurazione
SMTP per l'MFA (`095`-`100`): poter testare l'invio di un'email **prima**
di salvare la configurazione, con un feedback chiaro (successo/errore e
dettagli) per poter debuggare senza dover salvare, uscire e rifare un login
per ogni tentativo.

## Situazione prima

Nessun modo per verificare se una configurazione SMTP funziona senza
salvarla e poi innescare un invio reale altrove nell'app (es. il login
MFA o il reset password) - un errore si scopriva solo indirettamente, con
uno stack trace su una pagina che non ha nulla a che fare con le
impostazioni email.

## Situazione dopo

- **`SettingsController::postTestEmail()`** (nuovo, auto-instradato via
  reflection dal modulo `settings` - stesso meccanismo di `AdminCmsUsersController`
  per l'MFA, nessuna route esplicita necessaria): riceve i valori
  attualmente digitati nel form (`smtp_driver`, `smtp_host`, `smtp_port`,
  `smtp_username`, `smtp_password`, `email_sender`, `tls_ssl`) **senza
  salvarli**, e un indirizzo destinatario di test. Se la password arriva
  vuota (come nel form, che non la ripopola mai in chiaro) usa quella gia'
  salvata - stessa convenzione di `postSaveSetting()`. Prova a spedire una
  email reale e ritorna JSON `{success, message, error?}`.
- **Scoperta tecnica rilevante** (spiega anche un comportamento visto in
  `100`): `config/mail.php` di questo progetto conserva ancora la chiave
  top-level `'driver'` (formato Laravel <= 6.x, mai migrato al formato
  moderno `'mailers'`). `Illuminate\Mail\MailManager::getConfig()` ha un
  ramo di compatibilita' esplicito: se `mail.driver` e' valorizzato, usa
  **sempre** l'intero array `mail` (le chiavi piatte `host`/`port`/...)
  per **qualunque** mailer richiesto, ignorando silenziosamente
  `mail.mailers.<nome>`. Il test iniziale di questa feature (config per
  mailer nominato) falliva sempre con un errore SMTP reale invece di usare
  il transport atteso, proprio per questo motivo. Corretto usando le
  stesse chiavi piatte legacy gia' usate con successo da
  `CRUDBooster::sendEmail()`/`sendEmailQueue()` - non un ripiego, e' il
  modo corretto per **questo specifico progetto**.
- **Vista**: pulsante "Send test email" + campo destinatario (precompilato
  con l'email dell'admin loggato), visibile **solo** per il gruppo "Email
  Setting" (`$page_title === 'Email Setting'`), posizionato dentro lo
  stesso form ma con un bottone `type="button"` che non lo invia - una
  chiamata AJAX a parte legge i valori correnti dai campi. Messaggio di
  esito mostrato inline (verde/rosso), incluso il messaggio d'errore reale
  dell'eccezione per poter debuggare subito senza aprire i log.
- **Log applicativo**: ogni test (riuscito o fallito) viene comunque
  loggato (`Log::info`/`Log::warning`) con driver/host/porta/errore, per
  chi preferisce controllare `storage/logs/laravel.log`.
- Pulita anche la cache dei setting (`Cache::forget('setting_smtp_driver')`
  ecc.) rimasta non aggiornata da una modifica diretta a DB fatta durante
  il debug di `100` (non passata da `postSaveSetting()`, che e' l'unico
  punto che la invalida normalmente).

## Motivazione

Il test deve riflettere esattamente cosa succederebbe salvando quella
configurazione, quindi non poteva accontentarsi di un endpoint "finto" -
doveva usare il vero stack di invio dell'app. Scoprire che
`mail.mailers.*` viene ignorato in questo progetto e' stata una scoperta
diretta del debug di questa feature, non un'ipotesi: senza saperlo, il
pulsante avrebbe sempre mostrato un esito "falso" (o sempre lo stesso
errore, indipendentemente dai valori digitati).

## Test

- `php -l` sul controller modificato.
- Verificato via `curl` (sessione superadmin autenticata):
  - `smtp_driver=log` → `{"success":true,...}`, contenuto dell'email
    effettivamente scritto in `storage/logs/laravel.log`.
  - `smtp_host` inesistente → `{"success":false, "error": "... getaddrinfo
    ... failed: Name or service not known"}` - messaggio d'errore reale e
    leggibile.
  - Entrambi i casi confermati anche in `storage/logs/laravel.log`
    (`Log::info`/`Log::warning`).
- Verificato dal vivo in browser su `/admin/settings/show?group=Email+Setting`:
  bottone "Send test email" visibile solo in questo gruppo, click reale
  → messaggio d'errore mostrato inline sotto il campo (driver 'sendmail'
  di fallback, coerente con l'assenza di un vero MTA in locale - stessa
  limitazione nota di `097`/`100`, qui pero' con un feedback chiaro invece
  di un 500).
- Non eseguita la suite di test automatici (nessuna richiesta esplicita).

## Rischi e note

- Il test **non salva nulla**: se il test ha successo ma poi l'utente non
  preme "Save", la configurazione provata va persa - comportamento voluto
  (il pulsante serve a provare prima di salvare, non e' un salvataggio
  parziale).
- Superadmin-only (stesso controllo gia' presente su tutto il modulo
  Settings), nessun rate limiting aggiuntivo: e' uno strumento diagnostico
  interno, non un endpoint esposto a utenti non fidati.
- La scoperta sul comportamento di `MailManager::getConfig()` vale per
  **qualunque** codice di questo progetto che provi a usare
  `config(['mail.mailers.*' => ...])` per personalizzare l'invio - va
  tenuto a mente per interventi futuri sul sistema email (es. se in futuro
  si vorra' migrare `config/mail.php` al formato moderno, rimuovendo la
  chiave `driver` legacy, andranno aggiornati in coppia sia
  `CRUDBooster::sendEmail()`/`sendEmailQueue()` sia questo `postTestEmail()`).

## Correzione post-consegna (stesso giorno)

Segnalato dall'utente: il test dava **"successo" anche senza aver
configurato nulla**, e nessuna email arrivava davvero. Causa doppia:

1. Il campo "Mail Driver" in pagina mostrava il placeholder "Select Mail
   Driver" (nessuna opzione valida selezionata) perche' il valore salvato
   in DB (`log`) era un residuo lasciato da un mio test precedente (`100`)
   e non e' nemmeno una delle opzioni valide del menu (`smtp,mail,sendmail`).
   Ripristinato a `mail` (il valore originale prima di quei test).
2. `postTestEmail()` faceva fallback al valore **salvato** su `cms_settings`
   per `smtp_driver`/`smtp_host`/`smtp_port`/`smtp_username`/`email_sender`/
   `tls_ssl` quando il campo arrivava vuoto - stessa convenzione (giusta)
   usata per la password, ma sbagliata per gli altri campi: un driver non
   selezionato in pagina finiva comunque per usare quello salvato (`log`),
   dando un falso esito positivo invece di segnalare che non era stato
   scelto nulla. Rimosso il fallback per tutti i campi tranne la password
   (l'unico che nel form arriva sempre vuoto per design); aggiunta una
   validazione esplicita: driver mancante o host mancante (per il driver
   `smtp`) → messaggio d'errore dedicato invece di un test silenzioso.

Verificato di nuovo via `curl`: driver vuoto → errore "Select a mail driver
before testing."; driver `mail` senza MTA reale → errore reale di sendmail
(come atteso, nessun MTA in locale); driver `log` scelto esplicitamente →
successo genuino (scrive solo nel log, comportamento corretto quando e'
una scelta esplicita, non piu' un fallback silenzioso).

## Rollback

Rimuovere `postTestEmail()` dal controller e il blocco condizionale (bottone
+ script) da `setting.blade.php` riporta la pagina "Email Setting" al
comportamento precedente, senza altri effetti collaterali (nessuna
migration, nessun dato persistito da questa feature).
