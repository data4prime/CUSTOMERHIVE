# 078 - API Generator: tutti gli endpoint riservati al superadmin

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiCustomController.php`
  - `tests/Feature/ApiCustomCrudTest.php`

## Contesto

"Punto A" di [065](065-api-generator-rce-e-test.md): in quell'intervento
era stata corretta la RCE raggiungibile tramite questo gap, ma non il gap
stesso (rimandato di proposito, poi in backlog come priorità alta). Ripreso
su richiesta.

## Situazione prima

`CBBackend` verifica solo "sei loggato". Nel controller avevano un
controllo `isSuperadmin()` solo `getIndex()`, `getGenerator()`,
`getEditApi()`. Senza alcun controllo di privilegio, usabili da **qualunque
utente autenticato** (anche un Viewer senza permessi):

| Endpoint | Effetto |
|---|---|
| `POST save-api-custom` | crea/modifica API generate (scrive controller PHP su disco) |
| `GET delete-api/{id}` | cancella un'API e il suo file controller |
| `GET screet-key` | **elenca tutte le API key in chiaro** |
| `GET generate-screet-key` | crea una nuova API key attiva e la restituisce |
| `GET status-apikey` | attiva/disattiva una API key |
| `GET delete-api-key` | cancella una API key |
| `GET column-table/{tabella}` | elenca le colonne di qualunque tabella del DB |
| `GET download-postman` | esporta l'elenco delle API (URL, parametri) |

Il backlog citava i primi 5 metodi di scrittura; `screet-key`,
`column-table` e `download-postman` sono emersi rileggendo il controller.

Un test (`test_caratterizzazione_postsaveapicustom_e_raggiungibile_da_utente_senza_alcun_permesso`)
fissava esplicitamente il comportamento vulnerabile, per accorgersi del
giorno in cui fosse stato corretto.

## Situazione dopo

- Nuovo helper privato `denyUnlessSuperadmin($name)`: stesso controllo,
  stesso log (`log_try_view`) e stesso redirect `denied_access` già usati
  da `getIndex()`/`getGenerator()`/`getEditApi()`. Applicato agli 8
  endpoint sopra (i 3 già protetti non toccati).
- Test di caratterizzazione convertito in regressione
  (`test_postsaveapicustom_nega_accesso_a_utente_senza_alcun_permesso`:
  rifiutato, nessuna riga né file creato) + nuovo
  `test_endpoint_api_key_e_gestione_api_negano_accesso_a_tenant_admin`
  (7 endpoint GET da tenant admin con il modulo visibile: tutti rifiutati,
  nessuna API key esposta/creata/modificata/cancellata, nessuna API
  cancellata).

**Comportamento visibile che cambia**: un utente non superadmin che chiami
questi URL riceve "accesso negato" invece di eseguire l'azione. L'interfaccia
dell'API Generator era già inaccessibile ai non superadmin (pagina indice
bloccata), quindi nessun flusso legittimo tramite UI viene toccato.

## Motivazione

Il modulo è già concettualmente riservato al superadmin (la sua UI lo era):
i singoli endpoint non lo erano. Esporre le API key a qualunque utente
loggato equivale a dargli accesso a tutte le API custom.

## Test

- Manuale via curl da superadmin: `api_generator`, `screet-key`,
  `column-table/cms_users/list`, `download-postman`,
  `delete-api-key?id=999999`, `delete-api/999999` → 200, risposte invariate
  (es. `{"status":0}` su id inesistente). Da non loggato → login.
- Caso non superadmin: coperto dai 2 test automatici sopra (in locale non
  c'è un utente non superadmin con password nota). Eseguiti su richiesta
  il 2026-09-24 insieme a `StatisticBuilderCrudTest` e
  `ModuleGeneratorCrudTest`: 56 test / 217 asserzioni verdi (i 21 di questo
  file inclusi); DB di sviluppo verificato intatto dopo il run.
- `php -l` sul controller e sul file di test.

## Rischi e note

- Se in produzione qualche integrazione chiamasse questi endpoint con un
  utente non superadmin (improbabile: sono endpoint di amministrazione
  dell'API Generator, non le API generate stesse, che restano sotto
  `api/...` con la loro autenticazione), smetterebbe di funzionare.
- Le API generate (`api/{permalink}`) non sono toccate.
- `apiDocumentation()` non ha il prefisso `get`: non è registrata come
  route da `routeController()`, lasciata com'è.

## Rollback

Rimuovere le chiamate a `denyUnlessSuperadmin()` (o l'helper) dal
controller; ripristinare il test di caratterizzazione dal git history.
