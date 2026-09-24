# 085 - Pulizia file inutilizzati e branch remoti obsoleti

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Housekeeping
- **File/aree di codice coinvolte**:
  - `phpunit.xml.bak` (rimosso)
  - `5.0` (rimosso)
  - `public/vendor/crudbooster/assets/assets/` (rimossa, 15 MB / 1014 file)
  - Branch remoti `origin/*` (8 cancellati)

## Contesto

Richiesta esplicita dell'utente: controllare se ci sono file non più
necessari e ripulire i branch obsoleti già segnalati nel backlog di
`README.md` ("Branch remoti obsoleti da ripulire... chiarire quali sono
ancora utili prima di fare pulizia").

## Situazione prima

**File**: il segnale automatico di `tokensave_dead_code` su questo
progetto è inaffidabile (verificato su 2 campioni: `ValidateSignare.php`
e `QlikItemsController.php`, entrambi falsi positivi — sono referenziati
tramite wiring "stringly-typed" che il grafo statico non traccia:
`Kernel::$routeMiddleware`, route registrate da stringa in
`routes/web.php`). Verificati invece manualmente (zero riferimenti in
tutto il repo, tracciati o no) 3 elementi genuinamente morti:
- `phpunit.xml.bak`: config PHPUnit 9 pre-upgrade (`backupGlobals`,
  `whitelist`, opzioni non più valide su PHPUnit 10/11), superata da
  `phpunit.xml`.
- `5.0`: file vuoto (0 byte) alla radice del repo, commit message
  "version" — probabile redirect sbagliato di un comando, mai referenziato.
- `public/vendor/crudbooster/assets/assets/`: duplicazione ricorsiva già
  identificata in [036](036-rimozione-assets-legacy.md) come "possibile
  pulizia futura separata" ma mai eseguita.

**Branch**: 8 branch remoti fermi dal 2024 (`bootstrapupdate`, `ckeditor`,
`license-local`, `main_backup`, `main_backup2`, `master`, `qlikdashboard`,
`sapienza`), mai verificati prima d'ora.

## Situazione dopo

**File**: rimossi con `git rm`/`git rm -r` i 3 elementi sopra (staging,
non ancora committato — commit lasciato a decisione esplicita
dell'utente).

**Branch**: verificato con `git merge-base --is-ancestor origin/<branch>
origin/main` e `origin/dev` per ciascuno degli 8 — tutti risultati
ancestor completo sia di `main` che di `dev`, **zero commit unici**
(`git rev-list --left-right --count origin/dev...origin/<branch>` → 0
a destra per tutti). Cancellati con `git push origin --delete` in un
singolo comando.

## Motivazione

File: ridurre rumore/dimensione del repo senza cambiare comportamento
(nessun riferimento trovato). Branch: `git merge-base --is-ancestor` con
0 commit unici è la verifica più forte possibile che un branch non
contenga lavoro non ancora integrato — a differenza di un controllo
"ultimo commit vecchio", questo garantisce che cancellare il branch non
perde nulla, a prescindere da quanto tempo fa sia stato toccato.

## Test

- `git status --short` sui path rimossi: tutti in stato `D` (staged).
- `git push origin --delete` sugli 8 branch: tutti confermati
  `[deleted]` da GitHub, nessun errore/rifiuto (nessuna protezione di
  branch attiva su di essi, nessuna PR aperta che li referenziasse —
  `gh` non disponibile in sessione per un controllo diretto delle PR,
  ma tutti privi di commit unici rende la cosa comunque non rilevante).
- Non verificato: comportamento a runtime dell'app dopo la rimozione dei
  3 file (nessuno dei tre è mai caricato da PHP/Laravel a runtime, solo
  `phpunit` legge `phpunit.xml` — non il `.bak` — quindi rischio nullo).

## Rischi e note

- La rimozione dei 3 file resta solo nello staging locale: va committata
  esplicitamente (regola del progetto, nessun commit automatico).
- Cancellare branch remoti è irreversibile lato GitHub oltre la finestra
  di recovery di reflow (i commit restano comunque raggiungibili per un
  periodo tramite i reflog/eventi di GitHub, essendo `git merge-base`
  confermato ancestor di `dev` — nessun contenuto è comunque andato
  perso, dato che ogni commit di quei branch vive già nella storia di
  `dev`/`main`).
- Segnalato ma **non toccato**: il grafo tokensave ha prodotto ~547
  falsi positivi di "dead code" su hook CRUDBooster (`cbInit`,
  `hook_*`) e classi Eloquent/controller referenziate solo da stringa —
  non riutilizzabile come lista di rimozione senza verifica puntuale
  caso per caso.

## Rollback

File: `git restore --staged --worktree phpunit.xml.bak 5.0
public/vendor/crudbooster/assets/assets` prima del commit; dopo il
commit, `git revert`. Branch: nessun rollback diretto (branch cancellati
su origin) — ricreabili da un checkout locale se qualcuno li avesse
ancora, o dai commit stessi (tutti presenti nella storia di `dev`) con
`git branch <nome> <hash>` + push, se mai necessario.
