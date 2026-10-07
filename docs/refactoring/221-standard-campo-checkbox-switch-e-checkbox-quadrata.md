# 221 - Standard unico per i campi checkbox (switch + checkbox quadrata)

- **Data**: 2026-10-05
- **Stato**: Completato (da verificare a vista)
- **Area**: Frontend / UI standard
- **File/aree di codice coinvolte**:
  - `public/css/ch-components.css` (blocco "Checkbox, radio, switch")
  - `public/css/theme.css` (rimosse regole di dimensione/colore sulle checkbox)
  - `resources/views/crudbooster/partials/ch_check.blade.php` (nuovo)
  - `resources/views/crudbooster/default/type_components/checkbox/component.blade.php`
  - `resources/views/crudbooster/default/table.blade.php`, `mass_edit/form_body.blade.php`,
    `module_generator/step1|step2_v2|step3_v2.blade.php`,
    `statistic_builder/components/chartarea_v2|chartbar_v2.blade.php`

## Contesto

Dopo l'intervento 206-209 (Bootstrap 5.3 + token `--ch-*`) le checkbox erano
ancora disomogenee: quelle native con `accent-color`, alcune con dimensioni
fissate a mano (14/15/16px) in `theme.css`, alcune già `form-switch`. Si è
deciso uno standard unico, valido per tutta l'app, scelto da un mockup con tre
proposte (`.scratch/mockup-checkbox.html`):

- **B, switch**: singolo booleano (sì/no);
- **A, checkbox quadrata**: scelte multiple, tabelle, matrici, qualsiasi altro caso.

## Situazione prima

- `type_components/checkbox/component.blade.php` conteneva 5 blocchi HTML
  duplicati (singolo, `dataenum`, `datatable` con/senza relazione, `dataquery`),
  con `<div class="checkbox"><label><input>` stile Bootstrap 3.
- Label dei `datatable`/`dataquery` scritti **senza escape**.
- `dataenum`: `value="1"` fisso per ogni opzione e `isset($checked)` sempre vero
  dalla seconda opzione in poi (tutte risultavano selezionate).
- Altre checkbox sparse nelle viste, con stili propri.

## Situazione dopo

- **Un solo stile** in `ch-components.css`:
  - `input[type=checkbox]` senza `role="switch"` (anche "nude", ad es. nei
    moduli dei clienti non tracciati) → checkbox quadrata A, con stati
    hover/checked/indeterminate/focus/disabled/errore;
  - `.form-switch .form-check-input[role=switch]` → switch B (38×22).
- **Un solo markup** in `partials/ch_check.blade.php` (Bootstrap nativo,
  l'`<input>` resta reale: il JS esistente non cambia).
- `checkbox/component.blade.php` raccoglie le opzioni in un array e le
  rende con il partial: singolo → switch, elenchi → checkbox quadrate.
  `name` (`campo[]`), `value` e `data-val` invariati per `datatable`/`dataquery`.
- **Settings con `radio` yes/no** (es. "Email Enabled") → switch in `setting.blade.php`,
  con un `<input type="hidden">` "no" davanti: una checkbox spenta non viene inviata e
  `postSaveSetting` salta i campi assenti, quindi il valore non tornerebbe mai a "no".
- Passati a switch: export "imposta come default", toggle "Aggiorna questo
  campo" (mass edit), "Also create menu" (generator step 1), "stacked" dei
  grafici. Aggiunto `role="switch"` agli switch del generatore (step 2/3).
- Restano checkbox quadrate (stile A, automatico): selezione righe e
  "seleziona tutto" in lista, colonne export, matrice privilegi, abilitazione
  moduli/tenant, preview del generatore.

## Motivazione

Un solo posto da modificare per cambiare l'aspetto di ogni checkbox;
nessuno stile da ricreare nei nuovi moduli; clienti allineati senza toccare i
loro controller.

## Test

- Compilazione di tutte le view (`php artisan view:cache`) e render del
  componente nei casi: singolo on/off/disabilitato, `dataenum`, `dataquery`
  (script temporaneo, poi rimosso). Verificato l'escape dei label e gli id univoci.
- **Non** verificato a vista nel browser né con la suite di test.

## Rischi e note

- **Cambia l'aspetto per tutti i clienti** (voluto). Il comportamento di salvataggio non cambia.
- **Correzione di comportamento in `dataenum`**: ora `value` = valore
  dell'opzione (prima `1` per tutte) e `checked` solo se il valore è
  tra quelli salvati (prima: dalla seconda opzione in poi tutte selezionate).
  Dati già salvati come `1;1;1` in campi `dataenum` non corrisponderanno più
  alle opzioni al riapri-modifica.
- **Escape dei label** (`datatable`, `dataquery`, `dataenum`): label con HTML
  voluto ora vengono mostrati come testo.
- Testi "Aggiorna questo campo" e "Also create menu" restano hardcoded come prima (non toccati).
- Stato Sì/No accanto allo switch: non implementato (scelta deliberata).
- Da controllare a vista: `privileges`, lista con selezione righe, export,
  mass edit, form con `dataenum`.

## Rollback

Ripristinare i file elencati (git) e rimuovere `ch_check.blade.php`.
