# 180 - Qlik conf: upload della chiave privata `.pem` rifiutato

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/QlikConfController.php` (campo `private_key` in `cbInit`)

## Contesto

Caricando `privatekey (1).pem` nel campo "Private Key" del form
`admin/qlik_confs/edit/3` il salvataggio veniva rifiutato dalla validazione.

## Situazione prima

Il campo aveva `'validation' => 'mimes:pem'`. La regola `mimes` di Laravel non
guarda l'estensione del nome file: deduce l'estensione dal contenuto (MIME
sniffing). Un PEM è testo semplice, quindi il MIME è `text/plain` e
l'estensione dedotta è `txt`, mai `pem`: qualunque file `.pem` valido veniva
scartato (riprodotto nel container con un PEM di prova: `mimes:pem` → errore).

## Situazione dopo

`'validation' => 'extensions:pem'`: la regola (Laravel 11+) controlla
l'estensione dichiarata dal client, che è ciò che si voleva. Stesso
`upload` e stesso salvataggio di prima; cambia solo l'accettazione dei `.pem`
(prima nessuno, ora tutti). Nessun cambio di testo UI.

### Seguito: lettura della chiave dopo l'upload

Una volta caricato, il salvataggio del `.pem` funzionava ma associare un utente
Qlik da `admin/users/profile#qlik` dava `file_get_contents(/storage/uploads/1/
2026-09/privatekey_1.pem): Failed to open stream`. `CRUDBooster::uploadFile()`
salva un path relativo alla public root (`/storage/uploads/...`), mentre
`QlikHelper::getJWTToken()`/`getJWTTokenOP()` lo passavano tale e quale a
`file_get_contents()`, che lo cerca alla radice del filesystem. Aggiunto
`QlikHelper::readPrivateKey()`: se il valore inizia con `/storage/` e il file
esiste sotto `public_path()` lo legge da lì, altrimenti comportamento invariato
(URL assoluti e path di filesystem legacy). Verificato nel container: la chiave
già caricata (3243 byte) viene letta. Non verificato il flusso completo verso Qlik.

### Seguito 2: "Prova connessione" falliva su users/me (301)

Con la chiave letta, login JWT OK (HTTP 200) ma il passo `users/me` risultava
fallito: Qlik Cloud risponde `301` verso `/api/v1/users/<id>`, comportamento
normale che `QlikHelper::createUser()` gestisce con `FOLLOWLOCATION`, mentre
`QlikConnectionTester::httpStep()` non segue mai i redirect. In
`stepSaasLogin()` ora un redirect di `users/me` che resta sullo stesso host
sotto `/api/` viene seguito una volta (stessa sessione/cookie); ogni altro
redirect continua a essere segnalato come errore. Non verificato con Qlik reale
dopo la modifica.

## Motivazione

Alternativa scartata: `mimes:pem,txt` — accetterebbe anche qualunque testo
rinominato e non dice cosa si intende (un file `.pem`).

## Test

Nel container: `UploadedFile` di un PEM di prova con la regola `mimes:pem` →
fallisce, con `extensions:pem` → passa. Non verificato nel browser.

## Rischi e note

Il controllo è sull'estensione, non sul contenuto: non garantisce che il file
sia una chiave valida (lo verifica già il pulsante "Prova connessione").
Comportamento visibile: prima impossibile caricare un `.pem`, ora possibile.

## Rollback

Ripristinare `mimes:pem` nella riga del campo `private_key`.
