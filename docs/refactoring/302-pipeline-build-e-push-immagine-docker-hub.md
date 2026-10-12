# 302 - Pipeline: build e push dell'immagine di produzione su Docker Hub

- **Data**: 2026-10-12
- **Stato**: Pronto, NON eseguito (workflow modificato, non committato né provato in GitHub Actions)
- **Area**: CI / Docker
- **File/aree di codice coinvolte**: `.github/workflows/deploy.yml` (job nuovo `build-and-push`, trigger sui tag `v*`)

## Contesto

Il blueprint (sezione "Pipeline di build e rilascio") prevede: test → build dell'immagine → push su Docker Hub privato con tag
`dev-latest`, `dev-<sha>`, `vX.Y.Z`, poi webhook verso il Gateway (CI → Chive CP). Qui si prepara la parte build e push,
per ora solo per CustomerHive (il piano gratuito di Docker Hub ha un solo repository privato).

## Situazione prima

`deploy.yml` ha il job `tests` (push e PR su main/dev) e `deploy-dev` (SSH su dev.thecustomerhive.com solo su push a `dev`).
Nessuna immagine pubblicata.

## Situazione dopo

- `on.push.tags: ['v*']`: un tag `vX.Y.Z` fa girare `tests` (e il nuovo job); `deploy-dev` resta solo per `dev` (la sua condizione è invariata).
- Job **`build-and-push`** (`needs: tests`, solo push su `dev` o tag `v*`, mai sulle PR): login a Docker Hub con i Secrets
  `DOCKERHUB_USERNAME` e `DOCKERHUB_TOKEN`, build con `docker/Dockerfile.prod`, push su **`donicid4p/customerhive`**:
  - push su `dev` → `dev-latest` e `dev-<sha7>`;
  - tag `v1.4.0` → `v1.4.0` (nessuna immagine di versione senza un tag esplicito);
  - `APP_VERSION` (footer) = tag della release oppure `dev-<sha7>`; cache di build GitHub (`type=gha`); solo linux/amd64.
- Il passo finale (webhook al Gateway) è un commento: si attiva quando esiste `POST /internal/ci/webhook` in LICENSES.

## Motivazione

Stessa pipeline del blueprint, con un solo job aggiunto: i test restano la condizione, il deploy su dev non cambia.
Alternativa scartata: un workflow separato (non potrebbe dipendere dal job dei test senza `workflow_run`).

## Test

`actionlint` sul file: nessun errore. Non eseguito in GitHub Actions. Non verificata una build da checkout pulito in CI
(in locale `docker build` da cartella di lavoro funziona: docs 298).

## Rischi e note

- **Prima di eseguirlo, creare su Docker Hub il repository `customerhive` come PRIVATO**: un repository creato dal primo push
  può nascere pubblico (dipende dall'impostazione dell'account).
- **La CI costruisce da git, non dalla cartella di lavoro**: `docker/Dockerfile.prod`, `docker/entrypoint.prod.sh`, `docker/apache/*prod*`,
  `docker/php/prod.ini`, i comandi `Instance*`, `HealthController`, `config/trustedproxy.php`, il nuovo `.dockerignore` ecc. sono oggi
  **non committati**: senza di loro il job fallisce. Vanno committati insieme (o prima) del workflow.
- Il job parte anche per i tag: se un tag punta a un commit non testato, i test girano comunque sul tag (il job `tests` ora si attiva sui tag).
- Immagine solo amd64: se il Data Plane fosse arm64 serve `platforms: linux/amd64,linux/arm64`.
- Scansione di vulnerabilità e firma dell'immagine non previste.

## Rollback

Rimuovere il job `build-and-push` e il blocco `tags` da `deploy.yml`.
