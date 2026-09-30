# 161 - Modulo incorporato: traduzioni e modulo mancante non rompe la dashboard

- **Data**: 2026-09-30
- **Stato**: Completato e verificato in locale
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/panelcustom.blade.php`
  - `resources/lang/en/crudbooster.php`
  - `resources/lang/it/crudbooster.php`

## Contesto

Analisi del widget "Modulo incorporato" (`panelcustom`, "Module Panel"
nel builder legacy) richiesta dall'utente. Il widget viene usato per
*lavorare* (aprire e salvare record), non solo per consultare, quindi è
pianificato un passaggio a iframe con layout ridotto (`?embed=1`, fasi
successive, interventi separati). Questo intervento è la **Fase 0** del
piano: correzioni di robustezza e traduzioni, senza alcun cambio di
comportamento visibile, utili anche se il resto non venisse fatto.

## Situazione prima

- Testi visibili hardcoded in una sola lingua, contro la regola del
  repo: "Modulo non configurato", "Seleziona per scegliere quale modulo
  incorporare", label "Name" / "Modulo da mostrare", etichette
  "Lista"/"Aggiungi" nella select, "Please wait, loading..." e i
  messaggi d'errore (404/500/generico) nello script di caricamento.
- Il ramo `showFunction` chiama `route($value)` senza controllo: se il
  modulo salvato nel widget non ha più una route con quel nome (modulo
  rimosso o rinominato), Laravel lancia `RouteNotFoundException` durante
  il render PHP dell'intera dashboard, che va in errore per tutti gli
  utenti invece di perdere solo quel widget.

## Situazione dopo

- Tutti i testi del widget passano da `trans('crudbooster.module_widget_*')`
  (10 chiavi nuove in en e it). Nello script JS le stringhe arrivano già
  tradotte da Blade con `json_encode`; il messaggio d'errore viene
  inserito con `.text()`, non come HTML.
- `showFunction` controlla `Route::has($value)`: se il modulo non esiste
  più mostra "Modulo non disponibile: potrebbe essere stato rimosso o
  rinominato. Riconfigura il widget." e non emette lo script di
  caricamento. Con un modulo valido l'output resta identico a prima.
- _Nota (stessa giornata)_: le chiavi `module_widget_loading` e
  `module_widget_error_*` sono poi state rimosse da
  [163](163-modulo-incorporato-iframe-e-apri-pagina-intera.md), che ha
  sostituito lo script di caricamento con un iframe.
- **Nessun cambio di comportamento visibile** per i widget configurati
  correttamente, a parte la lingua dei testi (prima fissa, ora segue la
  lingua dell'utente).

## Motivazione

Rispetto della regola sulle traduzioni e isolamento del guasto: un
widget che punta a un modulo inesistente non deve rendere inaccessibile
l'intera dashboard.

## Test

Manuali, in locale (Docker): `php -l` sui due file lingua, `view:clear`,
poi render della vista con uno script PHP temporaneo (rimosso):
- route inesistente → solo il messaggio, nessun errore e nessuno script;
- route valida (`AdminCmsUsersControllerGetIndex`) → `<div>` + script come
  prima, con messaggio di caricamento tradotto;
- configurazione in inglese → label e opzioni ("List"/"Add") tradotte.

Non verificato: rendering in browser dentro una dashboard reale e messaggi
404/500 del `.fail()` (il codice è cambiato solo nella sorgente dei testi).
Suite di test non lanciata.

## Rischi e note

- Non tocca lo scraping di `#content_section`, sostituito dall'iframe
  nelle fasi successive.
- Il partial condiviso `_empty_widget_state.blade.php` ha ancora
  "Clicca qui per configurare" hardcoded: fuori perimetro, riguarda
  tutti i widget, da sistemare a parte.

## Rollback

Ripristinare `panelcustom.blade.php` e rimuovere le chiavi
`module_widget_*` dai due file lingua.
