# 250 - Module Generator: dettaglio modulo come nel mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Generatore moduli
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php` (nuovo `getDetail`)
  - nuovo `resources/views/crudbooster/module_generator/detail.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

`admin/module_generator/detail/{id}` usava il dettaglio generico (`CBController::getDetail`
+ `default.form`): cinque righe con etichette inglesi fisse e l'icona mostrata come testo
(`fa fa-music`). Nel mockup "Confronto UI CustomerHive" il dettaglio mostra l'icona vera e
la sezione "Enable/Disable Module on Tenants" in sola lettura.

## Situazione prima

Righe Name / Table Name / Icon / Path / Controller (etichette scritte nel form del
controller, non tradotte), icona come nome di classe, nessuna informazione sui tenant.

## Situazione dopo

- `ModulsController::getDetail` con gli stessi controlli di accesso del dettaglio standard
  (`ModuleHelper::can_view`, log del tentativo, redirect) e vista dedicata.
- Righe Nome / Nome tabella / Icona / Percorso / Controller con etichette tradotte; icona
  disegnata (`IconMap::toBi`) con la classe salvata in grigio accanto.
- Matrice di sola lettura "Attiva/disattiva il modulo sui tenant": una colonna per tenant,
  pillola verde (attivo) o grigia (non attivo), stesso dato della pagina di modifica
  (`ModuleHelper::is_enabled`).
- Pulsante "Modifica Dati" (solo superadmin) e link di ritorno alla lista.
- Nuove chiavi en+it `mg_detail_*`, `mg_enable_on_tenants`, `mg_enabled`, `mg_disabled`.
- Invariati: lista, modifica, wizard, salvataggi, permessi.

## Motivazione

Allineamento al mockup; chi guarda un modulo vede subito su quali tenant è attivo.

## Test

A vista su `module_generator/detail/38` (Aziende): righe, icona, matrice (tutti non attivi:
`module_tenants` è vuota in locale) e pulsante. `php -l`. Non verificati: un modulo attivo
su qualche tenant (pillole verdi), utente non superadmin, id inesistente.

## Rischi e note

La pagina di modifica (`module_generator/edit`) ha ancora il vecchio stile (matrice con
checkbox, form generico): da allineare a parte. Il dettaglio dei moduli non appare nei
pulsanti della lista (`button_detail = false`) ma l'URL diretto funziona come prima.

## Rollback

Rimuovere `getDetail` da `ModulsController`, la vista `detail.blade.php` e le chiavi lingua.
