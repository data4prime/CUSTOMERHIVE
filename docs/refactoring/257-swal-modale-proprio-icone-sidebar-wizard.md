# 257 - SweetAlert sostituito dal modale del progetto + rifiniture module generator

- **Data**: 2026-10-07
- **Stato**: Completato (non committato, non provato a vista nel browser)
- **Area**: Frontend / Module generator
- **File/aree di codice coinvolte**:
  - `public/js/ch-dialog.js` (nuovo), `public/css/theme.css`
  - `resources/views/crudbooster/partials/ch_scripts.blade.php`, `admin_template_plugins.blade.php`, `statistic_builder/layout.blade.php`
  - `app/Helpers/CRUDBooster.php` (`deleteConfirm`), `components/action.blade.php`, `default/table.blade.php`, `header.blade.php`, `setting.blade.php`, `import.blade.php`, `statistic_builder/index.blade.php`, `type_components/filemanager/component.blade.php`
  - `partials/ch_icon_picker.blade.php`, `public/js/ch-icon-picker.js`
  - `sidebar.blade.php`, `admin_template.blade.php`, `module_generator/step4_v2.blade.php`

## Contesto

Restavano SweetAlert (libreria con stile suo, fuori dal tema) per conferme di
eliminazione/logout/azioni di gruppo, piu' alcune rifiniture segnalate nel
module generator: filtro icone, voce di sidebar doppia, pulsanti della lista nei
passi del wizard, area di trascinamento dello step 4 troppo piccola.

## Situazione prima

- `swal()` usato in 9 punti (helper `deleteConfirm`, azioni di riga con
  conferma, azioni di gruppo, logout, impostazioni, import, statistic builder,
  filemanager), libreria caricata da `vendor/crudbooster/assets/sweetalert`.
- Il modale proprio esisteva solo come `.ch-sync-*` costruito a mano in
  `public/js/qlik_app_delete.js`.
- Selettore icone: la ricerca cercava solo tra i nomi Bootstrap Icons (chi scrive
  "user", "home", "cog" non trovava nulla); Invio nella ricerca inviava il form del passo.
- Sidebar: "Elenco moduli" e "Abilita/disabilita" avevano la stessa condizione
  `active` su `/module_generator`.
- I passi del wizard mostravano i pulsanti "Generate New Module" / "Import Module"
  (`index_button` del controller).
- Step 4: area di rilascio dei campi `min-height` 56px, blocchi nuovi alti 3 righe.

## Situazione dopo

- `public/js/ch-dialog.js` (caricato da `ch_scripts`): `chConfirm({title, text,
  confirmText, cancelText, danger, type}, onConfirm)` e `chAlert({...})`, stile
  `.ch-sync-*`, testi sempre passati/tradotti da chi chiama. Tutti i call site del
  progetto usano `chConfirm`/`chAlert`; la libreria SweetAlert non e' piu'
  inclusa. Resta un **shim `window.swal`** (stessa API vecchia, stesso modale) solo
  per i controller/moduli custom dei clienti che la chiamano ancora.
  `deleteConfirm()` mantiene `location.href="..."` nel callback (lo legge
  `qlik_app_delete.js`).
- Icone: la ricerca trova anche per nome FontAwesome (alias da `IconMap::map()`,
  JSON `#ch-ip-aliases`), ignora i prefissi `bi-`/`fa-`, Invio non invia piu' il form.
- Sidebar: una sola voce attiva nel gruppo Module Generator (lista copre anche
  dettaglio/modifica/passi, "Aggiungi" solo `step1` senza id, "Abilita" per `enable*`).
- Pulsanti lista nascosti quando la vista ha `$active_tab` (solo i passi del wizard).
- Step 4: area di rilascio `min-height` 140px in modifica, blocchi nuovi alti 5 righe.

## Motivazione

Un solo sistema di modali coerente col tema; meno dipendenze; UX piu' pulita nel wizard.

## Test

Solo `php -l`, `php artisan view:cache` (compilazione di tutte le viste) e lettura del
diff. NON provato a vista nel browser ne' con la suite di test.

## Rischi e note

- Il filtro icone: la causa esatta del "non funziona" segnalato non e' stata riprodotta
  nel browser; l'ipotesi piu' probabile (ricerca per nomi FontAwesome + Invio che
  inviava il form) e' stata corretta. Se persiste, serve il termine cercato.
- `public/vendor/crudbooster/assets/sweetalert/` resta su disco (non incluso piu');
  si puo' cancellare quando tutti i clienti sono aggiornati.
- Il `confirm()` nativo dello step 4 ("elimina scheda") non e' stato toccato.

## Rollback

Ripristinare i file elencati e rimettere le due righe `<script>/<link>` di
sweetalert in `admin_template_plugins` e `statistic_builder/layout`.
