# 185 - Qlik confs, tooltip Hub/QMC diversi per On-Premise e SaaS

- **Data**: 2026-10-01
- **Stato**: Completato
- **Area**: Frontend / Qlik
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/QlikConfController.php` (`cbInit`, `addaction`)
  - `resources/lang/en/crudbooster.php`, `resources/lang/it/crudbooster.php`

## Contesto

Nella lista di `admin/qlik_confs` i due bottoni di azione per riga (solo
superadmin) aprono l'Hub e la console di gestione della conf Qlik. Verificando
che i link fossero corretti è emerso che gli URL generati sono giusti
(`/hub/`, `/qmc/` per On-Premise; `/hub/`, `/console/` per SaaS, vedi
`AdminQlikItemsController::GetRouteSenseHub/GetRouteSenseQMC`), ma i tooltip no.

## Situazione prima

I titoli erano stringhe fisse in chiaro, uguali per ogni conf: "Qlik Sense Hub"
e "Qlik Sense QMC". Per una conf SaaS (Qlik Cloud) il secondo era fuorviante:
la QMC esiste solo su Qlik Sense on-premise, su Qlik Cloud la pagina `/console/`
è la Management Console. In più i testi non passavano da `trans()`.

## Situazione dopo

Quattro bottoni invece di due, due per tipo, scelti con `showIf` sul campo
`type` della riga:

| Tipo | Hub | Console |
|---|---|---|
| On-Premise (`type != "SAAS"`) | Qlik Sense Hub | Qlik Sense QMC |
| SaaS (`type == "SAAS"`) | Qlik Cloud Hub | Qlik Cloud Management Console / Console di gestione Qlik Cloud |

Le URL di destinazione non cambiano (stesse route di prima). Nuove chiavi
`qlik_action_hub_onpremise`, `qlik_action_qmc_onpremise`, `qlik_action_hub_saas`,
`qlik_action_console_saas` in en e it.

## Motivazione

`addaction` è definito una sola volta per tutta la lista, non per riga:
`showIf` (valutato da `components/action.blade.php`) è il modo supportato per
mostrare un bottone solo per certe righe. Alternativa scartata: un tooltip
neutro unico ("Hub" / "Console di gestione").

## Test

Solo `php -l` sui tre file (nel container). Non verificato a mano nel browser:
da controllare in lista che una conf On-Premise mostri i due bottoni "Qlik
Sense ..." e una conf SaaS quelli "Qlik Cloud ...", e che i link aprano la
pagina giusta.

## Rischi e note

- Cambio solo visibile (tooltip), nessun cambio di comportamento dei link.
- `showIf` viene valutato con `eval` sul valore del campo `type`; il confronto
  è `!= "SAAS"` per On-Premise, quindi qualunque valore diverso da `SAAS`
  (incluso vuoto) ricade nel ramo On-Premise, come già in
  `GetRouteSenseHub` (`$conf->type == 'SAAS'`).
- Nei titoli non vanno usati apostrofi (finiscono dentro una stringa `eval`
  tra apici singoli): per questo l'italiano è "Console di gestione Qlik Cloud".

## Rollback

Ripristinare i due `addaction` originali in `QlikConfController::cbInit`
(titoli fissi "Qlik Sense Hub" / "Qlik Sense QMC", senza `showIf`) e togliere
le quattro chiavi `qlik_action_*` dai file lingua.
