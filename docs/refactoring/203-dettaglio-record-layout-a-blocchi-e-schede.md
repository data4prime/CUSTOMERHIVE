# 203 - Dettaglio record con layout a blocchi e schede

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend / Module generator
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form_detail.blade.php`
  - `resources/views/crudbooster/default/form_detail_row.blade.php` (nuovo)
  - `resources/views/crudbooster/default/form_detail_layout.blade.php` (nuovo)
  - `resources/views/crudbooster/default/form_detail_layout_rows.blade.php` (nuovo)

## Contesto

Il layout a blocchi e schede scelto nel passo Form del module generator (197)
era disegnato solo in add/edit (`form_layout.blade.php`). Il dettaglio di un
record seguiva solo l'ordine dei campi, in un'unica tabella piatta con righe
di intestazione per blocco/scheda (`ModuleGeneratorLayout::detailOrder`).

## Situazione prima

`form_detail` con layout attivo: una sola tabella, campi nell'ordine del
layout, titoli di blocco/scheda come righe `<th>`. Posizione e larghezza dei
blocchi e schede navigabili non venivano riprodotte.

## Situazione dopo

Con un blocco `FORM LAYOUT` nel controller, il dettaglio usa la stessa
griglia da 12 colonne del form: ogni blocco è una card con titolo e una
tabella etichetta/valore; i blocchi "schede" hanno le tab Bootstrap. Le
colonne di sistema (`sys`) sono righe in sola lettura. I campi non
posizionati rimasti (tenant/group, hidden) sono in una card in coda, come
prima in coda alla tabella.

Senza layout il dettaglio è invariato (`#table-detail`, stesso markup).

Il corpo del vecchio `foreach` (callback, join, componente per tipo) è
estratto in `form_detail_row.blade.php` e usato sia dal ramo piatto sia da
quello a blocchi, senza duplicare la logica. Non cambiano né i componenti
`component_detail` né i dati passati alla vista.

## Motivazione

Coerenza tra form e dettaglio: chi configura il layout lo vede anche quando
consulta il record. Alternativa scartata: riusare `form_layout.blade.php`
con campi disabilitati, che avrebbe cambiato il markup dei valori in sola
lettura (file, immagini, join).

## Test

- `view:clear` + render di prova nel container (script in `.scratch/`) di
  `form_detail` con un layout (blocco + schede + campo di sistema + campo
  non posizionato) e senza layout: tre card, due tab-pane, riga
  "Data creazione" formattata, campo non posizionato in coda; ramo piatto
  con `#table-detail` e righe come prima.
- NON verificato nel browser, né con tipi di campo particolari (file,
  immagine, join, callback) né con sotto-moduli.

## Rischi e note

- Le larghezze dei singoli campi (`w`) si applicano anche nel dettaglio: ogni
  campo è una mini tabella etichetta/valore dentro un contenitore flex con
  la stessa formula di larghezza del form (`.cbd-field`), con l'etichetta
  sopra il valore (CSS che porta `table/tr/td` a `display: block`). Prima
  versione, scartata: una riga di tabella per campo, che mostrava i campi
  `w: 3` uno sotto l'altro mentre nel form stanno affiancati.
- Cambia l'aspetto visibile del dettaglio per i moduli con layout attivo
  (card/schede invece di una tabella unica).
- `ModuleGeneratorLayout::detailOrder` non è più usato dalla vista; lasciato
  nell'helper.
- La logica ora vive in un partial incluso (`form_detail_row`): i
  `component_detail` ereditano le variabili (`$value`, `$name`, `$form`,
  `$row`, `$table`) come prima. Un override utente in
  `views/vendor/crudbooster` di `form_detail` non riceve la modifica.

## Rollback

Ripristinare `form_detail.blade.php` dal git e rimuovere i tre partial
nuovi.
