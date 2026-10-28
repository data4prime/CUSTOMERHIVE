# 309 - Voci di menu Qlik / Agent AI: `end(explode())` rotto con PHP 8.5

- **Data**: 2026-10-28
- **Stato**: Completato (non committato)
- **Area**: Frontend / Menu, compatibilità PHP 8.5
- **File/aree di codice coinvolte**:
  - `app/Helpers/MenuHelper.php` (`parse_path_for_qlik_item_id`, `parse_path_for_chat_ai_id`)

## Contesto

Segnalazione: nella sidebar, cliccando la voce "Analytics Dashboard" (menu 14,
tipo `Qlik`, path `qlik_items/content/1`) non succedeva nulla, e in console
compariva `Uncaught SyntaxError: Unexpected end of input`. Visto sullo stack
di test dell'upgrade (`localhost:8090`, PHP 8.5.11), non sul DB di sviluppo
principale. Collegato agli interventi 303-306 (upgrade PHP 8.5 / Laravel 13).

## Situazione prima

`parse_path_for_qlik_item_id()` e `parse_path_for_chat_ai_id()` facevano
`end(explode('/', $URI))`. Passare a `end()` il risultato di una funzione
(per riferimento) genera un notice, che Laravel converte in `ErrorException`
("Only variables should be passed by reference").

In `CRUDBooster::sidebarMenu()` quella chiamata è dentro un `try/catch
(\Exception)`: l'eccezione marcava la voce `is_broken = true`.
`MenuHelper::menu_to_html_for_sidebar()` rendeva allora
`href='javascript:alert('Controller / Rotta Non Trovata')'`: gli apici interni
chiudono l'attributo e il browser esegue `javascript:alert(` (da qui
"Unexpected end of input"). Colpiva tutte le voci di tipo `Qlik` e `Agent AI`.

## Situazione dopo

Le due funzioni salvano `explode()` in una variabile prima di chiamare `end()`.
Nessuna eccezione, la voce non è più `is_broken` e il link porta a
`/admin/qlik_items/content/{id}`. Comportamento visibile: le voci Qlik e
Agent AI tornano cliccabili.

## Motivazione

Correzione della causa (uso non valido di `end()`), cambio minimo e
behavior-preserving: il valore restituito per path validi è identico
(`qlik_items/content/1?m=3` → `1`).

## Test

- Riprodotto l'`ErrorException` nel container della 8090.
- Dopo la modifica: `php -l` ok e le due funzioni restituiscono `1` e `7` per
  `qlik_items/content/1?m=3` e `chat_ai/content/7` (container di questo repo).
- NON verificato nel browser sulla 8090 (quel container usa il codice di
  `CUSTOMERHIVE-upgrade-test`, vedi sotto). Suite di test non lanciata.

## Rischi e note

- La 8090 serve `CUSTOMERHIVE-upgrade-test`, copia separata del codice: lì la
  correzione va riportata (o la cartella riallineata) per vedere l'effetto.
- Problema secondario NON corretto: il ramo `is_broken` di
  `menu_to_html_for_sidebar()` mette `alert('...')` dentro un `href='...'` con
  apici singoli, quindi non funziona mai come previsto. Va sistemato a parte
  (escaping), è un cambio indipendente.

## Rollback

Ripristinare `app/Helpers/MenuHelper.php` (`git checkout -- app/Helpers/MenuHelper.php`
o ripristinare le due righe `end(explode('/', $URI))`).
