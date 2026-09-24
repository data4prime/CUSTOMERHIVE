# CustomerHive — istruzioni per Claude Code

Questo file vale per chiunque usi Claude Code su questo repo. Regole
comportamentali prima, contesto tecnico dopo.

## Regole ferme (non derogabili senza chiederlo esplicitamente)

- **Niente commit/push in autonomia.** Modificare file va bene; `git
  add`/`commit`/`push` restano una decisione esplicita della persona che
  guida la sessione. Non proporlo nemmeno a fine risposta ("procedo con il
  commit?") — l'iniziativa deve venire da lei/lui.
- **Niente accesso diretto ai server** (SSH o altro) su `dev.thecustomerhive.com`,
  `staging.thecustomerhive.com` o i server dei clienti in produzione, nemmeno
  per verifiche rapide. Se serve fare/controllare qualcosa lì, dare i comandi
  esatti da eseguire e aspettare l'esito riportato dalla persona.
- **Niente esecuzione della suite di test in autonomia** dopo ogni modifica.
  Va lanciata solo su richiesta esplicita. Per verifiche leggere va bene un
  lint (`php -l`), leggere il diff, o test manuali mirati.
- **CustomerHive è già in produzione presso clienti reali.** Ogni modifica a
  comportamento esistente (login, licensing, dati, CRUD) va trattata come
  *behavior-preserving*: cambiamenti piccoli e verificabili, non riscritture
  ampie "e si spera funzioni". Se una modifica cambia comportamento visibile
  (non solo struttura interna), dirlo esplicitamente.
- **Ogni testo visibile in UI va aggiunto in inglese E in italiano, mai
  hardcoded in una sola lingua.** Usare `trans('crudbooster.chiave')` (mai
  stringhe letterali in blade/PHP per testo mostrato all'utente), aggiungendo
  la stessa chiave sia in `resources/lang/en/crudbooster.php` sia in
  `resources/lang/it/crudbooster.php`. Per testo generato via JS (embedded in
  uno `<script>`), passare la stringa già tradotta da Blade con
  `{!! json_encode(trans('crudbooster.chiave')) !!}`, non scriverla in chiaro
  nel JS. Vale per help text, messaggi di validazione custom, hint dinamici,
  label di bottoni/campi — qualunque cosa l'utente legga.
- **Dentro questa cartella** (ambiente Docker locale, non i server remoti) è
  invece normale eseguire liberamente comandi di verifica — `docker compose
  exec/up`, `php artisan migrate/route:list`, lint, ecc. — senza fermarsi a
  chiedere il permesso per ognuno. Restano un'eccezione le operazioni
  distruttive non necessarie (drop di tabelle, reset del DB locale, force
  push) e comunque mai su server remoti (vedi sopra).

## Documentare sempre il lavoro fatto

- **Ogni intervento** (bug fix, refactor, hardening, nuova feature — non
  solo le riscritture grosse) va documentato **in automatico**, senza
  bisogno che venga chiesto: copiare `docs/refactoring/_template.md` in
  `docs/refactoring/NNN-titolo-breve.md` (NNN = prossimo numero libero,
  vedi l'ultimo usato in `docs/refactoring/README.md`), compilare le
  sezioni (Contesto, Situazione prima/dopo, Motivazione, Test, Rischi e
  note, Rollback) e aggiungere la riga corrispondente nella tabella di
  `docs/refactoring/README.md`. Le sezioni "Contesto" e "Situazione prima"
  vanno scritte prima di modificare il codice quando possibile.
- **A fine giornata di lavoro** (o comunque a fine sessione, quando il
  lavoro su un certo giorno si può considerare concluso), scrivere/aggiornare
  `docs/riassunti-giornalieri/YYYY-MM-DD.md` con un riassunto di tutto
  quello che è stato fatto quel giorno — stesso stile dei file già presenti
  in quella cartella: titolo (`# YYYY-MM-DD — sintesi`), una sezione per
  ogni intervento/argomento affrontato, e in fondo una sezione "Commit di
  oggi" con gli hash e una riga di descrizione per ciascuno (solo se sono
  stati effettivamente fatti commit quel giorno).

## Dove gira l'app in locale — controllare prima di cercare altrove

L'ambiente di sviluppo locale di **questo progetto** è **sempre Docker**
(`docker-compose.yml` in root, servizi `customerhive_app` e `customerhive_db`)
— mai XAMPP, un PHP/MySQL installato direttamente sull'host, o altri
progetti/ambienti eventualmente presenti sulla stessa macchina. Prima di
cercare un binario (`php`, `mysql`, `mysqlbinlog`, ecc.) o assumere dove
gira l'app:

1. Controllare se il container è già attivo: `docker compose ps` (dalla
   root del repo).
2. Se non è attivo, avviarlo con `docker compose up -d` (rientra nei
   comandi eseguibili senza chiedere permesso, vedi sopra) invece di cercare
   un'installazione alternativa sull'host.
3. Eseguire i comandi **dentro** il container (`docker compose exec app
   php ...`, `docker compose exec db mysql ...`), non con un eventuale `php`/
   `mysql` installato sull'host — anche se disponibile, non è detto sia la
   stessa versione/configurazione, e potrebbe appartenere a un progetto
   diverso installato sulla stessa macchina (es. una XAMPP usata per
   tutt'altro).

Se un tool non è disponibile dentro il container (es. `mysqlbinlog` non
incluso nell'immagine `mysql-community-server-minimal`), è un limite reale
da segnalare — non un motivo per cercarlo sull'host assumendo che sia lo
stesso ambiente.

## Se è disponibile il tool MCP tokensave

Questo repo è indicizzato da **tokensave** (code graph MCP). Se i tool
`mcp__tokensave__*` sono disponibili nella sessione, usarli al massimo per
qualunque ricerca/lookup — non solo su codice, anche su doc/README/config
tracciati nel repo — invece di `grep`/`find`/`cat` via Bash:

- `tokensave_context` / `tokensave_search` per ricerche per simbolo o testo
  (con `literal: true` per stringhe esatte, anche su file `.md`).
- `tokensave_files` per trovare file per nome/pattern.
- `tokensave_callers`/`tokensave_callees`/`tokensave_impact` per capire cosa
  chiama/dipende da cosa prima di modificare qualcosa.
- Bash resta per ciò che tokensave non fa: eseguire comandi (git, docker,
  artisan, composer), ispezionare processi/container, tutto ciò che muta
  stato o esegue codice.

Se un lookup testuale via Bash viene bloccato da un controllo dei permessi,
è il segnale per usare tokensave, non per aggirare il blocco con variabili
d'ambiente o flag — e non per usare un agente Explore al posto di tokensave
quando tokensave è disponibile.

**Mai usare `TOKENSAVE_DISABLE_GREP_HOOK=1`** (o prefissi/flag equivalenti)
per far passare comunque un `grep`/`find`/`cat` bloccato dall'hook di
tokensave. Se il pattern sembra un simbolo/nome di funzione, usare
`tokensave_search`; per una stringa letterale esatta (anche dentro file
`.md`/doc), `tokensave_search` con `literal: true`. Il blocco dell'hook è
il segnale per cambiare tool, non per aggirarlo — aggirarlo spreca token
esattamente come l'agente Explore che questa stessa regola vieta sopra, e
in più un comando Bash con un prefisso di variabile d'ambiente non è
analizzabile staticamente dal controllo permessi, quindi fa comunque
comparire una richiesta di conferma anche dentro la cartella del progetto.

## Comandi Bash semplici (evitano prompt di conferma inutili)

Il controllo permessi (`permissions.blockReadsOutsideWorkingDirectories`)
verifica staticamente, leggendo il testo del comando, che i file letti
restino dentro la cartella di lavoro. Su un comando semplice ci riesce; su
un comando composito (più istruzioni incatenate con `&&`, pipe come `|
head`/`| grep`, più righe nello stesso blocco, o un prefisso `cd "..." &&`)
spesso non riesce a dimostrarlo e chiede conferma — anche per operazioni di
sola lettura, anche dentro la cartella del progetto, indipendentemente dal
permesso di eseguire comandi liberamente qui dentro. `git diff <path>` in
particolare non è riconosciuto come un "leggi questo file" ovvio quanto
`cat <path>`.

Per evitare questi prompt evitabili:
- **Non ripetere `cd "..." &&`** a inizio comando: la working directory
  resta impostata da una chiamata Bash all'altra all'interno della stessa
  sessione, non serve reimpostarla ogni volta.
- Per una singola lettura (un file, un `git diff` su un path, un `git log`)
  preferire **un comando semplice per chiamata** invece di incatenarne
  più con `&&`/pipe quando non è necessario.

**Tenere sempre aggiornato l'indice di tokensave** (non il tool in sé): dopo
un batch di modifiche ampio, un cambio di branch, o se i risultati sembrano
non riflettere lo stato attuale del codice (`tokensave_status` mostra
`last_sync_at` vecchio rispetto alle modifiche fatte), lanciare `tokensave
sync` (incrementale, dentro la cartella del progetto) per riallineare
l'indice invece di fidarsi ciecamente di risultati potenzialmente non
aggiornati.

## Cos'è questo progetto

Laravel (attualmente v13) + un fork di **CRUDBooster** (motore
CRUD/auth/routing/menu). Il pacchetto vendorizzato `packages/crocodicstudio/`
non esiste più come cartella (uscito il 2026-08-28): il suo codice vive ora
sotto `app/` come classi normali dell'app (es.
`app/Http/Controllers/System/CBController.php`,
`app/Helpers/CRUDBooster.php`). I vecchi namespace/alias
(`crocodicstudio\crudbooster\controllers\...`, `CRUDBooster::`, la vista
`crudbooster::...`, le tabelle `cms_*`) sono stati **deliberatamente
mantenuti** invece di rinominarli ovunque — la compatibilità è garantita da
alias (`class_alias()`) in `app/Support/legacy_crudbooster_aliases.php`. La
rimozione dei riferimenti testuali a "crudbooster" è pianificata ma
rimandata: tocca 1400+ occorrenze e anche i controller custom dei singoli
clienti.

**Perché non si rinomina/rimuove subito**: ogni cliente ha controller/moduli
generati da interfaccia specifici (in `app/Http/Controllers/` fuori da
`System/`, sempre gitignored) copiati manualmente in fase di aggiornamento
(vedi sotto). Qualunque compatibilità retroattiva deve restare in piedi
finché *ogni* cliente non è stato aggiornato con quella migrazione — non
basta che il codice base sia pronto.

L'autenticazione è in migrazione additiva ("strangler fig") da sessione
custom (`Session::put('admin_id', ...)`) ai guard Laravel nativi
(`Auth::login()`): entrambe le vie scrivono/leggono in parallelo, i punti di
lettura vengono migrati uno alla volta. Dettagli in
`docs/refactoring/001-auth-guard-additivo-fase-1.md` e
`docs/refactoring/002-cbbackend-guard-fase-3.md`.

## Processo di aggiornamento di un cliente in produzione

Ambiente dev → si duplica il DB di produzione del cliente → si copiano i
file caricati (`storage/...`) e i controller/moduli custom di quel cliente
dentro la nuova versione del codice → si mette in produzione il risultato.
È un processo **per-cliente e asincrono** (ogni cliente aggiornato in un
momento diverso) — da tenere a mente per qualunque migrazione che tocchi i
controller custom dei clienti.

## Gotcha noti

- **Codice vendorizzato/personalizzato (CRUDBooster, AdminLTE) può avere JS
  patchato ad-hoc.** Prima di rimuovere un attributo `data-*`, una classe
  CSS o un id che sembrano "residuo morto" di un framework non più in uso,
  cercare il valore letterale nei file JS effettivi (non fermarsi a
  un'ipotesi plausibile basata sul naming) — un `data-bs-toggle="..."` può
  essere agganciato da jQuery vendorizzato anche se "sembra" Bootstrap 5 non
  usato altrove.
- L'ambiente Docker locale ha `vendor/`, `storage/framework/` e
  `bootstrap/cache/` su **volumi Docker nativi**, non bind mount (bind mount
  di `vendor/` è lentissimo su Docker Desktop Windows).
- `.env` richiede `APP_PATH` valorizzato, altrimenti il flusso di licenza
  fallisce.
- Nel blocco `environment:` del servizio `app` in `docker-compose.yml` non
  forzare `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`: blocca silenziosamente
  l'override di `.env.testing`, facendo girare i test sul DB di sviluppo
  vero invece che su `customerhive_testing`.
- I test automatici girano su **MySQL vero** (`customerhive_testing`), non
  SQLite — scelta deliberata (stesso motore della produzione).
- **CSRF è disabilitato globalmente** (`VerifyCsrfToken` commentato in
  `app/Http/Kernel.php`) — non dare per scontato che i form/endpoint siano
  protetti da CSRF. È un gap noto, pianificato come intervento separato
  (rischio diverso: riattivarlo può rompere form/AJAX senza token). Dettagli
  in `docs/login-e-licensing.md`.
- **I controlli di licenza sono attivi anche in locale**: se il login/uso
  del pannello si blocca senza una modifica di codice apparente, controllare
  prima se la tabella `license` ha una riga valida prima di sospettare un
  bug — dettagli in `docs/login-e-licensing.md`.

## Dove guardare per saperne di più

- `docs/refactoring/README.md` — indice di ogni intervento di refactoring/
  hardening tracciato (numerato, con contesto/prima/dopo/rischi).
- `docs/docker-local-dev.md` — setup ambiente Docker locale (incluse le
  credenziali dell'utente admin di test).
- `docs/login-e-licensing.md` — come funzionano oggi login, CSRF e licensing.
- `docs/test-coverage.md` — catalogo dei test automatici esistenti.
- `docs/pre-push-checklist.md` — cose da verificare prima di un push su `main`.
- `docs/cicd-pipeline.md` — come funziona oggi la pipeline CI/CD (solo `dev`
  automatizzato, `staging`/`main` ancora manuale).
- `docs/piano-testing-manuale.md` — scenario aziendale con dati concreti per
  i giri di test manuale.
