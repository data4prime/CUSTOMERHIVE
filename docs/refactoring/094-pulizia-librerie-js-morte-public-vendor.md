# 094 - Pulizia librerie JS/CSS morte vendorizzate a mano in public/vendor

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Housekeeping
- **File/aree di codice coinvolte**:
  - `public/vendor/crudbooster/assets/edit_area/` (rimossa)
  - `public/vendor/crudbooster/assets/fancy/` (rimossa)
  - `public/vendor/crudbooster/assets/datetimepicker-master/` (rimossa)
  - `public/vendor/crudbooster/assets/bootstrap-datetimepicker/` (rimossa)
  - `public/vendor/crudbooster/assets/bootstrap/` (rimossa)
  - `public/vendor/crudbooster/assets/unslider/` (rimossa)
  - `public/vendor/crudbooster/assets/css/{animate,form-elements,media-queries,style}.css` (rimossi)
  - `public/vendor/crudbooster/assets/js/dateformat.js` (rimosso)
  - `public/vendor/crudbooster/assets/fonts/` (rimossa, font DroidNaskh)
  - `public/vendor/crudbooster/assets/bg_blur{1-5}.jpg` (rimossi)
  - `public/vendor/crudbooster/assets/jquery.numberformatter-1.2.4.min.js` (rimosso)
  - `public/vendor/crudbooster/laravel-filemanager/` (rimossa, duplicato obsoleto)

## Contesto

Punto cieco segnalato in [082](082-rimossa-build-gulp-e-package-json.md): le
librerie JS/CSS copiate a mano in `public/vendor/` non hanno un manifest,
quindi Dependabot non le vede — serviva un censimento dedicato per capire
cosa è davvero servito dall'app e cosa è morto.

## Situazione prima

`public/vendor/crudbooster/` (16 MB) conteneva, oltre alla cartella
`assets/adminlte/` (11 MB, il template AdminLTE realmente usato in blocco),
una dozzina di librerie/file indipendenti mai verificati singolarmente.

## Situazione dopo

Incrociato ogni cartella/file con tutti i riferimenti nel repo tracciato
(`asset()`, path letterali in blade/JS, incluso il contenuto interno di
`main.css`/`main.js` per gli `@import`/riferimenti dinamici) usando
`tokensave_search` in modalità letterale. Risultato:

**Rimosso (zero riferimenti, ~2.5 MB)**:
- `edit_area/` (617K) — code editor standalone, mai incluso in nessuna view.
- `fancy/` (353K) — fancybox vendorizzato, mai caricato. La classe CSS
  `fancybox` compare una volta in
  `resources/views/crudbooster/default/type_components/child/component_detail.blade.php`
  ma senza il relativo script/CSS mai incluso da nessuna parte — la feature
  era già incompleta/rotta indipendentemente da questa pulizia, non toccata
  oltre (fuori scope).
- `datetimepicker-master/` (573K) e `bootstrap-datetimepicker/` (40K) — il
  date/datetime picker realmente usato dall'app è `daterangepicker` (vedi
  `assets/js/main.js`, applicato sia a `.datepicker` che a
  `.datetimepicker`), non queste due librerie.
- `bootstrap/` (316K) — copia standalone di Bootstrap mai referenziata
  (AdminLTE include già la propria copia di Bootstrap).
- `unslider/` (13K).
- `css/animate.css`, `css/form-elements.css`, `css/media-queries.css`,
  `css/style.css` (84K) — non importati dall'unico CSS realmente caricato in
  quella cartella (`css/main.css`, 10 righe, verificato per intero).
- `js/dateformat.js` (8K).
- `fonts/DroidNaskh-*` (228K) — nessun `@font-face` che li referenzi.
- `bg_blur1.jpg`...`bg_blur5.jpg` (196K).
- `jquery.numberformatter-1.2.4.min.js` (8K).
- `laravel-filemanager/` dentro `crudbooster/` (49K) — copia duplicata e
  obsoleta (contenuto diverso, verificato con `diff`) rispetto a quella
  realmente servita e pubblicata da composer, `public/vendor/laravel-filemanager/`
  (1.1 MB, non toccata).

**Confermato usato, non toccato**: `assets/adminlte/` (11 MB, il grosso del
peso — referenziato in blocco da `admin_template_plugins.blade.php`),
`select2`, `summernote`, `sweetalert`, `lightbox`, `jsoneditor`, `ionic`,
`css/main.css`, `js/main.js` (referenzia dinamicamente `sound/bell_ring.{ogg,mp3}`,
non trovato da una ricerca statica su `asset()`), `rtl.css`,
`logo_crudbooster.png`, `jquery-sortable-min.js`.

`public/vendor/crudbooster/` passa da 16 MB a 14 MB.

## Motivazione

Riduce la superficie di codice vendorizzato morto (coerente con
[036](036-rimozione-assets-legacy.md)/[085](085-pulizia-file-morti-e-branch-obsoleti.md))
e chiude il punto cieco Dependabot segnalato in 082 per la parte che poteva
essere eliminata subito. `assets/adminlte/plugins/` (l'altro 11 MB) resta
fuori scope: servirebbe un audit dedicato dei singoli plugin interni,
lavoro molto più ampio.

## Test

- `tokensave_search` letterale su ogni cartella/file candidato, zero
  riferimenti trovati nel repo tracciato (incluse le view condivise
  `type_components` che i moduli custom dei clienti riusano per i campi
  standard — i controller custom dei clienti sono gitignored ma non hanno
  viste proprie per questi campi).
- Verificato che `css/main.css` (unico CSS di quella cartella realmente
  caricato) non importi nessuno dei CSS rimossi.
- Ambiente locale dopo la rimozione: `/admin/login` → 200,
  `/vendor/crudbooster/assets/css/main.css` → 200,
  `/vendor/crudbooster/assets/select2/dist/css/select2.min.css` → 200
  (verifica che gli asset ancora in uso restino serviti correttamente).

## Rischi e note

- Non verificabile al 100%: un modulo custom di un cliente specifico
  (fuori da questo repo, copiato manualmente in fase di aggiornamento)
  potrebbe in teoria avere una view non standard che referenzia uno di
  questi path direttamente. Rischio ritenuto basso perché i moduli custom
  generati da interfaccia riusano gli stessi `type_components` condivisi
  già verificati qui, non hanno viste proprie per i campi standard.
- `assets/adminlte/plugins/` (11 MB) non è stato oggetto di audit interno:
  possibile pulizia futura separata, più ampia.
- La feature "fancybox" già incompleta (classe CSS orfana in
  `child/component_detail.blade.php`) non è stata corretta né rimossa qui,
  resta com'era prima di questo intervento.

## Rollback

`git revert` del commit — ripristina tutti i file rimossi, nessun impatto
su `assets/adminlte/` o sul resto dell'app.
