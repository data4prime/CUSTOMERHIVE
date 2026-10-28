# 305 - Header `x-app-version` nel check-in della licenza

- **Data**: 2026-10-16
- **Stato**: Implementato (lint ok, non provato end-to-end né con i test)
- **Area**: Licensing / Chive CP
- **File/aree di codice coinvolte**:
  - `app/Services/ConnectorService.php` (`writeLicense()`)

## Contesto

Chive CP (LICENSES 019) registra i check-in delle istanze Managed e vuole sapere che versione gira, per mostrarla e per segnalare le istanze
rimaste indietro rispetto all'ultima release. Il check-in è la chiamata `POST .../license` che l'app fa dopo il login e poi ogni ora.
Per le istanze Managed Chive CP conosce già il tag che ha impostato lui, ma non sa cosa gira davvero (per esempio dopo un aggiornamento fallito
a metà) e per le self-hosted, rimandate, sarebbe l'unica fonte.

## Situazione prima

La chiamata portava gli header `x-host`, `x-host-name`, `Authorization`, `Content-Type`, `Accept` e nessuna informazione sulla versione.

## Situazione dopo

Si aggiunge `x-app-version` con `config('app.version')` (`APP_VERSION` dell'immagine Docker, cioè `vX.Y.Z` o `dev-<sha7>`; altrimenti la
versione git di `MyHelper::version()`), **solo se** ha il formato `[A-Za-z0-9][A-Za-z0-9._+-]{0,63}`. Se è vuota o ha altri caratteri
l'header si omette e la chiamata parte come prima. Nessun altro comportamento cambia: corpo, URL, timeout e gestione degli errori sono
invariati.

## Motivazione

Informazione utile a Chive CP, senza costo per l'app. Il formato è validato in tre punti (qui, nel Gateway, in Chive CP) perché il valore
arriva da un client non fidato e finisce in un database e in un pannello.

## Test

`php -l`. Nessun test esistente controlla gli header di questa chiamata (cercato `x-host-name` in `tests/`: nessun risultato); suite non lanciata.
**Provato con Docker vero (2026-10-16)**: immagine costruita da `docker/Dockerfile.prod` con questa modifica (`APP_VERSION=checkin-test`), istanza creata
da Chive CP con il Gateway locale come `LICENSE_SERVER_URL`; `php artisan command:GetLicense` dentro l'istanza → Chive CP ha registrato
`app_version = checkin-test` (l'header ha attraversato app → Gateway → Chive CP) e l'istanza è passata da `awaiting_check_in` ad `active`
(LICENSES 019). Istanza poi eliminata con il job di Chive CP.
NON verificato: il caso di una versione non conforme al formato (coperto solo dai test di LICENSES e del Gateway), i clienti legacy con la versione git.

## Rischi e note

- **Cambia una chiamata di produzione di ogni cliente** (un header in più, ignorato da qualsiasi server che non lo conosca). Per i clienti già in
  produzione arriva solo quando vengono aggiornati con questa versione, come ogni altra modifica.
- Un LICENSES o un Gateway più vecchi, che non conoscono l'header, lo ignorano: nessuna dipendenza d'ordine nel rilascio.
- La versione è dichiarata dall'app e non è attendibile: serve solo a mostrarla.

## Rollback

Rimuovere il blocco `$appVersion` e tornare a passare l'array di header direttamente a `Http::withHeaders()`.
