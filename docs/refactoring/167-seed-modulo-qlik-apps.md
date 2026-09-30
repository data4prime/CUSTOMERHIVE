# 167 - Seed del modulo "Qlik Apps" in cms_moduls

- **Data**: 2026-09-30
- **Stato**: Completato
- **Area**: Database / Seeder
- **File/aree di codice coinvolte**:
  - `database/seeders/CmsModulsSeeder.php`
  - tabella `cms_moduls` (DB locale, riga inserita a mano)

## Contesto

`http://localhost:8080/admin/qlik_apps` rispondeva 404 sul DB Docker locale.
In CRUDBooster le rotte `/admin/<path>` dei moduli sono generate a runtime
dalle righe di `cms_moduls`: senza riga non c'è rotta, anche se controller
(`QlikAppController`) e tabella (`qlik_apps`) esistono.

## Situazione prima

Nessuna migrazione e nessun seeder creava il modulo "Qlik Apps": le
migrazioni creano solo la tabella `qlik_apps` (e `qlikapps_tenants`/
`qlikapps_groups`), `CmsModulsSeeder` seminava solo "Qlik Items" e "Qlik
Configuration". Sulle installazioni esistenti il modulo era stato creato a
mano da Module Generator; su un DB nuovo (migrate + seed) non esisteva.
`ModuleHelperSeeder` ha invece già la voce di help "Qlik Apps".

## Situazione dopo

- `CmsModulsSeeder` semina anche "Qlik Apps" (`path` `qlik_apps`,
  `table_name` `qlik_apps`, controller `QlikAppController`,
  `is_protected` 0 come "Qlik Items", `is_active` 1). Il seeder salta già le
  righe con lo stesso `name`, quindi è idempotente.
- Nel DB locale la riga è stata inserita direttamente (id 37), con
  `INSERT ... WHERE NOT EXISTS` sul nome.

## Motivazione

I DB creati da zero devono avere il modulo senza passare dal Module
Generator. `is_protected` 0 allineato a "Qlik Items" perché `ModuleHelper`
gestisce già `qlik_apps` per la visibilità dei tenant admin.

## Test

- `php -l` sul seeder (nel container): nessun errore.
- `curl http://localhost:8080/admin/qlik_apps` ora risponde 302 verso
  `/admin/login` (prima 404), cioè la rotta esiste.
- Non verificata la pagina da loggati né un seed completo su DB vuoto.

## Rischi e note

- Comportamento visibile cambiato solo per i DB nuovi (nuovo modulo).
- I permessi per ruolo (`cms_privileges_roles`) non sono assegnati dal
  seeder di questo modulo: il super admin vede il modulo, gli altri ruoli
  vanno abilitati da Privileges.
- Sulle installazioni esistenti il seeder non duplica nulla se il nome
  "Qlik Apps" coincide; se in produzione il modulo ha un nome diverso,
  rieseguire `CmsModulsSeeder` ne creerebbe uno secondo.

## Rollback

Togliere il blocco "Qlik Apps" da `CmsModulsSeeder.php`; nel DB locale
`DELETE FROM cms_moduls WHERE path = 'qlik_apps'`.
