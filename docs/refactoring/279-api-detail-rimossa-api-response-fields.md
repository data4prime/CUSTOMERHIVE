# 279 - API detail: rimossa la chiave api_response_fields dalla risposta

- **Data**: 2026-10-08
- **Stato**: Completato
- **Area**: API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ApiController.php` (`execute_api()`, ramo `detail`)

## Contesto

La risposta di un endpoint `detail` conteneva, prima dei dati del record,
la chiave `api_response_fields` con l'elenco dei campi configurati. Richiesta
di toglierla.

## Situazione prima

`{"api_status":1,"api_message":"success","api_response_fields":[...], ...campi del record}`.
La chiave veniva restituita anche quando `can_view()` svuotava il record, e
nel `detail` stava allo stesso livello dei campi del record.

## Situazione dopo

La chiave non e' piu' presente nel `detail`. Restano `api_status`,
`api_message`, `api_authorization` (solo in debug mode) e i campi del record.
Vale sia per `api/*` che per `api2/*` (codice condiviso). Le altre azioni
(`list`, ecc.) non sono toccate.

## Motivazione

Elenco ridondante rispetto ai dati restituiti e non legato ai permessi.

## Test

`php -l` OK. Suite non eseguita. Nessun test o altro codice leggeva la chiave.

## Rischi e note

Cambia la risposta visibile: eventuali client esterni che leggono
`api_response_fields` dal `detail` smettono di trovarla (nel repo non risulta
usata).

## Rollback

Riaggiungere `$result['api_response_fields'] = $responses_fields;` nel ramo
`detail`, prima del blocco `api_debug_mode`.
