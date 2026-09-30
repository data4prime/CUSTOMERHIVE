# 135 - Widget Indicatore KPI: fix filtro della Query guidata non salvato

- **Data**: 2026-09-29
- **Stato**: Completato (verificato leggendo il codice, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/statistic_builder/components/_query_builder_fields.blade.php`

## Contesto

Segnalato dall'utente subito dopo 134 (che aveva corretto il salvataggio
di `config[sql]`/`config[mode]`/`config[dataset]`/`config[metric]`):
anche impostando un filtro nella Query guidata (es. `stato = Attivo` sul
dataset Contratti), il filtro non risultava salvato.

## Situazione prima

La select del filtro (`.ch-builder-filter-key`) e il campo valore
(`.ch-builder-filter-value`) in `_query_builder_fields.blade.php` non
avevano **mai avuto un attributo `name`**, a differenza di Dataset e
Metrica (`name="config[dataset]"`/`name="config[metric]"`). Erano letti
solo lato JS per il pulsante "Prova" (preview live, via `.val()`
diretto), mai collegati al form vero: `$form.serialize()` li ignorava a
prescindere dal bug 134 (nesting del popup), perche' senza `name=` un
campo non fa parte dei dati di un form HTML, punto. Bug indipendente e
mai stato funzionante da quando il filtro fu introdotto in 131.

## Situazione dopo

Aggiunta `syncFilterValueName()`: assegna dinamicamente a
`.ch-builder-filter-value` il `name="config[filters][<colonna>]"` in
base alla colonna scelta in `.ch-builder-filter-key` (la chiave non si
conosce staticamente, dipende dalla scelta utente) - formato che Laravel
interpreta gia' come mappa associativa, esattamente cio' che
`DashboardDatasetRegistry::execute()` si aspetta in `$params['filters']`.
Se nessun filtro e' selezionato, il `name=` viene rimosso (filtro
opzionale, niente da inviare). Richiamata al cambio dataset (che
ripopola la select filtro) e al cambio diretto del filtro scelto.

## Motivazione

Correzione minima e mirata sul sintomo esatto riportato: nessun'altra
select/input del pannello aveva questo problema (Dataset/Metrica/SQL
libera avevano gia' `name=` statico, corretto da sempre).

## Test

**Non verificato in browser**: l'utente ha chiesto esplicitamente di non
eseguire più test in browser in autonomia (vedi memoria
`feedback_no_autonomous_browser_testing`). Verificato leggendo il codice
(`php -l` sul file, lettura del JS aggiornato) e ragionando sul formato
atteso lato server (`DashboardDatasetRegistry::execute()`,
`renderComponentPayload()`) - la modifica non e' stata provata a runtime.
Da confermare con un giro manuale: impostare un filtro guidato (es.
Contratti, filtro `stato = Attivo`), Salva, ricaricare, verificare che il
pannello mostri ancora il filtro e che il valore del widget rifletta il
conteggio filtrato.

## Rischi e note

Se in futuro il filtro dovesse supportare piu' di una coppia
colonna/valore contemporaneamente, questo approccio (un solo `name=`
dinamico) andrebbe rivisto - oggi il pannello supporta un filtro singolo,
coerente con `_query_builder_fields.blade.php` attuale.

## Rollback

`git diff` di `_query_builder_fields.blade.php` per tornare alla
situazione precedente (filtro non salvabile).
