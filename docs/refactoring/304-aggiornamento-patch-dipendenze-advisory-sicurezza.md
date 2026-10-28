# 304 - Aggiornamento di patch di due dipendenze per advisory di sicurezza

- **Data**: 2026-10-16
- **Stato**: Completato (3 advisory su 4 chiuse; la quarta resta aperta di proposito)
- **Area**: Dipendenze / Sicurezza
- **File/aree di codice coinvolte**:
  - `composer.lock` (solo `symfony/http-foundation` e `league/commonmark`);
    `composer.json` non toccato da questo intervento

## Contesto

Nel doc 303 `composer audit` segnalava «3 advisory su 2 pacchetti, da valutare a
parte». Rilanciato il 2026-10-16 ne segnala 4 su 3 pacchetti (ne è uscita una
nuova). CustomerHive è in produzione presso clienti reali: l'obiettivo è
chiudere le advisory con il cambiamento più piccolo possibile, senza salti di
versione maggiore.

## Situazione prima

`composer audit` (PHP 8.5.11, Laravel 13.29.0):

| Pacchetto | Versione | Gravità | Problema |
|---|---|---|---|
| `league/commonmark` | 2.10.0 | alta | DoS quadratico nell'estensione tabelle GFM |
| `league/commonmark` | 2.10.0 | media | `DisallowedRawHtml` aggirabile |
| `symfony/http-foundation` | v7.4.18 | n/d | ReDoS in `HeaderUtils::split()` raggiungibile da `Cache-Control` (CVE-2026-108082) |
| `laravel/framework` | v13.29.0 | bassa | XSS nella pagina di debug (CVE-2026-102279, fix in 13.30.0) |

`league/commonmark` è solo una dipendenza transitiva di `laravel/framework`
(`composer why`): né il codice dell'app (`app/`, `resources/`, `routes/`,
`config/`, `database/`) né le viste chiamano Markdown (`Str::markdown`,
`CommonMark`, ecc.). L'unica occorrenza della parola è un commento in
`ApiDocBuilder.php`.

## Situazione dopo

`composer update "symfony/http-foundation:~7.4.19" "league/commonmark:~2.10.3"`:

- `symfony/http-foundation` v7.4.18 → v7.4.21 (patch)
- `league/commonmark` 2.10.0 → 2.10.3 (patch)

Nessun altro pacchetto cambia. `composer audit` ora segnala **1** advisory:
quella di `laravel/framework` (bassa).

## Motivazione

- Le due correzioni sono release di patch della stessa linea: nessun cambio di
  comportamento atteso.
- `symfony/http-foundation` è nel percorso di ogni richiesta HTTP, quindi la
  ReDoS su `Cache-Control` riguarda l'app davvero. `league/commonmark` è
  raggiungibile solo se si usa Markdown, cosa che non avviene: aggiornato
  comunque perché a costo quasi nullo.
- **Alternativa scartata**: `composer update laravel/framework ...` (anche
  con `~13.30.0`). Il solver porta con sé 34 pacchetti e tutto Symfony da
  7.4 a **8.1** (salto di versione maggiore). Non accettabile su
  un'app in produzione per correggere un problema di gravità bassa.

## Test

Eseguiti in locale (Docker):
- `composer audit` prima/dopo: da 4 a 1 advisory;
- `composer update --dry-run` per vedere l'effetto prima di applicarlo;
- `php artisan about` e `php artisan route:list` senza errori; il
  `package:discover` post-update riesce.

NON verificato: suite di test completa (da lanciare su richiesta), flussi reali
a runtime (login, licensing, CRUD, export). Il cambio è di sola patch.

## Rischi e note

- **Aggiornamento successivo (2026-10-16): chiusa dal doc [306](306-aggiornamento-laravel-13-30.md)** con un aggiornamento del solo
  `laravel/framework` a 13.30.1, senza Symfony 8 (il salto serviva solo con `--with-dependencies`). Quanto segue è la situazione al momento del 304.
- **Advisory residua `laravel/framework`** (XSS nella pagina di debug, bassa):
  riguarda solo con `APP_DEBUG=true`, che in produzione deve essere spento (in
  locale è attivo). Si chiude con l'aggiornamento di Laravel a ≥ 13.30, che
  oggi richiede di decidere cosa fare di Symfony 8: da trattare come intervento
  a sé, non come patch. Fino ad allora verificare `APP_DEBUG=false` nelle
  istanze: non è fissato nell'immagine ma nel `.env` per istanza. Per le
  istanze create da Chive CP è verificato (lo stub `resources/chivecp/env.stub`
  di LICENSES scrive `APP_ENV=production` e `APP_DEBUG=false`; il default di
  `config/app.php` è comunque `false`). Resta da controllare nelle
  installazioni dirette dei clienti, il cui `.env` è fuori dal repo.
- Le istanze già in produzione ricevono la correzione solo quando vengono
  ricostruite con la nuova immagine.

## Rollback

Ripristinare `composer.lock` dalla versione precedente e `composer install`
(oppure `composer update symfony/http-foundation:7.4.18 league/commonmark:2.10.0`).
