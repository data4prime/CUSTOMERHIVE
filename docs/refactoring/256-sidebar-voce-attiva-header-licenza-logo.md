# 256 - Sidebar (voce attiva, logo), "mezzo header" e modale Licenza

- **Data**: 2026-10-07
- **Stato**: Completato (verificato a vista nel browser locale)
- **Area**: Frontend / Layout
- **File/aree di codice coinvolte**:
  - `public/css/ch-ui2.css` (voce attiva, sfondo del contenitore, logo senza pillola)
  - `public/css/theme.css` (blocco `#licenseModal`)
  - `resources/views/crudbooster/sidebar.blade.php`, `license_modal.blade.php`
  - nuovo `public/images/customerhive_sidebar.png`
  - `resources/lang/{en,it}/crudbooster.php`

## Contesto

Quattro richieste: (1) la voce di menu selezionata aveva un colore fucsia, diverso dal
mockup; (2) in alto a destra sembrava esserci "mezzo header" assente dal mockup; (3) la
modale Licenza non era in linea con le altre; (4) logo e nome CustomerHive come PNG
trasparente per la sidebar.

## Situazione prima

1. Voce attiva: `linear-gradient` tra `--ch-role-accent` (rosso per il superadmin) e
   `--ch-accent-2` viola => fucsia, diverso per ogni ruolo.
2. `.content-wrapper` aveva un gradiente radiale viola ancorato a `-80px`; il contenitore
   parte però sotto l'header (68px), quindi il gradiente veniva tagliato e appariva come una
   banda a destra con un bordo netto.
3. Modale Licenza: il blocco CSS dedicato era stato rovinato da un rinomina automatico
   (commento e prima regola fusi): la regola veniva scartata e la modale restava quella di
   default (tabella `table-bordered`, etichette in inglese fisso, icona del titolo non stilata
   perché il selettore cercava `.fa-key`).
4. Logo predefinito su una pillola bianca (il PNG ha testo grigio-verde per fondo chiaro).

## Situazione dopo

1. Voce attiva con `var(--ch-grad)` (indaco -> viola del mockup) per tutti i ruoli.
2. `.content-wrapper` con solo `var(--ch-bg)`: nessuna banda.
3. Blocco CSS ricostruito: card arrotondata, icona del titolo a badge, righe etichetta (maiuscolo
   piccolo, grigio) / valore, moduli come `ch-pill`, pulsante `btn-secondary`. Tutte le etichette
   passano da `trans()` (chiavi `license_modal_*`, en+it): prima erano in inglese fisso
   ("YES"/"NO", "days").
4. `customerhive_sidebar.png` (376x67, trasparente): ricavato dal logo esistente con GD (testo
   "Customer" quasi bianco, alveare e "Hive" arancioni resi appena piu' luminosi). La sidebar lo usa
   senza pillola quando non c'e' un logo personalizzato (`sidebar-logo-bare`); con un logo caricato
   dal cliente resta la pillola chiara di prima (colori liberi).

## Motivazione

Coerenza col mockup e con le altre modali; logo leggibile direttamente sul fondo scuro.

## Test

A vista: voce "Qlik" attiva in viola, assenza della banda in alto a destra, logo sulla sidebar,
modale Licenza (righe, pillole, "Chiudi"). Non verificati: altri ruoli (colori di ruolo),
tenant con logo personalizzato, tema scuro, pagina di login.

## Rischi e note

Cambio visibile: la voce attiva non segue piu' il colore del ruolo (resta altrove, es. badge e
accenti di pagina). Il PNG e' un ricolore del raster esistente: se arriva il file vettoriale/
originale del marchio conviene rigenerarlo da quello. Il logo originale
(`customerhive_trasparente.png`) e' invariato e usato altrove.

## Rollback

Ripristinare i due CSS, `sidebar.blade.php` e `license_modal.blade.php` dalla versione
precedente (git); il PNG nuovo si puo' lasciare o eliminare.
