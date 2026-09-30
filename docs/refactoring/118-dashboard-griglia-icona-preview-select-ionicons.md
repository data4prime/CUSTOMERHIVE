# 118 - Dashboard a griglia libera: anteprima icona nel picker Ionicons dello Small Box

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`

## Contesto

Richiesto dall'utente: nella select "Icon By Ionicons" si vedeva solo il
nome testuale dell'icona (es. `ion-android-add`), senza l'icona vera e
propria — difficile capire cosa si sta scegliendo.

## Standard scelto (e perché)

Il progetto ha già lo stesso identico problema risolto altrove, con uno
standard consolidato: `resources/views/crudbooster/components/
list_icon.blade.php` e `resources/views/crudbooster/module_generator/
step1.blade.php` usano entrambi una funzione `formatIcon` per select2
con **icona a sinistra, nome a destra** (`<i>` con
`margin-right:6px`, poi il testo), applicata sia al risultato in
tendina sia al valore selezionato. Riusato lo stesso standard qui
(icona+nome, non nome+icona) per coerenza visiva con il resto
dell'interfaccia, invece di introdurre un ordine diverso solo per
questo picker.

## Situazione dopo

`smallbox.blade.php` carica ora anche `ionicons.min.css` (vendorizzato,
già caricato altrove per le pagine admin standard e per il builder
legacy, ma **non** nella nuova pagina standalone del builder a griglia,
`builder_grid.blade.php` - per questo l'icona non sarebbe comunque
comparsa lì senza questa aggiunta) e una `formatIcon` che genera
`<i class="ion ion-nome-icona">` prima del testo, passata sia a
`templateResult` sia a `templateSelection` di select2.

## Test

Verificato in browser: aperta la select delle icone su un widget KPI di
prova, ogni riga della tendina mostra l'icona reale accanto al nome
(es. punto esclamativo per `ion-alert`, triangolo per
`ion-alert-circled`, "+" per `ion-android-add`). Widget di prova poi
rimosso.

## Rischi e note — una scoperta da segnalare, non risolta qui

Le icone di **questo picker** (classi CSS Ionicons v2, es.
`ion-android-add`) sono un set diverso dalle icone che il widget KPI
*mostra davvero* una volta salvato: `smallbox.blade.php` (ramo
`layout`) usa `<ion-icon name="[icon]">`, il web component di
**Ionicons v7** (icone moderne, nomi diversi, es. `add-circle` invece
di `ion-android-add` - v7 non ha nemmeno più varianti
android/iOS separate). Il nome scelto da questo picker molto
probabilmente **non corrisponde a un'icona v7 valida**, quindi il
widget finito potrebbe non mostrare nessuna icona (comportamento
preesistente, non introdotto da questo lavoro - la stessa
incongruenza esiste già nel builder legacy). Non risolto qui perché
fuori dallo scope della richiesta (l'anteprima nel picker); da valutare
separatamente se l'icona nel widget finito risulta davvero mancante.

## Rollback

`git diff` di questo file per tornare al testo semplice nella select.
