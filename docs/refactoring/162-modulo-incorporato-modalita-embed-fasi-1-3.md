# 162 - Modulo incorporato: modalità embed (layout ridotto, flag in CBBackend, login a pagina intera)

- **Data**: 2026-09-30
- **Stato**: Completato e verificato in locale (Fasi 1-3; il widget non usa ancora l'iframe, vedi Fase 4)
- **Area**: Statistic Builder / Frontend / Middleware
- **File/aree di codice coinvolte**:
  - `app/Http/Middleware/CBBackend.php`
  - `resources/views/crudbooster/admin_template.blade.php`
  - `public/css/theme.css`

## Contesto

Seguito di [161](161-modulo-incorporato-robustezza-e-traduzioni.md). Il
widget "Modulo incorporato" viene usato per *lavorare* (aprire e salvare
record) e oggi incorpora il modulo scaricando la pagina completa e
ritagliando `#content_section`: gli script della pagina incorporata
girano nel contesto della dashboard e ogni azione (paginazione, filtri,
link) porta fuori dalla dashboard. Piano concordato con l'utente: il
widget caricherà il modulo in un `<iframe src="...?embed=1">`; altezza e
larghezza le decide già il builder (niente auto-altezza); la vista
pubblica non mostra questo widget; sessione scaduta → login a pagina
intera; pulsante "apri a pagina intera" nel widget. Questo intervento
prepara il lato server (Fasi 1-3); l'iframe nel widget (Fase 4) è un
intervento separato.

## Situazione prima

- `admin_template.blade.php` è l'unico layout: include sempre header,
  chat, modale licenza, sidebar e footer. Esiste solo il caso
  `$target_layout == 2` (Qlik) che nasconde parte dello shell via JS
  dopo il caricamento.
- `CBBackend` fa solo controllo login/lock e redirect della dashboard.
- Nessun concetto di "pagina incorporata".

## Situazione dopo

- **Flag `?embed=1`** (solo quel valore, letto dalla query string) in
  `CBBackend::handle()`: condivide `ch_embed` alle viste e, sulla
  risposta, riscrive i redirect aggiungendo `embed=1` (anche il
  `redirect_url` delle risposte JSON di `CRUDBooster::redirect()` per le
  chiamate ajax). Solo URL dello stesso host: un redirect verso host
  esterno non viene toccato. **Senza `embed=1` il comportamento è
  invariato.**
- **Sessione scaduta / schermo bloccato** in modalità embed: il redirect
  a `login`/`lock-screen` diventa una pagina minima che porta la finestra
  *intera* (`window.top`) al login; per le risposte JSON, campo
  `break_frame` gestito dallo script embed. Il flash "non sei connesso"
  resta in sessione e lo consuma il login vero.
- **Layout ridotto** in `admin_template.blade.php`: con `ch_embed` niente
  header, chat, modale licenza, sidebar e footer; body con classe
  `ch-embed`. Restano titolo e **pulsanti d'azione del modulo**
  (Aggiungi/Esporta/Importa/`index_button`, che stanno nel
  `.content-header`): servono per lavorare dentro il widget. Il
  breadcrumb è nascosto via CSS (`theme.css`).
- **Script embed** (solo in modalità embed): mantiene `embed=1` su link,
  form (POST: `action`; GET: campo nascosto) e chiamate ajax jQuery
  (`ajaxPrefilter`) dello stesso sito; `target` diverso da `_self`, link
  esterni, `#`, `javascript:`/`mailto:` restano invariati.

## Motivazione

I redirect lato server coprono il caso più delicato (dopo un salvataggio
l'iframe deve restare nel widget) senza toccare le ~30 chiamate di
redirect dei controller; lo script lato client copre link, form e ajax.
Nessuno stato di sessione: una scheda normale aperta in parallelo non
viene contagiata. Alternativa scartata: nascondere l'intero
`.content-header` (come fa il caso Qlik), che avrebbe tolto i pulsanti
"Aggiungi" & co. dal widget.

## Test

Manuali, in locale (Docker), richieste GET in sola lettura simulate
in-process con l'utente superadmin del DB di sviluppo (l'utente ha MFA
attiva, quindi niente curl; script temporaneo rimosso):
- `/admin/users?embed=1` → 200, body `ch-embed`, nessun `<aside>`/header/
  footer, script embed presente, titolo e pulsante "Aggiungi" presenti;
- `/admin/users` (senza embed) → 200, `<aside>`, header e footer
  presenti, nessuno script embed, nessuna classe `ch-embed`: identica a
  prima;
- `/admin?embed=1` → 302 verso `.../statistic_builder/dashboard?embed=1`;
  senza `embed` il `Location` non cambia;
- ospite + `embed=1` → pagina con `window.top.location` verso il login;
  ospite senza embed → 302 al login come prima;
- `CBBackend` chiamato direttamente: redirect e JSON riscritti con
  fragment/querystring corretti (`/admin/users?x=1&embed=1#t`), host
  esterno invariato, login JSON → `break_frame`.

**Non verificato**: comportamento in browser dentro un iframe reale (è la
Fase 4) — in particolare il flusso completo aprire/salvare/eliminare
record, i link generati dinamicamente da JS e le chiamate non-jQuery
(`fetch`/XHR nativi non ricevono `embed=1`). Suite di test non lanciata.

## Rischi e note

- Il parametro `embed=1` finisce anche in `Request::all()`/`fullUrl()`,
  quindi in alcuni `return_url` e link generati dal server: voluto (li
  mantiene in embed), ma un URL copiato dall'iframe contiene `embed=1`.
- Chi apre a mano `?embed=1` vede la pagina senza shell: innocuo, non
  dà accesso a nulla di più (stessi controlli di login e permessi).
- Pagine non admin (login, ecc.) non passano da `CBBackend` e non sono
  toccate.

## Rollback

Ripristinare i tre file. Senza `embed=1` nessun comportamento dipende
da queste modifiche, quindi anche lasciarle in sede è innocuo.
