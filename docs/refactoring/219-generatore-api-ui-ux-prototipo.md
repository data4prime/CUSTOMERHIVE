# 219 - Generatore API: nuova UI/UX (prototipo nelle viste reali)

- **Data**: 2026-10-05
- **Stato**: In corso (prototipo da provare a mano, non committato)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `public/css/ch-api.css` (nuovo, solo token `--ch-*`)
  - `resources/views/crudbooster/partials/api_nav.blade.php` (nuovo, schede comuni)
  - `resources/views/crudbooster/api_documentation.blade.php`
  - `resources/views/crudbooster/api_documentation_public.blade.php`
  - `resources/views/crudbooster/api_key.blade.php`
  - `resources/views/crudbooster/api_generator.blade.php`
  - `resources/views/api_tokens/add.blade.php`, `reveal.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `api_tab_*`, `api_action_*`, `api_doc_*`, `api_keys_*`, `api_ed_*`, `api_tokens_*`)

## Contesto

Rifare l'UI/UX della parte "Generatore API" partendo da sei mockup
concordati. Il prototipo vive nelle viste vere (con `admin_template` e sidebar),
senza toccare controller, rotte, nomi dei campi del form né il DB.

## Situazione prima

- Documentazione admin: due blocchi di testo grezzo ("How To Use"), tabella con
  solo nome e icone, dettaglio con tabelle annidate in tabelle, eliminazione
  con `swal`.
- Chiavi: chiave in chiaro per intero, "Non Active" come etichetta del pulsante,
  aggiunta riga via JS, `swal`.
- Editor: pagina unica di 890 righe, etichette inglesi fisse, nessuna anteprima.
- Doc pubblica: stesse tabelle annidate; `$ac->nama` nel `title` mai definita.
- Il menu a schede era ripetuto a mano in ogni vista con URL fissi.

## Situazione dopo

- `partials/api_nav`: schede Endpoint / Chiavi segrete / Nuovo endpoint con
  `CRUDBooster::mainpath()`.
- Documentazione: card "Come collegarsi" (Bearer `api2` / chiave segreta `api`,
  URL base copiabile, esempio), elenco endpoint con badge del metodo, ricerca e
  filtro per azione, dettaglio espandibile (parametri + risposta di esempio),
  conferma di eliminazione nella pagina.
- Chiavi: chiave mascherata con mostra/copia, interruttore attiva/disattiva,
  conferma inline, stato vuoto. Nessuna colonna nuova in `cms_apikey`
  (nessun "ultimo uso").
- Editor: quattro sezioni (cosa espone / riceve / restituisce / descrizione),
  metodo come gruppo di scelte, anteprima fissa che si aggiorna (URL, query
  string dei parametri attivi, JSON di esempio dai campi attivi), barra di
  salvataggio sempre visibile. **Il JS di caricamento/aggiunta righe è invariato**:
  stessi id (`#combo_tabel`, `#tipe_action`, `#table-parameters`,
  `#table-response`), stesso ordine delle colonne, stessi `name` dei campi.
- Doc pubblica: indice a sinistra raggruppato per tabella, dettaglio a destra
  (un endpoint alla volta, ancora `#permalink`), esempio cURL con Bearer.
- Token: scadenza come gruppo di scelte con data calcolata, pagina del token
  con esempio d'uso.
- Tutti i testi via `trans('crudbooster.*')` in en e it; JS con stringhe passate
  da Blade.

## Motivazione

Coerenza con lo standard UI (Bootstrap 5.3 + token `--ch-*`), meno codice
duplicato, flussi più chiari (dove si crea, cosa si copia, cosa succede
eliminando).

## Test

- `php -l` sui file di lingua; `view:cache` compila tutte le viste senza errori.
- NON verificato a vista né con una sessione reale: da provare a mano le sei
  pagine (creare/modificare/eliminare un endpoint, generare/disattivare/eliminare
  una chiave, creare un token, aprire la doc pubblica).

## Rischi e note

- Cambia comportamento visibile (non la logica): eliminazioni con conferma
  inline invece di `swal`; la generazione di una chiave ricarica la pagina.
- La doc pubblica mostra un endpoint alla volta (prima accordion); gli esempi
  usano `api2`.
- I valori "SI/NO" delle select dell'editor ora sono tradotti; i `value`
  restano `1`/`0`.
- `api_tabs.blade.php` è codice morto già prima: non toccato.
- Le righe generate dal JS (`success`, `info`) usano ancora classi legacy
  senza stile; cosmetico.

## Rollback

Ripristinare le viste elencate da git (`git checkout -- <file>`) e rimuovere
`ch-api.css` e `partials/api_nav.blade.php`; le chiavi di lingua aggiunte sono
innocue.
