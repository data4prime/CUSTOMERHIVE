# 269 - Import dati: abbinamento automatico intestazioni/colonne

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Lista / Import
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`getImportData`, nuovo `autoMapImportColumns`)
  - `resources/views/crudbooster/import.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (`import_auto_mapped`, `import_auto_mapped_summary`)

## Contesto

Nel passo 2 dell'import (corrispondenza) ogni colonna del modulo partiva su
"Non importare questa colonna" e andava scelta a mano.

## Situazione prima

Nessun suggerimento: tutte le select vuote.

## Situazione dopo

Le select sono preselezionate quando l'intestazione del file somiglia alla
colonna. Il confronto ignora maiuscole, accenti, spazi e simboli e usa il nome
della colonna piu' le etichette del modulo (form e lista). Ordine: uguali,
una contiene l'altra (almeno 4 caratteri), molto simili (>= 80%). Ogni
intestazione si usa una sola volta (si assegnano prima i punteggi piu' alti).
Le colonne abbinate hanno una bacchetta magica con tooltip e il riepilogo dice
quante sono state abbinate. Tutto resta modificabile.

Dalla pagina di corrispondenza sono escluse anche `created_by`, `updated_by` e
`deleted_by` (oltre a `id`, `created_at`, `updated_at`, `deleted_at`),
indipendentemente dal contenuto del file: sono colonne di sistema.

## Motivazione

Meno lavoro manuale, nessun cambio al salvataggio: il submit e' invariato.

## Test

`php -l`. Non provato con un file reale.

## Rischi e note

Euristica, non intelligenza semantica: sinonimi lontani ("Cliente" /
"Ragione sociale") non vengono abbinati. Si puo' aggiungere un dizionario di
sinonimi o un abbinamento via AI in seguito.

## Rollback

Ripristinare i tre file (nessuna migrazione).
