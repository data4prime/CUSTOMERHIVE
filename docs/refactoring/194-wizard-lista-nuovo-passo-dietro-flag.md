# 194 - Module generator, passo Lista (nuova interfaccia dietro flag)

- **Data**: 2026-10-01
- **Stato**: Completato (flag spento di default; verificato in Docker, test manuale nel browser reale da fare)
- **Area**: Frontend / Module generator
- **File/aree di codice coinvolte**:
  - `config/module_generator.php` (nuovo, flag `wizard_v2`)
  - `app/Helpers/ModuleGeneratorList.php` (nuovo, logica pura)
  - `app/Http/Controllers/System/ModulsController.php` (`getStep3`, `postStep3`, `getStep5`)
  - `app/Http/Controllers/System/CBController.php` (`getIndex`: formato e badge)
  - `resources/views/crudbooster/default/table.blade.php` (larghezza colonna)
  - `resources/views/crudbooster/module_generator/step3_v2.blade.php` (nuovo)
  - `resources/views/crudbooster/module_generator/step5.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Fase 2 della revisione del module generator (vedi 193 per il piano
completo). Il passo Lista di oggi ha 10 colonne di input per riga (nome
colonna in lista, nome colonna DB, join table/column, callback PHP, query,
larghezza px, immagine, download) senza spiegazioni. Il nuovo passo parte
dalle colonne reali della tabella: si sceglie cosa mostrare, in che ordine e
con quale formato. Si introduce il flag `wizard_v2` (spento di default) per
poter distribuire il codice senza cambiare il wizard visibile in produzione.

## Situazione prima

- `getStep3` legge il blocco `# START COLUMNS` del controller con `eval` e
  mostra una tabella di input; `postStep3` riscrive il blocco con
  `var_export`. Il wizard salva solo `label`, `name`, `join`, `image`,
  `download`, `width`, `callback_php`, `query`: ogni altra chiave presente
  nel blocco (`visible`, `join_where`, `join_id`, `nl2br`, `str_limit`, ...)
  viene persa al salvataggio.
- `CBController::getIndex` interpreta `name` in tre modi: colonna semplice,
  `tabella.colonna`, oppure espressione SQL con alias (`... as alias`, selezionata
  con `DB::raw`); `callback_php` sostituisce `[campo]` con i valori della riga
  (solo i campi selezionati) e fa `eval`; `query` esegue una SELECT per riga.
- Limit e Order By sono nel passo Configurazione.
- **Bug preesistente scoperto**: in `table.blade.php` la larghezza e'
  `$width = isset($col['width']) ?: "auto"`, che con `width` valorizzato vale
  `true` e produce `<th width='1'>`: il valore in px scelto nel wizard non ha
  mai avuto l'effetto dichiarato. Non lo correggo cambiando `width` (cambierebbe
  l'aspetto dei moduli dei clienti): il nuovo passo scrive una chiave nuova,
  `col_width`, rispettata dalla vista; `width` resta com'e'.

## Situazione dopo

**Flag**: `config('module_generator.wizard_v2')`, da `.env`
`MODULE_GENERATOR_WIZARD_V2` (default `false`, aggiunto a `.env.example`).
Spento: nessuna differenza rispetto a prima, tranne il passo Configurazione
gia' modificato in 193.

**Con il flag acceso**:

- `getStep3` restituisce `step3_v2` (`getStep3V2()`): elenco delle colonne
  reali della tabella (quelle gia' nel modulo nell'ordine salvato, poi le
  altre spente), con interruttore "mostra", trascinamento (jQuery UI sortable
  gia' caricato dal template admin), titolo colonna, formato in base al tipo
  DB (data, data/ora, importo, badge colorato, troncamento a N caratteri,
  immagine, download), larghezza (Auto/Stretta/Media/Larga), tabella
  collegata in modale (suggerita dal `datatable` del form se presente),
  colori dei badge in modale (per voce se il form ha un `dataenum`, altrimenti
  un colore unico), colonne calcolate SQL o PHP con espressione in modale,
  colonne di sistema (`id`, `group`, `tenant`, `*_at`, `*_by`) bloccate ma
  spostabili e mostrabili, anteprima della lista (desktop/tablet/telefono) con
  avviso oltre 7 colonne, ordinamento e righe per pagina.
- `postStep3` riconosce il campo `payload` (JSON) e passa a
  `postStep3V2()`; senza `payload` esegue il codice di sempre. Prima della
  scrittura viene salvata una copia del controller in
  `storage/app/module_generator_backups/` (ultime 20 per controller).
- Limit e Order By si impostano qui e vengono scritti nel blocco
  CONFIGURATION con `mergeConfig()` (aggiorna solo le due proprieta'); nel
  passo Configurazione restano come campi nascosti con i valori correnti
  perche' `postStep5` riscrive il blocco solo con le chiavi presenti nel POST.
  Il Campo titolo resta nel passo Configurazione.

**Logica in `App\Helpers\ModuleGeneratorList`** (nessun accesso a DB/request):
`readBlock` (stessa lettura del blocco di sempre), `describeRows`,
`buildColumns` (validazione con whitelist: nome colonna deve esistere, join
solo verso tabelle esistenti con identificatori validi, formato in elenco,
colori `#rrggbb`, SQL senza `;` e commenti, campi `[x]` del PHP devono essere
colonne), `replaceColumnsBlock`, `mergeConfig`, `formatValue`. Tutti i
valori finiscono nel sorgente del controller via `var_export`.

**Colonne esistenti conservate**: al salvataggio si mantengono le chiavi che
il vecchio passo avrebbe perso (`join_where`, `join_id`, `nl2br`, `color`,
`style`, `callback`, `callback_php`, `query`), le colonne personalizzate con
nome non semplice (`tabella.colonna`, espressioni) e le colonne
`visible => false` (servono ai `callback_php` che citano `[campo]`).
Le colonne spente nell'editor non vengono scritte, come col vecchio passo.

**Chiavi nuove, tutte opzionali** (ignorate da chi non le conosce):

- `format` (`date_short`, `date_long`, `datetime_short`, `money_eur`,
  `money_plain`, `badge`) e `badge` (`colors`, `default`): applicati in
  `CBController::getIndex` con `ModuleGeneratorList::formatValue`, subito
  prima di `str_limit`/`callback_php` (che restano prioritari).
- `col_width` (px): larghezza scelta nel wizard, usata da `table.blade.php`.
- `calc => 'php'`: marcatore delle colonne calcolate PHP (agganciate alla
  prima colonna citata; i campi citati e non in lista diventano colonne
  nascoste).
- Le colonne calcolate SQL usano il meccanismo gia' presente di `getIndex`:
  `name` = `(espressione) as alias` (selezionato con `DB::raw`, nessuna query
  per riga, a differenza di `query`).
- `troncamento` riusa la chiave esistente `str_limit`; `immagine`/`download`
  le chiavi `image`/`download`.

**Larghezza colonna**: `table.blade.php` usa `col_width` se presente,
altrimenti il comportamento di sempre per `width` (che vale `true`, quindi
`width='1'`, vedi "Situazione prima"). Una colonna salvata dal nuovo passo
perde la chiave `width` e guadagna `col_width` (se non Auto): per quella
colonna l'intestazione passa da `width='1'` ad `auto`/px. E' un cambiamento
visibile, limitato alle colonne risalvate col nuovo passo.

## Motivazione

Vedi 193: rendere il wizard comprensibile senza cambiare il formato di
`col`/`form`/config e senza toccare i moduli esistenti finche' non vengono
risalvati.

## Test

Eseguiti nel container Docker locale, senza lanciare la suite di test
(script una tantum, poi rimossi):

- **Logica pura** (59 controlli): lettura dei blocchi COLUMNS/FORM, righe
  iniziali, costruzione delle colonne (ordine, join, formati, badge, SQL/PHP,
  colonne nascoste e personalizzate conservate), errori di validazione (colonna
  inesistente, join verso tabella ignota o con apici, `;` nell'SQL, campo PHP
  inesistente, formato ignoto), sintassi PHP valida del controller riscritto
  anche con apici e backslash nelle etichette, round trip del blocco, merge
  della configurazione (valore malevolo resta un literal), formattazione
  (date, importi, badge con escape HTML e colori non validi ignorati).
- **Idempotenza**: riaprire le colonne scritte dal wizard e risalvarle senza
  modifiche produce lo stesso blocco. Il controllo ha trovato un difetto
  reale (una colonna calcolata PHP, agganciata a una colonna vera, veniva
  riletta come colonna normale e perdeva il marcatore): corretto.
- **Cablaggio del controller** (18 controlli, con un modulo temporaneo creato
  e poi rimosso: riga `cms_moduls`, file controller e copie di sicurezza):
  `getStep3V2` (vista, righe, limit/orderby letti), `postStep3V2` (flag
  spento = nessuna scrittura; flag acceso = colonne, `col_width`, `str_limit`,
  join, limit/orderby scritti, resto della configurazione intatto, sintassi
  valida, copia di sicurezza identica al file precedente; colonna inesistente
  e payload non JSON = nessuna scrittura e messaggio tradotto).
- **Interfaccia** in Chrome headless con clic simulati (17 righe da una
  tabella di prova, formati proposti per tipo, modale colori, join
  precompilato, colonna calcolata, ordinamento/limite, anteprima) e payload
  reale passato alla logica server; screenshot controllato. Nessun errore JS.
- Compilazione Blade e sintassi di `table.blade.php`, `step3_v2.blade.php`,
  `step5.blade.php`; `php -l` su helper, controller e file di lingua.
- Passo Configurazione renderizzato con flag spento (Limit/Order By visibili)
  e acceso (nascosti con i valori correnti).

**Non verificato**: uso nel browser reale dentro il template admin (stili
AdminLTE, il drag con il mouse), una richiesta HTTP completa con
autenticazione superadmin, i test PHPUnit esistenti (non lanciati; il vecchio
percorso di `postStep3` non e' stato modificato, il test che controlla il
formato dei valori scritti riguarda solo quello). Non esistono ancora test
PHPUnit per il nuovo passo: i controlli sopra sono script una tantum, da
convertire in test se richiesto.

## Rischi e note

- Il nuovo passo scrive PHP nel sorgente del controller, come il vecchio:
  stessi vincoli (solo superadmin, `var_export`), validazione piu' rigida e
  copia di sicurezza prima della scrittura. Le espressioni SQL/PHP delle
  colonne calcolate restano codice eseguito dall'app (SQL con `DB::raw`, PHP
  con `eval` e sostituzione grezza di `[campo]` con i valori della riga: i
  valori di testo vanno messi tra apici dall'autore dell'espressione): rischio
  gia' presente in `callback_php`, non aumentato ma non eliminato.
- Le colonne spente nell'editor non si scrivono nel file: se erano state
  configurate (titolo/formato) la configurazione va rifatta alla prossima
  riattivazione, come col vecchio passo.
- Un `orderby` con piu' criteri (`a,desc;b,asc`) si mantiene se non lo si
  cambia; l'editor ne gestisce uno solo.
- Il flag e' globale (non per modulo). Spegnerlo riporta il vecchio passo; i
  controller gia' risalvati col nuovo passo restano validi (chiavi opzionali
  ignorate).
- Fasi successive: Campo titolo, passo Campi unificato e layout form a blocchi
  (vedi 193).

## Rollback

Flag `module_generator.wizard_v2` a `false` (default): il passo Lista torna
quello di prima. Le chiavi opzionali nuove nei controller (`format`, `badge`,
`col_width`) sono ignorate dal vecchio codice.
