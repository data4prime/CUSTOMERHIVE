# 260 - Formato data utente, colonna titolo in lista, tabelle proposte nel wizard

- **Data**: 2026-10-07
- **Stato**: Completato (non committato, non provato a vista nel browser)
- **Area**: Module generator / Liste e dettagli / Profilo
- **File/aree di codice coinvolte**: `app/Helpers/DateFormat.php` (nuovo), migrazione `2026_10_07_120000_add_date_format_to_cms_users`, `CBController::getIndex`, `ModuleGeneratorList::formatValue`, `type_components/{date,datetime}/component_detail`, `AdminCmsUsersController::postProfilePreferences`, `users/profile.blade.php`, `ModuleGeneratorFields::selectableTables`, `ModulsController` (step 2/3), `step3_v2.blade.php`, lang en/it

## Contesto

Rifiniture richieste: "Tabella collegata" solo dove ha senso; formato data personale;
colonna titolo (passo 5) evidenziata e cliccabile in lista; meno tabelle nel menu "Quale tabella?".

## Situazione prima

Pulsante "Tabella collegata" su ogni colonna normale; date in lista come arrivavano dal DB
(o `d/m/Y` col formato wizard); dettaglio date con nome del mese (`strftime`); titolo non
distinto in lista; select tabelle con tutte le tabelle del DB.

## Situazione dopo

- "Tabella collegata" solo per colonne intere (int/bigint/smallint/mediumint) o con collegamento gia' impostato.
- Preferenze > **Formato data** (5 formati: `d/m/Y`, `d-m-Y`, `Y-m-d`, `m/d/Y`, `d.m.Y`, default `d/m/Y`), colonna `cms_users.date_format`. Vale per colonne date/datetime in lista (senza format/callback), formati wizard `date_short`/`datetime_short` e dettaglio date/datetime (con ora HH:MM). `date_long` invariato.
- Moduli creati dal module generator: la colonna `title_field` e' in grassetto e porta al dettaglio (se l'utente puo' leggere e il pulsante dettaglio e' attivo). I moduli di sistema non cambiano.
- "Quale tabella?" (passo Campi) e select di collegamento (passo Lista): solo tabelle dei moduli/utente piu' `cms_users`, `tenants`, `groups`; nascoste `cms_*`, `mfa_*`, `qlik*`, `chat_ai/chatai_*`, `dashboard_*`, `module_*`, `menu_*`, `items_*`, `sync_*`, code/log/licenza/cache, tabelle di collegamento. Le tabelle gia' scelte restano. La validazione server non cambia (accetta ancora tutte).

## Test

`php -l`, migrazione locale, `view:cache`, smoke test di `DateFormat` e `selectableTables`. Test automatici NON eseguiti.

## Rischi e note

- Cambio visibile: date in lista/dettaglio con il formato scelto (default `d/m/Y`, prima nel dettaglio "dicembre, 25 2026").
- Colonne titolo in grassetto/link solo se la colonna e' semplice (no join/callback gia' link).
- La tabella `log` e' nascosta, `mg_log` no (prefisso modulo).

## Rollback

Ripristinare i file; la colonna `date_format` e' innocua.
