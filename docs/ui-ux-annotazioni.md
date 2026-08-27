# Annotazioni UI/UX

Raccolta di cose notate (durante test manuali, sviluppo di altre parti,
ecc.) che riguardano l'interfaccia — non bug funzionali, ma cose da
migliorare quando si affronterà il rinnovo UI/UX (punto della roadmap
generale, dopo/in parallelo al lavoro di backend, vedi
[`refactoring/README.md`](refactoring/README.md)). Non sono interventi
numerati: sono spunti da tenere a mente per quando quella fase inizierà
davvero, per non doverli riscoprire da capo.

Come aggiungere una voce: una entry breve con data, dove si vede il
problema, e perché è un problema (non serve una soluzione già pronta).

---

## Banner di successo/fallimento dopo il salvataggio di un record

- **Data**: 2026-08-27
- **Dove**: dopo il salvataggio di un record (es. Tenant) da un modulo
  CRUDBooster, si torna alla pagina di lista con un banner che annuncia
  l'esito dell'operazione.
- **Problema**: il banner è visivamente datato/invadente. Da sostituire con
  qualcosa di più leggero, tipo una notifica breve in un angolo dello
  schermo (toast) invece di un banner a piena larghezza.

## Tabella nelle pagine di lista

- **Data**: 2026-08-27
- **Dove**: le pagine di lista (index) dei moduli CRUDBooster (Tenant,
  Gruppi, Utenti, ecc.), la tabella con i record.
- **Problema**: da rivedere (l'utente l'ha segnalata durante il test
  manuale senza ancora dettagliare cosa non va — da chiedere/osservare
  meglio quando si affronterà il rinnovo UI/UX).
