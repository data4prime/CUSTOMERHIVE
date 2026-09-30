# 182 - Sync app Qlik Cloud: HTTP 400 su api/v1/items

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / Bug fix
- **File/aree di codice coinvolte**:
  - `app/Services/QlikSync/SaasQlikDriver.php` (`listApps()`)

## Contesto

La sincronizzazione delle app (run 27, conf 3, Qlik Cloud) falliva subito con
"Qlik ha risposto con HTTP 400 (api/v1/items)".

## Situazione prima

`listApps()` chiamava `/api/v1/items?resourceType=app&limit=100&sort=name`.
Qlik Cloud rifiuta `sort=name` (senza segno): risposta 400 con errore
`COLLECTIONS-2-0 bad_request`. Riprodotto dal container con la stessa
chiamata autenticata.

## Situazione dopo

`sort=%2Bname` (ordinamento crescente esplicito): stessa chiamata, risposta 200
con l'elenco delle app, sempre in ordine alfabetico.

## Motivazione

Il parametro `sort` di Qlik Cloud richiede il prefisso `+`/`-`. Provate dal
container anche la chiamata senza `sort` (200) e con `sort=%2Bname` (200).

## Test

Chiamata reale dal container (login JWT + GET items) per le tre varianti; lint
del file. Non rilanciata la sincronizzazione completa dalla UI.

## Rischi e note

Il segno `+` va codificato (`%2B`), altrimenti il server lo legge come spazio.
Il ramo on-premise non è toccato.

## Rollback

Ripristinare `sort=name` (o togliere `sort`) nell'URL di `listApps()`.
