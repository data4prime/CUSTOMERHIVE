# 275 - Elenco API: conferma eliminazione, esempio curl discreto, Postman su /api2

- **Data**: 2026-10-08
- **Stato**: Completato (da verificare a vista)
- **Area**: API Generator / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/api_documentation.blade.php`
  - `public/css/ch-api.css`
  - `app/Http/Controllers/System/ApiCustomController.php` (`getDownloadPostman`)

## Contesto

Su `/admin/api_generator` (1) la barra "Eliminare l'endpoint?" era sempre
visibile su ogni endpoint, (2) l'esempio curl nel riquadro "Come collegarsi"
era un blocco nero molto vistoso, (3) l'export Postman generava ancora URL
su `/api` invece di `/api2`.

## Situazione prima

1. La barra di conferma aveva l'attributo `hidden` ma anche la classe
   `d-flex` (`display:flex !important`), che annulla `hidden`: restava
   sempre mostrata; il JS (`.api-del-ask`) cambiava solo `hidden`.
2. `.api-code` è un blocco scuro a contrasto pieno.
3. `getDownloadPostman` usava `url('api/'.permalink)` e nessun header.

## Situazione dopo

1. `hidden` è su un contenitore esterno senza `d-flex`; la riga flex è
   dentro. La barra compare solo cliccando il cestino.
2. Nuova variante `.api-code.api-code-soft` (sfondo chiaro, bordo sottile,
   testo attenuato, font più piccolo) per i due esempi del riquadro
   "Come collegarsi". La risposta di esempio dei singoli endpoint resta
   com'era.
3. Postman: URL su `/api2/<permalink>` e header `Authorization: Bearer <token>`.
   Gli endpoint su `/api` restano raggiungibili (le route esistono per
   entrambi i binari), nessuna modifica a routing o ai client esistenti.

## Motivazione

Coerenza con l'indicazione "per le nuove integrazioni usa api2" già
presente nelle pagine; correzione di un bug di visualizzazione.

## Test

`php -l` sul controller, `view:clear`. Non verificato a vista in browser.

## Rischi e note

Cambia il contenuto del file Postman esportato (URL e header): chi
reimporta la collection dovrà inserire un token (pagina Token API).

## Rollback

Ripristinare `url('api/'...)` e `'header' => []` nel controller; rimettere
`hidden`+`d-flex` sullo stesso elemento e togliere `api-code-soft`.
