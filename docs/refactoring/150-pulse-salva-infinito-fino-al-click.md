# 150 - Pulse sul pulsante "Salva" infinito finché non viene cliccato

- **Data**: 2026-09-29
- **Stato**: Completato e verificato ispezionando il sorgente compilato, non in browser su richiesta esplicita dell'utente
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_source_config.blade.php`

## Contesto

Richiesta diretta dell'utente sul pulse introdotto in 139: durava troppo
poco (0.8s × 2 ripetizioni = 1.6s) e rischiava di passare inosservato se
l'utente non guardava lo schermo in quel preciso momento.

## Situazione prima

`.ch-sidebar-save.ch-pulse { animation: ch-pulse-save 0.8s ease-in-out 2; }`
- si fermava da solo dopo 1.6s (`animation-iteration-count: 2`), con un
`setTimeout` di supporto che toglieva la classe allo scadere dello stesso
tempo (per permettere di far ripartire l'animazione a un successivo
open/close del popup).

## Situazione dopo

`animation-iteration-count: infinite` - il pulse continua finche' il
pulsante non viene davvero cliccato. `pulseSaveButton()` non usa piu' un
timer per rimuovere la classe: registra invece un handler `click`
(namespace `.chPulse`, `off().one()` per evitare di accumularne piu' di
uno se il popup si apre/chiude piu' volte senza mai premere Salva) che
toglie `ch-pulse` al primo click reale sul pulsante.

## Motivazione

Richiesta diretta dell'utente: un segnale visivo che sparisce da solo
dopo 1.6s puo' sfuggire, uno che si ferma solo all'azione richiesta
(cliccare Salva) no.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato ispezionando il
sorgente COMPILATO (script isolato, bootstrap Laravel, eseguito e poi
rimosso): `animation: ch-pulse-save 0.8s ease-in-out infinite` e
`off('click.chPulse').one('click.chPulse', ...)` presenti nel file
compilato di `_source_config.blade.php`.

## Rischi e note

Nessuno noto - il pulsante resta comunque cliccabile/funzionante durante
il pulse (l'animazione tocca solo `box-shadow`, non pointer-events).

## Rollback

`git diff` di `_source_config.blade.php` per tornare al pulse a durata
fissa (2 ripetizioni, 1.6s).
