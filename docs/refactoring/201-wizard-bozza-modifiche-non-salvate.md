# 201 - Wizard v2: bozza delle modifiche non salvate

- **Data**: 2026-10-02
- **Stato**: Completato (da provare nel browser sul wizard reale)
- **Area**: Frontend/Backend (dietro flag `wizard_v2`)
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/module_generator/template.blade.php` (logica client, attributi dati, avviso)
  - `app/Http/Controllers/System/ModulsController.php` (`postDraft`, `postDraftDiscard`, `applyWizardDraft`, `forgetWizardDraft`)
  - `resources/lang/{en,it}/crudbooster.php` (`mg_draft_notice`, `mg_draft_discard`)

## Contesto

Con la navigazione senza ricarica (200) restava il problema delle modifiche
non salvate di un passo: cambiando passo, premendo "Indietro", chiudendo la
pagina o uscendo all'improvviso andavano perse. Salvare davvero a ogni cambio
non va bene: il passo Campi crea colonne nel database e una scelta a metà
non deve diventare struttura.

## Situazione prima

Nessuna protezione: il form del passo si salvava solo con "Avanti"/"Salva".

## Situazione dopo

Una **bozza** per modulo e passo, tenuta in sessione (non tocca database né
file del controller):

- **Quando si scrive**: prima di cambiare passo (link dei passi, "Indietro",
  tasto indietro del browser), all'uscita dalla pagina (`pagehide` con
  `sendBeacon`, anche chiudendo la scheda o cambiando pagina), quando la
  scheda va in background, e dopo 2 secondi senza interazioni nel wizard.
  Si scrive solo se il form è diverso dallo stato salvato; se si torna allo
  stato salvato la bozza si cancella.
- **Cosa contiene**: i campi del form del passo. I passi con editor (Campi,
  Lista, Layout) preparano il proprio `payload` nel gestore di submit, che
  si fa girare con un evento finto ignorato dall'invio via fetch.
- **Ripristino**: passi 2-4 → la bozza fa da old input (`old('payload')`, lo
  stesso meccanismo del ritorno da un errore di validazione, che ha la
  precedenza); passi 1 e 5 → i valori vengono rimessi nei campi. In cima
  al passo compare un avviso con il pulsante "Scarta modifiche".
- **Quando si cancella**: salvataggio riuscito del passo (`postStep1`, v2 di
  2/3/4, `postStep5`) oppure "Scarta modifiche". Gli invii non scrivono mai
  una bozza.
- Endpoint solo superadmin e solo con il flag; massimo 1 MB per bozza.

## Motivazione

Nessuna perdita di lavoro senza effetti collaterali sul DB e senza cambiare
il flusso di salvataggio: la bozza è solo uno stato temporaneo dell'interfaccia.

## Test

In Docker: salvataggio e lettura della bozza, applicazione come old input
(con precedenza dell'input da errore di validazione), scarto, cancellazione,
rifiuto di step non valido / JSON rotto / flag spento / utente non superadmin.
In Chrome headless con `fetch`/`sendBeacon` simulati sul template reale:
bozza inviata prima del cambio passo (checkbox deselezionata inclusa), nessuna
bozza senza modifiche, ripristino di campi e avviso, bozza all'evento
`pagehide`, scarto con ricarica, salvataggio automatico di un `payload` dopo 2 s
e cancellazione al ritorno allo stato salvato. Non verificato: wizard reale con
login, chiusura reale della scheda (la consegna di `sendBeacon` all'uscita
dipende dal browser), tutti i campi del passo Configurazione (ripristino con
eventi `change`).

## Rischi e note

- La bozza vive nella sessione: se la sessione scade, si perde.
- Una bozza dimenticata riappare al successivo ingresso nel passo (con
  l'avviso) finché non si salva o si scarta.
- Il ripristino dei passi 1 e 5 lancia l'evento `change` sui campi: eventuali
  effetti collaterali dei gestori di quei campi si ripetono.
- Il modulo nuovo (id 0) ha una sola bozza per il passo Informazioni.

## Rollback

Spegnere il flag, oppure ripristinare da git i file elencati sopra. Le bozze
sono solo in sessione e spariscono da sole.
