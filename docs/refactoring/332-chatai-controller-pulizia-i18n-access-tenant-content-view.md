# 332 - AdminChatAIController: pulizia, i18n di access/tenant e content_view locale (batch 1)

- **Data**: 2026-10-30
- **Stato**: Completato (test eseguiti il 2026-10-30: 70 verdi; batch 2 rimandato)
- **Area**: Architettura / Chat AI
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminChatAIController.php`
  - `resources/views/chat_ai/access.blade.php`
  - `resources/views/chat_ai/tenant.blade.php`
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`
  - `tests/Feature/ChatAIPagesAndContentViewTest.php` (nuovo)
  - Piano di riferimento: `docs/refactoring/piani/332-chatai-controller-pulizia-i18n-form-request.md`

## Contesto

Dopo i doc 329-331 (autorizzazione di `send_message_agent`, Action, `ChatAIClient`)
restavano nel controller duplicazioni, codice morto e testi inglesi scritti a mano
nelle pagine `access`/`tenant`, contro la regola i18n del repo. Il piano 332 prevede
due batch separati da una pausa: questo documento copre solo il **batch 1**
(pulizia, i18n, `content_view` locale). Il **batch 2** (Form Request per
`send_message*`, con cambio visibile D2) **non è stato fatto** ed è rimandato.
Decisione D1 della persona: nessun trait condiviso con Qlik.

## Situazione prima

- `use GroupHelper;` inutilizzato.
- `hook_before_add` e `hook_before_edit` duplicavano la normalizzazione di
  `$postdata['primary']` (`== "primary"` → 1, altrimenti 0), con un commento `//dd`.
- `access()` e `tenant()` erano gemelli: stesso corpo, cambiavano tabella, vista, tipo di
  campo datamodal e percorsi. Testi scritti a mano in inglese: titoli
  ("Authorize Group"/"Authorize Tenant"), alert "Warning! / Select an element to add...",
  etichette `Name`/`Description`, placeholder di descrizione.
- Le viste `chat_ai/access.blade.php` e `tenant.blade.php` avevano tooltip in inglese
  (`Remove`, `Return`, `Main Module`); `tenant.blade.php` anche l'`<h4>` "Authorized Tenants".
- `content_view()` leggeva `$_GET['m']` direttamente e mescolava risoluzione del menu,
  dimensioni del frame, `target_layout` e dati di pagina in un unico metodo.
- Nessun test copriva il contenuto delle pagine `access`/`tenant` né `content_view`.

## Situazione dopo

Refactoring behavior-preserving; unico cambio visibile dichiarato: i testi delle pagine
in italiano.

**1a. Pulizia.** Rimosso `use GroupHelper;` (gli altri `use` sono stati verificati uno per
uno con ricerca letterale: risultano tutti usati). Nuovo metodo `private normalizePrimary(array &$postdata)`
usato da `hook_before_add` e `hook_before_edit` (stessa espressione
`$postdata['primary'] == "primary" ? 1 : 0`, confronto lasco; nomi e firme degli hook
invariati; tolto il commento `//dd`).

**1b. `access()` / `tenant()`.** Mantengono nome, firma e controllo `isSuperadmin` con
redirect `denied_access` per primi, poi chiamano il nuovo `private authorizationPageData($item_id, $alert_id, array $cfg)`.
Query, `find`, `select`, tipi di campo (`item_access_datamodal`/`item_tenant_datamodal`),
percorsi, `command` e `button_addmore` sono identici. Ogni testo scritto a mano è ora
`trans('crudbooster.*')`:
- chiavi riusate: `name`, `description`, `qlik_warning` ("Warning!"/"Attenzione!"), `adm_remove`;
- 8 chiavi nuove in en e it: `chatai_authorize_group`, `chatai_authorize_tenant`,
  `chatai_select_element_to_add`, `chatai_group_description_placeholder`,
  `chatai_tenant_description_placeholder`, `chatai_authorized_tenants`,
  `chatai_tooltip_return`, `chatai_tooltip_main_module` (inglese identico al vecchio testo).

Il datamodal usa ora `trans('name')` anche per `datamodal_columns_alias` (prima `'Name'`):
il reviewer ha verificato che l'alias viaggia solo come `columns_name_alias` nell'URL di
modal-data e diventa solo l'intestazione di colonna in `datamodal_relation/browser.blade.php`,
non un identificatore.

Viste: tradotti i tre tooltip (Remove → `adm_remove`, Return, Main Module) in entrambe;
in `tenant.blade.php` anche l'`<h4>` "Authorized Tenants". Scostamento dal piano:
la vista non era la gemella perfetta (`access` usa già `__('crudbooster.authorized')`).

**1c. `content_view()`.** `$_GET['m']` sostituito da
`request()->query->has('m') ? Menu::find(request()->query('m')) : Menu::activeDashboard()->first()`
(stessa semantica di `isset`, verificata anche per `?m=` vuoto con `ConvertEmptyStringsToNull`
e per `?m[]=`). Frame, `target_layout` e dati di pagina in `private frameData(ChatAIConf $row, $menu)`
(secondo parametro senza tipo di proposito). Ordine dei controlli e viste invariati.
Nessuna modifica a `AdminQlikItemsController` (D1).

**Test nuovo** `tests/Feature/ChatAIPagesAndContentViewTest.php`: access e tenant da
superadmin con elenco solo della conf; alert con `/alert/1` e non con `/alert/2` o assente;
non superadmin `denied_access`; conf inesistente = pagina 200 con elenco vuoto (nessuna guardia
nuova: le viste non usano `$chat_ai`); soft-deleted; testi in en e it (data provider);
`content_view` con `?m=` assente/vuoto/inesistente/valido, `target_layout=1`, dashboard attiva,
conf non visibile, conf inesistente.

Nessuna rotta nuova, nessuna migrazione.

## Motivazione

Elimina duplicazione e codice morto, porta le pagine alla regola i18n (testo UI sempre in
en e it) e toglie l'accesso diretto a `$_GET` da `content_view`. Il test nuovo fissa il
comportamento di pagine finora scoperte. Alternativa scartata (D1): trait condiviso con
`AdminQlikItemsController`, per ~12 righe in comune e un menu risolto in modo diverso, a
fronte del rischio di toccare codice in uso presso i clienti.

## Test

Verificato davvero:
- `php -l` ok su controller, file lang, test.
- Le due viste Blade compilate con il compilatore reale + `php -l`: ok.
- Pint `--test`: PASS sul test nuovo; su controller e lang segnala solo stile storico non toccato.
- `route:list --path=chat_ai`: nessuna rotta nuova.
- Review di `ch-reviewer` (incluso i18n e il controllo dell'alias del datamodal).

**Eseguiti il 2026-10-30 su richiesta della persona: 70 test verdi.** `ChatAIPagesAndContentViewTest`
19 (102 asserzioni) e la regressione 51 (184 asserzioni): `ChatAIPrimaryAndRedirectsTest` (11, hook
primary), `ChatAIAuthorizationTest` (28), `ChatAIDeletedConfTest` (4), `ChatAISendMessageAgentTest` (8).

Il **primo run del test nuovo dava 17 rossi su 19**, per due difetti del setup del test (non del
codice del controller), corretti: (1) la licenza simulata conteneva solo `modules`, ma le pagine
`access`/`tenant` rendono l'admin template completo, che include `license_modal.blade.php` e legge
`$license['domain']` e le altre chiavi (500 `Undefined array key "domain"`); la licenza simulata ora
ha tutte le chiavi; (2) la conf del test non aveva `url`/`token`, ma `chat_ai.view` chiama
`ChatAIHelper::getToken()` (500 `Undefined array key "scheme"`); la conf ora ha `url` e `token` validi.
Dopo la correzione, 19/19.

**Non verificato**: che i test sul comportamento fossero verdi anche sul codice precedente al
refactoring (non rieseguiti su HEAD).

## Rischi e note

1. Test eseguiti: 70 verdi (vedi sopra), dopo due correzioni al setup del test nuovo.
2. **Il test soft-deleted fissa un comportamento PROBABILMENTE SCORRETTO**: gruppi e tenant
   cancellati compaiono nell'elenco degli autorizzati e nel popup (query `DB::table` con join
   senza `deleted_at`; `groups`, `tenants` e le pivot hanno `deleted_at`; il datamodal lato
   Qlik non filtra i gruppi cancellati, mentre `group_tenant_datamodal` filtra i tenant).
   Follow-up, non corretto: il test va rovesciato quando lo si corregge.
3. **Cambio visibile**: in italiano i testi delle pagine sono tradotti e l'intestazione
   colonna del popup datamodal è ora "Nome" (alias tradotto, verificato innocuo). L'alias
   `Name` resta hardcoded in inglese in `AdminQlikItemsController`, `AdminGroupsController`,
   `AdminTenantsController`, `AdminCmsUsersController` (follow-up i18n, fuori perimetro).
4. I test fanno più richieste nello stesso test: la flakiness per stato statico degli helper
   CRUDBooster non si è manifestata nell'esecuzione (una sola run: non è una prova).
5. Il test popola anche `$_GET['m']` per compatibilità con il codice vecchio (oggi non serve).
6. I test di caratterizzazione sul comportamento dovrebbero essere verdi anche sul codice
   precedente, quelli sui testi tradotti no (non verificato, non rieseguiti su HEAD).
7. `qlik_warning` è riusata in un contesto ChatAI: il prefisso può sorprendere.
8. Il diff del controller nel working tree contiene anche modifiche non committate di altri
   interventi (doc 323, 329, 330, 331): non sono attribuibili al 332.
9. Fuori perimetro, emersi dal reviewer nei file lang: la chiave
   `'your_file_extension_is_not_allowed '` ha uno spazio finale in en e non in it; la chiave
   `Generate_Screet_Key` è duplicata in it; i segnaposto di
   `statistic_builder_extra_series_name`/`_color` differiscono tra en e it; il confronto dei
   segnaposto si è interrotto su una chiave con valore array (controllo parziale).
10. ChatAI non è attivo in produzione (doc 300): rischio per i clienti ridotto.
11. **Rimandati** (dal piano): `getEdit`/`getDetail` con `findActiveRow`, `getConf()`,
    `isSuperadmin` ripetuto 6 volte, TODO di `hook_query_index`, e il **batch 2**
    (Form Request per `send_message*`, con cambio visibile D2: JSON 422).

## Rollback

Nessuna migrazione da annullare. Ripristinare da git `AdminChatAIController.php` (attenzione:
contiene anche modifiche non committate di altri interventi, quindi ripristinare solo i metodi
`hook_before_add`/`hook_before_edit`, `access`, `tenant`, `content_view` e rimuovere
`normalizePrimary`, `authorizationPageData`, `frameData` rimettendo `use GroupHelper;`),
togliere le 8 chiavi `chatai_*` nuove da `resources/lang/en|it/crudbooster.php`, ripristinare
`resources/views/chat_ai/access.blade.php` e `tenant.blade.php`, cancellare
`tests/Feature/ChatAIPagesAndContentViewTest.php`.
