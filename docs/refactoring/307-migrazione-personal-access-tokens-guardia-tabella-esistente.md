# 307 - Migrazione `personal_access_tokens`: non fallire se la tabella esiste già

- **Data**: 2026-10-28
- **Stato**: Completato (verificato su un dump reale di un cliente; suite di test non rilanciata)
- **Area**: Database / Aggiornamento clienti
- **File/aree di codice coinvolte**:
  - `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`

## Contesto

Prima "prova generale" di aggiornamento di un cliente rimasto indietro: dump del DB di un'istanza ferma a novembre 2024 (ultima migrazione
applicata `2024_11_19_110607_add_column_to_cms_apicustom_table`, 106 migrazioni), attaccato a un worktree isolato del codice di `dev`
(stack Docker separato, `.env` con la `APP_KEY` del cliente, controller e `storage/` del cliente copiati). Le migrazioni mancanti erano 37.

## Situazione prima

`php artisan migrate` si fermava alla prima migrazione pendente:

```
2019_12_14_000001_create_personal_access_tokens_table ... FAIL
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'personal_access_tokens' already exists
```

Nel DB del cliente la tabella esiste già (stesse colonne della migrazione) ma la migrazione non è registrata nella tabella `migrations`:
la tabella è stata creata in altro modo (probabilmente da un pacchetto/versione precedente). `Schema::create` non ha guardia, quindi
l'aggiornamento di quel cliente non sarebbe andato oltre. Le altre 36 migrazioni, una volta superata questa, passavano tutte.

## Situazione dopo

La migrazione esce subito (`return`) se `Schema::hasTable('personal_access_tokens')`; altrimenti crea la tabella come prima. Nessun
cambiamento di comportamento sui DB in cui la tabella non c'è (installazioni nuove, test su DB vuoto). `down()` invariato.

## Motivazione

Un aggiornamento in produzione non deve fermarsi per una tabella che c'è già con la struttura giusta. La guardia è la correzione minima e
behavior-preserving; alternativa scartata: registrare a mano la migrazione nel DB di ogni cliente (da ricordarsi, non ripetibile).

## Test

Prova eseguita sul worktree isolato (nulla toccato nello stack di sviluppo):

1. Primo giro senza correzione: fallimento come sopra; la migrazione è stata segnata a mano nel DB di prova per vedere il resto, e le
   altre 36 sono passate.
2. DB ricreato e dump reimportato (106 migrazioni, 62 tabelle, 7 utenti), poi `migrate` con la correzione: **37 migrazioni eseguite
   senza errori**, 142 registrate, 70 tabelle. Un secondo `migrate`: «Nothing to migrate».
3. Dati invariati prima/dopo: `cms_users` 7, `cms_moduls` 46, `cms_menus` 28, `cms_apicustom` 8; `cms_settings` 16 → 17 (nuova chiave
   `email_enabled`); `cms_email_queues` eliminata (era vuota). `route:list` carica 1011 rotte inclusi i controller del cliente;
   `/admin/login` risponde 200.

**Non verificato**: login e uso del pannello sul DB migrato (a carico di chi ha le credenziali del cliente; la licenza nel dump è quella
di un altro dominio), suite di test automatici dopo la modifica.

## Rischi e note

- Se una tabella `personal_access_tokens` preesistente avesse una struttura diversa, la guardia la lascerebbe com'è. Nel caso provato le
  colonne coincidono; il pacchetto Sanctum non è usato dall'app per emettere token, quindi il rischio pratico è basso.
- Altre migrazioni potrebbero avere lo stesso problema su altri clienti: il giro di prova va ripetuto con dump di altre istanze.

## Rollback

Ripristinare il file della migrazione al commit precedente. Nessuna modifica ai dati o allo schema.
