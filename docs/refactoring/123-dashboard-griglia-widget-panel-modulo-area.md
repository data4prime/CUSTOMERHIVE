# 123 - Dashboard a griglia libera: aggiunti Panel Area, Module Panel, Chart Area

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/chartarea_v2.blade.php` (nuovo)
  - `resources/views/crudbooster/statistic_builder/components/panelarea.blade.php`
  - `resources/views/crudbooster/statistic_builder/components/panelcustom.blade.php`
  - `resources/views/crudbooster/statistic_builder/builder_grid.blade.php`
  - `app/Dashboards/LegacyDashboardGridConverter.php`

## Contesto

Richiesto dall'utente dopo un confronto esplicito tra i widget
disponibili nel builder legacy e quelli della nuova griglia: mancavano
"Chart Area", "Panel Area" e "Module Panel" (il quarto mancante, "Qlik
Widget", rimandato a un secondo momento su richiesta esplicita).

## Situazione prima

La palette della nuova griglia ([111](111-dashboard-griglia-fase3-editor-frontend.md),
[112](112-dashboard-griglia-fase4-widget-apexcharts.md)) copriva solo
KPI/Tabella/Grafico a linee/Grafico a barre.

## Situazione dopo

- **Chart Area** → `chartarea_v2.blade.php` (nuovo), copiato dal pattern
  già usato per `chartline_v2`/`chartbar_v2`: ApexCharts `type: 'area'`
  con riempimento sfumato, stessa doppia modalità SQL/query guidata,
  stessa forma dati (`label`/`value`). Il widget legacy "Chart Area"
  (Morris) resta invariato.
- **Panel Area** e **Module Panel** → nessun nuovo widget: sono
  `panelarea`/`panelcustom` esistenti, riusati così come sono (nessun
  concetto di query/dataset, quindi nessun bisogno del toggle SQL/query
  guidata) - aggiunto solo il placeholder "non configurato" (coerente
  con [120](120-dashboard-griglia-placeholder-widget-non-configurato.md)/
  [122](122-dashboard-griglia-link-configura-vista-pubblica.md)) e la
  voce in palette.
- Palette del builder: 3 nuove voci ("Grafico ad area", "Pannello di
  testo", "Modulo incorporato").
- `LegacyDashboardGridConverter::DEFAULT_HEIGHTS`: aggiunta voce
  `chartarea_v2` per coerenza (non strettamente necessaria: la
  conversione da dashboard legacy preserva il `component_name`
  originale `chartarea`, già presente in mappa).

## Motivazione

Riuso diretto dei widget esistenti (`panelarea`/`panelcustom`) dove non
serve alcuna modifica di logica - solo il placeholder mancante; nuovo
file solo dove serve davvero (grafico ad area, stesso schema degli
altri due grafici moderni).

## Test

Verificato in browser su una dashboard di prova: aggiunti tutti e 3 i
nuovi tipi dalla palette, ciascuno mostra il proprio placeholder "non
configurato" con icona e messaggio distinti; verificato che il form di
configurazione di "Modulo incorporato" popola correttamente la select
con 42 moduli reali, e quello di "Pannello di testo" mostra i campi
Name/Content attesi. Widget di prova poi rimossi.

## Rischi e note

- "Module Panel" incorpora la pagina di un altro modulo via AJAX
  (`panelcustom.blade.php`, logica invariata): non è stato verificato
  che il contenuto incorporato si adatti bene alle dimensioni ridotte
  di una cella di griglia (nel builder legacy l'area occupava un'intera
  colonna a piena altezza pagina).
- "Qlik Widget" resta fuori, come da richiesta esplicita.

## Rollback

`git diff` di questi file per tornare alla palette con solo 4 widget.
