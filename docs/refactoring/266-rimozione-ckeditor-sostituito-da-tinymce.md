# 266 - Rimozione di CKEditor, sostituito da TinyMCE

- **Data**: 2026-10-07
- **Stato**: Completato (non committato; test manuale a cura dell'utente)
- **Area**: Form / Module generator
- **File/aree di codice coinvolte**:
  - rimossi `resources/views/crudbooster/default/type_components/ckeditor/` e `public/ckeditor5/` (224 file, ~24 MB)
  - `app/Http/Controllers/System/CBController.php` (`cbLoader`)
  - `app/Helpers/ModuleGeneratorFields.php` (`type()`, `SQL_BY_TYPE`)
  - `resources/views/crudbooster/module_generator/step2_v2.blade.php`, `step4_v2.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (voce `mg_field_types.ckeditor`)

## Contesto

Il wizard offriva due tipi "Testo formattato" quasi identici (CKEditor e
TinyMCE). Si vuole un solo editor: TinyMCE.

## Situazione prima

Il componente `ckeditor` era un abbozzo di prova: nessun `name` sul campo che
si salva (il valore scritto non arrivava al server), contenuto fisso "Hello
from CKEditor 5!" e un `alert` se la pagina era aperta da file. Gli asset
CKEditor 5 occupavano 224 file in `public/ckeditor5/`.

## Situazione dopo

- Il tipo `ckeditor` non esiste piu' (componente e asset eliminati) e non e'
  piu' proposto nel wizard (passo Campi, anteprima del passo Layout, lingua).
- Compatibilita': un modulo che dichiara ancora `"type" => "ckeditor"` viene
  trattato come `tinymce` in `CBController::cbLoader()` (form, dettaglio e
  salvataggio) e nel wizard (`ModuleGeneratorFields::type()`): al primo
  salvataggio del passo Campi la voce viene riscritta come `tinymce`.
- Colonna database invariata (LONGTEXT come prima).

## Motivazione

Un solo editor da mantenere; l'alias evita errori "view not found" sui moduli
dei clienti che avessero ancora il vecchio tipo (i controller custom si
copiano a mano in fase di aggiornamento).

## Test

`php -l` sui file PHP e di lingua; `view:cache` per Blade. Da provare a mano:
modulo con campo TinyMCE (crea/modifica/dettaglio); modulo di prova con un
campo `type => ckeditor` scritto a mano nel controller; passo Campi del wizard
su un modulo cosi' (deve mostrare "Testo formattato (TinyMCE)").

## Rischi e note

- Le cartelle sono state rimosse con `git rm` (quindi gia' in staging).
- Il campo ckeditor non salvava dati, quindi non c'e' perdita di contenuti;
  resta l'alias per evitare che le viste vadano in errore.
- Rimane il commento in `EmailTemplatesController` che cita `ckeditor`
  (codice commentato).
- Il tipo `wysiwyg` (Summernote) non e' stato toccato.

## Rollback

`git revert` del commit: ripristina componente e asset.
