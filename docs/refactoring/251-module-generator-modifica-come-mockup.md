# 251 - Module Generator: modifica modulo come nel mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale; salvataggio non provato)
- **Area**: Frontend / Generatore moduli
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/edit.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Seguito di 250. In `admin/module_generator/edit/{id}` il link di ritorno era un testo
sottolineato e la matrice "Enable/Disable Module on Tenants" aveva checkbox quasi
invisibili in una tabella con bordi, a differenza del mockup (matrice con colonna "tutti").

## Situazione prima

Link di ritorno come testo; matrice `table-bordered` con `<input type=checkbox>` nativi e
etichetta inglese scritta nella vista.

## Situazione dopo

- Link di ritorno: markup standard `<p><a><i chevron></i>&nbsp; testo</a></p>` (la pillola
  lilla la dà il CSS), lo stesso di `default/form.blade.php`. Correzione fatta lo stesso
  giorno: in 245 (`qlik_sync/run`) e 250 (`module_generator/detail`) avevo usato
  `btn btn-secondary btn-sm` (pulsante bianco, diverso dalle altre pagine); ripristinato il
  markup standard anche lì e uniformati `module_generator/enable` e `import` (prima link nudo
  senza icona).
- Matrice in `rel-table` con checkbox standard (`partials/ch_check`), una colonna per tenant
  e una colonna "Tutti" a inizio riga che seleziona/deseleziona tutti i tenant e si
  riallinea quando cambia un singolo tenant. Etichetta tradotta (`mg_enable_on_tenants`,
  nuova `mg_all_tenants`).
- Invariati: campi del form (Name, Table Name, Icon, Path, Controller), nome dei campi
  della matrice (`module_tenant_enabler[modulo][tenant] = 1`), pulsanti Annulla/Salva,
  `postEditSave` e `update_enabled_tenants`. La checkbox "Tutti" non ha `name`, quindi non
  viene inviata.

## Motivazione

Allineamento al mockup e matrice leggibile (prima le spunte quasi non si vedevano).

## Test

A vista su `module_generator/edit/38`: matrice con "Tutti" + 5 tenant; "Tutti" spunta tutti, togliendone uno "Tutti" si toglie.
Non provato il salvataggio (per non scrivere in `module_tenants`) né l'aspetto con tenant già
attivi, né il tema scuro. `php -l` sui file lingua.

## Rischi e note

Il selettore icone e gli altri campi restano quelli di prima (già in stile attuale).
`module_generator/enable` (impostazioni moduli) ha ancora la sua matrice vecchia: a parte.

## Rollback

Ripristinare `edit.blade.php` dalla versione precedente (git) e rimuovere `mg_all_tenants`.
