# 103 - Fix: pulsante Save invisibile su admin/users/profile

- **Data**: 2026-09-25
- **Stato**: Completato
- **Area**: Users / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/form_body.blade.php`

## Contesto

Segue direttamente la scoperta fatta in
[102](102-mfa-badge-bootstrap3-vs-bootstrap5.md): il pulsante "Save" della
pagina profilo (`admin/users/profile`) era invisibile/non cliccabile,
perche' il box collassabile "System Information" (apre su un campo
`tenant`, si aspetta di chiudersi su un campo `group`) non si richiudeva
mai per `AdminCmsUsersController`, che usa `primary_group` invece di
`group` - inglobando tutto cio' che segue (Save incluso) in un contenitore
nascosto via CSS.

## Situazione prima

`form_body.blade.php` riconosceva solo `$name == 'group'` come marcatore di
chiusura del box. Il modulo Users non ha mai avuto un campo `group`, solo
`primary_group` - quindi per **ogni** pagina che passa da questo
controller con quel ramo di codice (profilo, e potenzialmente Add/Edit se
in circostanze diverse da quelle verificate) il box restava aperto.

## Situazione dopo

Una riga sola cambiata: la condizione di chiusura ora riconosce anche
`primary_group`:
```
@if($name == 'group' || $name == 'primary_group')
```
Questo permette al box di chiudersi correttamente subito dopo il campo
"Primary Group", esattamente come era pensato per funzionare - senza
rimuovere o disabilitare nessun campo, nessun cambiamento a
`AdminCmsUsersController`.

## Perche' NON e' stata la prima soluzione tentata (2 tentativi scartati)

Prima di arrivare a questo fix minimale sono stati esplorati e **scartati**
due approcci piu' invasivi, entrambi con un problema serio scoperto solo
testando dal vivo:

1. **Chiudere i div "a mano" solo sulla pagina profilo** (in
   `form.blade.php`, subito prima della sezione MFA): risolveva la
   sovrapposizione visiva ma **non** rendeva visibile il pulsante Save
   (che restava comunque dentro al box collassato, prima di quel punto di
   chiusura manuale).
2. **Rimuovere Tenant/Primary Group/Expiry date/Status dal form del
   profilo** (`AdminCmsUsersController::cbInit()`, con un
   `if (!CRUDBooster::isProfilePage())`): risolveva sia la sovrapposizione
   sia la visibilita' del Save, ma **ha causato una corruzione dati
   reale, verificata due volte**: cliccando "Save" con questi campi
   rimossi dal form, `status` diventava `NULL`, `tenant` e `primary_group`
   diventavano `0` sull'utente salvato - **CBController::input_assignment()
   itera esattamente `$this->form`** per decidere cosa scrivere, quindi un
   campo assente dal form viene trattato come "svuotato", non come "non
   toccare". Corretto sul database locale con `UPDATE` manuali
   (`status='Active', tenant=1, primary_group=1`) entrambe le volte prima
   di scartare l'approccio.

Il fix finale (aggiungere `primary_group` alla condizione di chiusura) evita
il problema alla radice: **nessun campo viene tolto da `$this->form`**,
quindi zero rischio di perdita dati - cambia solo *dove* il box collassabile
si richiude nel markup.

## Test

Tutti dal vivo su Docker locale, con verifica DB dopo ogni salvataggio:
- `admin/users/profile`: box "System Information" ora si chiude
  correttamente (Tenant + Primary Group dentro, correttamente collassato di
  default, espandibile con "+"); pulsante Save visibile
  (`offsetParent !== null` confermato via JS) **e funzionante** - salvato
  due volte (una con l'errore ancora presente per confermare la
  riproducibilita', una dopo il fix) e verificato via `mysql` che
  `status`/`tenant`/`primary_group` restano invariati dopo il salvataggio.
- `admin/users/edit/5` (utente reale, Tenant Admin): pagina Edit standard
  dello stesso modulo, verificata prima e dopo - stesso comportamento
  (nessuna card "System Information" visibile qui, non era comunque rotta),
  Save testato e confermato che non altera i dati (`tenant`, `primary_group`,
  `status`, `data_scadenza` invariati dopo il salvataggio).
- Nessun errore in console del browser su nessuna delle due pagine.

## Rischi e note

- Il fix e' scoped al nome del campo (`primary_group`): se in futuro un
  altro modulo con `tenant` usasse un terzo nome diverso da `group`/
  `primary_group` per il proprio campo di raggruppamento, si
  ripresenterebbe lo stesso bug con quel nome - non e' una soluzione
  generale al pattern (aprire su un nome, chiudere su un altro), solo
  la correzione mirata per i nomi di campo realmente usati in questo
  progetto oggi (verificato: solo `group` e `primary_group` esistono).
- Sull'ambiente Docker locale, l'utente superadmin (`id=1`) ha avuto
  `status`/`tenant`/`primary_group` temporaneamente corrotti due volte
  durante questo lavoro (sempre ripristinati nella stessa sessione, mai
  lasciati in stato inconsistente) - nessun impatto oltre l'ambiente
  locale, nessun commit intermedio con quello stato.

## Seguito lo stesso giorno: "System Information" spostata in fondo al form

Segnalato dall'utente: anche dopo il fix, "System Information" compariva
"in mezzo" al form (tra Email ed Expiry date), interrompendo il flusso dei
campi principali del profilo.

**Fix**: in `AdminCmsUsersController::cbInit()`, l'intero blocco che
definisce Tenant/Primary Group (tre varianti: superadmin, tenant admin,
utente base) e' stato spostato dalla sua posizione originale (subito dopo
Email/Privilege) a **dopo** "Password Confirmation", appena prima del
blocco Qlik. Puro riordino, nessuna riga di logica cambiata all'interno
del blocco.

**Perche' e' sicuro** (a differenza dei due tentativi scartati sopra):
`CBController::input_assignment()` itera `$this->form` e scrive
`$this->arr[$name]` per ogni campo **per nome**, non per posizione -
riordinare l'array non cambia quali colonne vengono scritte, solo l'ordine
di rendering in pagina. Verificato comunque con lo stesso identico
protocollo (salvataggio reale + controllo DB) per non ripetere l'errore
delle volte precedenti.

**Risultato**: ordine finale del form profilo — Name, Email, Expiry date,
Status, Photo, Language, Password, Password Confirmation, "System
Information" (Tenant + Primary Group, collassata), Save. Stesso identico
riordino si applica anche alle pagine Add/Edit standard del modulo Users
(stesso `cbInit()`), che ora mostrano Tenant/Primary Group in fondo invece
che subito dopo Privilege - **comportamento visibile cambiato anche li'**,
segnalato qui per trasparenza anche se non esplicitamente richiesto per
quelle pagine.

**Test**: verificato dal vivo che il box si espande/collassa
correttamente (Tenant "Tenant 1", Primary Group "IT" mostrati giusti per
il superadmin locale) e che un salvataggio reale, sia su
`admin/users/profile` sia su `admin/users/edit/5`, non altera
`status`/`tenant`/`primary_group`/`data_scadenza` (confrontato via
`mysql` prima e dopo in entrambi i casi).

## Rollback

Ripristinare la condizione originale (`$name == 'group'`) riporta al
comportamento rotto (Save invisibile su questo modulo) - non consigliato.
