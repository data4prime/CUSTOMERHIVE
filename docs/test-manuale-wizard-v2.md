# Test manuale del nuovo module generator (wizard v2)

Checklist per provare nel browser le fasi 1-5 della revisione del module
generator (interventi 193-197). Quello che si e' gia' verificato da script e
cosa resta da vedere dal vivo e' nei singoli documenti, sezione "Test".

## Preparazione

1. Ambiente Docker locale acceso (`docker compose up -d`), accesso come
   superadmin (credenziali in `docs/docker-local-dev.md`).
2. **Prima di tutto, con il flag spento** (default): controlla che il
   wizard e i moduli esistenti si comportino come prima (vedi "A flag spento").
3. Per il nuovo wizard: nel `.env` aggiungi `MODULE_GENERATOR_WIZARD_V2=true` e
   riavvia/svuota la cache di configurazione
   (`docker compose exec app php artisan config:clear`).
4. Meglio provare su un **modulo di prova** (nuovo), non su un modulo vero: ogni
   salvataggio crea una copia di sicurezza del controller in
   `storage/app/module_generator_backups/`.

## A flag spento (regressione)

- Passo 5 (Configurazione): interruttori raggruppati, stile pulsanti con
  anteprima, Limit/Order By/Campo titolo in alto. Salva e verifica nella lista del
  modulo che pulsanti e stile corrispondano a quanto scelto, e che i valori
  salvati siano gli stessi di prima (pulsante spento = assente).
- Aprire, modificare e salvare un modulo **esistente** (form e lista): devono
  comportarsi come sempre. Verifica in particolare un modulo con campi
  `select2` e `radio`, e un modulo `mg_*` aperto da superadmin (compaiono
  Tenant e Group in fondo, nel box "System Information"): sono i tipi che non
  si sono potuti confrontare da riga di comando (intervento 195).
- Un sotto-modulo (modulo figlio aperto da un record padre): il campo che lo
  collega al padre deve restare nascosto e salvarsi.

## Passo 2 - Campi (flag acceso)

- Modulo **nuovo**: aggiungi 3-4 campi di tipi diversi (testo breve, numero,
  data, elenco a tendina con voci fisse, scelta da un'altra tabella). Controlla
  che il nome della colonna si generi dall'etichetta e non sia modificabile.
- "Avanzate": elenco fisso (aggiungi/togli voci, prova `1|Attivo`), da un'altra
  tabella (il menu "quale campo mostrare?" si deve riempire: **non si e'
  potuto provare** il caricamento delle colonne), query, regole (min/max,
  univoco, date ammesse...).
- Salva: la tabella viene creata con le colonne dei campi. Date, importi e
  testi lunghi sono salvati come testo/numero (limite noto).
- Modulo con **tabella esistente**: le colonne esistenti compaiono come
  "Colonna esistente"; una colonna non nel modulo compare spenta e si puo'
  aggiungere; "rimuovi" toglie il campo dal modulo ma **non** la colonna (verifica
  nel database). Aggiungi un campo nuovo: la colonna viene aggiunta in coda.
  Cambiare il tipo di un campo esistente non deve toccare la colonna.
- Prova a salvare con un errore (etichetta vuota, nome doppio): messaggio in
  italiano/inglese, nessuna modifica al database, i dati inseriti restano nel form.

## Passo 3 - Lista (flag acceso)

- Spunta/togli colonne, trascinale, cambia titolo, formato (data, importo,
  badge colorato, testo troncato a N caratteri), larghezza.
- Badge: modale dei colori, per voce se l'elenco e' fisso, colore unico se viene
  da una tabella.
- Tabella collegata (modale): scegli tabella e campo da mostrare. (Anche qui
  il caricamento dei campi non e' stato provato dal vivo.)
- Colonna calcolata SQL (es. `CONCAT(nome, ' ', cognome)`) e PHP (es.
  `number_format([prezzo] * [quantita], 2)`; i valori di testo vanno tra
  apici: `strtoupper('[nome]')`).
- Ordinamento e righe per pagina (ora qui, non piu' nel passo 5).
- Anteprima Desktop/Tablet/Telefono; con piu' di 7 colonne compare l'avviso.
- Salva, apri la lista del modulo e controlla: formati, badge, larghezze
  (`col_width`), colonne calcolate, ordinamento, righe per pagina.
- Riapri il passo e risalva senza toccare nulla: il risultato deve restare uguale.

## Passo 4 - Layout del form (flag acceso)

- Aggiungi un blocco e delle schede, trascina i blocchi dall'intestazione e
  ridimensionali dai bordi; usa "adatta altezza".
- Trascina campi da un blocco all'altro, da un blocco alla scheda attiva, e dal
  pannello "Campi da posizionare" a un blocco; rimetti un campo nel pannello.
  Per spostare un campo in una scheda non attiva usa l'ingranaggio ("Dove si
  trova").
- Larghezza del campo (intera, 1/2, 1/3, 1/4) e testo di aiuto dall'ingranaggio.
- Colonne di sistema (creato da, data creazione...): trascinale nel form;
  compaiono in sola lettura. Tenant e gruppo non si possono posizionare (nota nel
  pannello).
- Anteprima Desktop e Telefono.
- Prova a salvare lasciando fuori un campo **obbligatorio**: messaggio di errore.
- Salva e apri il form del modulo (nuovo record e modifica):
  - i blocchi sono nelle posizioni scelte, le schede funzionano;
  - i campi hanno l'etichetta sopra e la larghezza scelta; **controlla i
    componenti piu' complessi** (`select2`, `radio`, `checkbox`, editor di
    testo, upload, finestra di scelta) dentro i blocchi: il CSS di adattamento e'
    generico e qualcosa potrebbe richiedere ritocchi;
  - il campo lasciato fuori dal layout **non** compare e, salvando, il suo valore
    gia' presente nel database **non viene cancellato**;
  - Tenant/Gruppo (se sei superadmin o tenant admin) compaiono in fondo nel box
    collassabile "System Information";
  - un campo obbligatorio dentro una scheda non attiva: premendo Salva si apre la
    scheda col campo non valido;
  - su schermo stretto (o finestra ridotta) i blocchi si impilano.
- Apri il **dettaglio** di un record: campi nell'ordine del layout con i titoli
  di blocco/scheda e le colonne di sistema.

## Export / import

- Esporta un modulo con layout: il JSON contiene `form_layout`. Importalo con un
  altro nome (su un'installazione di prova): il form importato ha lo stesso
  layout. Esporta un modulo senza layout: nessuna chiave `form_layout`.

## Cosa non e' stato fatto

- **Tipi di colonna** (date, importi decimali, testi lunghi): il generatore crea
  solo testo/numero/booleano. Estenderli richiede di cambiare `save_table()` e
  `getTableStructure()`; serve una decisione (intervento 196).
- **Campo titolo** (`title_field`): resta nel passo Configurazione.
- **Dismissione del vecchio wizard**: solo quando tutti i clienti sono stati
  aggiornati (regola del progetto); i controller dei clienti non vanno toccati
  finche' non si fa l'aggiornamento.
- **Test automatici PHPUnit** per i nuovi passi: non scritti (i controlli fatti
  sono script una tantum, descritti negli interventi 193-197).
