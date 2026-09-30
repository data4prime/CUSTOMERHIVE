# 158 - `DatasetAccessScope`: nucleo di sicurezza del widget "Elenco record" (Fase 1)

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Sicurezza / Dashboard
- **File/aree di codice coinvolte**:
  - `app/Dashboards/DatasetAccessScope.php` (nuovo)
  - `app/Http/Controllers/System/CBController.php` (`getModalData()`)
  - piano: `docs/piano-widget-tabella-elenco-record.md`

## Contesto

Fase 1 del piano del widget Tabella con configurazione guidata "Elenco
record". Il widget mostrera' righe vere (non aggregati) di tabelle `mg_*`
dentro una dashboard, quindi serve un controllo unico su *chi puo' leggere
una tabella* e *quali righe vede*. `CRUDBooster::isRead()` non e'
utilizzabile: legge il modulo corrente, che nel widget e'
`statistic_builder`.

## Situazione prima

Lo scoping per tenant/gruppo/righe create da tenant admin esisteva solo
inline in `CBController::getModalData()` (intervento 070), non
riutilizzabile. Nessun controllo "questo ruolo puo' leggere questa tabella"
fuori dal modulo corrente.

## Situazione dopo

Nuova classe `App\Dashboards\DatasetAccessScope`:

- `canRead($table)`: false se la tabella non e' `mg_*`; true per il
  superadmin; altrimenti true solo se esiste un modulo con quella
  `table_name`, `is_active = 1`, `deleted_at IS NULL` e una riga in
  `cms_privileges_roles` con `is_read = 1` per `CRUDBooster::myPrivilegeId()`.
- `applyRowScope($query, $table)`: il blocco tenant/gruppi/`created_by`
  spostato da `getModalData()`, stesse condizioni nello stesso ordine.

Differenza rispetto al piano: `applyRowScope()` **non** applica
`deleted_at IS NULL` (nel piano era inclusa). In `getModalData()` quel
filtro e' applicato prima dello scoping, con `q`; lasciarlo al chiamante
mantiene identico il SQL generato. `executeRows()` (Fase 2) lo aggiungera'
da se'.

`getModalData()` ora chiama `DatasetAccessScope::applyRowScope()`.

## Motivazione

Un'unica implementazione della regola di visibilita' righe, invece di
duplicarla nel widget (rischio di divergenza su codice di sicurezza).

## Test

Verifica manuale (nessuna suite lanciata), script temporaneo nel
container Docker locale: per ognuno dei 5 utenti locali (superadmin +
4 non superadmin) e per ogni tabella `mg_*` piu' `cms_users`, confrontato
vecchio blocco inline e nuovo helper su `toSql()`, bindings e `count()`.
Risultato: 25 confronti, tutti identici. `php -l` ok su entrambi i file.

`canRead()` verificata per gli stessi utenti: superadmin vero su tutte le
`mg_*`; gli altri solo su `mg_fatture`/`mg_contratti`; falso per
`cms_users` e tabelle inesistenti.

Limite: i 4 utenti non superadmin locali hanno 0 righe visibili in ogni
tabella (i dati di prova sono del superadmin), quindi il confronto sul
numero di righe per loro e' debole; l'uguaglianza del SQL e' la prova
forte. I test automatici per ruolo (tenant A/B, con/senza gruppo) sono
previsti in Fase 5.

## Rischi e note

- Tocca `getModalData()` (produzione), ma la query generata e' identica.
- Il superadmin ha `canRead` true su qualunque `mg_*` anche senza modulo
  attivo: coerente con `can_view()` (il superadmin vede tutto).
- Un modulo esiste a volte con due righe (es. `mg_fatture`, id 20 e 22):
  basta una riga valida (decisione del piano).

## Rollback

Ripristinare in `getModalData()` il blocco inline (vedi 070) e rimuovere
`DatasetAccessScope.php`.
