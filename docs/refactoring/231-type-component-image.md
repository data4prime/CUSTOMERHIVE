# 231 - Nuovo type_component `image` (foto profilo, logo...) e uso in Utenti

- **Data**: 2026-10-05
- **Stato**: Completato (non committato, da provare a vista)
- **Area**: Frontend / type_components
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/image/` (nuovo: `component`, `component_detail`, `asset`, `info.json`)
  - `app/Http/Controllers/System/CBController.php` (validazione e salvataggio)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (campo Photo)
  - `public/css/ch-components.css`, `resources/lang/{en,it}/crudbooster.php` (`image_change`)

## Contesto

Nel profilo la foto è un input file scritto a mano con avatar tondo; nel form
utenti era il type `upload` (riquadro file generico). Serviva un componente
unico per i casi "immagine con anteprima" (foto profilo, logo, ecc.).

## Situazione prima

Solo `upload`: scheda file con miniatura 48px, pulsante "Scegli file" e link
elimina; nessuna anteprima della nuova scelta.

## Situazione dopo

Nuovo type `image`: anteprima grande (tonda o quadrata) con "Scegli/Cambia
immagine", "Elimina" (stesso `delete-image` di `upload`) e anteprima immediata
della nuova scelta. Opzioni: `shape` (`circle` default | `square`), `size`
(48–240 px, default 96), `icon` (segnaposto Bootstrap Icons), `accept`,
`resize_width/height`, `validation`, `help`. Dettaglio: stessa anteprima, con
lightbox e immagine di riserva solo per `cms_users.photo`.
Salvataggio identico a `upload`: in `CBController` `image` è trattato come
`upload` nei quattro punti che lo distinguevano (required solo se manca il
file in modifica, niente NULL su vuoto, `uploadFile` con resize, `_nome` di
riserva). Stile: classi `.ch-image*` in `ch-components.css` con i token `--ch-*`.
In Utenti il campo Photo ora è `image` (tondo, icona persona), stessa validazione
e stesso resize 90×90.

**Estensione (stesso giorno)**
- Il disegno è nel partial `crudbooster/partials/ch_image.blade.php`, condiviso dal
  componente e dal **profilo personale** (`users/profile.blade.php`: tolti avatar con
  iniziale, input nascosto e relativo JS e CSS; il salvataggio AJAX di
  `postProfileGeneral` è invariato, aggiorna l'anteprima con `photo_url`).
- **Utenti**: senza foto caricata (form e dettaglio) il campo mostra l'avatar di
  default per ruolo (`UserHelper::icon()`: admin, manager, utente; generico in
  creazione), come già nel profilo; "Elimina" compare solo se c'è un file vero.
- **Tenants**: Logo, Favicon e Background Image ora sono `image` (quadrata, con
  icona di riserva); le anteprime quadrate mostrano l'immagine intera
  (`object-fit: contain`).
- **Wizard v2** (`ModuleGeneratorFields`, `step2_v2`): tipo `image` nel gruppo
  "file" con scelta della forma (tonda/quadrata) e dimensione dell'anteprima
  (scritte come `shape`/`size` solo se diverse dal default), regole = solo
  dimensione massima in MB (la regola `image` è sempre scritta). Colonna nuova
  VARCHAR(255) come `upload`. `isFileType()` raggruppa `upload` e `image` per le
  regole di validazione. Chiavi lingua `mg_fld_img_*` e `mg_field_types.image`.

## Motivazione

Un solo componente riusabile invece di duplicare markup/JS per ogni foto o logo.
Alternativa scartata: aggiungere opzioni a `upload` (avrebbe cambiato un
componente usato da molti moduli dei clienti).

## Test

`php -l`, `php artisan view:cache`. NON verificato a vista: da provare add/edit
utente (scelta file, anteprima, salvataggio, elimina, campo vuoto), dettaglio,
foto non più su disco. Suite di test non lanciata.

## Rischi e note

- Altri moduli dei clienti restano su `upload` finché non vengono cambiati.
- Il wizard non offre `icon`/`accept`/resize del tipo `image`: se presenti in un
  campo esistente vengono conservati, ma non sono modificabili dall'interfaccia.
- Verificato solo `ModuleGeneratorFields::build/parseOpts/parseValidation` con una
  prova mirata; l'interfaccia dello step Campi non è stata provata a vista.

## Rollback

Rimettere `"type" => "upload"` sul campo Photo di `AdminCmsUsersController`; il
resto è additivo (cartella `image/` e CSS inutilizzati).
