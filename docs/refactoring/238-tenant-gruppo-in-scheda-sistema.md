# 238 - Tenant e gruppo nella scheda "Sistema"

- **Data**: 2026-10-06
- **Stato**: Completato (verificato a vista su Contratti, modifica; form piatti e dettaglio da provare)
- **Area**: Frontend / Form
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form_layout.blade.php` (form a schede e blocchi)
  - `resources/views/crudbooster/default/form_body.blade.php` (form piatto)
  - `resources/views/crudbooster/default/form_detail_layout.blade.php`, `form_detail.blade.php` (dettaglio)
  - `app/Helpers/ModuleGeneratorLayout.php` (`systemTabIndex()`)
  - `resources/lang/{it,en}/crudbooster.php` (`form_tab_general`, `form_tab_system`)

## Contesto

I campi `tenant` e `group` (e `primary_group`) erano in un riquadro collassabile
"Informazioni di sistema" in fondo ai form. Richiesta dell'utente: metterli in
una scheda "Sistema", riusando quella del modulo se esiste già.

## Situazione prima

- Form a layout: box collassabile in fondo (`form_layout`), tranne dove il
  layout posiziona questi campi (scheda "Sistema" degli utenti).
- Form piatto: lo stesso box, aperto da `form_field` sul campo `tenant` e chiuso
  su `group`/`primary_group` (accoppiamento fragile: vedi 103).
- Dettaglio: tenant/gruppo in coda alla tabella o in una card a fondo pagina.

## Situazione dopo

- **Form a layout**: se il layout ha una scheda il cui titolo è "Sistema" o
  "System" (senza distinzione di maiuscole), tenant/gruppo vanno lì, in una card
  sotto i blocchi esistenti; altrimenti si aggiunge in coda la scheda "Sistema".
  Resta valido il caso di tenant/gruppo posizionati esplicitamente dal layout
  (utenti): nulla cambia.
- **Form piatto**: se il modulo ha almeno uno di questi campi visibili, il form
  si divide in schede "Generale" + "Sistema"; senza, resta senza schede. Lo
  stesso script che apre la scheda del primo campo non valido (già nel form a
  layout) è presente anche qui.
- **Dettaglio**: stessa logica (scheda "Sistema" aggiunta o riusata; nel dettaglio
  piatto "Generale" + "Sistema").
- Tenant/gruppo sono sempre gli stessi campi (`add_default_form_fields`, logica
  per ruolo invariata): cambia solo dove vengono disegnati. I campi `hidden`
  duplicati restano dove erano.

## Motivazione

Il riquadro in fondo era poco scopribile e, nei form piatti, dipendeva dall'ordine dei campi. Una
scheda dedicata è coerente con il nuovo linguaggio visivo e con la scheda
"Sistema" che alcuni moduli hanno già.

## Test

`php -l`, `view:cache` senza errori. Verificato a vista: Contratti (modifica) ha
la scheda "Sistema" con i campi di sistema in sola lettura e, sotto, Tenant e
Group. **Da provare**: un modulo senza layout (form piatto) con tenant/gruppo,
nuovo record (tenant/gruppo obbligatori nella scheda non attiva → si apre la
scheda), dettaglio piatto e a layout, utente con ruolo tenant admin.

## Rischi e note

- Non toccato `mass_edit/form_body.blade.php` (modifica di massa): ha ancora il
  proprio riquadro, senza schede.
- `form_field.blade.php` conserva il vecchio box come ripiego per chi lo include
  senza `no_system_box` (nessun chiamante noto oltre `mass_edit`).
- Il menu management ha campi propri chiamati `tenant`/`group` ma usa la sua
  vista, quindi non è coinvolto.

## Rollback

Ripristinare da git i quattro file `default/*.blade.php`; `systemTabIndex()` e le
chiavi lang possono restare.
