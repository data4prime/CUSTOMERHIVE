# 262 - Gestione Menu: testi tradotti (en/it)

- **Data**: 2026-10-07
- **Stato**: Completato (non provato a vista nel browser)
- **Area**: Menu / i18n
- **File/aree di codice coinvolte**: `MenusController` (label, opzioni radio, help, placeholder, titoli di pagina, placeholder JS), `MenuHelper::menu_to_html` (tooltip), `resources/lang/{en,it}/crudbooster.php` (chiavi `mm_*`)

## Contesto

La pagina `/admin/menu_management` e il form di modifica avevano molti testi scritti in inglese (regola del progetto: tutto con `trans()`).

## Situazione prima

Etichette dei campi, opzioni (Active/InActive, Yes/No, tipi, layout), help, placeholder, titoli pagina e tooltip Edit/Delete/Dashboard in inglese fisso.

## Situazione dopo

Tutti tradotti via chiavi `mm_*` in en e it. I valori salvati non cambiano (le opzioni usano `valore|etichetta`: es. `Module|Modulo`). Restano invariati i nomi di prodotto (Qlik, Agent AI).

## Test

`php -l`, verifica delle chiavi con `trans()`. Test automatici non eseguiti.

## Rischi e note

Etichette in italiano diverse da prima (es. "Privileges" -> "Ruoli", "Custom Icon" -> "Origine icona").

## Rollback

Ripristinare `MenusController` e `MenuHelper`.
