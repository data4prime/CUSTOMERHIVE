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

## ⚠️ Verificare che il license server di produzione parli già la nuova busta {success, data}

`ConnectorService.php` (`getAccessToken`, `writeLicense`) e
`AdminController::postActivateLicense()` sono stati aggiornati per leggere
le risposte del license server nella nuova busta `{success, message, data}`
invece del formato piatto precedente (`status`/`result` a livello radice).
Verificato solo contro un'istanza Docker locale di LICENSES già aggiornata.

**Prima di pushare su `main`**: confermare che `license.thecustomerhive.com`
(produzione) risponda già con la nuova busta su `/auth/login` e
`/license-server/license`/`/licenses` — altrimenti login e attivazione
licenza si rompono immediatamente in produzione. Dettaglio in
[`refactoring/004-licensing-envelope-success-data.md`](refactoring/004-licensing-envelope-success-data.md).

---

<!-- Aggiungere qui le prossime voci della checklist -->
