# 195 - Form: estrazione del partial `form_field` da `form_body`

- **Data**: 2026-10-01
- **Stato**: Completato (verificato con confronto dell'HTML in Docker; test nel browser reale da fare)
- **Area**: Frontend / Form dei moduli
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form_body.blade.php`
  - `resources/views/crudbooster/default/form_field.blade.php` (nuovo)

## Contesto

Fase 3 della revisione del module generator (vedi 193 e 194). Il layout a
blocchi e schede del form (fase 5) deve poter disegnare i campi in posizioni
diverse riusando esattamente lo stesso codice che li disegna oggi. Per
questo il corpo del loop di `form_body.blade.php` va estratto in un partial
richiamabile per singolo campo. **Nessun cambiamento visibile**: e' un
refactoring puramente strutturale.

## Situazione prima

`form_body.blade.php` ha due loop: il primo include gli `asset` di ogni tipo
una sola volta; il secondo, per ogni campo di `$forms`, calcola le variabili
che i componenti dei tipi si aspettano (`$name`, `$value` con valore
precedente/`old()`/`callback_php`/`callback`/join, `$validation`, `$required`,
`$readonly`, `$disabled`, `$placeholder`, `$col_width`, override a `hidden`
per `$parent_field`), aggiorna lo stato `$header_group_class` (cumulativo tra
un campo e il successivo) e include il componente del tipo
(`type_components/<tipo>/component`, con override in
`views/vendor/crudbooster/type_components`). Intorno ai campi `tenant` e
`group`/`primary_group` stampa l'apertura e la chiusura del box collassabile
"System Information" (la chiusura avviene in un'iterazione diversa da
quella di apertura, quindi il box dipende dall'ordine dell'output).

Tutto e' in un unico template: non si puo' disegnare un singolo campo senza
rieseguire il loop.

## Situazione dopo

- **`form_field.blade.php`** (nuovo): il corpo del secondo loop di
  `form_body`, spostato senza modifiche di logica. Riceve `$form` e
  `$index` (e `$header_group_class`, solo in lettura), e usa dalle variabili
  condivise `$row`, `$parent_field`, `$parent_id`. Contiene il calcolo di
  `$name`/`$value`/`$validation`/`$required`/..., l'override a `hidden` per
  `$parent_field`, il box "System Information" intorno a `tenant` e
  `group`/`primary_group` e l'include del componente del tipo (con override
  in `views/vendor`).
- **`form_body.blade.php`**: resta il caricamento degli asset (invariato) e
  il loop dei campi, che ora calcola solo lo stato cumulativo
  `$header_group_class` (dipende dal campo precedente, quindi non puo' vivere
  nel partial: ogni include ha il proprio scope) con la stessa condizione di
  prima (tipo di default `text`, `hidden` se `$parent_field` coincide col nome
  del campo, `header` che azzera il gruppo) e poi fa
  `@include('crudbooster::default.form_field', ...)`.
- Il partial e' il punto che il layout a blocchi (fase 5) richiamera' per
  disegnare un singolo campo.

Il disegno dei componenti dei tipi non e' stato toccato: l'unica differenza
nell'HTML prodotto e' di spazi/a capo tra la fine di un campo e l'inizio del
successivo (vedi "Test").

## Motivazione

Prerequisito della fase 5 (layout form a blocchi): il renderer a blocchi
chiamera' `form_field` per ogni campo posizionato. Estrarre ora, da solo e
verificato con un confronto dell'HTML, tiene separato il rischio del
refactoring da quello della nuova funzionalita'.

## Test

Eseguiti nel container Docker locale, senza lanciare la suite di test.

**Confronto dell'HTML prima/dopo**: stesso insieme di campi renderizzato con
il `form_body` originale (estratto da git HEAD) e con quello nuovo, poi
confronto dell'HTML. 8 scenari, tutti equivalenti:

- nuovo record e modifica con valori (text, textarea, number, select con
  `dataenum`, checkbox, date, email in sola lettura, money disabilitato,
  password, hidden, campo con `callback_php`, tipo inesistente, due header);
- sotto-modulo (`parent_field` su un campo normale) e `parent_field` che cade
  su un campo `header`;
- campi senza `type` (default `text`) con un header in mezzo;
- campo con `join` e riga valorizzata;
- box "System Information": `tenant` senza campo di chiusura (il box resta
  aperto, come prima) e `tenant` chiuso da `primary_group`.

L'HTML coincide, token per token, in tutti gli scenari; **non coincide byte
per byte**: nel nuovo manca lo spazio/a capo tra la fine di un componente e
l'inizio del successivo (`</div>` seguito direttamente da `<div ...>` invece
che da righe vuote e indentazione), perche' il partial non riproduce le righe
vuote che stavano fra i blocchi PHP del vecchio loop. Sono elementi di
blocco (righe del form) o `input hidden`: nessun effetto sul rendering. Il
contenuto interno dei componenti, comprese le `textarea`, e' invariato.

Verificato anche che nessun componente in `type_components/*/component`
dipenda da variabili del template padre diverse da `$header_group_class` e
`$index` (entrambe passate al partial).

**Non verificato**: i tipi `radio`, `select2` (con `datatable`) e il campo
`group`/`tenant` aggiunto in automatico da `add_default_form_fields()` per i
moduli `mg_*`: nel mio script da riga di comando i loro componenti non
possono essere renderizzati (servono una rotta e una richiesta reali), quindi
falliscono in modo identico nella versione vecchia e nella nuova. Il percorso
del partial per quei tipi e' lo stesso, ma non e' stato confrontato. Da
controllare aprendo nel browser un modulo con select2 e un modulo `mg_*` da
superadmin.

## Rischi e note

- E' un refactoring dietro il quale passano **tutti** i form dei moduli (non
  c'e' flag): per questo il confronto dell'HTML. Il rischio residuo e'
  concentrato nei tipi non confrontati qui sopra; un controllo visivo in
  browser su un modulo con select2 e su un modulo `mg_*` e' consigliato prima
  di rilasciare.
- Se un tipo personalizzato in `views/vendor/crudbooster/type_components`
  leggesse una variabile del template padre diversa da `$header_group_class`
  e `$index`, non la troverebbe piu' nello scope del partial (nei componenti
  del repo non succede).
- `form_detail.blade.php` ha un loop analogo ma non e' stato toccato: sara'
  trattato con il layout a blocchi (fase 5).

## Rollback

Ripristinare `form_body.blade.php` da git e rimuovere `form_field.blade.php`.
