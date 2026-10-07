# 232 - Amministrazione: UI/UX di tenants, ruoli, gruppi, utenti e registro accessi

- **Data**: 2026-10-05
- **Stato**: Completato (da provare a mano, vedi "Test")
- **Area**: Frontend / UI standard / Amministrazione
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminTenantsController.php`
  - `app/Http/Controllers/System/PrivilegesController.php`
  - `app/Http/Controllers/System/AdminGroupsController.php`
  - `app/Http/Controllers/System/AdminCmsUsersController.php`
  - `app/Http/Controllers/System/LogsController.php`
  - `resources/views/tenants/{form,members,group}.blade.php`
  - `resources/views/crudbooster/privileges.blade.php`
  - `resources/views/groups/{members,tenant,items}.blade.php` + nuovi partial `_add_card`, `_remove`, `_remove_script`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `adm_*` e `delete_not_empty_tenant_count`)

## Contesto

Dopo lo standard UI Bootstrap 5 (206-213) e i nuovi type_component
(`image`, `color`, `radio` segmentato, 226-231), le cinque pagine di
amministrazione erano rimaste con la grafica "da CRUD generico". Analisi e
mockup fatti il 2026-10-05; qui si implementano.

## Situazione prima

- **Tenants**: lista con 8 colonne (Id, Favicon, due colori hex di login...) e
  nessun conteggio; azioni "Members"/"Groups" senza distinzione; pagina membri
  con nome/email/foto e un pulsante "Move"; form senza anteprima del login;
  cancellazione bloccata con un messaggio senza numeri.
- **Ruoli**: colore tema = select di 12 `skin-*` (sei accenti effettivi);
  matrice permessi con una query per modulo (due volte), classi di colore
  legacy, nessuna ricerca/preset; lista senza conteggi.
- **Gruppi**: colonna "Help" per il campo `description`, placeholder fuorviante
  sul nome; pagine membri/tenant/item con il form "aggiungi" sopra l'elenco,
  foto/nome/email in colonne separate, rimozione con un solo clic.
- **Utenti**: foto in colonna separata, stato e scadenza testo semplice, stato
  = select con la sola voce `Inactive`.
- **Registro accessi**: solo filtro avanzato; `displayDiff()` salvava nel log
  HTML con chiavi/valori non escapati; dettaglio con user agent grezzo.

## Situazione dopo

- **Tenants**: lista = Logo, Nome, Descrizione, Dominio, Utenti, Gruppi,
  Creato il (senza logo caricato la lista e l'anteprima del form mostrano un
  quadrato con le iniziali del tenant, colore stabile in base al nome; conteggi come colonne con `callback` su `id`, non subquery, cosi'
  il filtro avanzato non genera SQL su colonne inesistenti); titoli azioni
  tradotti; messaggio di blocco con il numero di utenti; pagina membri con
  avatar, ruolo, gruppo principale, stato, ricerca client e "Apri utente";
  form con anteprima live della pagina di login (colori, logo, nome).
- **Ruoli**: lista con Utenti e Moduli abilitati; colore = sei pallini (valori
  salvati sempre `skin-*`, le `-light` si ripresentano sul loro accento); matrice
  con una sola query, ricerca, preset (Nessuno/Sola lettura/Editor/Tutto),
  caselle colonna/riga con stato parziale, contatore, intestazione fissa.
  Nomi dei campi `privileges[id][is_*]` invariati.
- **Gruppi**: "Help" -> "Description", placeholder tolto, lista con conteggi
  Membri/Tenant/Item (Item solo con Qlik in licenza); pagine relazione con
  elenco in primo piano, form "aggiungi" dietro un pulsante (si apre da solo
  se c'e' un avviso), rimozione con conferma sulla riga (stesso GET di prima),
  gruppo principale come etichetta.
- **Utenti**: cella Nome con avatar+email, ruolo/scadenza/stato come badge
  (scadenza: scaduto = rosso, entro 14 giorni = arancio); Status passa da
  select a radio segmentato (valori `Active`/`Inactive` invariati).
  Pagine nuovo/modifica/dettaglio come nel mockup: intestazione propria
  (`users/form_header.blade.php`: briciole "Utenti / Nome", titolo, pulsante
  "Invia link di reset password" in alto a destra al posto della scheda
  Sicurezza, eliminata insieme a `users/security_tab.blade.php`), scheda
  Generale a due card (dati a sinistra, foto a destra), Lingua come radio
  segmentato, form senza la card esterna. Il controller condivide
  `$flat_form_header` (solo getEdit/getAdd/getDetail) e `default/form.blade.php`
  e `users/form.blade.php` lo includono; senza quella variabile non cambia nulla.
  Il dettaglio utente (`/admin/users/detail/ID`) usa la stessa struttura della
  modifica (schede Generale/Sistema/Qlik, due card), in sola lettura, con i
  valori in riquadri come i campi (CSS `.flat-form .cbd-table` in
  `form_detail_layout.blade.php`, attivo solo con `$flat_form_header`). In
  `type_components/radio/component_detail` il valore e' ora testo semplice
  (prima un `.badge` senza sfondo, bianco su bianco, valido per ogni modulo).
  Il pulsante "Reset password" e' anche nel dettaglio. La scheda Qlik (campo child) non ha piu' card dentro card (solo con `$flat_form_header`) e il dettaglio della griglia child usa le stesse classi `ch-grid-*` del form (tabella vera, stato vuoto, testi escapati, niente hidden input) in `type_components/child/component_detail`.
- **Pagine nuovo/modifica/dettaglio di tenants, ruoli, gruppi e utenti in stile mockup**: intestazione comune (`crudbooster/partials/flat_form_header.blade.php`, attivata da `AppHelpersFlatForm::share()` nel `cbInit()` di ogni controller; azioni a destra opzionali, per gli utenti `users/form_actions`), niente card esterna, i campi senza schede/blocchi stanno in una card (`.flat-card`). **Tenants**: modifica/dettaglio con le schede Identita' / Pagina di login (campi + anteprima affiancata) / Dominio (+ Login URI) tramite il motore del layout; `getEdit` ora rende `crudbooster::default.form` e le tre viste `tenants/form*.blade.php` sono eliminate; anteprima login in `tenants/login_preview.blade.php` (campo `custom` con `exception`, JS senza jQuery). **Ruoli**: due card (dati del ruolo, permessi per modulo). **Gruppi**: card campi + pagine relazione con testata (`groups/_page_head`: briciole, titolo con conteggio, pulsante aggiungi), tabelle pulite (`.rel-table`) e ruoli/stati con `.ch-pill`. Il dettaglio non disegna piu' un riquadro vuoto per i soli campi hidden.
- **Registro accessi**: lista con data `gg/mm/aaaa hh:mm`, utente con avatar
  (foto o iniziali), colonna Evento come etichetta tenue (Accesso, Uscita,
  Creazione, Modifica, Eliminazione, Accesso negato, Sicurezza, Altro), niente
  barra filtri aggiuntiva (restano ordinamento e filtro standard). Il tipo di
  evento si ricava confrontando la descrizione con i modelli delle chiavi
  `log_*` in en e it (`LogsController::eventInfo`); le frasi non riconosciute
  sono "Altro". Pagina di dettaglio su misura (`getDetail` + `logs/detail.blade.php`):
  a sinistra dati dell'evento (data, utente, IP, browser, URL, user agent
  completo a scomparsa), a destra "Cosa e' cambiato" con prima/dopo
  evidenziati. `displayDiff()` ora fa l'escape; `details` e' ripulito (solo
  tag di tabella, senza attributi) anche per i log vecchi.

## Motivazione

Pagine piu' leggibili e coerenti con lo standard UI, senza toccare le regole di
salvataggio. La correzione di `displayDiff()` chiude un XSS memorizzato
(valore con markup salvato nel log e reso con `{!! !!}`).

## Test

- `php -l` su controller e file lingua; compilazione Blade + `php -l` del
  risultato per tutte le viste toccate e per i tre partial: tutto pulito.
- `LogsController::displayDiff/sanitizeDetails/parseUserAgent` provati con
  input di esempio (markup escapato, attributi e `<script>` rimossi, Edge/Safari).
- **Non verificato a vista**: nessuna pagina e' stata aperta nel browser e la
  suite di test non e' stata lanciata. Da provare a mano: lista e form di ogni
  modulo, salvataggio di un ruolo (matrice + colore), aggiunta/rimozione
  membro/tenant/item, filtro del registro, stato utente in creazione/modifica.

## Rischi e note

- Le colonne conteggio usano `name => id` (o `is_superadmin`): nel filtro
  avanzato compaiono come colonne duplicate di `id`. Una query per riga (20
  righe a pagina).
- **Status utente**: un utente con `status` vuoto in DB non ha nessun radio
  selezionato in modifica (prima la select mostrava "Active"); per chi non
  e' admin il campo resta `disabled` come prima (non viene postato).
- Il form "aggiungi" delle pagine relazione e' nascosto per chi non ha
  create/update (prima era visibile ma non poteva salvare).
- Tenant: il campo "Domain name" in modifica e' ancora editabile e in creazione
  viene sovrascritto da `hook_before_add` (comportamento invariato, non toccato).
- Registro: il tipo di evento e' una stima dalla frase salvata (non c'e' un
  campo `event`): frasi di altre lingue o non previste da `log_*` finiscono in
  "Altro". Il dettaglio standard di CBController e' sostituito da un
  `getDetail` proprio con gli stessi controlli di accesso.
- **Rimasto fuori**: pulsante cestino disabilitato in lista
  per tenant/gruppi/ruoli in uso (richiede di toccare `components/action`,
  comune a tutti i moduli); conferma di cancellazione di un ruolo con utenti
  (cambierebbe comportamento); log gia' esistenti restano con l'HTML salvato
  originale (solo ripulito in visualizzazione).

## Rollback

Ripristinare i file elencati da git (nessuna migrazione, nessun dato toccato);
i partial `groups/_*.blade.php` sono nuovi e si possono eliminare insieme.
