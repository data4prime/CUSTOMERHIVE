# 306 - Aggiornamento di Laravel 13.29.0 → 13.30.1 (ultima advisory di `composer audit`)

- **Data**: 2026-10-16
- **Stato**: Completato (app avviata e rotte caricate; suite di test non lanciata)
- **Area**: Dipendenze / Sicurezza
- **File/aree di codice coinvolte**:
  - `composer.lock` (solo `laravel/framework`); `composer.json` non toccato da questo intervento

## Contesto

Il doc 304 aveva chiuso 3 advisory su 4 e lasciato aperta quella di `laravel/framework` (XSS nella pagina di debug, gravità bassa, corretta in
13.30.0), perché il tentativo di aggiornare Laravel con `--with-dependencies` trascinava 34 pacchetti e Symfony da 7.4 a 8.1 (salto di versione
maggiore). Il 2026-10-16 si è visto che il salto non era necessario.

## Situazione prima

Laravel v13.29.0, Symfony 7.4.x. `composer audit`: 1 advisory (PKSA-d5tc-s1qs-h781, `>=13.0.0,<13.30.0`).

## Situazione dopo

`composer update "laravel/framework:~13.30.0"` **senza** `--with-dependencies`: cambia un solo pacchetto, `laravel/framework`
v13.29.0 → v13.30.1; Symfony e tutto il resto restano come erano (la simulazione con `--dry-run` lo aveva confermato). `composer audit`: nessuna
advisory. `composer validate` ok.

## Motivazione

Chiude l'ultima advisory con il cambiamento più piccolo possibile. Alternative scartate: aggiornare a 13.37 (la più recente) fissando Symfony
a 7.4: possibile, ma sposta 30 pacchetti, senza un motivo; aggiornare con `-W`/`--with-dependencies` senza vincoli: porta Symfony 8.

## Test

Verificato (Docker locale, PHP 8.5.11): `php artisan about` (Laravel 13.30.1), `route:list` (si carica, nessun errore), `view:clear` e
`config:clear`, `composer audit`, `composer validate`.

NON verificato: suite di test (da lanciare su richiesta) e i flussi reali a runtime (login, licensing, CRUD, export Excel/PDF, upload).

Letti i titoli dei PR di 13.30.0 e 13.30.1 (solo i titoli, non il codice). Cambiamenti di comportamento potenzialmente rilevanti:
- `Storage::path()` è confinato alla radice del disco: nel repo è usato solo in `ConnectorService` con `path('license.json')` (nome semplice,
  non toccato); **i controller custom dei clienti non sono nel repo e non sono stati controllati**.
- la pagina di debug non mostra più HTML nei tooltip delle eccezioni (la correzione di sicurezza);
- `queue:work` stampa il motivo dello stop, `WorkerStopping` porta connessione e coda: solo informativo;
- correzioni per SQL Server (`update()` con `limit()`) e cache Redis con tag: non usati, per quel che si sa; non verificato quale driver di cache
  usano le installazioni in produzione.

## Rischi e note

- Le istanze e i clienti già in produzione ricevono l'aggiornamento solo quando vengono ricostruiti/aggiornati.
- Da controllare per ogni cliente, prima dell'aggiornamento: controller custom che usano `Storage::...->path()` o `Storage::path()` con percorsi
  che escono dalla radice del disco.
- Resta comunque aggiornato dentro la stessa linea 13.x: nessuna modifica del codice dell'app è stata necessaria.

## Rollback

Ripristinare `composer.lock` e `composer install` (oppure `composer update laravel/framework:13.29.0`). Riaprirebbe l'advisory.
