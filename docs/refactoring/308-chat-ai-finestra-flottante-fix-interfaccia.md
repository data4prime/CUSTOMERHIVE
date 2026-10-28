# 308 - Chat AI (finestra flottante): fix di interfaccia

- **Data**: 2026-10-28
- **Stato**: Completato (verificato solo con lint/compilazione Blade, non a vista)
- **Area**: Frontend / Chat AI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/chat3.blade.php`
  - `resources/views/crudbooster/admin_template_plugins.blade.php`
  - `resources/views/chat_ai/view.blade.php`, `resources/views/chat_ai/fullscreen_view.blade.php`
  - `app/Helpers/ChatAIHelper.php` (`aiAvatarHtml`, `userAvatarHtml`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

La finestra Chat AI flottante (inclusa in ogni pagina admin da
`admin_template.blade.php`) aveva diversi difetti di interfaccia. Richiesta
di controllare e sistemare.

## Situazione prima

- Placeholder ("Chiedi qualcosa...") e pulsante ("Invia") scritti in
  italiano nel markup, in violazione della regola EN+IT.
- I messaggi aggiunti via JS avevano markup diverso da quelli resi dal server
  (`<p>` dentro `media-body` grigio, senza `d-flex`): prima e dopo il
  ricaricamento della pagina avevano un aspetto diverso. Lato server il
  messaggio dell'utente non era blu (la regola CSS mirava solo a un figlio
  diretto).
- Un messaggio vuoto partiva comunque verso `/admin/chat_ai/send_message`.
- Il testo dell'utente era inserito come HTML (anche nella versione resa dal
  server con `{!! !!}`).
- Aprendo la finestra non scorreva in fondo ai messaggi: lo scroll avveniva a
  finestra ancora `display: none`.
- Errori di rete o risposta senza `text`/`message`: nessun feedback visibile.
- Sfondo `white` fisso (non seguiva il tema scuro); nessun `max-width` su
  schermi stretti.

## Situazione dopo

- Placeholder e pulsante via `trans()`; nuove chiavi `chatai_placeholder` e
  `chatai_error_generic` in EN e IT (`send` esisteva già).
- Un solo markup per i messaggi (JS identico al Blade), regola CSS
  `.media-chat-reverse .media-body` per il blu dell'utente.
- Messaggio vuoto/solo spazi: non si invia nulla.
- Testo dell'utente escapato (JS e Blade `{{ }}`); la risposta AI resta HTML
  come prima (`data.text`/`data.message`, `{!! !!}`).
- `toggleChatWindow()` (in `admin_template_plugins`) apre/chiude e scorre in
  fondo; usata da pulsante header, X di chiusura e `#openChat`.
- Bolla di errore tradotta se la richiesta fallisce o la risposta è vuota.
- Sfondo `var(--ch-surface)`, `max-width: calc(100vw - 16px)`.

## Motivazione

UX coerente e regola i18n rispettata. Cambiamento visibile all'utente (stile
dei messaggi dell'utente dopo il reload, errori mostrati, niente invio di
messaggi vuoti): minimo e intenzionale.

## Test

`php -l` sui due file lingua; compilazione Blade dei due template + `php -l`
sul risultato. NON verificato nel browser né l'invio reale di un messaggio
(serve un workflow N8N attivo).

## Estensione (stessa giornata)

- Stessi fix applicati a `chat_ai/view.blade.php` (Chat AI come voce di menu,
  ID con suffisso `{{$row->id}}`) e `chat_ai/fullscreen_view.blade.php`:
  placeholder tradotto, no invio vuoto, testo utente escapato, errori
  visibili, sfondo `var(--ch-surface)`, messaggio utente blu in `view`.
  Il testo di errore "Assicurati che: workflow N8N / URL / passphrase" era
  in italiano nel JS: ora usa le chiavi `chatai_error_check_*` (EN+IT).
- `fullscreen_view`: la risposta AI dopo il reload era escapata (`{{ }}`)
  mentre via JS veniva inserita come HTML: ora entrambe HTML, come negli
  altri due template.
- Tolto il `margin-right: 20px` dal footer del widget flottante (la maniglia
  di resize di jQuery UI sporge 7px dentro il padding di 10px, non copre il
  pulsante).
- **Niente più immagini statiche** (`chatai.jpg`, `admin.jpeg`) negli avatar:
  stessa logica delle foto profilo (intervento 287). Utente: foto se caricata,
  altrimenti iniziali, via `ChatAIHelper::userAvatarHtml()` →
  `LogsController::avatarHtml()`. Chat AI: cerchio con "AI" nello stesso stile
  (`ChatAIHelper::aiAvatarHtml()`). Nel JS gli avatar arrivano da Blade con
  `json_encode`.

## Rischi e note

- `chat_ai/fullscreen_view.blade.php` (usato con `target_layout == 1`) è un
  frammento senza `@extends` e la sua `.chat-window` parte con
  `display: none` senza nulla che la apra: sembra già non funzionante a
  prescindere da questo intervento. Non cambiato il layout, da verificare a
  vista.
- `.avatar` e i file `public/images/user/chatai.jpg`/`admin.jpeg` restano
  (la classe è globale in `chat3`, altri punti potrebbero usarla).

## Rollback

Ripristinare i quattro file elencati da git.
