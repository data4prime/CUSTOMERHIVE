# 200 - Wizard v2: navigazione tra i passi senza ricaricare la pagina

- **Data**: 2026-10-02
- **Stato**: Completato (da provare nel browser sul wizard reale)
- **Area**: Frontend (dietro flag `wizard_v2`)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/template.blade.php`
  - `resources/views/crudbooster/module_generator/partial.blade.php` (nuovo)
  - `resources/views/crudbooster/module_generator/navigation.blade.php` (stato "in caricamento")
  - `step2_v2`, `step3_v2`, `step4_v2`, `step5` (pulsante "Indietro" da `button` a link)

## Contesto

Ogni cambio di passo ricaricava l'intera pagina admin (header, sidebar, chat,
licenza, footer, decine di CSS/JS) ed era lento. Richiesta del 2026-10-02.

## Situazione prima

Link dei passi e "Indietro" con `location.href`, "Avanti"/"Salva" con submit
normale e redirect: sempre pagina intera.

## Situazione dopo

Con `MODULE_GENERATOR_WIZARD_V2=true` i link dei passi, i pulsanti "Indietro"
e l'invio dei form del wizard passano da `fetch` con l'header `X-Wizard-Partial`.
Il server, con quell'header, estende `partial.blade.php` invece di
`admin_template` e restituisce solo stili, contenuto del passo, notifica e
script (niente header/sidebar/chat/footer). Il browser sostituisce `#mg-wizard`,
aggiunge gli stili mancanti, riesegue gli script del passo in ordine (le
librerie già caricate non si ricaricano), mostra la notifica e aggiorna
l'URL con la history (il tasto indietro del browser funziona). Durante il
caricamento il contenuto è attenuato. Risposte che non sono un passo (lista
moduli dopo il salvataggio finale) e errori portano alla navigazione normale.
Con il flag spento nulla cambia (stesse pagine intere).

## Motivazione

Meno rendering lato server (niente shell admin) e meno lavoro nel browser;
stesso comportamento di salvataggio e validazione. Limitato al flag perché i
vecchi passi 2-4 registrano `$(document).on(...)` che si accumulerebbero
rieseguendo gli script.

## Test

Harness in Chrome headless con `fetch` simulato sulle pagine reali del
template (parziali generate da Blade): POST con header e campi (incluso
il pulsante di invio), sostituzione contenuto, script rieseguiti una volta per
passo, stile della nuova pagina aggiunto, notifica mostrata, tasto indietro,
link "Indietro", un solo `#mg-wizard`, gestori installati una volta. Verificato
che senza header si sceglie `admin_template`. Non verificato: wizard reale con
login, passi 2-4 con tutte le librerie (gridstack, Sortable), misura dei tempi.

## Rischi e note

- Le modifiche non salvate si perdono cambiando passo dal menu dei passi o da
  "Indietro", come prima.
- Nei passi v2 gli script non devono registrare listener su `document`/`window`
  (oggi non lo fanno): in tal caso si accumulerebbero a ogni passo.
- `main.css`, `custom.css` e `theme.css` sono caricati con `?r=time()`
  (`admin_template.blade.php`): non si usa mai la cache del browser, quindi ogni
  pagina intera di tutta l'app li riscarica. Non toccato qui; sarebbe un
  intervento a parte con effetto globale.

## Rollback

Spegnere il flag, oppure ripristinare da git i file elencati sopra.
