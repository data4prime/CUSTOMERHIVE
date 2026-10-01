# 184 - CI: `queue:restart` dopo il deploy su dev

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: CI
- **File/aree di codice coinvolte**:
  - `.github/workflows/deploy.yml` (job `deploy-dev`)
  - `docs/cicd-pipeline.md`

## Contesto

La sincronizzazione app/item Qlik (176-178) gira su una coda Laravel
(`qlik_sync`) consumata da un worker. In locale il worker è il servizio
`worker` di `docker-compose.yml`, da riavviare a mano dopo ogni aggiornamento
del codice. Su `dev.thecustomerhive.com` il worker deve girare come processo
permanente (supervisor/systemd).

## Situazione prima

Il deploy su dev eseguiva `git reset`, `composer install`, `migrate` e
`optimize:clear`, ma non riavviava i worker. Un worker è un processo lungo che
tiene in memoria il codice caricato all'avvio: dopo un deploy continuava a
eseguire il codice vecchio finché qualcuno non lo riavviava a mano.

## Situazione dopo

Lo script SSH del deploy termina con `php artisan queue:restart`. Il comando
segnala ai worker di uscire dopo il job in corso; il supervisor li rilancia
con il codice nuovo.

## Motivazione

Equivalente su server di `docker compose restart worker`. Non interrompe i job
in corso (uscita graziosa) e non richiede privilegi sul supervisor.

## Test

Nessun test automatico (modifica al workflow). Da verificare al primo deploy
su dev dopo il push.

## Rischi e note

- `queue:restart` scrive un timestamp nella cache: funziona solo se la cache
  è condivisa tra CLI e worker (driver `file` sullo stesso storage va bene).
- Se sul server non c'è un supervisor/systemd che rilancia il worker, il
  comando lo spegne e basta. Il worker permanente va configurato sul server
  (vedi `docs/pre-push-checklist.md`).
- Il comando non fallisce se non ci sono worker attivi.
- `staging` e `main` non sono coperti: la pipeline li deploya ancora a mano.

## Rollback

Rimuovere la riga `php artisan queue:restart` dallo script SSH in
`.github/workflows/deploy.yml`.
