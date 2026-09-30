# 128 - Reskin delle icone Font Awesome con Lucide (CSS mask, zero markup toccato)

- **Data**: 2026-09-28
- **Stato**: Completato e verificato in browser (fase 1 di 2, vedi Rischi)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `public/css/icons-lucide.css` (nuovo)
  - `public/vendor/lucide/icons/*.svg` (nuovo, 75 file)
  - `resources/views/crudbooster/admin_template.blade.php`
  - `resources/views/chat_ai/public.blade.php`
  - `resources/views/crudbooster/default/type_components/{datamodal,group_items_datamodal,group_members_datamodal,group_tenant_datamodal,item_access_datamodal,item_tenant_datamodal,tenant_group_datamodal,user_groups_datamodal}/browser.blade.php`

## Contesto

La sidebar (`sidebar.blade.php`) era già stata rifatta con icone SVG inline
in stile stroke-thin (Fase 0+1 del revamp UI/UX, [069](069-uiux-revamp-fase0-fase1-guscio-e-auth.md)).
Il resto dell'interfaccia (pulsanti azione CRUD, form, popup, icone di
modulo/menu salvate per cliente) è rimasto su Font Awesome 4, con sintassi
già deprecata in FA5 (`-o` come suffisso "outline") — visivamente in
contrasto netto con la sidebar. Richiesta esplicita dell'utente: portare
tutto allo stile della sidebar.

## Situazione prima

- 82 nomi icona Font Awesome distinti, ~124 file blade coinvolti (conteggio
  corretto dopo aver scoperto che il primo censimento ignorava le classi
  con apici singoli `class='fa fa-x'`).
- Font Awesome caricato in 10 punti (`admin_template.blade.php` — il
  layout condiviso da quasi ogni pagina admin — `chat_ai/public.blade.php`
  e 8 popup `datamodal/browser.blade.php` indipendenti).
- **Rischio individuato prima di toccare qualunque file**: in
  `admin_template.blade.php` il JS del collapse-sidebar fa
  `icon.removeClass('fa-plus').addClass('fa-minus')` (e viceversa) su un
  `<i class="fa fa-minus">` reale in `sidebar.blade.php` — una sostituzione
  del markup (es. da `<i class="fa fa-x">` a `<svg>`) avrebbe rotto quel
  toggle. Nota collaterale: verificato in browser che il collapse in sé
  non risponde al click già oggi, bug preesistente e noto, documentato in
  [069](069-uiux-revamp-fase0-fase1-guscio-e-auth.md) — non introdotto né
  peggiorato da questo intervento.

## Situazione dopo

**Nessun file blade con markup `<i class="fa fa-...">` è stato toccato.**
L'approccio scelto, per azzerare il rischio del punto sopra, è stato
un reskin via CSS invece di una sostituzione di markup:

- Per 82 classi `fa-*` effettivamente in uso, un file CSS
  (`public/css/icons-lucide.css`, generato) ridefinisce l'icona:
  `content: none !important` sullo pseudo-elemento `::before` (che prima
  disegnava il glifo del font) + `mask-image` sull'elemento stesso, puntando
  a un SVG Lucide vendorizzato in `public/vendor/lucide/icons/`
  (`background-color: currentColor` + CSS mask, quindi eredita colore/
  dimensione (`fa-lg`, `fa-2x`, ecc.) esattamente come il font originale).
- Le classi, gli `<i>`, il JS che le manipola (incluso il toggle
  fa-plus/fa-minus) restano identici — **nessun rischio di rompere
  interazioni esistenti**, perché nulla nel DOM/JS cambia.
- Le icone salvate per cliente (icona di modulo/menu in `cms_moduls`,
  letta dinamicamente) ereditano il nuovo stile automaticamente, senza
  alcuna migrazione dati: sono anch'esse semplici classi `fa fa-*`.
- fa-google esclusa di proposito (marchio, non fa parte di Lucide).
- I ~3 nomi non mappati (rari/inutilizzati, es. varianti dinamiche dentro
  commenti HTML morti in `sidebar.blade.php`) restano automaticamente sul
  font Font Awesome originale — nessuna icona sparisce.

Verificato in browser (Docker locale): sidebar (collapse icon, submenu
Tenant/Impostazioni), `Menu Management` (icone di modulo dinamiche da DB),
lista `Tenants` (riga intera di azioni: members/groups/detail/edit/delete),
`Log User Access` — tutte le icone mappate rendono correttamente in stile
Lucide, dimensione e colore coerenti col contesto (incl. righe evidenziate).

## Motivazione

Un reskin CSS invece di 124 modifiche blade:
- **Comportamento-preserving per costruzione**: zero rischio sulle
  interazioni JS esistenti che manipolano classi `fa-*` (nessuna trovata
  al di fuori del caso sidebar, ma il rischio esisteva per costruzione con
  un approccio a markup).
- **Nessuna migrazione dati**: le icone salvate per cliente (moduli, menu,
  widget) ereditano il nuovo stile senza toccare `cms_moduls`/config dei
  widget.
- Effetto immediato su tutta l'app in un solo intervento, invece di un
  lavoro spalmato su 124 file nel tempo.

Alternativa scartata: sostituire `<i class="fa fa-x">` con `<x-icon>`/
`<svg>` in ogni blade — scartata per il rischio di rompere il toggle JS
già individuato, e perché avrebbe richiesto toccare 124 file per lo stesso
risultato visivo ottenuto qui con 10.

## Test

- Lint implicito: le pagine sono state caricate in browser senza errori
  Blade/500 (Menu Management, Tenants, Log User Access, Dashboard).
- Verifica visiva in browser (Docker locale, utente Super Admin):
  sidebar, submenu Tenant/Impostazioni, tabella `default/table.blade.php`
  (icone azione + icone di modulo dinamiche), log utenti.
- Non eseguita la suite di test automatici (non richiesta esplicitamente,
  e comunque nessun test PHP copre l'aspetto visivo delle icone).
- Non verificato: pagine popup `datamodal` (bootstrap standalone, stesso
  meccanismo ma non aperte in questo giro), form di dettaglio/modifica con
  pulsante "Indietro" (`fa-chevron-circle-{{trans}}`, mappato ma non visto
  a schermo in questa sessione).

## Rischi e note

- **Icone non ancora Lucide**: i widget della nuova dashboard a griglia
  usano `<ion-icon>` (Ionicons v7, caricato da CDN esterno `unpkg.com`,
  non vendorizzato) — fuori scope di questo intervento, restano come
  debito tecnico noto (vedi backlog in `README.md`).
- **Font Ionicons v2 morto** (`vendor/crudbooster/ionic/`, caricato
  globalmente per un solo uso reale) — notato ma non rimosso in questo
  intervento.
- Font Awesome resta vendorizzato e caricato (non rimosso): i controller
  custom dei singoli clienti (fuori da questo repo, copiati manualmente ad
  ogni aggiornamento) possono referenziare icone FA con nomi non presenti
  in questa mappatura — restano visibili col font originale, nessuna
  rottura, ma non ancora "moderne". Rimozione di Font Awesome rimandata
  finché ogni cliente non ha verificato di non usare nomi non mappati.
- I ~3 nomi FA trovati ma non mappati sono tutti dentro commenti HTML
  morti (`<!-- ... -->`) in `sidebar.blade.php`, verificato con grep
  mirato — nessun impatto reale.

## Rollback

Rimuovere il tag `<link href="{{asset('css/icons-lucide.css')}}" ...>`
dai 10 file elencati sopra (o eliminare `public/css/icons-lucide.css`):
Font Awesome torna a disegnare i glifi originali via `::before`, nessun
altro file coinvolto.
