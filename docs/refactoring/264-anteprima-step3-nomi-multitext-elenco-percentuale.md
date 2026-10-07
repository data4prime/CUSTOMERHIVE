# 264 - Anteprima step 3 con nomi, multitext come elenco, simbolo % sulle percentuali

- **Data**: 2026-10-07
- **Stato**: Completato (non provato a vista nel browser)
- **Area**: Module generator / Dettaglio / Liste
- **File/aree di codice coinvolte**: `module_generator/step3_v2.blade.php` (`sample`), `type_components/multitext/component_detail`, `type_components/percent/component_detail`, `ModuleGeneratorList::formatNumericField`

## Situazione prima

L'anteprima dello step 3 mostrava numeri per `created_by`/`tenant`/`group`; il dettaglio di "Piu' testi" mostrava la stringa con i `|`; le percentuali in lista/dettaglio senza simbolo.

## Situazione dopo

- Anteprima: nomi di esempio per le colonne di sistema (come la lista reale).
- Dettaglio multitext: elenco puntato (valori separati da `|`, vuoti scartati).
- Percentuali: `12,5%` in dettaglio e in lista (separatore secondo le Preferenze).

## Test

`php -l`, `view:cache`. Test automatici non eseguiti.

## Rollback

Ripristinare i file elencati.
