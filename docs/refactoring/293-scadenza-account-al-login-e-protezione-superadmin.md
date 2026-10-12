# 293 - Scadenza account anche al login, protezione dei superadmin, pulizia comandi

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: Auth / Utenti / Console
- **File/aree di codice coinvolte**:
  - `app/Helpers/UserHelper.php` (`isAccountExpired`, `expiredActiveUserIds`)
  - `app/Http/Controllers/System/AdminController.php` (`postLogin`)
  - `app/Providers/AppServiceProvider.php` (token Sanctum api2)
  - `app/Console/Commands/CheckUserExpiry.php`, `app/Console/Kernel.php`
  - rimossi: `MigrateLegacyCrudboosterExtends.php`, `users.sql`, `users.json`
  - `tests/Feature/AccountExpiryLoginTest.php` (nuovo), `CheckUserExpiryTest.php`

## Contesto

Seguito di [292](292-check-user-expiry-pulizia-e-test.md): la scadenza degli
account era applicata **solo** dal cron `user:check-expiry`; senza
`schedule:run` configurato un account scaduto restava utilizzabile per
sempre. Richiesti anche: protezione dei superadmin e pulizia di comandi e file
residui.

## Situazione prima

- `postLogin()` controllava solo `status == 'Active'`; il token Sanctum
  controllava solo `status === 'Active'`.
- Nessuna protezione: se scadevano tutti i superadmin, nessuno poteva più
  riattivare gli account.
- `MigrateLegacyCrudboosterExtends` (comando una tantum di migrazione degli
  extends legacy, vedi [016](016-comando-migrazione-extends-legacy-clienti.md))
  era già stato eliminato dalla working tree ma ancora registrato nel Kernel
  (ogni `php artisan` dava errore). `users.sql`/`users.json` erano residui di
  debug.

## Situazione dopo

- `UserHelper::isAccountExpired($user)`: scaduto se `data_scadenza` è passata
  (stessa regola del cron: alla mezzanotte del giorno di scadenza; NULL e
  `0000-00-00` non scadono). Usato in `postLogin()` (l'utente scaduto riceve lo
  stesso messaggio generico delle credenziali errate, nessuna informazione in
  più) e nel controllo dei token api2.
- **Protezione dei superadmin**: un superadmin (privilegio con
  `is_superadmin = 1`) risulta scaduto solo se esiste un altro superadmin
  attivo e non scaduto. L'ultimo (o tutti, se sono tutti scaduti) non scade
  mai né al login né per il cron.
- `UserHelper::expiredActiveUserIds()` applica la stessa regola e viene usato
  da `user:check-expiry`, così login e cron non possono divergere.
- Rimossa la registrazione di `MigrateLegacyCrudboosterExtends` dal Kernel
  (e il file); eliminati `users.sql` e `users.json`. I documenti 016 e 288
  restano come storia: citano un file che non esiste più.

## Motivazione

La scadenza non deve dipendere dalla presenza di un cron; e un blocco totale
degli amministratori non deve essere possibile per semplice scadenza.

## Test

- `AccountExpiryLoginTest` (7): login negato con account scaduto; ok con
  scadenza futura o assente; ultimo superadmin scaduto entra; superadmin
  scaduto non entra se ce n'è uno valido; casi limite di `isAccountExpired`
  (ieri, oggi, domani, NULL); coincidenza tra regola del login e del cron.
- `CheckUserExpiryTest` (+3): superadmin tutti scaduti restano attivi;
  scaduti disattivati se ne esiste uno valido; un superadmin senza scadenza
  conta come valido.
- **Non eseguiti** (suite solo su richiesta). Verificati: `php -l` su tutti i
  file e uno script usa-e-getta in transazione con rollback (superadmin tutti
  scaduti → protetti, utente standard scaduto → da disattivare; con un
  superadmin valido → gli scaduti diventano disattivabili); `php artisan list`
  torna a funzionare.

## Rischi e note

- Cambio visibile: un utente scaduto non può più fare login nemmeno se il cron
  non è ancora passato. Un superadmin scaduto non è mai bloccato finché è
  l'ultimo utilizzabile: va rinnovato a mano, la protezione non lo avvisa.
- Le **sessioni già aperte** non vengono interrotte dalla scadenza (nessun
  controllo per richiesta): restano valide fino al logout o alla disattivazione
  del cron.
- Niente `git add`/commit: le cancellazioni sono nella working tree.

## Rollback

Ripristinare i file dal commit precedente; per riavere il comando di migrazione
degli extends, `git checkout <commit> -- app/Console/Commands/MigrateLegacyCrudboosterExtends.php`
e la riga nel Kernel.
