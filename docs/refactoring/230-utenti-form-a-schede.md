# 230 - Utenti: form a schede (Generale / Sistema / Qlik)

- **Data**: 2026-10-05
- **Stato**: Completato (non committato, da provare a vista)
- **Area**: Utenti (add/edit/detail) / form a schede
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`cbInit`, `buildTabsLayout`)
  - `resources/views/users/form_body.blade.php`
  - `resources/views/crudbooster/default/form_layout.blade.php`, `form_detail_layout.blade.php`

## Contesto

Dopo l'intervento 229 (layout schede → blocchi → campi nel module generator),
richiesta di usare la stessa struttura nel form degli utenti: scheda Generale
(nome, email, scadenza, stato, lingua), Sistema (tenant, gruppo primario) e
Qlik (solo con licenza Qlik).

## Situazione prima

Form piatto: tutti i campi in elenco, con tenant/primary group in fondo e il
campo child Qlik (solo modifica, solo con `LicenseHelper::isActiveQlik()`).

## Situazione dopo

**Aggiunta (stesso giorno): scheda Sicurezza.** Contiene il pulsante "Resetta
password" (con il suo messaggio di esito), spostato da sopra il form alla nuova
vista `users/security_tab.blade.php`, inclusa da `form_layout` tramite la
chiave `view` di una scheda (usabile solo da layout scritti nel codice, non dal
wizard). La scheda compare nelle stesse condizioni di prima: pagina di modifica,
utente diverso da sé, ruolo che può agire su quell'utente
(`UserHelper::can_do_on_user`). Chiave lingua nuova: `users_tab_security`.

`cbInit` imposta `$this->form_layout` (formato v2 di `ModuleGeneratorLayout`)
costruito da `buildTabsLayout()` solo coi campi realmente presenti in `$this->form`:
- **Generale**: nome, email, privilegio, scadenza, stato, lingua, foto e, solo in
  creazione, password + conferma. Privilegio e foto non erano nell'elenco
  richiesto ma vanno in una scheda: con un layout attivo i campi non posizionati
  non si disegnano né si salvano.
- **Sistema**: tenant e primary group (con le stesse varianti per ruolo di prima).
- **Sicurezza**: vedi sopra.
- **Qlik**: campo `qlik_users`; la scheda esiste solo se il campo esiste (licenza
  attiva, pagina di modifica).
Add: già usava `default.form_body`; edit: `users.form_body` ora delega a
`form_layout` come l'altro. Il dettaglio usa il layout automaticamente.

Modifiche condivise in `form_layout`/`form_detail_layout`: (1) tenant/group/primary_group
vanno nel box "System Information" in fondo solo se il layout non li posiziona;
(2) con due campi con lo stesso nome (select disabilitata + hidden di backup del
tenant admin per tenant e privilegio) il layout disegna quello visibile e
l'hidden resta disegnato a parte, così il valore viene comunque inviato.
Per i moduli generati non cambia nulla (non hanno quei duplicati e non
posizionano tenant/group).

## Motivazione

Form più ordinato e coerente con i moduli generati; riuso del motore esistente
invece di una vista nuova. Testi: riuse delle chiavi `profile_section_general/
system/qlik` (IT/EN già presenti).

## Test

`php -l`, `php artisan view:cache`. NON verificato a vista (nessuna prova
in browser): da controllare add ed edit come superadmin, tenant admin e utente
base, con e senza licenza Qlik, salvataggio (soprattutto privilegio/tenant per
tenant admin), validazione di un campo obbligatorio in una scheda nascosta e
il menu a cascata tenant → gruppo, dato che le select2 si inizializzano in una
scheda non visibile (possibile problema di larghezza). Suite di test non lanciata.

## Rischi e note

- Ogni nuovo campo aggiunto in futuro a `$this->form` degli utenti va aggiunto
  anche a `buildTabsLayout()`, altrimenti non viene disegnato né salvato.
- Il profilo personale (`users/profile`) ha già le sue sezioni e non è toccato.

## Rollback

Togliere la riga `$this->form_layout = $this->buildTabsLayout();` in `cbInit`
ripristina il form piatto (le modifiche alle viste sono retrocompatibili).
