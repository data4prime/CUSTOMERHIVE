# 254 - Statistic Builder (lista) e pagina chiavi segrete API come nel mockup

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Statistic Builder, API Generator
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`
  - `resources/views/crudbooster/api_key.blade.php`
  - `public/css/ch-components.css` (`.api-note-danger`)
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Richieste: in `admin/statistic_builder` togliere la colonna Layout e mostrare Modifica ed
Elimina solo come icona; allineare `admin/api_generator/screet-key` al mockup.

## Situazione prima

- Lista: colonne Name + Layout (join su `dashboard_layouts`); azioni con stile
  `button_icon_text` ("Modifica Dati" ed "Elimina" con testo).
- Chiavi segrete: nota informativa blu a una riga, contatore come testo semplice, pulsante
  "Genera Chiave Segreta" attivo, "Elimina" rosso pieno.

## Situazione dopo

- Lista: tolta la colonna Layout (il campo `layout` resta in tabella e nascosto nel form,
  `hide_form`; le nuove dashboard nascono sempre a griglia). Azioni solo icona con stile
  `button_icon_strict` (lo stile `button_icon` mostrava anche l'occhio del dettaglio al superadmin
  nonostante `button_detail = false`); restano Builder e Griglia libera.
- Chiavi segrete (come `apinote.danger` del mockup): nota rossa con titolo in grassetto e
  secondo paragrafo "saranno dismesse… passa ai Token API →" (link ai Token); contatore a
  pillola; "Elimina" in versione ghost con testo rosso; **pulsante "Genera Chiave Segreta"
  disabilitato** (sia in alto sia nello stato vuoto) con tooltip. Variabile
  `$apiKeyGenerateDisabled` in cima alla vista: metterla a `false` lo riattiva.
- Nuove chiavi en+it: `api_keys_note_title`, `_note_body`, `_note_sunset`, `_generate_disabled`.

## Motivazione

Allineamento al mockup. La lista del builder non ha più una colonna senza informazione utile;
le chiavi segrete vengono dismesse a favore dei Token API.

## Test

A vista: Statistic Builder (solo Nome + quattro icone), pagina chiavi segrete (nota rossa,
pillola, pulsante spento, Mostra/Copia/Elimina). Non provati: Mostra/Copia/Elimina e
l'interruttore di stato (non toccati), creazione di una nuova dashboard, tema scuro.

## Rischi e note

**Cambio di comportamento visibile**: da interfaccia non si possono più creare chiavi segrete
(il pulsante è spento, come nel mockup). L'endpoint di generazione (`generate-screet-key`) non
è stato toccato: chi lo chiama direttamente funziona come prima. Le chiavi esistenti continuano
a funzionare e si possono ancora attivare/disattivare, mostrare, copiare, eliminare.

## Rollback

Statistic Builder: ripristinare colonna Layout e `button_icon_text`. Chiavi segrete: mettere
`$apiKeyGenerateDisabled = false` per riattivare la creazione; per il resto ripristinare la vista
dalla versione precedente (git).
