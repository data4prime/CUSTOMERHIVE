# 198 - Module generator: barra dei 5 passi in stile prototipo

- **Data**: 2026-10-02
- **Stato**: Completato
- **Area**: Frontend
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/navigation.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (chiavi `mg_nav_1`..`mg_nav_5` e `_sub`)

## Contesto

Nel prototipo del nuovo module generator (`.scratch/module-generator-prototipo.html`)
i 5 passi sono schede con numero, titolo e sottotitolo. Il wizard reale
mostrava ancora i vecchi link testuali "Step N - ...", in inglese hardcoded e
con l'etichetta attiva solo sottolineata. Vedi 193-197.

## Situazione prima

`navigation.blade.php`: un `<ul class="nav">` con 5 link testuali, testo
inglese fisso, passo attivo sottolineato.

## Situazione dopo

Barra di 5 schede come nel prototipo: cerchio numerato (blu sul passo attivo,
verde con spunta sui passi precedenti), titolo e sottotitolo. Stessi link e
stesso `$active_tab`/`$id` di prima; i testi passano da `trans()` (EN/IT). Vale
per tutti i moduli e a prescindere dal flag `wizard_v2`. Nomi dei passi 3 e 4
resi "Impostazioni lista/form" (come nei vecchi titoli); gli stili sono
inline nella vista con prefisso `mg-`, senza dipendere da nuove librerie.

## Motivazione

Coerenza con il prototipo concordato e indicazione chiara di dove si è nel
percorso. Solo presentazione: nessun cambio di routing o dati.

## Test

Render della vista in Docker per IT ed EN con passo attivo 3: classi
`done`/`active` e testi corretti. Non verificato nel browser (aspetto grafico,
larghezze su schermi stretti).

## Rischi e note

La barra non è cliccabile quando il modulo non ha ancora un id (come prima,
`href="#"`). Se il tema ha stili su `a` più specifici, i colori potrebbero
differire leggermente dal prototipo.

## Rollback

Ripristinare `navigation.blade.php` da git; le chiavi `mg_nav_*` possono
restare.
