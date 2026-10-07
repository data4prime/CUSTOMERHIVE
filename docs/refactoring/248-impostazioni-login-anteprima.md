# 248 - Impostazioni "Login Register Style": anteprima della pagina di login

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Impostazioni
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/setting.blade.php`
  - nuovo `resources/views/crudbooster/partials/login_setting_preview.blade.php`
  - `public/css/ch-components.css` (`.ch-setgrid`, `.ch-lprev*`)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Nel mockup "Confronto UI CustomerHive" la pagina `admin/settings/show?group=Login+Register+Style`
ha, accanto al form, una card "Anteprima login". Nella pagina reale mancava (c'era solo
l'anteprima di "Application Setting", intervento 237, e quella del login per i tenant, 232).

## Situazione prima

Solo il form con i quattro campi (`login_background_color`, `login_font_color`,
`login_background_image`, `button_color`), in una colonna da 750px.

## Situazione dopo

- Per il gruppo di impostazioni che contiene `login_background_color` e `button_color`
  (riconosciuto dai campi: il nome del gruppo varia con la lingua) la pagina è a due
  colonne: form a sinistra, card "Anteprima login" a destra (sotto i 992px si impila).
- L'anteprima mostra sfondo, testo, pulsante "Accedi" e, se impostata, l'immagine di
  sfondo; si aggiorna mentre si scelgono i colori o si seleziona un file, senza salvare.
- Nuove chiavi en+it: `setting_login_preview_sub`, `_btn`, `_help`. Il titolo riusa
  `adm_login_preview`.
- Salvataggio, nomi dei campi e altri gruppi di impostazioni invariati.

## Motivazione

Allineamento al mockup e feedback immediato sul risultato dei colori scelti.

## Test

A vista sul gruppo Login: anteprima presente; cliccando un pallino dello sfondo l'anteprima
cambia colore (non salvato). Non verificati: anteprima con immagine di sfondo caricata,
selezione di un file, tema scuro, gli altri gruppi (non devono cambiare).

## Rischi e note

L'anteprima è indicativa (la pagina di login reale può differire nei dettagli). Il nome
mostrato è `appname` o "CustomerHive". Le impostazioni dei tenant hanno una propria anteprima.

## Rollback

Rimuovere il partial, i blocchi `@if($isLoginGroup)` in `setting.blade.php`, il CSS
`.ch-setgrid`/`.ch-lprev*` e le tre chiavi lingua.
