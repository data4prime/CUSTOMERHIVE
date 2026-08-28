# 033 - Documentazione originale CRUDBooster spostata in docs/crudbooster/

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Documentazione
- **File/aree di codice coinvolte**:
  - `docs/crudbooster/` (nuovo, 49 file `.md`)
  - `packages/crocodicstudio/crudbooster/docs/` (rimossa interamente)

## Contesto

`packages/crocodicstudio/crudbooster/docs/en/` conteneva la
documentazione originale (in inglese) del pacchetto CRUDBooster upstream
— installazione, tipi di campo form, guide "how to". Nessun riferimento
nel codice a quel path (verificato). Ultimo contenuto non-codice rimasto
nel pacchetto oltre a `src/` (`assets/`, `fonts/`, `views/`).

## Situazione prima

`packages/crocodicstudio/crudbooster/docs/en/*.md` (49 file).

## Situazione dopo

- Contenuto copiato in `docs/crudbooster/` (root del repo, accanto agli
  altri documenti di progetto).
- `packages/crocodicstudio/crudbooster/docs/` cancellata interamente.
- Verificato con diff (whitespace-insensitive) che i file copiati siano
  identici agli originali.

## Motivazione

Pura riorganizzazione documentale, nessun impatto sul codice: materiale
di riferimento upstream, non specifico di CustomerHive, ora nella
posizione standard `docs/` invece che sepolto nel pacchetto vendorizzato.

## Test

- Conteggio file: 49 in origine, 49 copiati.
- Diff di `index.md` (whitespace-insensitive): identico.
- Nessun riferimento nel codice al vecchio path (verificato prima di
  spostare) — nessun test funzionale necessario.

## Rischi e note

- Nessuno noto.

## Rollback

`git revert` del commit — ripristina la cartella nel pacchetto.
