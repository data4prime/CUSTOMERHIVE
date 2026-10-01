# 190 - Qlik items: "Descrizione", stato "presente" e data ultimo import

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminQlikItemsController.php`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Stesso trattamento della lista app (186) per la lista `admin/qlik_items`. Con
la sync (189) il campo `subtitle` contiene la descrizione del foglio Qlik.

## Situazione prima

Colonna "Subtitle" (testo intero), form con limite 70 caratteri, "Stato Qlik"
con solo il badge "non piu' presente", nessuna data di ultimo import.

## Situazione dopo

- "Subtitle" diventa **Descrizione** (lista, form, placeholder; campo DB
  `subtitle` invariato). In lista e' troncata a 80 caratteri con "…" e il
  testo completo nel tooltip.
- Il limite del form passa da 70 a 255 (lunghezza della colonna e di cio' che
  scrive la sync): con 70, modificare un item sincronizzato con descrizione
  piu' lunga avrebbe fallito la validazione.
- "Stato Qlik": badge verde "Presente su Qlik" per gli item sincronizzati
  almeno una volta, arancione "Non piu' su Qlik" se mancante, vuoto per quelli
  creati a mano. Nuova colonna "Ultimo import" (`d/m/Y H:i`).
- Nuove chiavi `qlik_item_description` e `qlik_item_description_placeholder`
  in en/it.

- **Fix placeholder**: il testo italiano conteneva un apice ("dell'item") e
  il componente `text` lo scrive dentro `placeholder='...'` con apici singoli,
  quindi il placeholder veniva troncato; tolto l'apice (e nota: nei
  placeholder dei form CRUDBooster non usare apici).
- **Campo "App Qlik"** nel form (add/edit): select2 su `qlik_apps.appname`,
  ricercabile e in ordine alfabetico, facoltativo. Un select2 vuoto verrebbe
  salvato come 0: `hook_before_add/edit` lo normalizzano a NULL. Per gli item
  sincronizzati (con `external_id`) `hook_before_edit` ignora la scelta e
  mantiene l'app attuale (la chiave della sync e' app + id foglio; cambiarla
  creerebbe un duplicato). `showInDetail=false` perche' il componente di
  dettaglio non regge un valore vuoto e il blocco app in
  `qlik_items/form.blade.php` lo mostra gia'. In modifica quel blocco ora
  mostra solo id foglio e badge "mancante" (l'app e' nel campo).
- Le app non sono filtrate per la configurazione scelta nello stesso form.
- **Descrizione senza limite**: il campo del form diventa `textarea` senza
  `max` e la colonna `qlik_items.subtitle` passa da VARCHAR(255) a TEXT
  (migrazione `2026_10_01_110000_change_qlik_items_subtitle_to_text`, con
  `down` che torna a 255 troncando); la sync non taglia piu' la descrizione a
  255. Il limite 255 dichiarato sopra e' quindi superato. In lista resta
  troncata a 80 caratteri.

## Motivazione

Coerenza con le app e informazioni utili a decidere se risincronizzare.

## Test

`php -l`; colonna DB `subtitle` verificata VARCHAR(255). Non provato a browser.

## Rischi e note

Il titolo ha ancora limite 70 nel form mentre la sync ne scrive fino a 255:
stesso problema potenziale sui titoli lunghi, non toccato qui.

## Rollback

Ripristinare le colonne e il form in `AdminQlikItemsController::cbInit` e
rimuovere le due chiavi.
