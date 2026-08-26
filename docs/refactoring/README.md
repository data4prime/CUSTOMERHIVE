# Tracciamento del refactoring

Questa cartella traccia, passo per passo, il percorso di modernizzazione del
progetto: aggiornamento di Laravel, miglioramento dell'architettura,
refactoring per stabilità/manutenibilità, semplificazione di
installazione/setup, rinnovo della UI/UX.

Obiettivo: chiunque (oggi o tra un anno) deve poter capire **cosa è cambiato,
perché, e come funzionava prima**, senza dover ricostruire il contesto da
zero leggendo i diff di git.

## Struttura

- **`glossario.md`** — termini/concetti ricorrenti nel progetto (CRUDBooster,
  tenant, licenza, guard, ecc.), definiti una volta sola e richiamati dagli
  altri documenti.
- **`_template.md`** — modello da copiare per ogni intervento di refactoring.
- **`NNN-titolo-breve.md`** — un file per ogni intervento, con la situazione
  prima/dopo. Numerati in ordine cronologico (`001-`, `002-`, ...).

## Come aggiungere un intervento

1. Copia `_template.md` in un nuovo file `NNN-titolo-breve.md` (`NNN` = numero
   progressivo successivo, `titolo-breve` in kebab-case).
2. Compila le sezioni **prima di iniziare a modificare il codice** (situazione
   "prima" e motivazione) e completa il resto a lavoro fatto.
3. Se introduci un termine nuovo o non ovvio, aggiungilo a `glossario.md`.
4. Aggiungi una riga nell'indice qui sotto.
5. Se l'intervento richiede di riattivare/rimuovere qualcosa prima del prossimo
   push, aggiungilo anche a [`../pre-push-checklist.md`](../pre-push-checklist.md).

## Indice degli interventi

| N.  | Titolo | Area | Stato | Data |
|-----|--------|------|-------|------|
| [001](001-auth-guard-additivo-fase-1.md) | Refactoring auth: guard Laravel additivo (Fase 1) | Auth | Completato | 2026-08-26 |
| [002](002-cbbackend-guard-fase-3.md) | Refactoring auth: primo file migrato al guard (Fase 3), fix logout | Auth | Completato | 2026-08-26 |
| [003](003-licensing-hardening.md) | Licensing: env configurabile, registerLicense() sicuro, opzione "ho già una licenza", riattivazione controlli | Licensing | Completato | 2026-08-26 |

**Stato**: `Pianificato` → `In corso` → `Completato` (o `Annullato` se si
decide di non procedere, motivando il perché nel file stesso).

## Roadmap refactoring auth (strategia additiva)

Percorso concordato per sostituire l'auth custom di CRUDBooster con i guard
Laravel nativi (vedi [001](001-auth-guard-additivo-fase-1.md) per il
dettaglio della strategia):

- ✅ Fase 0 — Ricognizione
- ✅ Fase 1 — Guard additivo su `postLogin()`
- 🔶 Fase 3 — Migrazione dei 42 file individuati in Fase 0, uno alla volta.
  **In corso, non come sprint dedicato**: si migra un file quando lo si
  tocca comunque per un altro motivo. `CBBackend` migrato in
  [002](002-cbbackend-guard-fase-3.md), **41 file ancora sul meccanismo
  legacy**.
- ⏸️ Fase 4 — Rimozione della scrittura della sessione legacy (solo quando
  la Fase 3 sarà sostanzialmente completa). **Messa in pausa esplicitamente
  dall'utente**, da riprendere più avanti — per ora si passa ad altre
  sezioni del progetto da refactorare.

## Backlog — emerso ma non ancora assegnato a un intervento numerato

Cose notate durante altri lavori (setup Docker, CI/CD), non ancora
trasformate in un intervento vero e proprio:

- **70 vulnerabilità segnalate da GitHub Dependabot** sul branch di default
  (2 critiche, 22 alte, 41 moderate, 5 basse) — da valutare come parte
  dell'audit di compatibilità dipendenze (vedi ordine di lavoro sotto).
- ~~`app/Http/Controllers/` nel `.gitignore`~~ — **voluto, non un rischio**:
  da interfaccia si possono creare moduli custom (controller generati), che
  devono restare specifici dell'ambiente in cui vengono creati e non
  finire nel repo condiviso. I 52 controller già tracciati lo erano prima
  che la regola venisse introdotta.
- ~~Compatibilità delle migration con SQLite non verificata~~ — risolto
  passando i test a MySQL vero (stesso motore della produzione), vedi
  [`../test-coverage.md`](../test-coverage.md).
- **Branch remoti obsoleti da ripulire**: `main_backup`, `main_backup2`,
  `sapienza`, `qlikdashboard`, `bootstrapupdate`, `ckeditor`,
  `license-local` — chiarire quali sono ancora utili prima di fare pulizia.
- **`AdminController::postLogin()` legge `$_SERVER['HTTP_HOST']`
  direttamente** invece che tramite l'oggetto `Request` di Laravel —
  funziona in produzione (Apache lo popola sempre) ma rende il codice
  testabile solo forzando a mano la superglobale nei test (vedi
  [`../test-coverage.md`](../test-coverage.md)). Da sistemare quando si
  affronterà il refactoring dell'auth (sostituzione con `request()->getHost()`
  o equivalente).

- **Bug lato server di licenza remoto** (`license.thecustomerhive.com`,
  progetto separato gestito dall'utente): il trial di attivazione
  restituisce `LicenseService::getLicenseByDomain(): Argument #1 ($domain)
  must be of type string, null given` — vedi
  [003](003-licensing-hardening.md#rischi-e-note). Da correggere in
  quel progetto, non qui.

## Documenti correlati

- [`../docker-local-dev.md`](../docker-local-dev.md) — ambiente di sviluppo locale
- [`../login-e-licensing.md`](../login-e-licensing.md) — sistema di login e licensing attuale
- [`../pre-push-checklist.md`](../pre-push-checklist.md) — cose da ripristinare/verificare prima di un push
- [`../test-coverage.md`](../test-coverage.md) — catalogo dei test automatici esistenti
