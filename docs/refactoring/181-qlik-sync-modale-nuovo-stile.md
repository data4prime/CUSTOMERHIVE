# 181 - Modale "Sincronizza da Qlik": stile in linea con la nuova UI

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Qlik / UI
- **File/aree di codice coinvolte**:
  - `public/js/qlik_sync_modal.js`
  - `public/css/theme.css` (sezione `.ch-sync-*` in fondo al file)

## Contesto

Il modale aperto dal pulsante "Sincronizza da Qlik" (liste app e item) era
rimasto in stile AdminLTE: header azzurro pieno `#3c8dbc`, footer grigio,
colori e ombre hardcoded. Gli altri popup (filtro, export, mass edit) usano
già la card arrotondata del tema `ch-shell`.

## Situazione prima

Tutto lo stile era inline nel JS (`style.cssText`), senza token di design.

## Situazione dopo

Il JS assegna solo classi (`ch-sync-overlay/dialog/head/title/body/field/
hint/msg`); lo stile vive in `theme.css` con i token `--ch-*`: card con
bordo e raggio del tema, header bianco con icona `fa-refresh` a badge tinto,
campi con focus indaco, messaggi esito con i colori soft di successo/errore,
footer `modal-footer` che riusa lo stile ghost/primario dei bottoni degli
altri popup. Nessun cambio di logica, testi o markup funzionale (stessi
fetch, stesse chiavi di traduzione).

Seguito: le liste con campo di ricerca (input + `select size=6`) sono state
sostituite da un solo `<select>` a discesa per campo. Nel select della
configurazione la prima opzione è il segnaposto "Scegli una configurazione."
(chiave `qlik_sync_choose_conf` già esistente, valore vuoto), così non si
preseleziona per errore la prima conf; l'avvio senza scelta mostra ancora
l'errore di prima. Il select delle app parte da "Tutte le app". Non serve più
la chiave `qlik_sync_search` (lasciata nelle lingue, inutilizzata).

## Motivazione

Coerenza visiva con il resto dell'interfaccia e niente colori duplicati nel
JS. Il modale resta un overlay autonomo (non passa dal modal di Bootstrap).

## Test

Nessun test eseguito: né sintassi JS (node non disponibile) né browser.
Servono hard refresh per la cache di `theme.css` e del JS.

## Rischi e note

Le classi `.ch-sync-*` hanno fallback sui colori, quindi il modale resta
leggibile anche senza i token del tema. Il modale "Prova connessione" della
conf Qlik (script in `QlikConfController`) ha ancora lo stile inline
vecchio: non toccato in questo intervento.

## Rollback

Ripristinare `public/js/qlik_sync_modal.js` dalla versione precedente e
rimuovere la sezione `.ch-sync-*` da `theme.css`.
