# Checklist pre-push

Cose da controllare/ripristinare prima di pushare su `main` (o comunque prima
che il codice arrivi in staging/produzione). Aggiungere qui ogni volta che si
introduce una modifica "temporanea" per lo sviluppo locale.

## ☐ Riattivare i controlli di licenza

Disattivati temporaneamente per poter lavorare in locale senza una licenza
valida (il server di licenza remoto `license.thecustomerhive.com` dava
errori — vedi `docs/login-e-licensing.md`).

**File modificato**:
- `packages/crocodicstudio/crudbooster/src/helpers/LicenseHelper.php`

Tutti i punti toccati sono marcati con il tag `LICENSE-CHECK-DISABLED-DEV`,
cercabile con:
```
grep -rn "LICENSE-CHECK-DISABLED-DEV" packages/
```

**Come riattivare**: rimuovere le righe `return ...;` (con il relativo
commento del marker) aggiunte all'inizio di questi 4 metodi, lasciando il
codice originale sottostante:

- `canLicenseLogin()` — rimuovere `return true;` (bloccava il login se la
  licenza non era valida)
- `canAddTenant()` — rimuovere `return true;` (bloccava l'aggiunta di tenant
  oltre il limite di licenza)
- `canAddUser()` — rimuovere `return true;` (bloccava l'aggiunta di utenti
  oltre il limite di licenza)
- `getLicenseInfo()` — rimuovere `return false;` (senza questo, con nessuna
  licenza in tabella `license` andava in fatal error `null->license_key`,
  perché chiamato da `header.blade.php`/`sidebar.blade.php` su ogni pagina
  per mostrare/nascondere i moduli Qlik/Chat AI)

**Dopo la riattivazione**: verificare che login e caricamento dashboard
funzionino ancora con una licenza valida configurata (altrimenti l'app torna
a bloccare tutto come da comportamento originale).

---

<!-- Aggiungere qui le prossime voci della checklist -->
