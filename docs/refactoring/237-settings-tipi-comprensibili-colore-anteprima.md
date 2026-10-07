# 237 - Settings: tipi comprensibili, colore, formato carta, anteprima

- **Data**: 2026-10-06
- **Stato**: Completato (verificato a vista in Application Setting; Login Register Style e "Nuovo Settings" da rivedere)
- **Area**: Frontend / Settings
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/setting.blade.php`
  - `resources/views/crudbooster/partials/ch_color.blade.php` (nuovo)
  - `app/Http/Controllers/System/SettingsController.php`
  - `database/migrations/2026_10_06_100000_settings_color_and_paper_size_types.php` (nuova)
  - `database/seeders/CmsSettingsSeeder.php`
  - `public/css/ch-components.css`, `resources/lang/{it,en}/crudbooster.php`

## Contesto

Dal prototipo: etichette comprensibili per "Type" in Nuovo Settings, colori con
pallini, "Default Paper Print Size" come select, anteprima in Application
Setting.

## Situazione prima

"Type" mostrava i valori tecnici (`upload_image`, `datepicker`…). I colori di
Login Register Style erano campi di testo ("Input hexacode"); la dimensione
carta un campo di testo libero; nessuna anteprima.

## Situazione dopo

- **Type**: opzioni "valore|Etichetta" tradotte (`setting_type_*`); il valore
  salvato è quello di sempre. Nuovo tipo `color`.
- **`select`** accetta `valore|Etichetta` nel campo "Radio / Select Data";
  senza `|` si comporta come prima.
- **Colore** (`ch_color`): pallini della palette, colore libero e codice
  esadecimale sincronizzati.
- **Migration**: `login_background_color`, `login_font_color`, `button_color`
  passano a `color` e `default_paper_size` a `select` (Letter, Legal, Ledger,
  A0-A8, B0-B10) **solo se il valore attuale è compatibile**; altrimenti restano
  `text`. Idempotente; il valore non cambia. Il seeder per le nuove installazioni
  è allineato.
- **Anteprima** in Application Setting (riconosciuto dalla presenza di `appname`
  e `logo`, perché il gruppo è salvato tradotto): scheda del browser con favicon
  e nome, sidebar con il logo. Si aggiorna scrivendo/scegliendo un file, senza salvare.

## Motivazione

Meno errori di inserimento (colori e formato carta) e impostazioni più leggibili
senza toccare i dati. Nessuna anteprima per il login: `login.blade.php` usa
sempre il pannello scuro del mockup e ignora questi colori, un'anteprima
sarebbe stata fuorviante.

## Test

Migration eseguita in locale; Application Setting verificata a vista (select
carta, anteprima). `php -l` e `view:cache` senza errori.
**Da provare**: salvataggio dei colori, Nuovo Settings (etichette dei tipi,
tipo Colore), Login Register Style.

## Rischi e note

- I tre colori restano `text` dove il valore non è un `#rrggbb` a 6 cifre.
- Un valore carta fuori elenco (es. `a4` minuscolo) resta `text`.

## Rollback

`php artisan migrate:rollback --step=1` (riporta i tipi a `text` e il testo di
aiuto della carta); poi ripristinare i file da git.
