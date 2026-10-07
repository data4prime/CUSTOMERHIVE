# 241 - Dettaglio: tenant e gruppo dei moduli mg_* nella scheda "Sistema"

- **Data**: 2026-10-07
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / Form
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form_detail.blade.php`

## Contesto

Seguito dell'intervento 238: tenant e gruppo stanno nella scheda "Sistema"
sia nel form di modifica sia nel dettaglio. Segnalato su
`/admin/mg_contratti/detail/71`: la scheda "Sistema" del dettaglio non
mostrava tenant e gruppo, a differenza della modifica.

## Situazione prima

I campi `tenant`/`group` dei moduli `mg_*` non sono nel `$form` del controller:
li aggiunge `ModuleHelper::add_default_form_fields()`, chiamata da
`form_body.blade.php` (modifica/aggiunta). `form_detail.blade.php` non la
chiamava, quindi nel dettaglio `$forms` non li conteneva e la scheda
"Sistema" restava vuota (o non c'era).

## Situazione dopo

`form_detail.blade.php` chiama `add_default_form_fields($table, $forms)` in
testa, come `form_body`. Vale sia per il dettaglio piatto sia per quello a
blocchi/schede (`form_detail_layout`). Visibilità per ruolo invariata (stessa
funzione del form): superadmin tenant+gruppo, tenant admin tenant+gruppo,
utente semplice gruppo.

## Motivazione

Dettaglio coerente con la modifica; riuso della logica esistente, nessun
codice duplicato.

## Test

Solo lettura del codice; da verificare a vista su
`/admin/mg_contratti/detail/71` con superadmin e con un utente semplice.

## Rischi e note

Solo visualizzazione, nessuna scrittura. I moduli non `mg_*` non sono toccati
(la funzione ritorna `$forms` invariato).

## Rollback

Rimuovere la chiamata aggiunta in `form_detail.blade.php`.
