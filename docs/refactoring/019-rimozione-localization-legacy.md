# 019 - Rimossa packages/.../localization/ (mai caricata a runtime)

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Architettura / CRUDBooster
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/CRUDBoosterServiceProvider.php`
  - `packages/crocodicstudio/crudbooster/src/localization/` (rimossa interamente)

## Contesto

Verificato (discussione in chat) che `resources/lang/{it,en,...}/crudbooster.php`,
non `packages/.../localization/`, è ciò che Laravel usa davvero per
`trans('crudbooster.xxx')`:
- Nessun `loadTranslationsFrom()` in tutto il repo.
- Nessuna chiamata `trans('crudbooster::...')` con doppio due punti (la
  sintassi per un namespace di pacchetto) — sempre la forma semplice
  `trans('crudbooster.chiave')`, risolta solo in `resources/lang/{locale}/`.
- L'unico collegamento del pacchetto con `localization/` era un
  `$this->publishes([__DIR__.'/localization' => resource_path('lang')], 'cb_localization')`
  — una copia manuale una tantum via `vendor:publish`, mai un caricamento
  attivo.
- Le due copie sono nettamente divergenti: `it/crudbooster.php` nel
  pacchetto è ancora in inglese (276 righe, mai tradotto), quello in
  `resources/lang` è tradotto in italiano vero e più ricco (397 righe).
  Stesso quadro per `en/` (276 vs 403 righe, chiavi aggiunte/riscritte nel
  tempo).
- `resources/lang/{it,en}/crudbooster.php` è **già tracciato su git**, con
  storia di commit propria — quindi anche l'unico scopo dichiarato del
  pacchetto (seminare le traduzioni su un'installazione nuova) è
  vestigiale: un nuovo cliente riceve già `resources/lang` corretto
  semplicemente deployando questo repo, senza bisogno di
  `vendor:publish --tag=cb_localization`.

## Situazione prima

`packages/crocodicstudio/crudbooster/src/localization/` conteneva le
traduzioni originali di CRUDBooster (ar, en, es, id, it, pt_br, ru, tr,
zh-CN), mai aggiornate dopo il fork iniziale, referenziate solo dalla riga
`publishes()` sopra.

## Situazione dopo

- Cartella `localization/` cancellata interamente.
- Rimossa la riga `$this->publishes([__DIR__.'/localization' => resource_path('lang')], 'cb_localization');`
  da `CRUDBoosterServiceProvider::boot()` (path sorgente altrimenti
  inesistente, avrebbe fatto fallire un futuro `vendor:publish --tag=cb_localization`).
- `resources/lang/` non toccato: resta l'unica fonte di traduzioni,
  invariata.

## Motivazione

Elimina un pezzo di superficie del pacchetto vendorizzato che non era
solo "non necessario a runtime" ma **non serviva più nemmeno al suo unico
scopo dichiarato** (seed per installazioni nuove), essendo quel ruolo già
coperto in modo permanente dal tracking git di `resources/lang`.

## Test

- `php -l` su `CRUDBoosterServiceProvider.php`: nessun errore.
- `php artisan route:list`: 486 rotte, invariato.
- `curl` senza sessione su `/admin`, `/admin/logs`: 302, nessun 500.
- Verifica funzionale diretta: bootstrap completo dell'app,
  `trans('crudbooster.login_message')` con locale `it` →
  `"Accedi per iniziare la tua sessione"` (la traduzione italiana di
  `resources/lang`, non la stringa inglese che sarebbe stata nella copia
  del pacchetto ora cancellata) — conferma che nulla dipendeva dalla
  cartella rimossa.

## Rischi e note

- Nessuno noto: la cartella non era mai sul percorso di caricamento delle
  traduzioni, e il suo unico ruolo di "seed" era già reso irrilevante dal
  tracking git di `resources/lang`.

## Rollback

`git revert` del commit — ripristina la cartella e la riga `publishes()`,
nessun impatto su `resources/lang`.
