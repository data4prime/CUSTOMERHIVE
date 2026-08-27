# 008 - PrivilegesController: creare un nuovo privilegio non deve cambiare il tema di chi lo crea

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/PrivilegesController.php` (`postAddSave()`)

## Contesto

Emerso testando manualmente [006](006-controller-sistema-app-http-controllers-system.md):
su `/admin/privileges/add`, scegliendo un "Theme Color" per il nuovo
privilegio, il tema dell'interfaccia dell'utente **che sta creando** il
privilegio (un superadmin) cambiava — comportamento inatteso, non legato
allo spostamento dei controller (stesso bug presente identico prima, nel
file originale in `packages/`).

## Situazione prima

`postAddSave()` impostava incondizionatamente il colore tema della
sessione corrente uguale al Theme Color del privilegio appena creato:

```php
//set theme
Session::put('theme_color', $this->arr['theme_color']);
```

A confronto, `postEditSave()` fa la stessa cosa ma correttamente solo
quando l'utente sta modificando **il proprio** privilegio:

```php
if ($id == CRUDBooster::myPrivilegeId()) {
    ...
    Session::put('theme_color', $this->arr['theme_color']);
}
```

In `postAddSave()` il controllo mancava — probabile copia-incolla dalla
modifica senza adattare la condizione. Per un record nuovo non avrebbe
comunque avuto senso: l'id del privilegio di chi crea esiste già, non può
mai coincidere con quello appena inserito.

## Situazione dopo

Rimossa la riga da `postAddSave()`. Creare un privilegio con un
Theme Color non tocca più la sessione di chi lo sta creando.

**Aggiunta durante la verifica**: l'utente ha segnalato che il colore
cambiava ancora *durante* la compilazione del form (non solo dopo il
salvataggio) — causa diversa, puramente lato client:
`packages/crocodicstudio/crudbooster/src/views/privileges.blade.php` aveva
un listener JS che, al cambio della select `theme_color`, applicava subito
la classe scelta al `<body>` della pagina corrente (anteprima live, mai
salvata, sparisce ricaricando). Rimosso su richiesta esplicita
dell'utente (non era l'anteprima voluta).

**Seconda aggiunta**: su `/admin/privileges/detail/{id}` il campo
"Privilege" risultava vuoto. Causa: `superprivilege` (il campo del form
per scegliere Standard/Tenantadmin/Superadmin) non è una colonna reale di
`cms_privileges` — la pagina di dettaglio generica
(`CBController::getDetail()`/`form_detail.blade.php`, mai sovrascritta in
`PrivilegesController`) legge `$row->superprivilege` che non esiste.
Aggiunto un `callback_php` al campo (stessa idea già usata per il badge
nella colonna "Privilege" della lista, qui in testo semplice perché il
componente di dettaglio esegue l'escape HTML di `$value`). Tocca solo la
pagina di dettaglio: Aggiunta/Modifica usano una vista su misura
(`privileges.blade.php`) che ignora `$this->form` e non ne risentono.

## Motivazione

Nessun motivo valido perché creare un ruolo per qualcun altro debba
cambiare l'aspetto dell'interfaccia di chi lo sta creando.

## Test

Non eseguita la suite automatica (nessun test esistente su questo
controller). Verificato con `php -l` che il file resti sintatticamente
corretto. Verifica funzionale (creare un privilegio e controllare che il
proprio tema non cambi) lasciata all'utente.

**Terza aggiunta, controller diverso**: su `/admin/users` la colonna
"Expiry date" mostrava `01/01/1970` per gli utenti senza data di scadenza
impostata (`data_scadenza` NULL), invece di restare vuota. Causa:
`AdminCmsUsersController::cbInit()` formattava la colonna con
`date('d/m/Y', strtotime($row->data_scadenza))` senza controllare prima se
il valore fosse vuoto — `strtotime(null)` ritorna `0`, che `date()`
interpreta come l'epoca Unix. Corretto aggiungendo il controllo
(`empty($row->data_scadenza) ? '' : date(...)`), verificato che la colonna
ora sia vuota per l'utente senza scadenza. Trovato confrontando col
comportamento (corretto) della pagina di dettaglio, che già gestiva questo
caso.

**Quarta aggiunta, scoperta collaterale non risolta**: richiesto di
nascondere modifica/eliminazione su `/admin/logs`. Disattivati
`button_edit` (già lo era), `button_delete`, `button_bulk_action` in
`LogsController::cbInit()` — **inefficace per un superadmin**: lo stile
bottoni di default (`button_icon`, mai impostato esplicitamente altrove)
usa `ModuleHelper::can_edit()`/`can_delete()`, che ritornano `true`
incondizionatamente per `CRUDBooster::isSuperadmin()` **prima** di
controllare `$module->button_edit`/`button_delete` — quel controllo esiste
nel codice ma è irraggiungibile per un superadmin. Non è specifico di
Logs: vale per qualunque modulo con lo stile bottoni di default, in tutta
l'app. Discusse tre strade (override server-side solo per Logs, cambio
stile bottoni solo per Logs, fix globale su `ModuleHelper`) — **su
richiesta dell'utente, non perseguita per ora**. I flag restano comunque
disattivati (funzionano per chi non è superadmin, e per gli altri 3 stili
di bottoni azione che rispettano `button_edit`/`button_delete`
direttamente).

## Rischi e note

Nessuno noto — rimozione/aggiunta di codice minima, nessuna dipendenza da dati.

## Rollback

`git revert` del commit.
