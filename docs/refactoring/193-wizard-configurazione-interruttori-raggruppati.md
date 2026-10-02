# 193 - Module generator, passo Configurazione: interruttori raggruppati

- **Data**: 2026-10-01
- **Stato**: Completato (verificato in Docker, test manuale nel browser da fare)
- **Area**: Frontend / Module generator
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/step5.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`
    (nuove chiavi `mg_cfg_*`)

## Contesto

Prima fase della revisione del module generator concordata il 2026-10-01
(analisi dei 5 passi, prototipo HTML, piano di implementazione additivo).
Il piano completo prevede, in ordine: (1) passo Configurazione, (2) passo
Lista, (3) estrazione del partial `form_field.blade.php` dal loop di
`form_body.blade.php`, (4) passo Campi unificato, (5) layout form a blocchi
e schede, (6) dismissione del vecchio wizard solo quando tutti i clienti
sono stati aggiornati. Questa e' la fase 1: la meno rischiosa, perche' tocca
solo la vista e non il formato salvato nel controller del modulo.

## Situazione prima

`step5.blade.php` mostra 12 scelte binarie come coppie di radio TRUE/FALSE
disposte su tre colonne senza raggruppamento logico, piu' tre campi di testo
in alto (`title_field`, `limit`, `orderby`) e quattro radio per
`button_action_style`. Problemi: radio TRUE/FALSE per scelte on/off,
nessuna spiegazione di cosa faccia ciascuna voce (in particolare
`global_privilege`), tutti i testi hardcoded in inglese (violazione della
regola "ogni testo visibile in EN e IT"), blocco HTML commentato con dentro
espressioni Blade ancora valutate (`{{$cb_title_field}}` ecc.).

Il POST (`ModulsController::postStep5`) usa una whitelist di chiavi e
scrive nel blocco `# START CONFIGURATION` del controller generato; una
chiave assente dal POST non viene scritta e la proprieta' resta al valore di
default della classe base.

## Situazione dopo

`step5.blade.php` e' organizzato in:

- card **Lista** con `title_field`, `limit`, `orderby` (restano qui per ora:
  si spostano nel passo Lista nella fase 2, quando quel passo viene
  riscritto);
- tre card di interruttori (`form-switch`): **Sopra la lista** (Aggiungi,
  Filtro e ordinamento, Importa, Esporta), **Su ogni riga** (Colonna azioni,
  Dettaglio, Modifica, Elimina + scelta dell'aspetto dei pulsanti),
  **Selezione e permessi** (Azioni su piu' righe, Ignora i permessi per
  ruolo);
- lo stile dei pulsanti e' una scelta tra quattro schede con anteprima dei
  pulsanti reali (JS minimale, segue gli interruttori Dettaglio/Modifica/
  Elimina);
- ogni interruttore ha una descrizione breve; tutti i testi passano da
  `trans('crudbooster.mg_cfg_*')` con la stessa chiave in `en` e `it`
  (33 chiavi nuove); le etichette dei pulsanti dell'anteprima riusano le
  chiavi gia' esistenti `action_detail_data` / `action_edit_data` /
  `action_delete_data`, la stessa fonte dei pulsanti reali.

**Dato salvato invariato**: stessi nomi di campo del POST
(`button_add`, `button_edit`, `button_delete`, `button_detail`,
`button_filter`, `button_import`, `button_export`, `button_table_action`,
`button_bulk_action`, `button_action_style`, `global_privilege`,
`title_field`, `limit`, `orderby`), stessi valori (`'true'`/`'false'` per gli
interruttori, `button_icon` ecc. per lo stile), nessuna modifica a
`postStep5()` ne' al blocco `# START CONFIGURATION` del controller generato.
Ogni interruttore invia un campo nascosto `false` prima della checkbox
`true`: senza, una checkbox non spuntata non invierebbe nulla e la chiave
non verrebbe scritta (la proprieta' resterebbe al default della classe
base, che puo' essere `true`).

Differenze lievi rispetto a prima, tutte senza effetto sul dato:

- le variabili `$cb_*` mancanti non generano piu' un errore "undefined
  variable" (si usano `empty()` / `?? ''`);
- rimosso il blocco HTML commentato con espressioni Blade ancora valutate;
- "Limit Data" ora si chiama "Righe per pagina" in interfaccia (stessa chiave
  `limit`).

## Motivazione

Rendere la configurazione leggibile (voci raggruppate per area, interruttori
al posto delle radio, anteprima dello stile dei pulsanti, descrizioni) senza
cambiare nulla del dato salvato.

## Test

Eseguiti nel container Docker locale, senza lanciare la suite di test:

- `php -l` sui due file di lingua: nessun errore di sintassi;
- la vista e' stata compilata e renderizzata con dati di prova (senza il
  layout dell'admin) sia in italiano sia in inglese: nessuna chiave
  `mg_cfg_*` grezza nell'HTML; per ciascuna delle 10 chiavi on/off sono
  presenti il campo nascosto `false` e la checkbox `true`, con lo stato
  `checked` corretto; `orderby` da array viene riportato come
  `id,desc;name,asc` come prima; lo stile selezionato risulta `checked`.

**Non verificato**: il rendering nel browser (aspetto delle card, anteprima
dei pulsanti, evidenziazione della scheda scelta) e un salvataggio reale dal
form; i test esistenti su `postStep5` non sono stati rilanciati (la logica
del POST non e' cambiata).

## Rischi e note

- Il passo non ha ancora il flag del nuovo wizard: e' un'interfaccia nuova
  per tutti gli utenti del module generator (solo superadmin). Il dato
  salvato e' identico, quindi puo' coesistere con moduli creati col vecchio
  passo.
- `global_privilege`: la descrizione ("salta il controllo permessi del
  modulo") e' dedotta da `CBController`, dove il rifiuto d'accesso scatta
  solo se `$this->global_privilege == false`.
- `step5.blade copy.php` (copia di vecchia vista) non e' stata toccata.

## Rollback

Ripristinare `step5.blade.php` e rimuovere le chiavi `mg_cfg_*` dai due file
di lingua (nessun altro file e nessun dato toccati).
