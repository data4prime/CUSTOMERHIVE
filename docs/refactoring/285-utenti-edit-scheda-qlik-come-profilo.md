# 285 - Modifica utente: scheda Qlik con le stesse card del profilo

- **Data**: 2026-10-08
- **Stato**: Completato (non verificato a vista nel browser)
- **Area**: Frontend / Utenti
- **File/aree di codice coinvolte**:
  - `resources/views/users/_qlik_row.blade.php` (nuovo, riga/card condivisa)
  - `resources/views/users/_qlik_field.blade.php` (nuovo, scheda Qlik della modifica utente)
  - `resources/views/users/profile.blade.php` (usa il partial)
  - `resources/views/crudbooster/default/type_components/child/component.blade.php` (chiave opzionale `view`)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`'view'=>'users._qlik_field'` sul campo `qlik_users`)

## Contesto

In `/admin/users/profile#qlik` le associazioni utente -> configurazione Qlik
sono card ordinate (configurazione, login, user directory, IDP, con hint e
campi mostrati in base al tipo SaaS / On-Premise). In
`/admin/users/edit/{id}` la stessa cosa era la griglia generica del campo
`child` (tabella con datamodal), meno chiara. Richiesta: stessa resa.

## Situazione prima

- Profilo: card + JS dedicato, salvataggio AJAX su `users/profile-qlik`.
- Modifica utente: campo `child` `qlik_users` (griglia a tabella), salvato
  insieme al record dal Salva principale (`CBController`, input
  `qlikusers-<colonna>[]`) con `prepare_qlik_users()` in `hook_before_edit`
  che genera l'IDP per le conf SaaS.

## Situazione dopo

- Il markup di una card sta in `users/_qlik_row`, usato dal profilo (righe +
  `<template>`) e dalla modifica utente.
- Il campo `child` accetta una chiave opzionale `view`: se presente rende
  quella vista al posto della griglia. La scheda Qlik della modifica usa
  `users._qlik_field` (card, "Aggiungi"/"Rimuovi", campi per tipo di conf).
- Il **salvataggio non cambia**: le card postano gli stessi
  `qlikusers-qlik_conf_id[]`, `-qlik_login[]`, `-user_directory[]`,
  `-idp_qlik[]` (sempre tutti e quattro per riga, indici allineati) e il
  Salva principale + `prepare_qlik_users()` fanno il resto. Le righe senza
  configurazione vengono svuotate al submit così `CBController` le scarta.
- Nessuna nuova stringa: solo chiavi `profile_qlik_*` già in en e it.

## Motivazione

Coerenza tra le due pagine con rischio minimo: nessun endpoint nuovo né
cambio ai controlli di permesso. Alternativa scartata: AJAX proprio con
`postProfileQlik` generalizzato a un `user_id` (il Salva principale non
toccherebbe più Qlik, permessi tenant da riscrivere).

## Test

- Compilazione e render del partial in modalità profilo e modifica
  (con/senza prefisso) via app Docker: OK.
- Da provare a mano: aggiungi/rimuovi riga, cambio tipo conf (SaaS: solo IDP;
  On-Premise: login + directory), salvataggio e ricaricamento; creazione IDP
  per una conf SaaS; profilo invariato.

## Rischi e note

- Cambia solo la resa. Differenza visibile: i campi non pertinenti al tipo di
  conf sono nascosti (come nel profilo) ma restano nel form; un vecchio valore
  non pertinente resta in DB, come prima.
- Pagina di dettaglio: `child/component_detail` accetta la chiave opzionale
  `view_detail`; per `qlik_users` è `users._qlik_detail` (stesse card, campi
  disabilitati, senza pulsanti, messaggio "nessuna associazione" se vuoto).
  Due vincoli del layout di dettaglio emersi al primo test a vista:
  `component_detail.blade.php` **deve iniziare con `<tr>`** (lo controlla
  `form_detail_row`, altrimenti avvolge il campo in un'altra riga con label
  duplicata), e `form_detail_layout.blade.php` esclude dal "box da input" le
  celle che contengono `.ch-qlik-detail` (come già per `.ch-grid-card`).
- Il campo `child` con `view` è riutilizzabile da altri moduli, ma oggi lo usa
  solo questa scheda.

## Rollback

Togliere `'view'=>'users._qlik_field'` dal campo `qlik_users` ripristina la
griglia; per il resto ripristinare i file elencati.
