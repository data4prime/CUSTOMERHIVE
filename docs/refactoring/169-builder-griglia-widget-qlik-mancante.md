# 169 - Builder a griglia: widget Qlik mancante nella palette

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Statistic Builder / Qlik
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/qlikwidget.blade.php`

## Contesto

Segnalazione: "nel builder statistiche non c'è il widget Qlik che prima c'era".
Verificato che la licenza locale include il modulo `Qlik`
(`LicenseHelper::isActiveQlik()` restituisce `true`), quindi non era un
problema di licenza né di cache della licenza.

## Situazione prima

Il builder legacy (`layout.blade.php`) mostra il bottone "Qlik Widget"
(`data-component='qlikwidget'`) se `isActiveQlik()`. Il nuovo builder a
griglia (`builder_grid.blade.php`, vista standalone che non estende
`layout.blade.php`) ha una palette con 7 tipi di widget scritti a mano e
**non includeva `qlikwidget`**: sparito nel passaggio al builder a griglia.
Il resto del flusso lo supportava già (`postAddComponent` è generico,
`LegacyDashboardGridConverter::DEFAULT_HEIGHTS` ha `qlikwidget => 5`).

## Situazione dopo

- Palette del builder a griglia: voce "Widget Qlik" (`trans('crudbooster.qlik_widget')`,
  chiave già presente in EN e IT), visibile solo se `isActiveQlik()` come nel
  builder legacy. Dimensione di default 6x5.
- `qlikwidget.blade.php`: l'iframe del widget ha ora la classe
  `ch-module-frame`, che nel builder (legacy e a griglia) applica
  `pointer-events: none`: senza, l'iframe catturava il mouse e il widget non
  si poteva trascinare/selezionare nella griglia (stesso motivo per cui la
  classe esiste per "Modulo incorporato"). Fuori dal builder la classe non ha
  stili, quindi la dashboard pubblica resta interattiva.

### Seguito: stato "non configurato"

Un widget Qlik appena aggiunto (nessuna app scelta) mostrava un paragrafo,
il logo e un iframe vuoto di 30px, diverso dagli altri widget. Ora, se
`$mashup->id` è assente o 0, `qlikwidget.blade.php` include il partial
condiviso `_empty_widget_state` (come smallbox/panelarea/chart*_v2) con
icona "Q" e testi `qlik_widget_not_configured` /
`qlik_widget_not_configured_hint` (nuove chiavi EN+IT in
`resources/lang/*/crudbooster.php`); nella vista pubblica di sola lettura il
partial mostra il link "Clicca qui per configurare" (`$editUrl`). L'iframe
`/mashup/{id}` viene emesso solo a widget configurato. La chiave
`qlik_widget_setup` non è più usata da questa vista.

## Motivazione

Ripristinare una funzione che c'era e che la licenza abilita, senza
toccare backend o dati.

## Test

- Compilate con Blade le due viste e verificato `php -l`: OK.
- **Non verificato nel browser**: comparsa della voce, aggiunta del widget,
  dimensioni dell'iframe nella cella (80% dell'altezza del `.border-box`),
  configurazione dalla sidebar (select app/oggetti).

## Rischi e note

- Le etichette della palette esistente sono in italiano hardcoded (non
  toccate qui); la nuova voce usa `trans()`.
- Nessun cambio al builder legacy se non la classe sull'iframe
  (effetto: nel builder legacy l'anteprima Qlik non cattura più il mouse).

## Rollback

`git checkout --` sui due file.
