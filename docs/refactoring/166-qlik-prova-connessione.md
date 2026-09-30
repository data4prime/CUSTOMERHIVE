# 166 - Qlik: pulsante "Prova connessione" nella configurazione

- **Data**: 2026-09-30
- **Stato**: Completato (verificato in Docker locale con server Qlik finto; non provato contro un vero Qlik Cloud / Qlik Sense)
- **Area**: Qlik / Feature
- **File/aree di codice coinvolte**:
  - `app/Services/QlikConnectionTester.php` (nuovo)
  - `app/Http/Controllers/System/QlikConfController.php` (`postTestConnection()`, JS del pulsante in `script_js`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php` (chiavi `qlik_test_*`)

## Contesto

Una configurazione Qlik sbagliata (URL, chiave privata, Key ID, Issuer, Web Int ID, endpoint del virtual proxy) si scopriva solo quando un utente provava ad aprire un item o l'Hub, con messaggi generici ("JWT generation failed") o con l'iframe che chiedeva le credenziali. Richiesta: poter testare la connessione dal form della configurazione, sia su valori già salvati sia su valori non ancora salvati, per chiunque abbia accesso alle configurazioni Qlik, con un messaggio il più dettagliato possibile per indagare i problemi.

## Situazione prima

Nessun test di connessione. `QlikConfController` (modulo CRUDBooster su `qlik_confs`) aveva solo il form, con JS inline per mostrare/nascondere i campi in base a Tipo (On-Premise/SAAS) e Auth (JWT). Il JWT si costruisce in `QlikHelper::getJWTToken` (SaaS) e `getJWTTokenOP` (on-premise); il login SaaS server-side è in `QlikHelper::createUser` (POST `/login/jwt-session` + GET `/api/v1/users/me`), quello on-premise lo fa il browser (`public/js/qlik_op_jwt_login.js`: GET `{url}/{endpoint}/qrs/about` con Bearer JWT).

## Situazione dopo

- Nel form (add, edit, detail) della configurazione compare il pulsante **Prova connessione** accanto a Salva/Indietro; durante il test mostra uno spinner (nessun testo "in corso"), e l'esito si apre in un **popup** (overlay autonomo, chiudibile con X/Esc/click fuori) per non rompere la UI del form. Il click invia al server i valori correnti del form (file `.pem` appena scelto incluso) più l'id della configurazione se esiste (`conf_id`, ricavato dall'action del form).
- Endpoint `POST admin/qlik_confs/test-connection` (`postTestConnection`, auto-instradato da `CRUDBooster::routeController()`), stessa regola di accesso della lista del modulo (nessuna restrizione a superadmin: la può usare chi ha accesso alle configurazioni Qlik). Un tenant admin può usare `conf_id` solo di configurazioni del proprio tenant (`qlikconfs_tenants`).
- `QlikConnectionTester` esegue e riporta, passo per passo, con durata, dettagli e suggerimenti tradotti (en/it):
  1. **Configurazione**: campi obbligatori per tipo, URL valido http/https, avviso se il campo Porta non coincide con la porta nell'URL (l'app non usa il campo Porta).
  2. **Chiave privata**: origine (upload non salvato o file salvato), percorsi provati, intestazione PEM, tipo/bit, impronta SHA-256 della chiave *pubblica* derivata (confrontabile con quella registrata su Qlik), errore OpenSSL, chiave cifrata/non RSA/< 2048 bit.
  3. **Generazione JWT**: stessa costruzione di `getJWTToken`/`getJWTTokenOP`; mostra header e payload decodificati (mai la firma) e da dove viene l'identità utente (utente Qlik associato in `qlik_users` o segnaposto/casuale).
  4. **DNS**, 5. **TCP**, 6. **Certificato TLS** (soggetto, emittente, SAN, validità, giorni residui, corrispondenza hostname, autofirmato, protocollo/cifratura).
  7. **Login**: SaaS → `POST /login/jwt-session` poi `GET /api/v1/users/me`; on-premise → `GET /{endpoint}/qrs/about`. Per ogni richiesta: richiesta inviata (Authorization mascherata), stato HTTP, header di risposta (cookie mascherati), corpo (max 2000 caratteri), tempi DNS/TCP/TLS/TTFB, esito verifica certificato cURL, redirect (non seguiti).
- **Prerequisito**: l'utente che testa deve avere un utente Qlik associato (`qlik_users`) a quella configurazione. Altrimenti il pulsante è disattivato con un suggerimento ("associa prima un utente Qlik"); nel form di creazione (senza id) è sempre disattivato finché non si salva. Controllo ripetuto lato server (`postTestConnection` risponde 422).
- Il pulsante "Copia rapporto" (nel popup) mette l'intero rapporto negli appunti, per allegarlo a una segnalazione.
- Nessuna scrittura su DB/file: sola lettura. Nessuna modifica al comportamento esistente (login, JWT, `QlikHelper` non toccato: la costruzione del JWT è replicata nel tester, non estratta).

## Motivazione

Diagnosi in un colpo solo di dove si rompe (chiave, JWT, rete, certificato, risposta di Qlik) invece di procedere per tentativi dal browser. Alternativa scartata: riusare direttamente `QlikHelper::getJWTToken`, che legge la configurazione dal DB e quindi non permette di provare valori non salvati; estrarre la logica da `QlikHelper` avrebbe toccato codice di produzione per un'esigenza diagnostica.

## Test

- `php -l` su tester, controller e file lingua.
- Prova in Docker locale con uno script temporaneo (poi rimosso) e un server PHP finto su `127.0.0.1`: on-premise completo (200 + JSON `qrs/about`), SaaS con DNS inesistente (si ferma al passo DNS), chiave mancante (si ferma al passo chiave), e verso `example.com`, `self-signed.badssl.com`, `expired.badssl.com` per il passo TLS (autofirmato/scaduto riconosciuti).
- **Non verificato**: contro un vero Qlik Cloud o Qlik Sense; il rendering del pulsante/pannello nel browser; suite di test automatici (non lanciata).

## Rischi e note

- **SSRF**: il server effettua richieste verso un URL scelto da chi usa il pulsante (voluto: si ammettono anche IP privati, l'on-premise sta spesso in LAN). Solo schemi http/https (`CURLOPT_PROTOCOLS`), redirect non seguiti. Chi ha accesso alle configurazioni Qlik può quindi sondare la rete interna raggiungibile dal server, e il corpo della risposta (max 2000 caratteri) gli viene mostrato.
- Per on-premise il login reale lo fa il browser: il test prova chiave/JWT/raggiungibilità dal *server*, non dal browser (rete, CORS, cookie). Il rapporto lo dice esplicitamente.
- Identità segnaposto/casuale nel token: ormai raggiungibile solo se il mapping esiste ma `idp_qlik` (SaaS) è vuoto; resta segnalata con un avviso.
- Il valore salvato per la chiave è un path tipo `/storage/uploads/...` (relativo alla public root); `QlikHelper` lo passa a `file_get_contents()` così com'è. Il tester prova più interpretazioni (`public_path()`, `storage_path()`, ...) e riporta quale ha funzionato; **da verificare a mano su un'installazione con chiave già salvata che anche `QlikHelper` riesca a leggerla** (in DB locale non c'erano configurazioni per provarlo).
- I testi tecnici (URL, Key ID, Issuer, SAN, nomi header, "JWT header/payload") restano non tradotti perché sono nomi propri/tecnici.

## Rollback

Rimuovere `app/Services/QlikConnectionTester.php`, il metodo `postTestConnection()` e il blocco `$testConnectionJs`/`$this->script_js .= ...` da `QlikConfController.php`, e le chiavi `qlik_test_*` dai due file lingua. Nessuna migrazione.
