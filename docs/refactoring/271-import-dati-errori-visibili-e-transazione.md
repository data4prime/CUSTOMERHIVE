# 271 - Import dati: errori visibili, import tutto-o-niente

- **Data**: 2026-10-07
- **Stato**: Completato
- **Area**: Lista / Import
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/CBController.php` (`postDoImportChunk` riscritto, nuovo `describeImportError`, `getImportData`)
  - `resources/views/crudbooster/import.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `import_err_*`, `import_failed_*`, `import_done_summary`)

## Contesto

Un import e' risultato "completato" senza aver importato nulla.

## Situazione prima

`postDoImportChunk` catturava ogni errore di insert in una cache che la pagina
non mostrava mai, saltava in silenzio le righe con titolo vuoto e rispondeva
sempre `status: true`. La barra di avanzamento usava come totale il numero di
colonne del file invece delle righe. Un errore fatale (500) lasciava la pagina
in attesa senza messaggi.

## Situazione dopo

- L'import gira in una transazione: al primo problema si ferma, annulla tutto
  (nulla viene scritto) e risponde `status: false` con il motivo.
- Il messaggio indica riga del file e colonna (nome nel modulo + intestazione
  del file) e riconosce i casi comuni: valore non valido per il tipo
  (es. data/numero), testo troppo lungo, colonna obbligatoria vuota, fuori
  intervallo, duplicato, colonna inesistente, campo titolo vuoto, chiave vuota,
  tabella collegata non trovata. Altrimenti mostra il testo dell'errore.
- Bloccano anche: file senza righe di dati, nessuna colonna abbinata (sessione
  scaduta), file illeggibile, tutte le righe vuote nelle colonne abbinate.
  Le righe completamente vuote (tipiche in fondo ai fogli Excel) si ignorano
  e si contano.
- La pagina mostra la barra rossa e il motivo (anche per errori 500 di rete/
  server); a fine import mostra il riepilogo "N inserite, M aggiornate, K righe
  vuote ignorate". Il totale per la barra di avanzamento sono ora le righe dati.

## Motivazione

Un import che fallisce deve dirlo e dire dove, senza lasciare dati a meta'.

## Test

`php -l`. Non provato con file reali.

## Rischi e note

Comportamento visibile cambiato: righe con titolo vuoto prima saltate in
silenzio ora fermano l'import. Le date di Excel arrivano come numeri seriali:
su una colonna `date` daranno l'errore "valore non valido" con riga e colonna
(non convertite automaticamente: possibile intervento a parte). Le tabelle
collegate create durante l'import fanno parte della transazione.

## Rollback

Ripristinare i tre file (nessuna migrazione).
