# 010 - Popup "Browse data" non si chiudeva dopo Select (bug sistemico sui 7 componenti relazione)

- **Data**: 2026-08-27
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `packages/crocodicstudio/crudbooster/src/controllers/CBController.php` (`getModalData()`)
  - `packages/crocodicstudio/crudbooster/src/views/default/type_components/datamodal/browser.blade.php`
  - `packages/crocodicstudio/crudbooster/src/views/default/type_components/{tenant_group,group_tenant,group_members,group_items,item_access,item_tenant,user_groups}_datamodal/browser.blade.php`

## Contesto

Segnalato dall'utente su `/admin/tenants/group/1`: cliccando "Select" in
un popup "Browse data" (elenco gruppi da collegare a un tenant), il popup
non si chiudeva. Dalla console del browser: `Uncaught Error: Syntax
error, unrecognized expression: #` dentro `selectAdditionalDataname`.

## Situazione prima

Due bug distinti e sovrapposti, entrambi preesistenti (nessuno introdotto
da [006](006-controller-sistema-app-http-controllers-system.md)):

1. **Causa immediata del crash**: tutti e 7 i componenti "collega
   un'entità a un'altra" (`tenant_group`, `group_tenant`, `group_members`,
   `group_items`, `item_access`, `item_tenant`, `user_groups` -
   `_datamodal`) avevano un blocco copiato dal componente base `datamodal`
   che interpreta il parametro `select_to` come coppie
   `campo:destinazione` per costruire il JSON da passare al form al
   click di "Select". Ma tutti e 7 questi componenti riusano
   **lo stesso parametro** `select_to` per un motivo diverso: filtrare
   dalla lista le righe già collegate (es. i gruppi già assegnati al
   tenant), passando semplicemente l'id nudo del record (es. `1`, non
   `"campo:destinazione"`). Il parsing produceva quindi una chiave vuota
   `""` nel JSON risultante; in JS,
   `$('#' + key)` con `key=""` diventa il selettore jQuery invalido `'#'`,
   che lancia un'eccezione **prima** che il codice arrivasse a chiudere
   il popup (`hideModal...()`, l'ultima riga della funzione).

2. **Causa più profonda, che rendeva inutile il fix del singolo file**:
   `CBController::getModalData()` smista la richiesta del popup al
   `browser.blade.php` giusto in base al parametro `type`, ma lo `switch`
   **non aveva un case per `tenant_group_datamodal`, `group_tenant_datamodal`
   né `item_tenant_datamodal`** — finivano tutti nel `default:`, che usa
   il componente **base** `datamodal/browser.blade.php` invece di quello
   dedicato. Come effetto collaterale, il campo "Description" del form
   (pensato per auto-compilarsi alla selezione via una chiave
   `datamodal_description` che solo il componente dedicato imposta) non
   veniva mai valorizzato. Notato anche un `case 'user_groups_datamodal'`
   duplicato nello switch (innocuo, ma dead code).

## Situazione dopo

- `getModalData()`: aggiunti i 3 case mancanti, rimosso il case duplicato.
- Componente base `datamodal/browser.blade.php` (usato anche per un caso
  legittimo con vere coppie `campo:destinazione`, es. il campo Qlik Conf
  degli utenti): reso tollerante — se un elemento di `select_to` non ha i
  due punti (quindi non è nel formato atteso), viene ignorato invece di
  produrre una chiave vuota. Comportamento invariato per l'uso legittimo.
- I 7 componenti dedicati "*_datamodal": rimosso il blocco di parsing
  vestigiale (mai necessario lì, dato che `select_to` è sempre un id
  nudo usato solo per il filtro, mai per mappare campi extra).

## Motivazione

Il bug non era isolato a Tenant→Gruppi: colpiva tutti e 7 i popup di
collegamento tra entità nell'app (Tenant↔Gruppo, Gruppo↔Utenti,
Gruppo↔Qlik Item, Qlik Item↔Gruppo, Qlik Item↔Tenant, Utente↔Gruppi).
Aveva senso sistemarli tutti insieme piuttosto che uno alla volta quando
segnalati singolarmente.

## Test

Non eseguita la suite automatica (nessun test esistente su questi
componenti). Verificato con mezzi leggeri, incluso il payload JS reale
(non solo lettura del codice):
- `php -l` su tutti gli 8 file toccati: nessun errore.
- Richiesta diretta e autenticata all'endpoint del popup
  (`/admin/tenants/modal-data?...&type=tenant_group_datamodal`) prima e
  dopo il fix: **prima** → `{"datamodal_id":4,"datamodal_label":"sdafdf","":""}`
  (chiave vuota, avrebbe fatto esplodere lo script); **dopo** →
  `{"datamodal_id":1,"datamodal_label":"IT","datamodal_description":"Information Technology"}`
  (pulito, e il campo Description ora compare - bonus, prima mancava
  anche quello per lo stesso motivo del routing sbagliato).
- Verificato che il filtro "escludi già collegati" del componente
  dedicato funzioni davvero (tenant 1, con tutti e 4 i gruppi già
  assegnati, mostra un popup vuoto; tenant 2, senza gruppi assegnati,
  mostra tutti e 4).

## Rischi e note

Non ho un browser reale collegato in questa sessione: la chiusura visiva
del popup non è stata vista con i miei occhi, solo dedotta dal fatto che
il payload JS che la causava non contiene più la chiave che generava
l'eccezione. Da confermare visivamente dall'utente.

## Rollback

`git revert` del commit — tutte le modifiche sono behavior-preserving o
strictly-safer (rimozione di codice morto/difettoso), nessuna dipendenza
da dati.
