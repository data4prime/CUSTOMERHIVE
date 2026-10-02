# 204 - Pulsanti di riga: lo stile scelto nel module generator viene rispettato

- **Data**: 2026-10-02
- **Stato**: Completato (da provare a mano nel browser)
- **Area**: Frontend / Module generator
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/components/action.blade.php`

## Contesto

Il passo Configurazione (step 5) del module generator permette di scegliere
lo stile dei pulsanti di ogni riga della lista: `button_icon`,
`button_icon_text`, `button_text`, `button_dropdown`. La scelta viene scritta
in `$this->button_action_style` e letta da `CBController::getIndex`, che la
passa a `components/action.blade.php`.

## Situazione prima

- **Dropdown**: il wizard salva `button_dropdown`, ma la vista controllava
  `dropdown`. Scegliendo "dropdown" nessun ramo corrispondeva e cadeva
  nell'`@else` (icone di default).
- **Solo testo** (`button_text`): il pulsante Modifica aveva il testo
  commentato e l'`<a>` non era chiuso; Elimina era un `<a>` vuoto (colore
  warning, senza testo).
- **Icona + testo** (`button_icon_text`): Modifica ed Elimina mostravano solo
  l'icona (la versione con testo era commentata), solo Dettaglio aveva il
  testo.
- **Dropdown (voci)**: Modifica ed Elimina avevano solo l'icona.

## Situazione dopo

- Il ramo dropdown risponde sia a `dropdown` sia a `button_dropdown`.
- `button_text`: tre pulsanti con solo testo (Dettaglio, Modifica, Elimina),
  `<a>` chiusi, Elimina `btn-danger` come negli altri stili.
- `button_icon_text`: icona + testo per tutti e tre.
- Dropdown: voci con icona + testo per tutte.
- `button_icon` (default) e `button_icon_strict` invariati.

## Motivazione

Chi sceglie uno stile nel wizard deve vederlo nella lista. Le versioni con
testo erano già nel file, commentate: sono state ripristinate invece di
inventare un markup nuovo.

## Test

- `view:clear` + render di `components.action` nel container con una route
  `GetIndex` finta e sessione superadmin, per tutti gli stili: l'icona sola
  non ha testo, `button_icon_text` e `button_text` mostrano le tre etichette,
  `button_dropdown` e `dropdown` producono `dropdown-menu` con tre voci.
- NON verificato nel browser, né con azioni aggiuntive (`addaction`) o
  `showIf` negli stili modificati, né con utenti non superadmin.

## Rischi e note

- Cambia l'aspetto visibile della lista per i moduli con `button_icon_text`,
  `button_text` o `button_dropdown` già salvati: finora mostravano icone senza
  testo o un markup rotto.
- Gli stili `button_icon`/default non rispettano i flag
  `button_detail`/`button_edit`/`button_delete` per il superadmin
  (usano `ModuleHelper::can_*`), scelta storica non toccata qui; gli stili con
  testo e il dropdown invece sì.
- Nessun testo nuovo: riuso `action_detail_data`, `action_edit_data`,
  `action_delete_data`, già in en e it.

## Rollback

Ripristinare `components/action.blade.php` dal git.
