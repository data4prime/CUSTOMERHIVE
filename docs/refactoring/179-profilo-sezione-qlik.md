# 179 - Profilo utente: sezione "Qlik" (associazione utente Qlik)

- **Data**: 2026-09-30
- **Stato**: Completato (test manuale da fare)
- **Area**: UI/UX, Qlik
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`getProfile`, nuovo `postProfileQlik`)
  - `resources/views/users/profile.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

La pagina `/admin/users/profile` (a sezioni: Generale / MFA / Sistema /
Password, menu a destra) non permetteva di vedere né modificare
l'associazione dell'utente a uno o più utenti Qlik (tabella `qlik_users`).
L'associazione esisteva solo nel form di modifica utente generico
(`cbInit`, campo `child` "Qlik Users"), che per scelta è nascosto sulla
pagina profilo (`!isProfilePage()`).

## Situazione prima

- Nessuna sezione Qlik nel profilo.
- Associazione gestita solo da `/admin/users/edit/{id}` con il child
  `qlik_users` + `prepare_qlik_users()` (IDP generato per le conf SaaS).

## Situazione dopo

- Nuova voce "Qlik" nel menu di destra + pannello, **visibili solo se
  `LicenseHelper::isActiveQlik()`**.
- Il pannello elenca le associazioni (configurazione, login Qlik, user
  directory, IDP) con aggiungi/rimuovi riga; salvataggio AJAX su
  `users/profile-qlik` (`postProfileQlik`).
- Modificabile solo da superadmin/tenant admin; gli altri utenti vedono le
  proprie associazioni in sola lettura. Il server rifiuta (403) anche con
  licenza senza modulo Qlik o ruolo non abilitato.
- Stessa logica di `prepare_qlik_users()`: se la conf è SaaS e l'IDP è
  vuoto viene creato con `QlikHelper::createUser()`; se la creazione fallisce
  il salvataggio si ferma con errore (nessuna modifica). Configurazioni
  duplicate rifiutate dalla validazione (`distinct`).
- I campi dipendono dal tipo di configurazione scelta: **SaaS** → solo IDP
  Subject; **On-Premise** (ogni tipo diverso da `SAAS`) → login Qlik + user
  directory; nessuna configurazione → nessun campo. Anche il server scarta i
  campi dell'altro tipo (SaaS: login/directory azzerati; On-Premise: IDP
  azzerato), quindi un valore residuo non resta in `qlik_users`.
- L'IDP Subject è **solo in lettura**: il server ignora il valore inviato dal
  client, mantiene quello già salvato per la stessa configurazione o, se non
  c'è, lo genera con `createUser()`. Non si può più "svuotare per
  rigenerarlo" (come invece consentiva il form di modifica utente); per
  ottenerne uno nuovo si rimuove l'associazione e la si ricrea.
- Il salvataggio sostituisce le righe dell'utente in una transazione.

## Motivazione

Permettere di gestire l'associazione Qlik dal profilo senza passare dalla
schermata di modifica utente. Limitato ai ruoli admin perché login/user
directory decidono con quale identità Qlik entra l'utente: lasciarli
modificare a un utente base permetterebbe di assumere l'identità di un altro
utente Qlik.

## Test

- `php -l` su controller e file lingua, `view:clear`, `route:list`
  (route `admin/users/profile-qlik` presente).
- Non eseguita la suite automatica né un giro manuale nel browser: da fare
  con licenza Qlik attiva (vedi `docs/piano-testing-manuale.md`) e anche con
  licenza senza Qlik (sezione assente).

## Rischi e note

- L'elenco configurazioni non è filtrato per tenant, come già nel form di
  modifica utente.
- Il form di modifica utente continua a funzionare invariato.
- Nessun test automatico aggiunto in questo intervento.

## Rollback

Ripristinare i tre file sopra (`git checkout -- <file>`); nessuna migrazione.
