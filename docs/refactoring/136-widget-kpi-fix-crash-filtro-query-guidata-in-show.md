# 136 - Widget Indicatore KPI: fix crash "Object could not be converted to string" con filtro guidato

- **Data**: 2026-09-29
- **Stato**: Completato (verificato leggendo il codice e con un test PHP isolato, non in browser su richiesta esplicita dell'utente)
- **Area**: Statistic Builder / Backend
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`

## Contesto

Segnalato dall'utente subito dopo aver usato per la prima volta il fix
135 (filtro della Query guidata finalmente salvabile): visitando la
dashboard pubblica (`/admin/statistic_builder/show/test2?m=9`), errore
`Object of class stdClass could not be converted to string` con
riferimento a `smallbox.blade.php`. L'utente aveva nel frattempo creato
da solo dei widget reali seguendo i KPI suggeriti in chat, incluso uno
con un filtro guidato (`Fatture da incassare`, dataset `mg_fatture`,
filtro `pagata = 0`).

## Situazione prima

`renderComponentPayload()` itera **tutte** le chiavi di `$config`
(decodificato con `json_decode()` senza il flag associativo) e per
ognuna, se il valore e' truthy, renderizza il componente in modalita'
`showFunction` con quella chiave/valore e sostituisce `[chiave]` nel
layout - pensato per le chiavi scalari vere (name/icon/color/
description/link/...). `filters` (introdotto in 131, ma **mai
effettivamente popolato fino al fix 135**, perche' prima non veniva
salvato) e' una mappa `{colonna: valore}`: decodificata senza il flag
associativo diventa un `stdClass`, non uno scalare. Nessun layout ha mai
un token `[filters]` da sostituire, quindi il ramo generico di
`smallbox.blade.php` (`else { echo $value; }`) veniva comunque eseguito
con `$value` = oggetto, causando l'errore fatale non catturato (un
`\TypeError`/`\Error`, non un `\Exception`, quindi non intercettato dai
`try/catch` gia' presenti nel blade).

Bug latente da quando fu introdotta la chiave `filters` in 131: mai
emerso perche' fino a 135 quella chiave non veniva mai davvero salvata
con un valore non vuoto.

## Situazione dopo

Il ciclo generico salta ora le chiavi con valore non scalare
(`!is_scalar($value)`), prima del controllo di truthiness gia'
esistente. Il blocco dedicato alla modalita' `builder` (poco piu' sotto
nello stesso metodo), che gia' castava `(array) ($config->filters ??
[])` per `DashboardDatasetRegistry::execute()`, resta invariato: il
filtro continua a funzionare, solo il tentativo di trattarlo come
placeholder testuale viene evitato.

## Motivazione

Fix minimo e mirato al sintomo esatto: nessun layout usa `[filters]`
(ne' alcun altro widget) quindi scartare le chiavi non scalari dal ciclo
generico non cambia l'HTML prodotto in nessun caso gia' funzionante,
elimina solo il crash quando la chiave capita ad avere un valore
strutturato.

## Test

Non verificato in browser (regola esplicita dell'utente, vedi
`feedback_no_autonomous_browser_testing`). Verificato:
- `php -l` sul controller modificato;
- test PHP isolato che riproduce la decodifica reale del config salvato
  (`json_decode()` di `{"filters":{"pagata":"0"}}`) confermando che
  `filters` e' `stdClass` (`is_scalar()` false) e che il cast `(array)`
  usato piu' sotto nel metodo resta corretto (`{"pagata":"0"}`).
- letto direttamente da DB il config reale del widget "Fatture da
  incassare" che ha causato il crash, confermando lo scenario esatto.

Da confermare dall'utente: ricaricare
`/admin/statistic_builder/show/test2?m=9` (o il builder della stessa
dashboard) e verificare che il widget "Fatture da incassare" mostri il
conteggio filtrato senza errori.

## Rischi e note

Se in futuro un widget guadagnasse un'altra chiave di config
strutturata (array/oggetto) con un significato diverso da "filtro",
questo stesso controllo la escluderebbe automaticamente dal ciclo
generico - comportamento voluto (il ciclo generico non e' comunque
adatto a valori non scalari), ma da tenere a mente se in futuro
servisse davvero un placeholder `[chiave]` non scalare (oggi non serve
a nessun widget esistente).

## Rollback

`git diff` di `StatisticBuilderController.php` per tornare alla
situazione precedente (crash con filtro guidato salvato).
