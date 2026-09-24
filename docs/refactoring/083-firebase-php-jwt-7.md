# 083 - `firebase/php-jwt` 6 → 7 (CVE-2025-45769) con protezioni per chiavi deboli

- **Data**: 2026-09-24
- **Stato**: Completato (da verificare sui clienti prima del deploy, vedi sotto)
- **Area**: Dipendenze / Sicurezza
- **File/aree di codice coinvolte**:
  - `composer.json` (`^6.10` → `^7.0`) / `composer.lock` (6.10.1 → 7.2.0)
  - `app/Helpers/ChatAIHelper.php` (`getToken()`, HS256)
  - `app/Helpers/QlikHelper.php` (`getJWTToken()`, `getJWTTokenOP()`, RS256)
  - `app/Http/Controllers/System/AdminChatAIController.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Unica vulnerabilità trovata da `composer audit` sul `composer.lock`
(vedi [082](082-rimossa-build-gulp-e-package-json.md) per il resto degli
avvisi Dependabot): `firebase/php-jwt` < 7.0.0, CVE-2025-45769
(GHSA-2x45-7fc3-mxwq, "weak encryption", severità **bassa**). Dipendenza
diretta, nessun altro pacchetto la richiede.

Primo tentativo di aggiornamento secco annullato dopo aver scoperto il
cambio di comportamento sotto; ripreso con le protezioni su decisione
dell'utente (opzione "aggiorna a 7.x con protezioni").

## Situazione prima

La 6.x firma token JWT con qualunque chiave. Usi nel progetto (solo
`JWT::encode()`, firma compatibile tra 6 e 7):

- `ChatAIHelper::getToken()`: **HS256** con la "Passphrase" inserita a mano
  nella configurazione Chat AI (`chatai_confs.token`, nessun vincolo di
  lunghezza).
- `QlikHelper::getJWTToken()` / `getJWTTokenOP()`: **RS256** con la chiave
  privata della configurazione Qlik (`qlik_confs.private_key` = percorso di
  un file PEM sul server).

Già con la 6.x una chiave Qlik vuota/illeggibile faceva lanciare a
`encode()` una `DomainException` ("OpenSSL unable to validate key") che
arrivava fino alla pagina (500).

## Cosa cambia con la 7.x (verificato)

La 7.x rifiuta le chiavi deboli (è il contenuto della fix della CVE):
HS256 → chiave **≥ 32 byte**; RSA → **≥ 2048 bit**. Prova diretta con la
7.2.0: passphrase di 10 caratteri e chiave RSA 1024 bit →
`DomainException: Provided key is too short`; 32 caratteri / 2048 bit → OK.

## Situazione dopo

- `firebase/php-jwt` 7.2.0 (`composer audit`: nessuna vulnerabilità).
- `ChatAIHelper::getToken()`: `encode()` in try/catch; su chiave non
  valida → log `warning` ("ChatAI JWT non generato (conf N): …") e
  `return false` (lo stesso valore già restituito se la configurazione non
  esiste). Protegge anche `chat_ai/view.blade.php`, che chiama `getToken()`
  all'apertura della chat e prima sarebbe andata in 500.
- `QlikHelper::getJWTToken()`/`getJWTTokenOP()`: `encode()` in try/catch;
  su chiave non valida → log `warning` e token vuoto `''`. I chiamanti in
  `AdminQlikItemsController` gestiscono già il token vuoto ("JWT Token
  generation failed!"); widget/mashup ricevono un token vuoto invece di
  un 500. Copre anche il caso preesistente di chiave vuota/illeggibile.
- `AdminChatAIController::send_message()`/`send_message_agent()`: se il
  token manca, risposta `{"message": trans('crudbooster.chatai_token_error')}`
  (stesso formato dell'errore di rete già esistente) invece di chiamare il
  servizio AI con un token vuoto.
- Form Chat AI: campo Passphrase con validazione `min:32` e help text
  tradotto (`crudbooster.chatai_passphrase_help`), così nuove
  configurazioni e modifiche sono già conformi.
- Nuove chiavi di traduzione en/it: `chatai_passphrase_help`,
  `chatai_token_error`.

**Comportamento visibile che cambia**: su un'installazione con passphrase
Chat AI < 32 caratteri o chiave Qlik RSA < 2048 bit, dopo il deploy la chat
AI mostra il messaggio di errore e gli embed Qlik JWT non si autenticano
(invece di funzionare con la chiave debole). Salvare una configurazione Chat
AI ora richiede una passphrase di almeno 32 caratteri.

## Da fare sui clienti PRIMA del deploy

Da eseguire sul server di ogni cliente (io non accedo ai server):

```sql
-- passphrase Chat AI troppo corte (0 righe = ok)
SELECT id, title, CHAR_LENGTH(token) AS lunghezza
FROM chatai_confs
WHERE deleted_at IS NULL AND CHAR_LENGTH(token) < 32;

-- percorsi delle chiavi Qlik da controllare
SELECT id, confname, private_key FROM qlik_confs WHERE auth = 'JWT';
```

Per ogni file `private_key`:

```
openssl rsa -in /percorso/chiave.pem -noout -text | head -1
# deve riportare "Private-Key: (2048 bit ...)" o superiore
```

Se una passphrase è corta va sostituita con una da ≥ 32 caratteri **sia in
CustomerHive sia sul servizio AI che verifica il token** (altrimenti la
firma non combacia). Una chiave Qlik < 2048 bit va rigenerata e
ricaricata anche lato Qlik (JWT IdP / virtual proxy).

## Motivazione

Chiude la CVE senza rompere in silenzio le installazioni con chiavi deboli:
chi ha una configurazione conforme non vede differenze, chi non ce l'ha vede
un messaggio chiaro e un log invece di un 500, e le nuove configurazioni
non possono più essere deboli.

## Test

- Script usa-e-getta (poi cancellato), con fixture in transazione e
  rollback: ChatAI passphrase 10 caratteri → `false` gestito; 40 caratteri
  → token generato; Qlik RSA 1024 bit → `''` gestito; 2048 bit → token
  generato. Warning presenti nel log; tabelle `chatai_confs`/`qlik_confs`
  tornate a 0 righe.
- `composer audit --locked` → nessuna vulnerabilità.
- `php -l` sui file toccati.
- **Non verificato dal vivo**: la validazione `min:32` sul form Chat AI (in
  locale il modulo Chat AI non è registrato in `cms_moduls`, le sue pagine
  CRUD non esistono — vedi backlog) e i messaggi nella UI della chat.

## Rischi e note

- Il vero rischio è sui dati dei clienti (sezione sopra), non sul codice.
- Nota di ambiente: l'hook globale `~/.config/git/hooks/post-checkout` di
  tokensave fallisce in Git Bash (`syntax error near unexpected token '('`)
  perché il percorso `C:/Program Files (x86)/tokensave-v7.10.0-.../tokensave.exe`
  non è tra virgolette (e punta ancora alla 7.10.0 mentre è installata la
  7.12.1). Il checkout riesce comunque, fallisce solo l'hook.

## Rollback

Ripristinare `composer.json`/`composer.lock` dal git history e fare
`composer install` (torna la 6.10.1); le protezioni nel codice possono
restare (sono compatibili anche con la 6.x).
