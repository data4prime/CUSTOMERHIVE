# Tracciamento del refactoring

Questa cartella traccia, passo per passo, il percorso di modernizzazione del
progetto: aggiornamento di Laravel, miglioramento dell'architettura,
refactoring per stabilità/manutenibilità, semplificazione di
installazione/setup, rinnovo della UI/UX.

Obiettivo: chiunque (oggi o tra un anno) deve poter capire **cosa è cambiato,
perché, e come funzionava prima**, senza dover ricostruire il contesto da
zero leggendo i diff di git.

## Struttura

- **`glossario.md`** — termini/concetti ricorrenti nel progetto (CRUDBooster,
  tenant, licenza, guard, ecc.), definiti una volta sola e richiamati dagli
  altri documenti.
- **`_template.md`** — modello da copiare per ogni intervento di refactoring.
- **`NNN-titolo-breve.md`** — un file per ogni intervento, con la situazione
  prima/dopo. Numerati in ordine cronologico (`001-`, `002-`, ...).

## Come aggiungere un intervento

1. Copia `_template.md` in un nuovo file `NNN-titolo-breve.md` (`NNN` = numero
   progressivo successivo, `titolo-breve` in kebab-case).
2. Compila le sezioni **prima di iniziare a modificare il codice** (situazione
   "prima" e motivazione) e completa il resto a lavoro fatto.
3. Se introduci un termine nuovo o non ovvio, aggiungilo a `glossario.md`.
4. Aggiungi una riga nell'indice qui sotto.
5. Se l'intervento richiede di riattivare/rimuovere qualcosa prima del prossimo
   push, aggiungilo anche a [`../pre-push-checklist.md`](../pre-push-checklist.md).

## Indice degli interventi

| N.  | Titolo | Area | Stato | Data |
|-----|--------|------|-------|------|
| —   | *(nessun intervento ancora registrato)* | | | |

**Stato**: `Pianificato` → `In corso` → `Completato` (o `Annullato` se si
decide di non procedere, motivando il perché nel file stesso).

## Documenti correlati

- [`../docker-local-dev.md`](../docker-local-dev.md) — ambiente di sviluppo locale
- [`../login-e-licensing.md`](../login-e-licensing.md) — sistema di login e licensing attuale
- [`../pre-push-checklist.md`](../pre-push-checklist.md) — cose da ripristinare/verificare prima di un push
