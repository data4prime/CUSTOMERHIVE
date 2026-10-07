# 235 - Tema chiaro/scuro (ui2)

- **Data**: 2026-10-06
- **Stato**: Completato (da vedere a vista)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `public/js/ch-theme.js` (nuovo)
  - `public/css/ch-ui2.css` (sezione token scuri)
  - `resources/views/crudbooster/header.blade.php` (pulsante)
  - `resources/views/crudbooster/admin_template.blade.php` (caricamento script)
  - `resources/lang/{it,en}/crudbooster.php` (`ui_theme_toggle`)

## Contesto

Il prototipo ha il toggle "Proposta in scuro". Seguito di [234](234-nuovo-linguaggio-visivo-ui2.md).

## Situazione prima

Un solo tema chiaro.

## Situazione dopo

Pulsante luna/sole nell'header (solo con `UI_V2`). La scelta è per browser
(`localStorage` chiave `ch-theme`); senza scelta si segue
`prefers-color-scheme`. Lo script è caricato subito dopo `<body>` per applicare
il tema prima del primo disegno. I token scuri stanno in `ch-ui2.css` sotto
`body.ch-ui2[data-ch-theme="dark"]`.

## Motivazione

Il tema si cambia solo ridefinendo i token (il progetto li usa ovunque), senza
un secondo foglio di stile.

## Test

`php -l` sui file lang, `view:cache` senza errori. **Da verificare a vista**:
ogni pagina in scuro; i punti con colori scritti a mano (CSS inline, widget
della dashboard, moduli custom) possono restare chiari.

## Rischi e note

- Select2, calendario e modali usano già i token: da controllare.
- Editor ricchi (TinyMCE/CKEditor) e iframe dei widget hanno la loro skin chiara.
- Il tema scuro non si applica alle pagine standalone (login).

## Rollback

`CH_UI_V2=false`, oppure togliere il pulsante da `header.blade.php`.
