# 191 - Menu SUPERADMIN: un solo gruppo Qlik

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/sidebar.blade.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Nella sezione SUPERADMIN c'erano due gruppi Qlik separati ("Elementi Qlik" e
"Qlik Settings") anche se gli oggetti sono in cascata (configurazione >
app > elementi).

## Situazione prima

- "Elementi Qlik": Aggiungi nuovo elemento / Lista Elementi Qlik.
- "Qlik Settings" (etichetta non tradotta): Configurazione Qlik / App Qlik,
  con frecce di espansione sui sotto-link e condizioni `active` copiate da
  `qlik_items` (le voci si illuminavano sulle pagine sbagliate).
- Le sincronizzazioni raggiungibili solo dai pulsanti nelle liste.

## Situazione dopo

Un unico gruppo **Qlik** (sempre solo con il modulo Qlik in licenza), in
ordine di gerarchia: Configurazioni, App, Elementi, Sincronizzazioni (questa
solo per il superadmin, come la pagina stessa). Ogni voce e' attiva solo sulle
sue pagine (le sincronizzazioni, sotto `qlik_apps/sync-runs`, non accendono
"App"). Spariti "Aggiungi nuovo elemento" (c'e' il pulsante nella lista) e le
frecce. Nuove chiavi `qlik_menu_group|confs|apps|items|syncs` in en/it; le
vecchie `Qlik_Items`, `Qlik_Configuration`, `Qlik_Apps` restano (usate altrove).

## Motivazione

Struttura piu' chiara e coerente con la gerarchia dei dati.

## Test

Blade compilato (`view:cache`) senza errori. Non provato a browser.

## Rischi e note

Il menu e' hardcoded e comune a tutti i clienti: la modifica e' visibile a tutti
quelli con il modulo Qlik. I permessi di visibilita' non cambiano (stessa
condizione del blocco SUPERADMIN, sincronizzazioni solo superadmin). I link
"Aggiungi nuovo elemento" non esistono piu' nel menu; l'URL `qlik_items/add`
funziona come prima.

## Rollback

Ripristinare i due gruppi in `sidebar.blade.php` (git) e togliere le chiavi.
