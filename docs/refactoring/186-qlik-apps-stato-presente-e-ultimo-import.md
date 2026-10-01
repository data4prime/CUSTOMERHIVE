# 186 - Qlik apps: badge "Presente su Qlik" e colonna "Ultimo import"

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/QlikAppController.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Nella lista `admin/qlik_apps` la colonna "Stato Qlik" risultava sempre vuota:
mostrava un badge solo per le app non più su Qlik (`is_missing`), niente per
le altre. Dopo l'introduzione della sync (docs 176-178) serve capire anche
quanto è recente l'informazione.

## Situazione prima

Colonna "Stato Qlik": badge arancione solo se `is_missing = 1`, vuota
altrimenti. Nessuna indicazione di quando l'app sia stata sincronizzata
l'ultima volta (`last_synced_at` esisteva ma non era mostrato).

## Situazione dopo

- "Stato Qlik": badge arancione se `is_missing`, badge verde "Presente su
  Qlik" se sincronizzata almeno una volta (`last_synced_at` valorizzato),
  vuoto per le app create a mano.
- Nuova colonna "Ultimo import" con `last_synced_at` (`d/m/Y H:i`), vuota per
  le app mai sincronizzate. Una data vecchia segnala che conviene rilanciare
  la sync perché lo stato reale potrebbe essere cambiato.
- Nuove chiavi `qlik_sync_present_badge` e `qlik_sync_col_last_synced` in en
  e it.

## Motivazione

Rendere visibile sia lo stato sia l'età dell'informazione. Solo la lista app:
gli item (`AdminQlikItemsController`) non sono toccati.

## Test

`php -l` sul controller. Non verificato a browser.

## Rischi e note

Solo presentazione, nessun cambio ai dati. Il formato data è fisso `d/m/Y H:i`.

## Rollback

Ripristinare le due colonne in `QlikAppController::cbInit` e rimuovere le due
chiavi di traduzione.
