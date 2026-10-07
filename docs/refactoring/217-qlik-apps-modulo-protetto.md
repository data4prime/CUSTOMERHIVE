# 217 - "Qlik Apps" come modulo protetto (nascosto dal Module Generator)

- **Data**: 2026-10-05
- **Stato**: Completato
- **Area**: Module generator / Qlik
- **File/aree di codice coinvolte**:
  - `database/seeders/CmsModulsSeeder.php`
  - tabella `cms_moduls` (riga "Qlik Apps")

## Contesto

L'intervento 167 ha aggiunto "Qlik Apps" al seed di `cms_moduls` con
`is_protected = 0`. L'elenco di `/admin/module_generator`
(`ModulsController::hook_query_index`) mostra solo i moduli con
`is_protected = 0`, quindi "Qlik Apps" compariva come se fosse un modulo
generato dall'utente.

## Situazione prima

"Qlik Apps" aveva `is_protected = 0`: visibile (ed esportabile/cancellabile)
nel Module Generator, presente nelle schermate privilegi e in
`ModuleHelper::getEditableModules`. "Qlik Configuration" era invece
`is_protected = 1`.

## Situazione dopo

"Qlik Apps" ha `is_protected = 1` nel seed e nel DB locale. Non compare più
nel Module Generator. **Cambio di comportamento visibile**: sparisce anche
dalle schermate di aggiunta/modifica privilegi (`PrivilegesController`) e da
`ModuleHelper::getEditableModules` (tenant admin), e non è più
esportabile/cancellabile dal generator.

## Motivazione

È un modulo di sistema, come "Qlik Configuration": non va gestito dal Module
Generator. Alternativa scartata: filtrare solo l'elenco del generator
lasciando `is_protected = 0`.

## Test

- Seed modificato; `UPDATE` applicato al DB locale (id 37 → `is_protected = 1`).
- Non verificato a vista in browser; suite di test non lanciata.

## Rischi e note

`CmsModulsSeeder` è idempotente per nome: non aggiorna le righe esistenti,
quindi sulle installazioni già presenti serve l'`UPDATE` manuale (vedi
Rollback). Da verificare sui clienti che i tenant admin non dipendano
dall'accesso a "Qlik Apps" tramite ModuleHelper/privilegi.

## Rollback

```sql
UPDATE cms_moduls SET is_protected = 0 WHERE name = 'Qlik Apps' AND path = 'qlik_apps';
```

e rimettere `'is_protected' => 0` nel seed.

Per applicare il cambio su un'installazione esistente:

```sql
UPDATE cms_moduls SET is_protected = 1 WHERE name = 'Qlik Apps' AND path = 'qlik_apps';
```
