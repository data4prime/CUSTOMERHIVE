# Registro delle decisioni di architettura (ADR compatto)

> Letto da `/architect` e `ch-architect-analyst`. Serve a **non riproporre**
> ciò che è già stato deciso. Una riga di contesto, la decisione, il perché, e
> cosa NON fare. Le nuove decisioni si aggiungono anche con
> `tokensave_record_decision` (memoria di sessione), ma **questo file è la
> fonte**: è versionato e visibile a tutti.

| # | Decisione | Perché | Non fare |
|---|---|---|---|
| A1 | Mantenere namespace/alias/tabelle `crudbooster`/`cms_*` (via `class_alias`) | Ogni cliente ha controller custom copiati a mano in momenti diversi; la compatibilità deve reggere finché *tutti* sono migrati | Rinominare in blocco le 1400+ occorrenze |
| A2 | Migrazione auth "strangler fig": sessione custom + guard Laravel in parallelo | Zero downtime in produzione | Passare di colpo ai guard o togliere la sessione custom |
| A3 | CSRF disabilitato globalmente | Scelta nota e documentata (`auth-csrf-licenza.md`) | Riattivarlo "di passaggio" in un refactoring |
| A4 | Test su MySQL vero (`customerhive_testing`), non SQLite | Stesso motore della produzione | Spostare i test su SQLite |
| A5 | Ambiente locale sempre Docker | Riproducibilità; `vendor/` e `storage/framework/` su volumi nativi | Cercare php/mysql sull'host |
| A6 | SMTP solo dai Settings, via `MailSettings` | Una sola sorgente di configurazione | Leggere `config('mail')` direttamente in codice nuovo |
| A7 | `hide_form` invece di rimuovere da `$this->form` | Il save azzera i campi assenti | Togliere elementi dall'array `form` |
| A8 | Pint (PSR-12) solo `--test` sui file toccati; Larastan livello 0 con baseline su `app/Helpers` e `Controllers/System` | Evitare diff enormi in codice in produzione | Lanciarli sull'intero repo |
| A9 | Refactoring architetturale su branch dedicato, un batch alla volta, piano approvato prima | Rischio produzione | Implementare l'intero piano in un colpo |
| A10 | UI: Bootstrap 5.3 + Bootstrap Icons in locale, token `var(--ch-*)`, flag `CH_UI_V2` | Rimozione AdminLTE | Reintrodurre AdminLTE |
| A11 | Modelli: restare in `App\` (niente `App\Models\`) per ora; relazioni Eloquent **additive** con nomi nuovi, metodi esistenti come wrapper; local scope sì, **global scope per tenant no** | Il motore usa `DB::table()` in 400+ punti (un global scope non li coprirebbe); i controller dei clienti chiamano i metodi dei modelli; `App\User` è legato a Sanctum (`tokenable_type`) | Spostare/rinominare i modelli senza alias e morph map; sostituire i metodi pubblici esistenti; global scope tenant (piano 317) |
| A12 | Autorizzazioni ChatAI su **tabelle dedicate** (`chatai_groups_allowed`, `chatai_tenants_allowed`), non su `items_allowed`/`tenants_allowed` di Qlik | Le due tabelle Qlik sono in produzione e `item_id` è già lo spazio di id dei `qlik_items`: condividerlo fa collidere conf ChatAI e item Qlik (piano 318) | Aggiungere `chataiconf_id` alle tabelle Qlik; riusare `item_id` per altri tipi di oggetto |

Per aggiungere una decisione: nuova riga con numero progressivo. Una decisione
superata non si cancella: si marca `(sostituita da A#)`.
