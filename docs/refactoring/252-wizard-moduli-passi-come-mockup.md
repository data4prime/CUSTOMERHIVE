# 252 - Wizard Generatore Moduli: passo 1, passo 3 e pulsanti come nel mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale; invio dei form non provato)
- **Area**: Frontend / Generatore moduli
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/step1.blade.php`, `step2_v2`, `step3_v2`, `step4_v2`, `step5`
  - nuovo `resources/views/crudbooster/module_generator/_nav.blade.php`
  - `resources/views/crudbooster/module_generator/template.blade.php` (scambio dei passi via AJAX)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Segnalazioni su `module_generator/step1/38` e `step3/38`: interruttore "Also create menu"
disallineato dalla sua etichetta; icona da fare come nel mockup; in passo 3 "Ordinamento e
pagine" da mettere sopra l'anteprima con i tre campi su una riga; pulsanti Indietro/Avanti/
Salva modulo di tutti i passi da fare come nel mockup.

## Situazione prima

- Passo 1: icona con select2 (testo + icona piccola); interruttore con margini del
  `form-check` che lo spostavano sotto la riga dell'etichetta.
- Passo 3: "Ordinamento e pagine" nella colonna sinistra sotto l'elenco colonne; "Ordina per"
  e direzione in un `input-group`, "Righe per pagina" a parte.
- Pulsanti: dentro la card (`card-footer`), con etichette "Step 2 »", "Lista »", ... diverse
  per passo; passo 1 con "Indietro" a lista.

## Situazione dopo

- **Passo 1**: icona con il selettore del mockup (`partials/ch_icon_picker`, lo stesso del
  form di modifica del modulo; valore inviato invariato `bi bi-<nome>`; per un modulo nuovo
  parte dalla prima icona dell'elenco, come faceva il select). Riga dell'interruttore con
  `align-items-center`.
- **Passo 3**: "Ordinamento e pagine" è nella colonna destra sopra l'anteprima, con tre campi
  su una riga: Righe per pagina / Ordina per / Direzione (nuova etichetta). Gli id
  (`cfgLimit`, `cfgOrderField`, `cfgOrderDir`) sono quelli di prima: il JS del passo non cambia.
- **Pulsanti**: partial `_nav` usato dai cinque passi, sotto la card e a destra, dentro lo
  stesso `<form>`: "Indietro" (passo 1: alla lista; dal 2 in poi "« Indietro" con `mg-nav`,
  navigazione senza ricarica) e "Avanti »" (passi 1-4) / "Salva modulo" (passo 5, con
  `name="submit"` come prima). Il form ora avvolge card e pulsanti; campi e action invariati.
- `template.blade.php`: nello scambio dei passi i `<script type="application/json">` non
  vengono né eseguiti né rimossi, così l'elenco icone resta disponibile dopo la navigazione.
- Nuove chiavi en+it: `mg_nav_next`, `mg_list_order_dir`. Rimosse le vecchie etichette dei
  pulsanti dalle viste (le chiavi `mg_fld_next`, `mg_list_next`, `mg_lay_next` restano nel file
  lingua ma non sono più usate).

## Motivazione

Allineamento al mockup e coerenza tra i cinque passi (stessi pulsanti, stesse etichette).

## Test

A vista: passo 1 (interruttore allineato, selettore icone che si apre e filtra, pulsanti sotto
la card), passo 3 (impostazioni sopra l'anteprima, tre campi in riga, anteprima aggiornata),
passo 5 ("« Indietro" + "Salva modulo" fuori dalla card), passi 2 e 4 ("« Indietro" +
"Avanti »" dentro il form). Navigazione tra passi via stepper (AJAX) funzionante.
Non provato: invio dei form (Avanti/Salva) per non riscrivere il modulo 38; tema scuro.

## Rischi e note

Le bozze automatiche del wizard (X-Wizard-Partial) leggono i campi del `<form>`: la struttura
è cambiata solo nel posto dei pulsanti, ma conviene un giro manuale completo dei cinque
passi con salvataggio. I passi legacy non-v2 (`step2/3/4.blade.php`) non sono toccati.

## Rollback

Ripristinare le viste dei passi e `template.blade.php` dalla versione precedente (git),
eliminare `_nav.blade.php` e le due chiavi lingua.
