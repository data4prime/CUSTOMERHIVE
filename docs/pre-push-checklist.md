# Checklist pre-push

Cose da controllare/ripristinare prima di pushare su `main` (o comunque prima
che il codice arrivi in staging/produzione). Aggiungere qui ogni volta che si
introduce una modifica "temporanea" per lo sviluppo locale.

## ✅ Riattivare i controlli di licenza — fatto (2026-08-26)

Erano stati disattivati temporaneamente per poter lavorare in locale senza
una licenza valida. Riattivati in `LicenseHelper.php` (rimossi i 4 return
anticipati `LICENSE-CHECK-DISABLED-DEV`), con l'aggiunta di un guard
"nessuna licenza ancora" su `canAddTenant()`, `canAddUser()` e
`getLicenseInfo()` (che ne erano privi e sarebbero andati in errore
fatale a tabella `license` vuota). Dettaglio completo in
[`refactoring/003-licensing-hardening.md`](refactoring/003-licensing-hardening.md).

Il flusso di attivazione (trial e "ho già una licenza") resta bloccato da
un bug lato server di licenza remoto, non da questo repository — vedi lo
stesso documento.

---

<!-- Aggiungere qui le prossime voci della checklist -->
