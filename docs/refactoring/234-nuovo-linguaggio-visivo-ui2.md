# 234 - Nuovo linguaggio visivo (ui2): token e guscio

- **Data**: 2026-10-06
- **Stato**: Completato (da vedere a vista)
- **Area**: Frontend / UI-UX
- **File/aree di codice coinvolte**:
  - `public/css/ch-ui2.css` (nuovo)
  - `config/crudbooster.php` (flag `UI_V2`)
  - `resources/views/crudbooster/partials/ch_head.blade.php`
  - `resources/views/crudbooster/admin_template.blade.php`

## Contesto

Prototipo interattivo "Confronto UI CustomerHive" (artifact privato, pannelli
Attuale / Proposta) approvato dall'utente pagina per pagina. Questo è il primo
passo dell'implementazione: il livello 1 del piano (token e componenti) e la
parte di guscio del livello 2 (sidebar, header, intestazione di pagina), senza
toccare il markup.

## Situazione prima

Aspetto definito da `theme.css` (token `--ch-*`, header a tinta piena
nell'accento di ruolo, sidebar bianca) e da `ch-components.css`. Tutti i
componenti leggono i token, quindi cambiare i token cambia il prodotto.

## Situazione dopo

`ch-ui2.css` è un foglio di **override** caricato dopo `theme.css` e
`ch-components.css`, attivo solo con `body.ch-ui2`:

- nuovi valori dei token (accento `#5b4cf0` con gradiente verso `#8b5cf6`,
  raggi 12/14/20, campi da 44px con sfondo tenue, ombre morbide, sidebar 272px);
- le variabili `--bs-*` e quelle `--ch-*` che puntano ad altri token sono
  **ridichiarate sul body**: in `theme.css` si risolvono su `:root` e
  altrimenti non seguirebbero i nuovi valori;
- sidebar scura con gradiente; il logo sta su una pillola chiara (colori fissi,
  loghi dei tenant liberi); voce attiva con gradiente nell'accento di ruolo
  (`--ch-role-accent`, il "Theme Color" dei ruoli continua a funzionare);
- header trasparente con pulsanti-icona e pillola utente bianchi;
- intestazione di pagina: breadcrumb sopra, titolo 30px, niente badge icona;
  pulsanti primari a gradiente;
- card/box/modali con raggio 20, tabelle con intestazione "a pillola",
  schede segmentate, paginazione, badge;
- pagine di form (aggiungi/modifica/dettaglio), come nel mockup: niente titolo
  ripetuto nell'intestazione della card (c'è già quello di pagina), "torna
  all'elenco" a pillola, card con raggio 20 (larghezza massima 1100px),
  pulsanti in fondo con Indietro a sinistra e salvataggi a destra (sezione 11
  di `ch-ui2.css`; selettori `:has(> .card-body > form#form)`, nessun Blade toccato);
- blocchi del form/dettaglio a schede (`.cb-card`, `.cbd-card`): niente card
  dentro la card di pagina (bordo e ombra azzerati, causa dei "bordi bacati"),
  solo titolo di sezione in grassetto e campi; il dettaglio mostra etichetta
  piccola in maiuscolo e valore in evidenza, senza zebratura;
- i token chiari e (vedi 235) scuri convivono nello stesso file.

Flag: `config('crudbooster.UI_V2')`, da `CH_UI_V2` nel `.env` (default `true`).
Aggiunge la classe `ch-ui2` al body di `admin_template` e il link al foglio in
`ch_head`. Le pagine standalone (login ecc.) non hanno la classe e restano
invariate.

## Motivazione

Un foglio di override dietro flag (invece di riscrivere `theme.css`) rende
l'intervento reversibile in un secondo e lascia intatto il comportamento: nessun
Blade cambia struttura, nessuna logica PHP/JS.

## Test

- `php -l` su config e lang; `php artisan view:cache` (compilazione di tutti i
  Blade) senza errori.
- **Non verificato a vista**: serve un giro manuale su lista, form, dettaglio,
  dashboard, moduli generati e un modulo custom di cliente.

## Rischi e note

- Selettori `body.ch-shell.ch-ui2 ...` più specifici di `theme.css`; dove
  `theme.css` usa `!important` il nuovo foglio non vince (da sistemare caso per
  caso).
- Moduli custom dei clienti con CSS inline o classi AdminLTE possono avere
  contrasti da rivedere (stesso discorso di `ch-compat.css`).
- Il bianco fisso del logo-pillola è una scelta: un logo chiaro (testo bianco)
  su pillola bianca sparirebbe; in quel caso usare un logo scuro.

## Rollback

`CH_UI_V2=false` nel `.env`, poi `php artisan config:clear`. In alternativa
rimuovere `public/css/ch-ui2.css`.
