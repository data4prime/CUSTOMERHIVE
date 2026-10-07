# 253 - Wizard moduli: allineamento passo 3, passo 5 e controllo generale dei bordi

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale; invio dei form non provato)
- **Area**: Frontend / Generatore moduli
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/step2_v2`, `step3_v2`, `step4_v2`, `step5.blade.php`
  - `app/Http/Controllers/System/ModulsController.php` (`getStep5`)

## Contesto

Seguito di 252. Segnalazioni: in `step3/38` "Righe per pagina" disallineato dagli altri due
campi; in `step5/38` "Campo titolo candidato" da rendere select ricercabile e interruttori da
mettere su una riga (Importa/Esporta, Dettaglio/Modifica/Elimina); bordi delle card diversi.

## Situazione prima

- Passo 3: il campo numerico era `form-control-sm` (32px) mentre i due menu, trasformati in
  select2 dal potenziamento globale, erano alti 44px.
- Passo 5: titolo candidato come campo di testo libero; un interruttore per riga; card
  interne con raggi diversi (`.3rem`, `.4rem`, `.5rem`), bordi da 2px nelle card dello stile
  pulsanti e `border-strong` nei blocchi del layout.

## Situazione dopo

- Passo 3: i tre campi (Righe per pagina / Ordina per / Direzione) hanno la stessa altezza
  standard e la stessa base.
- Passo 5: "Campo titolo candidato" è una select (stesso `name="title_field"`) con le colonne
  della tabella del modulo (`getStep5` passa `title_candidates` con `Schema::getColumnListing`;
  il valore attuale resta selezionabile anche se non è una colonna), ricercabile con
  `chSelect(..., {minimumResultsForSearch: 0})`. Interruttori: Aggiungi + Filtro, Importa + Esporta
  e Dettaglio + Modifica + Elimina su una riga (helper `$swRow`); nomi e valori inviati invariati.
- Bordi: `.fld-card`, `.fld-panel`, `.cfg-row`, `.cfg-pv-frame`, `.lay-blk`, `.lay-ph`,
  `.cfg-style-card` → `1px solid var(--ch-border)` e `var(--ch-radius-md)`; elementi piccoli
  (`.lay-fi`, `.lay-tb`, `.lay-pi`) → `var(--ch-radius-sm)`. La card dello stile pulsanti
  selezionata usa bordo accento + anello `--ch-accent-soft` al posto del bordo da 2px.

## Motivazione

Coerenza visiva tra i cinque passi e con il resto dell'app (token `--ch-*` al posto di rem fissi);
scegliere il titolo da un elenco evita nomi di colonna sbagliati.

## Test

A vista: passo 3 (tre campi allineati), passo 5 (select con `id`, interruttori su una riga, card
con bordi uniformi), passo 2. Trovato e risolto durante la prova: l'init di `chSelect` doveva
girare dopo `$('.select2').select2()` di `template.blade.php` (altrimenti il contenitore
select2 veniva ridotto a 1px e il campo sembrava vuoto; stesso problema già noto al passo 1).
Non provati: il salvataggio del passo 5, la ricerca nel menu con molte colonne, il passo 4
dopo i ritocchi ai bordi, il tema scuro.

## Rischi e note

Se la tabella del modulo non esiste ancora, l'elenco contiene solo il valore attuale. Le card
esterne dei passi (header con fondo `--ch-thead`) non sono state toccate.

## Rollback

Ripristinare le quattro viste e `getStep5` dalla versione precedente (git).
