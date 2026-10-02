# 197 - Form dei moduli: layout a blocchi e schede (dietro flag)

- **Data**: 2026-10-01
- **Stato**: Completato (flag spento di default; verificato in Docker, test nel browser reale da fare)
- **Area**: Frontend / Backend / Module generator / Form dei moduli
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleGeneratorLayout.php` (nuovo, logica pura)
  - `app/Helpers/ModuleGeneratorList.php` (`readBlock` legge anche `FORM LAYOUT`)
  - `app/Http/Controllers/System/CBController.php` (`$form_layout`, `checkFormLayout()`)
  - `app/Http/Controllers/System/ModulsController.php` (passo 4 nuovo, passo Campi, export/import)
  - `resources/views/crudbooster/default/form_body.blade.php`, `form_field.blade.php`,
    `form_layout.blade.php` (nuovo), `form_layout_fields.blade.php` (nuovo), `form_detail.blade.php`
  - `resources/views/crudbooster/module_generator/step4_v2.blade.php` (nuovo)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Fase 5 della revisione del module generator (vedi 193-196). Il form di un
modulo e' un elenco piatto di campi, uno per riga. Il nuovo passo "Layout form"
permette di comporre il form con **blocchi** (riquadri con titolo) e **schede**
(tab) posizionabili e ridimensionabili su una griglia a 12 colonne, come nelle
dashboard (gridstack, gia' usato in `statistic_builder`), con i campi
distribuiti nei blocchi/schede, ciascuno con la propria larghezza. Sul telefono
i blocchi si impilano.

## Situazione prima

- `form_body.blade.php` disegna `$forms` (da `$this->form`, piu' tenant/group
  aggiunti da `ModuleHelper::add_default_form_fields()` per i moduli `mg_*`) un
  campo dopo l'altro, in un form orizzontale (etichetta a sinistra, campo a
  destra). Tenant e gruppo stanno in un box collassabile "System Information"
  aperto da `tenant` e chiuso da `group`/`primary_group`.
- `CBController::cbLoader()` rimuove da `$this->form` i campi in `hide_form`
  prima di impostare `$this->data_inputan` (da cui dipendono validazione e
  salvataggio): un campo non presente li' non viene ne' disegnato ne'
  assegnato in `input_assignment()`; un campo presente ma non inviato dal
  browser viene salvato come stringa vuota/null.
- Il passo 4 del wizard (form) e' la vecchia tabella di input; il blocco FORM
  non contiene alcuna informazione di posizione.
- La vista dettaglio (`form_detail.blade.php`) e' una tabella a due colonne con
  un loop proprio.
- Export/import dei moduli (`getExport`/`postImport`) portano `col`, `form` e
  `config`.

## Situazione dopo

**Formato**: nuovo blocco opzionale nel controller del modulo, subito dopo
`# END FORM`:

```
# START FORM LAYOUT DO NOT REMOVE THIS LINE
$this->form_layout = ['v'=>1,'blocks'=>[
  ['id'=>'b1','kind'=>'block','title'=>'...','x'=>0,'y'=>0,'w'=>8,'h'=>5,'fields'=>[['name'=>'nome','w'=>6], ['name'=>'created_at','w'=>6,'sys'=>1]]],
  ['id'=>'b2','kind'=>'tabs','x'=>0,'y'=>5,'w'=>12,'h'=>4,'tabs'=>[['id'=>'t1','title'=>'...','fields'=>[...]]]],
]];
# END FORM LAYOUT DO NOT REMOVE THIS LINE
```

Posizioni su griglia a 12 colonne; larghezza di un campo dentro il blocco in
dodicesimi (12, 6, 4, 3); `sys` = colonna di sistema mostrata in sola lettura
(`id`, `created_*`, `updated_*`, `deleted_*`). I marcatori non contengono la
stringa dei marcatori FORM, quindi il vecchio passo 4 e `extract_unit()` non li
confondono; la riscrittura del blocco FORM (vecchio passo 4 o nuovo passo
Campi) conserva il blocco layout.

**Runtime** (`CBController`): nuova proprieta' `$form_layout` (null di default),
condivisa con le viste. `checkFormLayout()`, subito dopo `checkHideForm()` e
prima di `$this->data_inputan = $this->form`, toglie da `$this->form` i campi
**non posizionati**, con lo stesso effetto di `hide_form`: non sono disegnati ne'
assegnati al salvataggio (un campo non inviato verrebbe altrimenti salvato
vuoto). Restano sempre: i campi posizionati, `tenant`/`group`/`primary_group`,
i campi di tipo `hidden` e il campo che collega un sotto-modulo al padre.

**Form** (`form_body`): con `$form_layout` valido delega a `form_layout.blade.php`
(griglia CSS a 12 colonne; ogni blocco e' una card, le schede sono tab
Bootstrap), altrimenti il loop piatto di prima. Ogni campo e' disegnato da
`form_field` (estratto in 195), che con `no_system_box` non emette piu' il box
"System Information": tenant e gruppo vanno in un unico box collassabile in
fondo al form, chiuso sempre (nel form piatto il box si chiude solo se esiste
`group`/`primary_group`), con la logica per ruolo di
`add_default_form_fields()` invariata. I componenti, pensati per un form
orizzontale, nei blocchi hanno l'etichetta sopra il campo (CSS locale). Le
righe della griglia CSS derivano dalle `y` (valori distinti): un blocco alto
occupa tutte le righe che iniziano dentro la sua altezza; sotto i 768 px i
blocchi si impilano per posizione. Le colonne di sistema (`sys`) sono input
disabilitati senza `name` (mai inviati); `*_at` formattate `d/m/Y H:i`, `*_by`
risolte col nome utente se esiste. Un `required` in una scheda non visibile
apre la scheda del campo non valido. Campi che non esistono piu' nel form
vengono ignorati.

**Dettaglio** (`form_detail`): con layout i campi seguono i blocchi (per
posizione) con una riga di intestazione per titolo di blocco/scheda e le righe
delle colonne di sistema; in coda i rimasti. Senza layout invariato.

**Passo Form del wizard** (`step4_v2`, con il flag di 194): editor con gridstack 9
da CDN (stessa versione delle dashboard), blocchi e schede trascinabili
dall'intestazione e ridimensionabili, campi trascinabili tra blocchi/schede e
dal/al pannello "Campi da posizionare" (jQuery UI sortable, gia' caricato dal
template admin), pulsante "adatta l'altezza", impostazioni del campo in
modale (dove si trova, larghezza 12/6/4/3, testo di aiuto), anteprima
Desktop/Telefono, colonne di sistema in sola lettura trascinabili. Tenant e
gruppo non sono posizionabili. Layout iniziale per i moduli senza layout: un
blocco "Dati generali" con tutti i campi, uno per riga. Salvataggio
(`postStep4V2`): validazione lato server con whitelist (nomi che esistono nel
FORM, colonne di sistema ammesse, nessun duplicato, posizioni limitate alla
griglia, campi obbligatori tutti posizionati, almeno un campo), `help` scritto
nella voce del form solo se cambia, copia di sicurezza, poi blocco layout.

**Passo Campi** (196): se il modulo ha gia' un layout, un campo nuovo viene
aggiunto in coda al primo blocco (altrimenti resterebbe invisibile) e un
campo tolto dal modulo esce dal layout; un campo gia' presente nel form e
lasciato fuori dal layout non viene riposizionato (e' una scelta).

**Export/import** (`getExport`/`postImport`): `form_layout` e' una chiave
opzionale del JSON (assente senza layout, `format_version` invariata); in
import il layout e' validato sul form importato e scritto con
`writeImportedLayout()`, un layout non valido si scarta.

## Motivazione

Vedi 193: layout del form scelto dall'utente, senza toccare i moduli esistenti
finche' non vengono risalvati (nessun blocco `FORM LAYOUT` = form disegnato
esattamente come prima).

## Test

Eseguiti nel container Docker locale, senza lanciare la suite di test
(script una tantum, poi rimossi):

- **Logica del layout** (42 controlli): validazione (campo sconosciuto o di
  sistema inventato, duplicati, obbligatorio fuori dal layout, nessun campo,
  struttura, limiti di posizioni e titoli), righe della griglia (incluso il caso
  di una colonna alta quanto due blocchi impilati), `filterForm`, `detailOrder`,
  scrittura nel sorgente (inserimento dopo FORM, sostituzione, blocco FORM e
  "OLD FORM" intatti, riscrittura del FORM che conserva il layout, titolo con
  apici/backslash/`$`/`?>` che resta un literal, sintassi PHP valida) e
  `systemValue`.
- **Renderer**: senza layout l'HTML di `form_body` coincide, token per token, con
  quello della versione di git; con layout: blocchi e posizioni CSS corretti, titoli
  di blocchi e schede, larghezze, solo i campi posizionati, colonna di sistema in
  sola lettura con data formattata, schede con id univoci, campo hidden e campo
  padre (sotto-modulo) mantenuti, blocco di sistema tenant/gruppo una sola volta
  e chiuso (div aperti = chiusi). Screenshot controllato.
- **Dettaglio** (6 controlli): ordine e intestazioni con layout, invariato senza.
- **Editor** in Chrome headless con clic simulati: aggiunta di blocchi e schede,
  spostamenti dalla modale del campo, larghezze, testo di aiuto, trascinamento
  simulato di una colonna di sistema dal pannello al blocco (stessa via del
  trascinamento reale: ricostruzione dello stato dal DOM), rimozione dal form,
  anteprima Desktop/Telefono; payload reale validato dal server. Il test ha
  trovato due difetti, corretti: (a) la chiusura asincrona di una modale
  azzerava lo stato se se ne apriva subito un'altra (anche nei passi Campi e
  Lista: la modifica si perdeva in silenzio); (b) dopo una modifica dalla modale
  il layout si ridisegnava solo alla chiusura, lasciando per un istante DOM e
  stato disallineati: ora si ridisegna subito.
- **Cablaggio e runtime con controllori reali** (22 controlli; controller generati
  dal template del progetto, tabelle e moduli temporanei poi rimossi, runtime
  verificato in un processo PHP separato): senza layout tutti i campi sono
  disegnati e assegnati al salvataggio come sempre; con layout il campo non
  posizionato non e' ne' nel form ne' tra i campi assegnati al salvataggio, i
  campi posizionati e l'hidden si; un altro modulo senza layout resta invariato;
  `getStep4V2`/`postStep4V2` (errori senza scrittura, scrittura, copia di
  sicurezza, `help` solo dove cambia, voci del form non toccate identiche);
  aggancio col passo Campi (campo nuovo in coda al primo blocco, campo tolto
  fuori dal layout, nessun riposizionamento automatico dell'orfano); import del
  layout (valido scritto, non valido scartato).
- Rilanciati i controlli delle fasi precedenti (logica lista 59, logica campi 54,
  cablaggio passo Lista 18, cablaggio passo Campi con DDL reale 23, confronto
  HTML del form 9): tutti verdi.

**Non verificato**: uso nel browser reale dentro il template admin (stili
AdminLTE, trascinamento col mouse, stacking di blocchi molto alti), il form
con i componenti `select2`/`radio`/`checkbox` dentro i blocchi (non renderizzabili da
riga di comando: l'etichetta sopra il campo e' un CSS generico che potrebbe
richiedere ritocchi per alcuni componenti), `getExport` e `postImport` via HTTP
(il `cbInit` del controller del Module Generator richiede una rotta: si e'
provata la condizione che usa e `writeImportedLayout()`), il box
collassabile di tenant/gruppo con AdminLTE, il tab con campi obbligatori
invisibili.

## Rischi e note

- **Un campo non posizionato non si disegna e non si salva** (come `hide_form`).
  E' la scelta che evita di azzerare i dati di quei campi, ma significa che
  dopo aver messo un layout un campo aggiunto altrove (a mano nel controller,
  o dal vecchio passo 4) non compare finche' non lo si posiziona. Il wizard
  nuovo lo gestisce (passo Campi e blocco "Campi da posizionare"); il vecchio
  passo 4 no.
- Il vecchio passo 4 (flag spento) riscrive il blocco FORM e lascia il layout:
  i nomi rimossi sono ignorati dal renderer, quelli nuovi restano non posizionati.
- Un `required` su un campo non posizionato non blocca piu' il salvataggio
  (il campo e' fuori da `data_inputan`): per questo il wizard rifiuta un layout
  che lascia fuori un campo obbligatorio.
- Il campo che collega un sotto-modulo al padre e i campi `hidden` non si
  possono posizionare (sono sempre mantenuti e disegnati in coda).
- `form_detail` non ha il partial per campo: con layout il dettaglio resta una
  tabella ordinata, non una griglia.
- Il layout per telefono e' l'impilamento automatico per posizione: non e'
  configurabile separatamente.

## Rollback

Flag `module_generator.wizard_v2` a `false` per il wizard. Per un modulo gia'
risalvato col layout basta togliere dal controller il blocco
`# START FORM LAYOUT ... # END FORM LAYOUT`: il form torna piatto con tutti i
campi. Copie di sicurezza in `storage/app/module_generator_backups/`.

## Rollback

Flag `module_generator.wizard_v2` a `false` per il wizard; per un modulo gia'
risalvato col nuovo passo basta rimuovere dal controller il blocco
`# START FORM LAYOUT ... # END FORM LAYOUT` (il form torna a quello piatto con
tutti i campi). Copie di sicurezza in `storage/app/module_generator_backups/`.
