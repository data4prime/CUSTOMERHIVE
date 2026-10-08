# 287 - Avatar: iniziali al posto dell'immagine di default

- **Data**: 2026-10-08
- **Stato**: Completato (non verificato a vista)
- **Area**: Frontend / Utenti
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/lockscreen.blade.php`
  - `resources/views/groups/members.blade.php`
  - `resources/views/crudbooster/home.blade.php`
  - `app/Helpers/UserHelper.php` (`latest_users`)
  - `app/Http/Controllers/System/CBController.php` (colonna immagine di `cms_users.photo`)
  - `resources/views/crudbooster/default/type_components/upload/component_detail.blade.php`

## Contesto

Header, log, profilo, lista utenti e membri tenant mostrano già le iniziali
(`LogsController::avatarHtml()`) quando l'utente non ha una foto. Il lockscreen
e altri punti mostravano ancora l'immagine di riserva (`user.png`, `admin.jpeg`,
`manager.jpeg`).

## Situazione prima

- Lockscreen: usava `Session::get('admin_photo')`, che al login è sempre valorizzata
  con l'immagine di riserva (`UserHelper::icon()`), quindi non si poteva sapere se
  esistesse una foto vera.
- Membri di un gruppo: `<img src="UserHelper::icon(id)">`.
- Home "Latest Members": `latest_users()` sostituiva la foto vuota con l'immagine di riserva.
- Lista e dettaglio di `cms_users.photo` generici (CBController / upload `component_detail`):
  immagine di riserva quando il valore è vuoto.

## Situazione dopo

Tutti questi punti usano `LogsController::avatarHtml(nome, foto_vera, id, size)`:
foto se caricata, altrimenti iniziali. Lockscreen: foto letta da `CRUDBooster::me()->photo`
(DB), non dalla sessione. `latest_users()` non sovrascrive più `photo`.
`UserHelper::icon()` e `User::photo()` restano invariati (usati ancora quando
la foto esiste).

## Motivazione

Coerenza visiva con il resto del pannello e con il nuovo look.

## Test

- Render del lockscreen con utente senza foto: avatar "MR" 72px. `php -l` su helper e controller.
- Non verificate a vista le altre viste (membri gruppo, home, dettaglio utente).

## Rischi e note

- `latest_users()` ora restituisce `photo` vuota se non caricata: l'unico chiamante è la home.
- `UserHelper::icon()` resta usato da `CRUDBooster::myPhoto()` e da `AdminController`
  (sessione `admin_photo`, ora non più letta dal lockscreen): lasciato per compatibilità
  con eventuali controller custom dei clienti.
- La classe CSS `.ch-lockscreen-avatar` in `theme.css` non è più usata.

## Rollback

Ripristinare i file elencati.
