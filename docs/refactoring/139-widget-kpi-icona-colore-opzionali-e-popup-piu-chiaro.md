# 139 - Widget Indicatore KPI: icona/colore opzionali, popup sorgente dati più chiaro e più grande

- **Data**: 2026-09-29
- **Stato**: Completato (verificato leggendo il codice, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/smallbox.blade.php`
  - `resources/lang/en/crudbooster.php`
  - `resources/lang/it/crudbooster.php`

## Contesto

Tre richieste dell'utente nella stessa sessione, dopo aver provato il
builder:
1. Icona e Colore nel pannello di configurazione risultavano obbligatori
   - vuole poterli lasciare vuoti/di default.
2. Confusione reale segnalata: si configura una query (libera o guidata)
   nel popup "Sorgente dati", si clicca il "Salva" del popup, e non è
   ovvio che serve premere ANCHE il "Salva" generale in fondo alla
   sidebar per salvare davvero sul server - il popup non salva nulla, si
   limita a chiuderlo e aggiornare il riepilogo (vedi 134/135, che
   avevano gia' sistemato il *salvataggio* effettivo ma non questa
   confusione sul *quando* premere cosa).
3. Il popup "Sorgente dati" (420px) era stretto, ancora di piu' dopo 138
   (piu' righe filtro possibili nella Query guidata).

## Situazione prima

- `Icona`: `<select ... required name='config[icon]'>` - bloccava il
  Salva generale (HTML5 validation) se non scelta.
- `Colore`: gia' senza `required`, ma senza dirlo esplicitamente
  all'utente (nessuna etichetta "(opzionale)", nessun aiuto sul default).
- Popup: due pulsanti "Salva" nella stessa schermata con significato
  diverso (uno chiude il popup, l'altro salva davvero) e lo stesso testo
  - nessun segnale che uno dei due non basta da solo.
- `.ch-source-modal { width: 420px; max-height: 85vh; }`.

## Situazione dopo

- **Icona opzionale**: tolto `required`, etichetta "Icona (opzionale)",
  placeholder "-- nessuna icona --". Nel layout del widget, `[icon]`
  compare solo se l'icona e' impostata (altrimenti, come per `[sql]` in
  134, il token resterebbe grezzo non sostituito) - senza icona la
  pastiglia resta semplicemente vuota (solo lo sfondo colorato).
- **Colore opzionale**: gia' non obbligatorio (un `<input type=color>`
  ha sempre un valore, non puo' essere davvero "vuoto") - resa esplicita
  l'etichetta "Colore (opzionale)" + aiuto "Se non lo cambi resta il
  colore di default (azzurro)".
- **Popup piu' chiaro**: pulsante rinominato da "Salva" a "Fatto"
  (chiude solo il popup, non salva), avviso giallo sopra i pulsanti
  ("Le modifiche non sono ancora salvate: premi «Salva» in fondo al
  pannello di configurazione.") e un pulse visivo (doppio bagliore blu,
  CSS `@keyframes`) sul VERO pulsante "Salva" della sidebar
  (`.ch-sidebar-save`, aggiunto da `builder_grid.blade.php` fuori da
  questo file) quando il popup si chiude - cercato dentro l'handler di
  chiusura (non all'avvio dello script) perche' quel pulsante viene
  aggiunto al form solo dopo che questo script e' gia' girato una prima
  volta.
- **Popup piu' grande**: `width` 420px → 560px, `max-height` 85vh → 88vh.
- Nuove chiavi di traduzione EN/IT (`statistic_builder_smallbox_source_popup_done`/
  `_hint`) per il testo nuovo/cambiato - il resto del pannello (label
  come "Nome", "Link", "Query", "Prova"...) resta hardcoded in italiano
  come gia' era prima in tutto questo builder, incoerenza preesistente
  non affrontata qui (fuori scope per una richiesta di sole 3 modifiche
  mirate).

## Motivazione

(1) e (3) sono richieste dirette. (2) e' un problema di UX reale
(azione a due passi non comunicata chiaramente) gia' occorso all'utente
durante il test delle funzionalita' di 138 - rinominare il pulsante
evita la falsa impressione di "gia' salvato", l'avviso lo spiega a
parole, il pulse lo mostra visivamente: tre rinforzi diversi sullo
stesso messaggio, a basso costo e reversibili.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato: `php -l` su
`smallbox.blade.php` e sui due file di lingua; lettura del codice per
confermare che `.ch-sidebar-save` esiste gia' nel DOM nel momento in cui
l'handler di chiusura del popup puo' effettivamente scattare (aggiunto
sincronamente da `builder_grid.blade.php` subito dopo l'injection della
risposta AJAX, prima di qualunque possibile click dell'utente). Da
confermare visivamente dall'utente: aprire un widget KPI, salvare senza
scegliere un'icona (deve passare), aprire il popup "Sorgente dati" e
verificare le dimensioni piu' grandi, l'avviso e il pulse sul Salva
generale alla chiusura.

## Rischi e note

Nessuno noto - modifiche isolate a un solo widget (`smallbox.blade.php`,
unico a usare questo popup, vedi 134/138), nessun cambio di formato dati
o di comportamento server-side.

## Rollback

`git diff` di `smallbox.blade.php` e dei due file di lingua per tornare
alla situazione precedente (icona obbligatoria, popup piu' piccolo,
pulsante "Salva" duplicato senza avviso).
