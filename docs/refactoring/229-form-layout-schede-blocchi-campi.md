# 229 - Layout del form: schede → blocchi → campi

- **Data**: 2026-10-05
- **Stato**: Completato (non committato, da provare a vista)
- **Area**: Module generator (step 4, flag `MODULE_GENERATOR_WIZARD_V2`) / form add-edit-detail
- **File/aree di codice coinvolte**:
  - `app/Helpers/ModuleGeneratorLayout.php`
  - `app/Http/Controllers/System/ModulsController.php` (`getStep4V2`)
  - `resources/views/crudbooster/module_generator/step4_v2.blade.php`
  - `resources/views/crudbooster/default/form_layout.blade.php`, `form_detail_layout.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (`mg_lay_hint`, `mg_lay_no_blocks`, `mg_lay_confirm_delete_tab`)

## Contesto

Richiesta: nello step 4 la "scheda" deve essere una tab. Dentro le tab si
aggiungono i blocchi, dentro i blocchi i campi; le schede si navigano
orizzontalmente in alto (Scheda 1, Scheda 2, ...). Estende il layout a blocchi
dell'intervento 197 (dettaglio: 203).

## Situazione prima

Il layout era una lista di blocchi su griglia a 12 colonne; un blocco poteva
essere di tipo `tabs` (contenitore di schede, ciascuna con i soli campi, senza
blocchi). Formato salvato: `['v'=>1,'blocks'=>[...]]` in `$this->form_layout`.

## Situazione dopo

Formato `['v'=>2,'tabs'=>[['id','title','blocks'=>[['id','title','x','y','w','h','fields'=>[...]]]]]]`.
- Editor: barra di tab in alto (con "+", rinomina ed elimina scheda); la griglia
  mostra i blocchi della scheda aperta; i campi si spostano tra i blocchi della
  scheda aperta (o da/verso il pannello "da posizionare"), e dalla finestra del
  campo si può scegliere anche un blocco di un'altra scheda.
- Form add/edit e dettaglio: nav-tabs in alto, un pannello per scheda con la sua
  griglia di blocchi. Con una sola scheda la barra non si vede (come prima, per
  chi non usa le schede).
- Compatibilità: `ModuleGeneratorLayout::normalize()` converte al volo il vecchio
  v1 (blocchi semplici → un'unica scheda; ogni scheda di un vecchio blocco
  `tabs` → blocco a sé, impilato in fondo). Anche `build()` accetta il vecchio
  formato (import di export precedenti). I controller già generati non vanno
  riscritti: il formato nuovo si scrive solo al prossimo salvataggio dello step 4.

## Motivazione

Modello più naturale e uguale a come lo si descrive: le schede organizzano il
form, i blocchi raggruppano i campi. Alternativa scartata: mantenere i blocchi
`tabs` annidati (non permetteva blocchi dentro una scheda).

## Test

`php -l`, `php artisan view:cache` (compilazione Blade) e prova mirata
dell'helper nel container (normalize v1→v2, build v1/v2, detailOrder,
dropFields, appendFields, defaultLayout, errore campo obbligatorio). NON
verificato: l'editor JS e il rendering reale a vista (browser) — da provare con
`docs/test-manuale-wizard-v2.md`. Suite di test non lanciata.

## Rischi e note

- Cambia il comportamento visibile solo dietro il flag wizard v2 e per i moduli
  con `FORM LAYOUT`; un vecchio layout con blocchi `tabs` cambia aspetto
  (le schede interne diventano blocchi impilati) finché non lo si riorganizza.
- Chiavi `mg_lay_badge_tabs`, `mg_lay_add_tabs`, `mg_lay_add_tabs_d` non più usate
  (lasciate nei file di lingua).
- `docs/test-manuale-wizard-v2.md` descrive ancora i "blocchi schede" nello step 4:
  da aggiornare dopo la prova a vista.

## Rollback

Ripristinare i file elencati dal git (`git checkout -- <file>`); i layout già
salvati in v2 andrebbero rigenerati dallo step 4 precedente.
