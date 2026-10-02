# 196 - Module generator, passo Campi unificato (dietro flag)

- **Data**: 2026-10-01
- **Stato**: Completato (flag spento di default; verificato in Docker con tabelle temporanee reali, test manuale nel browser reale da fare)
- **Area**: Frontend / Backend / Module generator
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleGeneratorFields.php` (nuovo, logica pura)
  - `app/Http/Controllers/System/ModulsController.php` (`getStep2`, `postStep2`, nuovi `getStep2V2`/`postStep2V2`)
  - `resources/views/crudbooster/module_generator/step2_v2.blade.php` (nuovo)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Fase 4 della revisione del module generator (vedi 193, 194, 195). Oggi il
modulo si definisce in due passi che parlano di cose diverse: il passo 2
(struttura della tabella: nome colonna, tipo SQL tra tre, lunghezza) e il
passo 4 (form: etichetta, nome colonna, tipo di input, validazione, larghezza,
opzioni). Il nuovo passo "Campi" li fonde: si definisce una volta sola cosa
e' un campo (etichetta, tipo di campo scelto tra i `type_components` con nomi
descrittivi, obbligatorio, opzioni e regole nel modale "Avanzate").

## Situazione prima (scoperte da `save_table()` e `getTableStructure()`)

- `save_table()` lavora per **indice di riga** e conosce solo tre tipi di
  colonna: `text` (string), `number` (integer), `boolean`.
- Su una tabella esistente **ogni colonna il cui indice manca dalla richiesta
  viene eliminata con `dropColumn`** (dati compresi). Nel vecchio passo 2
  cancellare una riga dalla tabella significa eliminare la colonna dal
  database.
- `getTableStructure()` (modo standard) traduce `varchar`→`text`,
  `int`→`number`, `tinyint`→`boolean` e **tutto il resto in `text` senza
  chiave `size`**: su una tabella con una colonna `date`/`decimal`/`text`,
  `save_table()` legge `$existing_table[$index]['size']` (chiave inesistente,
  errore in PHP 8) anche se l'utente non ha cambiato nulla.
- Il vecchio passo 4 scrive nel blocco FORM solo `label`, `name`, `type`,
  `validation`, `width` e le chiavi delle opzioni del tipo: ogni altra chiave
  di una voce esistente (`value`, `readonly`, `placeholder`, `callback`,
  `relationship_table`, ...) si perde al salvataggio.
- La validazione e' una stringa Laravel scritta a mano; le opzioni
  (`dataenum`/`datatable`/`dataquery`) sono tre caselle di testo.

## Situazione dopo

Con `module_generator.wizard_v2` acceso (flag di 194), il passo 2 diventa
"Campi" (`step2_v2`, `getStep2V2`/`postStep2V2`); con il flag spento nulla
cambia (resta il vecchio passo 2 e `save_table()` invariata).

**Interfaccia**: un elenco di campi trascinabile. Per ogni campo: etichetta,
tipo di campo scelto tra i 34 `type_components` con nome descrittivo e una
riga di spiegazione con esempio (testi in `mg_field_types`, raggruppati per
famiglia), interruttore "Obbligatorio", pulsante **Avanzate** (modale) e
rimozione. Il nome della colonna per i campi nuovi e' generato
dall'etichetta (con suffisso numerico se gia' usato o riservato) e non e'
modificabile. Nella modale: sorgente delle scelte (elenco fisso / da un'altra
tabella con filtro / query), finestra di scelta, tipo di file, campi
lat/lng, HTML, lunghezza nel database (solo colonne nuove) e regole di
controllo a scelta guidata (lunghezza o valore min/max, caratteri ammessi,
univoco, date ammesse, tipo e dimensione file); le regole non riconosciute
restano elencate e conservate. Le colonne della tabella non ancora nel modulo
compaiono spente ("Colonna della tabella non nel modulo") e si accendono per
aggiungerle.

**Perimetro prudente sul database** (vedi "Situazione prima"):

- non si modifica, rinomina o elimina nessuna colonna esistente: "rimuovi" su un
  campo esistente lo toglie solo dal blocco FORM;
- tabella nuova: si chiama il ramo di creazione di `save_table()` (invariato);
- tabella esistente: nuova funzione `addTableColumns()` che aggiunge solo
  colonne (stesse chiamate di schema e stesso log del ramo "add column" di
  `save_table()`, mai il ramo di modifica che elimina le colonne mancanti e che
  fallisce su tipi di colonna che il generatore non conosce);
- le tabelle `cms_*` restano rifiutate come in `save_table()`;
- solo superadmin; copia di sicurezza del controller prima di scrivere
  (`storage/app/module_generator_backups/`, ultime 20); validazione completa
  **prima** di toccare database o file; se la creazione/aggiunta di colonne fallisce
  il blocco FORM non viene scritto.

**Tipi di colonna**: il generatore sa creare solo `text` (varchar), `number`
(int) e `boolean`. Una colonna nuova e' quindi `number` per i campi "Numero
intero" e "Importo" e `text` per tutti gli altri (date, testi lunghi, scelte,
file...). Lunghezza di default 255 (11 per i numeri, 1000 per testi lunghi,
editor, JSON e testi multipli; modificabile fino a 4000 nella modale). I campi
senza colonna (header, sotto-tabella, HTML, mappa, finestre di sistema) non
creano nulla.

**Blocco FORM** (`App\Helpers\ModuleGeneratorFields`, nessun accesso a
DB/request): stesso formato di sempre. Rispetto al vecchio passo 4 si
**conservano** le chiavi che quel passo perdeva (`value`, `readonly`,
`placeholder`, `default`, `datatable_format`, parti aggiuntive di `datatable`,
`required`, ...): tutte se il tipo non cambia, solo quelle generiche
(`help`, `style`, `readonly`, `disabled`, `placeholder`, `value`) se cambia. Se le
scelte nella modale coincidono con quelle gia' nel file (elenco, tabella,
regole) si riusano le chiavi e la stringa di validazione originali
byte per byte; la `width` si imposta solo ai campi nuovi (`col-sm-10`) e non si
aggiunge a una voce esistente che non la aveva. I controlli di tipo
(`string`, `integer`, `email`, `date`, ...) si aggiungono alla validazione solo
ai campi nuovi.

## Motivazione

Vedi 193. Le scoperte sopra guidano un perimetro volutamente prudente: il
nuovo passo non deve poter cancellare, rinominare o ritipizzare colonne
esistenti, ne' perdere chiavi del form che il vecchio passo avrebbe perso.

## Test

Eseguiti nel container Docker locale, senza lanciare la suite di test
(script una tantum, poi rimossi):

- **Logica pura** (54 controlli): lettura delle voci del form e della
  struttura, regole (parse e build), round trip "riapri e risalva" identico
  voce per voce (chiavi extra, `datatable` a 3 parti, `dataenum` con
  `valore|etichetta`, validazione, header), conservazione delle chiavi al cambio
  di tipo, campi nuovi (colonne e validazione), errori (etichetta vuota, nome non valido,
  riservato, doppio, tipo ignoto, elenco vuoto, tabella ignota o con apice,
  finestra senza colonne, regole non numeriche), sintassi PHP valida del
  controller riscritto con apici/backslash/`$`. Hanno trovato un difetto:
  aggiungevo `width` a voci esistenti che non l'avevano (cambiava la larghezza
  disegnata): corretto.
- **Interfaccia** in Chrome headless con clic simulati (34 tipi in 8 gruppi,
  nome colonna generato, modale con elenco/tabella/regole, rimozione dal modulo,
  riattivazione di una colonna, campo nuovo) e payload reale passato alla
  logica server: 10 controlli sull'esito. Screenshot controllato. Render in
  italiano e inglese senza chiavi di traduzione grezze.
- **Cablaggio con DDL reale** (22 controlli significativi, tabelle e moduli
  temporanei creati e poi rimossi, nessun avanzo nel database ne' su disco):
  - tabella esistente con dati: tre colonne aggiunte in coda e nell'ordine
    delle righe, con i tipi attesi; colonne esistenti **identiche** (tipo,
    lunghezza, nullabilita'); dati intatti; blocco FORM riscritto e valido;
    copia di sicurezza uguale al file di prima; flag spento, non
    superadmin, etichetta vuota e tabella `cms_*` = nessuna scrittura e
    nessuna colonna;
  - "rimuovi dal modulo": il campo esce dal FORM, la colonna e i suoi dati
    restano;
  - tabella nuova: creata da `save_table()` con le colonne dei campi piu' le
    colonne di sistema, FORM scritto, il campo senza colonna (header) non
    crea nulla.
- Lint di helper, controller e file di lingua; la vista si compila.

**Non verificato**: uso nel browser reale dentro il template admin (stili
AdminLTE, trascinamento col mouse, caricamento delle colonne di una tabella via
`table-columns` — nel mio ambiente la richiesta non e' raggiungibile, quindi
l'elenco "quale campo mostrare?" e le colonne della finestra di scelta non sono
state provate dal vivo), una richiesta HTTP autenticata completa, i test PHPUnit
esistenti (non lanciati; `save_table()` e il vecchio passo 4 non sono stati
modificati). Non esistono ancora test PHPUnit per il nuovo passo.

## Rischi e note

- **Il nuovo passo non puo' rinominare, ritipizzare, ridimensionare o eliminare
  colonne esistenti**: con il flag acceso queste operazioni non sono disponibili
  (restano nel vecchio passo 2, a flag spento). Rimuovere una colonna e' una
  decisione da trattare a parte, con conferma e riepilogo.
- **Tipi di colonna limitati a testo/numero/booleano**: date, testi lunghi e
  importi decimali sono salvati in `varchar`/`int`. Estendere i tipi richiede di
  cambiare `save_table()` e `getTableStructure()` (che oggi legge ogni tipo
  sconosciuto come "testo" senza lunghezza): rimandato, serve una decisione
  esplicita perche' tocca la semantica dello schema.
- Un campo che nel database e' una `date`/`decimal`/`text` compare come
  "text" in "Nel database: ..." perche' `getTableStructure()` non conosce quei tipi
  (limite preesistente; non causa modifiche).
- **Il passo 4 del flusso (form) resta quello vecchio** finche' non arriva il layout
  a blocchi: se lo si risalva riscrive il blocco FORM perdendo di nuovo le chiavi
  non gestite (comportamento preesistente).
- I testi delle descrizioni dei sette tipi specifici CustomerHive e di
  `multitext`/`json` sono dedotti dai nomi: da rivedere.
- Il default dei campi suggeriti per colonne `tinyint` e' "Scelta singola" con
  `1|Yes;0|No` (testi da `confirmButtonText`/`confirmation_no`): da verificare con
  un modulo reale.

## Rollback

Flag `module_generator.wizard_v2` a `false`: tornano il passo 2 e il passo 4 di
prima. I controller risalvati col nuovo passo restano validi (stesso formato
del blocco FORM); le colonne aggiunte restano nel database. Le copie di
sicurezza del controller sono in `storage/app/module_generator_backups/`.

## Rollback

Flag `module_generator.wizard_v2` a `false`: il passo 2 e il passo 4 tornano
quelli di prima. Il formato salvato nel blocco FORM e' lo stesso.
