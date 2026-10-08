# 274 - Generatore API: select Obbligatorio/Attivo dei parametri non cliccabili

- **Data**: 2026-10-08
- **Stato**: Completato (da verificare a vista)
- **Area**: API Generator / UI
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/api_generator.blade.php` (nuova `cloneParamRow()`, usata in `load_parameters`, `init_data_parameters`, `addParam`)

## Contesto

Nella sezione "Cosa riceve" di `/admin/api_generator/generator` i select
OBBLIGATORIO e ATTIVO (e gli altri select delle righe) non rispondevano ai
click, quindi non si poteva indicare se un campo è obbligatorio o accettato.

## Situazione prima

Le righe dei parametri sono cloni della riga modello nel `<tfoot>`
nascosto. `public/js/ch-select.js` trasforma in select2 ogni `<select>`
della pagina, anche quelli del tfoot. Il clone ereditava la classe
`select2-hidden-accessible` e il contenitore select2 "morto": `eligible()`
lo scartava (non veniva reinizializzato) e il contenitore copiato non era
collegato a nessun select.

## Situazione dopo

`cloneParamRow()` clona la riga modello togliendo `.select2-container`,
la classe `select2-hidden-accessible` e gli attributi `data-select2-id`,
`aria-hidden`, `tabindex`. I select clonati vengono potenziati normalmente
da `ch-select.js` (MutationObserver). Nessun cambio lato server: i valori
`params_required[]` / `params_used[]` erano già salvati così com'erano.

## Motivazione

Correzione minima alla causa, senza toccare `ch-select.js` (usato in tutta
l'app). Alternativa scartata: `data-ch-native` sui select del tfoot, che
cambierebbe l'aspetto rispetto agli altri select.

## Test

`php -l` non applicabile (JS in Blade). Non verificato in browser: da
provare aggiungendo un parametro, cambiando tabella/azione e aprendo una API
esistente in modifica.

## Rischi e note

Per le azioni list/detail/delete il JS continua a preimpostare
Obbligatorio/Attivo (NO, e per detail/delete solo `id` a SÌ): ora però si
possono cambiare a mano. Un cambio di tabella/azione o "Reimposta" ricarica
i valori predefiniti.

## Rollback

Ripristinare le tre chiamate a `$('#table-parameters tfoot tr').clone()` e
rimuovere `cloneParamRow()`.
