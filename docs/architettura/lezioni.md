# Lezioni apprese dagli interventi

> Letto da `/architect` e `ch-architect-analyst` all'inizio di ogni analisi.
> Una voce per lezione, **breve** (3-5 righe), più recenti in alto. Si
> aggiorna a fine batch con il post-mortem (vedi
> `.claude/rules/agenti-post-intervento.md`). Non è un diario: solo ciò che
> cambia il modo di proporre o di implementare la prossima volta.

## Formato di una voce

```
### AAAA-MM-GG — titolo breve (doc NNN)
- **Piano prevedeva:** …
- **È successo:** …
- **Sfuggito al reviewer / ai test:** … (o "nulla")
- **Regola per la prossima volta:** …
```

## Voci

### 2026-10-29 — Relazioni additive, due fix, prime Action e scope (doc 319-322, piano 317 batch 2-3)
- **Piano prevedeva:** batch 2 relazioni additive con wrapper; 2b i due fix con
  i test del batch 0 rovesciati di proposito; 3 prima Action e local scope.
- **È successo:** i wrapper `Menu::tenants()` e `QlikItem::allowedGroups/Tenants()`
  non si sono potuti sostituire con le relazioni (queste escludono i
  soft-deleted, i wrapper no): restano intatti e la relazione è solo aggiunta.
  Un test scritto per il 2b (id duplicati nella pivot) era sbagliato: la pivot ha
  `unique(menu_id, tenant_id)`. Per gli scope il reviewer ha trovato un punto
  identico dimenticato (`AdminGroupsController:515`) e uno scope applicato dentro
  un commento. Tutto verde: 123 test.
- **Sfuggito al reviewer / ai test:** il test sbagliato lo ha preso il reviewer
  leggendo la migrazione, non il senior dev; `AdminQlikItemsController:566` era
  dentro `/* */` e il senior dev non lo aveva notato.
- **Regola per la prossima volta:** (1) prima di scrivere un test che inserisce
  dati "non validi" in una pivot, leggere la migrazione per vincoli unique e FK;
  (2) prima di sostituire con uno scope/Action, verificare che il punto non sia
  commentato; (3) una relazione Eloquent non è un sostituto drop-in di un
  `pluck()` su tabella pivot se ci sono soft-delete in gioco: confrontare i
  risultati con un test.

### 2026-10-29 — Autorizzazioni ChatAI su tabelle dedicate (doc 318, piano 318)
- **Piano prevedeva:** tabelle `chatai_*_allowed`, controller allineato a Qlik,
  popup condivisi con un parametro opzionale; test del comportamento obiettivo.
- **È successo:** al primo giro 4 test su 28 erano rossi per colpa del test, non
  del codice: le route `admin/chat_ai/*` passano da `EnforceModuleLicense`
  (licenza ChatAI assente in test → 302) e `GET menu_management/add` è sempre
  rimandata (`button_add = false`). Con la licenza simulata i test sono diventati
  28/28. Scoperto di passaggio un bug preesistente: `menus/form_body.blade.php:68`
  non compila in PHP 8 (`$join_query_{$join_table}`).
- **Sfuggito al reviewer / ai test:** il reviewer aveva segnalato solo la
  fragilità del test del menu; non aveva visto che **tutti** i test negativi
  sulle route custom erano verdi in modo vuoto (il middleware rimandava ogni
  richiesta, quindi nessuna riga veniva mai scritta).
- **Regola per la prossima volta:** un test che asserisce "non è stata creata
  nessuna riga" deve prima dimostrare, con lo stesso setup, che il caso positivo
  crea la riga: altrimenti passa anche se la richiesta non arriva al controller.
  Per le route di moduli a licenza (Qlik, ChatAI) il test deve simulare la
  licenza (`Storage::fake('license')` + riga in `license` + token in cache).

### 2026-10-29 — Modelli: caratterizzazione e igiene (doc 317, piano 317)
- **Piano prevedeva:** batch 0 di test per fissare il comportamento, batch 1
  di igiene sul posto, poi relazioni additive con `chataiconf_id`/`item_id` "da
  verificare".
- **È successo:** i test del batch 0 (34 verdi) hanno fatto emergere cose che la
  sola lettura del codice non mostrava: `chataiconf_id` non esiste in nessun DB
  e ChatAI condivide lo spazio di `item_id` con Qlik; `cms_users.primary_group`/
  `tenant` sono NOT NULL (l'orfano reale è un id su record soft-deleted); `can_menu`
  dipende dal path HTTP. Ne è nato il piano 318 (ADR A12).
- **Sfuggito al reviewer / ai test:** gap su `QlikSyncRun::records()` e sui
  `casts()` di `User`/`QlikSyncRun`; i test non erano stati rilanciati dopo il
  batch 1 al momento della documentazione.
- **Regola per la prossima volta:** prima di pianificare relazioni o scope su un
  modello, verificare lo **schema reale** (`information_schema` nel container) e
  chi scrive quelle tabelle, non solo il codice del modello: un metodo che
  "sembra" funzionare può puntare a una colonna inesistente. Rilanciare i test
  del batch 0 dopo il batch 1 prima di chiudere la documentazione.

### (migrata dalla memoria, senza data) — Rimuovere campi da `$this->form` corrompe i dati (doc 103)
- **Piano prevedeva:** togliere un campo dal form per non mostrarlo.
- **È successo:** al save i campi assenti da `$this->form` venivano azzerati.
- **Regola per la prossima volta:** mai rimuovere campi; usare `hide_form`.

### (migrata dalla memoria, senza data) — Un `data-*` "morto" può agganciare JS vendorizzato
- **Regola per la prossima volta:** prima di rimuovere un attributo, cercare il
  valore letterale con `tokensave_search literal:true`.

### 2026-10-29 — La copertura riportata da tokensave è ingannevole (osservata creando questa documentazione)
- **È successo:** `tokensave_test_risk` dà 0% perché i test sono HTTP.
- **Regola per la prossima volta:** usarlo per ordinare il rischio, e per la
  copertura vera guardare i nomi in `tests/Feature/`.
