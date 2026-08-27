# 009 - Modulo "Module Helpers" senza riga in cms_moduls (404)

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Bug fix / dati
- **File/aree di codice coinvolte**:
  - `database/seeders/CmsModulsSeeder.php`
  - Dati: tabelle `cms_moduls` e `module_helpers` (dev)

## Contesto

Segnalato dall'utente: `/admin/module_helpers` ritornava 404. Già notato
durante [006](006-controller-sistema-app-http-controllers-system.md) che
`AdminModuleHelperController` non compariva in nessuna rotta registrata,
ma all'epoca non era chiaro se fosse un effetto collaterale dello
spostamento dei controller o un problema preesistente — confermato qui
essere preesistente.

## Situazione prima

Il routing dei moduli "di sistema" dipende interamente da una riga in
`cms_moduls` (vedi [006](006-controller-sistema-app-http-controllers-system.md)
per il dettaglio del meccanismo) — senza quella riga, nessuna rotta viene
mai generata per il controller, a prescindere da dove viva il file.
`AdminModuleHelperController` non aveva **nessuna riga** in `cms_moduls`,
né una voce di menu. `database/seeders/CmsModulsSeeder.php` (che popola
`cms_moduls` per le installazioni pulite) non includeva questo modulo
nell'elenco — quindi il problema non era solo nel DB di sviluppo, ma
anche nel seeder: qualunque nuova installazione avrebbe avuto lo stesso
404.

Trovato anche un secondo seeder collegato,
`database/seeders/ModuleHelperSeeder.php`, che popola la tabella
`module_helpers` (il link di aiuto per-modulo mostrato nell'header
admin) cercando ogni modulo **per nome** in `cms_moduls` — cerca già
esplicitamente `"Module Helpers"`, ma senza la riga in `cms_moduls` quella
ricerca non trovava nulla, quindi nemmeno il modulo "Module Helpers"
aveva il proprio link di aiuto.

## Situazione dopo

- Aggiunta la riga mancante a `CmsModulsSeeder.php` (`name` =
  `"Module Helpers"` — deve combaciare esattamente con quanto cercato da
  `ModuleHelperSeeder`), `path` = `module_helpers`, `controller` =
  `AdminModuleHelperController`, `table_name` = `module_helpers`,
  `is_protected` = 1 (come gli altri moduli di sistema).
- Seeder rieseguito sul DB di sviluppo (idempotente: nell'array c'è già
  un controllo che salta i nomi già presenti, quindi ha inserito solo la
  riga nuova).
- Rieseguito anche `ModuleHelperSeeder` (anch'esso idempotente), che ora
  trova la riga e crea il proprio link di aiuto in `module_helpers`.

## Motivazione

Senza la riga nel seeder, il gap si sarebbe ripresentato su ogni nuova
installazione pulita, non solo qui.

## Test

- `php -l` sul seeder modificato: nessun errore.
- Eseguito `php artisan db:seed --class=Cms_modulsSeeder` sul DB di
  sviluppo: verificato con una query diretta che la riga esista
  (`id=17`, `controller=AdminModuleHelperController`).
- `php artisan route:list --path=admin/module_helpers`: ora mostra tutte
  le rotte generate per il modulo.
- `curl` autenticato su `/admin/module_helpers`: 200 (prima 404), pagina
  con la tabella renderizzata.
- Eseguito anche `\ModuleHelperSeeder` (classe senza namespace, va
  invocata con un backslash iniziale per bypassare il prefisso
  `Database\Seeders\` che `db:seed` aggiunge di default): verificato che
  la riga di aiuto per "Module Helpers" sia stata creata e che il link
  "Helper" nell'header admin la usi davvero.

## Rischi e note

`ModuleHelperSeeder.php` non ha una dichiarazione `namespace` (a
differenza di `CmsModulsSeeder.php`) — funziona comunque perché
`database/` è nel classmap di Composer, ma va invocato con
`--class='\ModuleHelperSeeder'` (backslash iniziale), non
`--class=ModuleHelperSeeder`. Non toccato in questo intervento (fuori
scope), solo notato per chi dovesse rieseguirlo in futuro.

## Rollback

`git revert` del commit per il seeder. Per rimuovere i dati inseriti:
`DELETE FROM cms_moduls WHERE name='Module Helpers'` e
`DELETE FROM module_helpers WHERE id_cms_moduls=<id>` (cancella anche le
righe reali che un utente avesse nel frattempo creato tramite quel
modulo, quindi da fare solo se non è mai stato usato).
