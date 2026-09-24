# 080 - Module Generator: tutto il wizard riservato al superadmin

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Sicurezza
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/ModulsController.php`
  - `tests/Feature/ModuleGeneratorCrudTest.php`

## Contesto

Rimandato di proposito in [068](068-module-generator-rce-e-test.md) ("non
esteso a tutto il wizard per non rompere un uso legittimo con permessi di
sola view non ancora caratterizzato"), poi in backlog. Ripreso su richiesta
esplicita.

## Situazione prima

Riservati al superadmin: `postStep3()`, `postStep5()`, `save_table()`
(DDL, chiamata da `postStep2()`), `getEdit()`, `enable()`, export/import.
Protetti solo da `isView()` (permesso di **sola visualizzazione** sul
modulo Module Generator):

- `getAdd()`, `getStep1()`–`getStep5()` (pagine del wizard);
- **`postStep1()`**: crea un modulo nuovo — genera il file controller PHP,
  la riga `cms_moduls`, un `cms_privileges_roles` con tutti i permessi per
  il ruolo di chi lo crea e opzionalmente una voce di menu;
- `postStep2()` (la DDL vera è poi bloccata da `save_table()`);
- `getTableColumns()` / `getCheckSlug()` (AJAX usati solo dal wizard).

**Senza alcun controllo**: `postStep4()` — riscrive il blocco `FORM` nel
sorgente PHP del controller di qualunque modulo generato. (Il backlog citava
solo `getStep4`; rileggendo il codice è emerso che era `postStep4` a non
avere nulla.)

`getEdit()` era già superadmin: nell'interfaccia il wizard è raggiungibile
da un non superadmin solo dal pulsante "Add" (→ step 1).

## Situazione dopo

Nuovo helper privato `denyWizardUnlessSuperadmin($moduleName, $json)`:
stesso log e redirect `denied_access` già usati negli altri step (oppure
403 JSON vuoto per i due endpoint AJAX, stesso formato del loro controllo
`isView()` esistente). Aggiunto, **dopo** il controllo `isView()` già
presente (non sostituito), a: `getAdd`, `getStep1`, `postStep1`,
`getStep2`, `postStep2`, `getStep3`, `getStep4`, `postStep4`, `getStep5`,
`getTableColumns`, `getCheckSlug`.

La lista moduli (`getIndex`) non è toccata.

Nuovi test: `postStep1` e `postStep4` rifiutati per un tenant admin con
pieno accesso al modulo (nessun modulo creato, file controller invariato);
tutte le pagine del wizard + i 2 AJAX rifiutati per lo stesso utente.

**Comportamento visibile che cambia**: un utente non superadmin con
permesso sul modulo Module Generator non può più creare moduli dal wizard
(prima poteva arrivare fino a generare controller + permessi + menu, anche
se la creazione della tabella veniva poi bloccata allo step 2).

## Motivazione

Il wizard scrive codice PHP eseguito dall'applicazione e assegna permessi:
stesso livello di rischio degli step già riservati al superadmin. Tenere
metà wizard aperta lasciava sia uno stato incoerente (moduli creati a metà
da chi non può completare gli step successivi) sia `postStep4()`
completamente scoperto.

## Test

- Manuale via curl da superadmin: `module_generator`, `add`, `step1`,
  `step1/19`…`step5/19`, `table-columns/cms_moduls`, `check-slug/xyz` → 200
  (invariati). Solo GET: nessun POST che creasse/modificasse moduli.
- Caso non superadmin: coperto dai 3 nuovi test automatici. Eseguiti su
  richiesta il 2026-09-24: tutti i 18 test del file verdi (run congiunto con
  078/079, 56 test / 217 asserzioni); nessun controller `*Phpunit*` rimasto
  su disco.
- `php -l` sul controller e sul file di test.

## Rischi e note

- **Da verificare sui clienti** (era il motivo del rinvio in 068): se in
  produzione esistono ruoli non superadmin che usano davvero il wizard per
  creare moduli, da ora non possono più farlo. Controllare
  `cms_privileges_roles` per il modulo `module_generator` sui ruoli non
  superadmin prima del deploy.
- Non toccato: `getTypeInfo()` (legge `type_components/{type}/info.json`,
  nessun controllo di privilegio, espone solo metadata statici dei field
  type).

## Rollback

Rimuovere le chiamate a `denyWizardUnlessSuperadmin()` (o l'helper) dal
controller; rimuovere i 3 test nuovi.
