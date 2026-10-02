# 212 - Colori hex delle viste verso i token `--ch-*`

- **Data**: 2026-10-02
- **Stato**: Completato (da vedere a vista)
- **Area**: Frontend / Standard UI
- **File/aree di codice coinvolte**:
  - `local-scripts/normalize-colors.php` (nuova opzione `--nearest`)
  - 28 viste Blade con `<style>` / `style=""` (chat AI, chat3, module generator,
    statistic builder, dashboard layouts, qlik_sync, qlik_items, profilo, ...)
  - rimosse `resources/views/crudbooster/chat.blade.php` e `chat2.blade.php`

## Contesto

Dall'analisi delle librerie UI (punto 11): 268 colori hex in 57 viste, contro
la regola di usare solo i token `var(--ch-*)` di `theme.css`. Lo script
`normalize-colors.php` sostituiva già i soli colori "di famiglia" presenti in
tabella; i restanti (es. `#48b0f7`, `#dd6b55`, `#ccc`) restavano a mano.

## Situazione prima

Il dry-run senza `--nearest` toccava 7 file / 44 colori. Gli altri hex senza
token equivalente restavano nelle viste. `chat.blade.php` e `chat2.blade.php`
non sono referenziate da nessun codice (solo `chat3` è inclusa da
`admin_template`).

## Situazione dopo

- `normalize-colors.php --nearest`: i colori senza token esplicito vanno al
  token più vicino (distanza RGB pesata) tra quelli dello stesso tipo. Grigi
  (saturazione < 28) solo ai neutri: sfondi `surface`/`bg`, bordi `border*`,
  testo `text*`/`surface`; colori saturi ai colori del tema (`accent`,
  `success`, `warning`, `danger`, `violet`, `blue`; per gli sfondi anche i
  `*-soft`). Resta limitato a `<style>` e `style=""`, proprietà
  colore/sfondo/bordo/fill/stroke.
- Applicato a 28 viste: 144 colori sostituiti.
- Esclusi di proposito: email (`vendor/notifications`, `emails/`, i client di
  posta non leggono le variabili CSS), JS (colori dei grafici, `swal`),
  `welcome` ed `errors/*` (pagine Laravel di default), `vendor/laravel-filemanager`
  e `mashup_sheet.blade.php` (pagina standalone senza `ch_head`/`theme.css`:
  le variabili non sarebbero definite).
- Eliminate `chat.blade.php` e `chat2.blade.php` (codice morto, 45 hex).

## Motivazione

Un solo punto di verità per i colori (tema, ruoli, futuro dark mode). Per i
colori senza equivalente si è scelto il token più vicino invece di crearne
di nuovi.

## Test

- `php artisan view:cache` OK (tutte le viste compilano), poi `view:clear`.
- Non verificato a vista: le pagine toccate possono cambiare sfumatura.

## Rischi e note

- **Cambia l'aspetto visibile**, in modo lieve: ad es. `#48b0f7` diventa
  `--ch-blue` (#2563eb, più scuro), `#007bff` diventa `--ch-accent` (indaco),
  `#000` diventa `--ch-text`, i grigi `#ccc`/`#ddd` diventano `--ch-border-strong`.
- Il JS continua ad avere hex propri (grafici, `swal`): fuori scopo.
- Restano gli `style=""` inline (364 in 134 file) da trasformare in classi:
  intervento a parte.
- Se un cliente richiamava `chat`/`chat2` da un proprio controller (non
  tracciato), la vista non esiste più: improbabile, nessun riferimento nel
  codice base.

## Rollback

`git checkout -- resources/views` sui file toccati (le viste `chat` e `chat2`
si recuperano da git).
