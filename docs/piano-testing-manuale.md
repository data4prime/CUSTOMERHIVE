# Piano di testing manuale — dev.thecustomerhive.com

Scenario aziendale simulato per coprire manualmente le funzioni principali
dell'app, con doppio scopo: QA (prima di considerare l'ambiente stabile) e
demo (lo stesso setup deve poter essere mostrato a un cliente/prospect).

Indipendente dal rinnovo UI/UX (vedi [`ui-ux-annotazioni.md`](ui-ux-annotazioni.md)
e `refactoring/README.md`): è un giro di test sull'app *attuale*, anche se
gli screenshot presi durante questo giro potranno servire da riferimento
"prima" per il futuro revamp.

**Stato**: piano discusso, non ancora eseguito. Il DB di dev verrà ripulito
dall'utente prima di iniziare. Nessun dato di questo scenario è stato
ancora creato in dev.

Questo documento contiene due varianti di scenario, **alternative tra
loro** (se ne esegue una, non entrambe insieme nello stesso giro di test):

- **Scenario A**: più aziende clienti separate — i tenant sono clienti
  reali distinti (il caso d'uso "normale" di CustomerHive).
- **Scenario B**: un solo cliente, dove i tenant rappresentano i reparti
  interni di quell'unica azienda — più complicato, mette sotto stress un
  meccanismo diverso (dashboard condivise tra più reparti).

# Scenario A — più aziende clienti

## Scope

**Dentro**: Tenants / Groups / Users / Roles (Privileges), Module
Generator, Statistic Builder (solo i widget non-Qlik), API Generator, Menu
Management, e le funzioni trasversali elencate in "Il resto".

**Fuori per ora** (deciso il 2026-09-02): Qlik Items, Qlik Settings, Chat
AI — nessuna configurazione di licenza/server Qlik in questo giro.

## Modello dati verificato (per costruire dati coerenti, non a caso)

- **Tenant** (`app/Tenant.php`, tabella `tenants`): ha branding proprio
  (logo, favicon, `login_background_color`, `login_font_color`) — login
  personalizzabile per cliente.
- **Group** (`app/Group.php`, tabella `groups`): collegato a uno o più
  tenant tramite la pivot `GroupTenants`.
- **User** (`app/User.php`, tabella `cms_users`): ha un `tenant` e un
  `primary_group` singoli, ma può appartenere anche a **gruppi
  secondari** tramite la pivot `UsersGroup` —
  `UserHelper::current_user_groups()` restituisce l'unione di primario +
  secondari. Ha anche un ruolo (`id_cms_privileges`) con flag
  `is_superadmin` / `is_tenantadmin`, e un campo reale di scadenza account
  `data_scadenza` con job di notifica dedicato
  (`app/Console/Commands/UserExpiryNotification.php`).
- **Visibilità dei menu** (`CRUDBooster::sidebarMenu()`): filtrata sempre
  per tenant (join `menu_tenants`) e, per i ruoli non admin, anche per
  gruppo (join `menu_groups` su `current_user_groups()`). Questo vale per
  qualsiasi voce di menu, incluse le dashboard Statistic Builder.
- **Fallback "dashboard home"** (`routes/crudbooster.php:130-134`): la
  vista `home.blade.php` (via `AdminController::getIndex()`) si vede
  *solo* se **nessun** menu ha `is_dashboard=1` per il proprio privilegio.

## Campi da compilare — riferimento rapido

Elenco dei campi reali dei form (verificati nei controller, non a memoria),
da usare come riferimento mentre si creano i record concreti più sotto.

**Tenant** (`AdminTenantsController`): Name, Description, Logo (upload),
Favicon (upload), Background Color (`login_background_color`, color
picker), Background Image (`login_background_image`, upload), Font Color
(`login_font_color`, color picker), Domain name (`domain_name` — **solo
lettere e numeri, max 20 caratteri**, niente spazi/trattini: validazione
`regex:/^[a-zA-Z0-9]+$/u`). Logo/Favicon/Background Image possono restare
vuoti (non richiesti) — i colori sono l'unico branding a costo zero e
utile per riconoscere i tenant a colpo d'occhio durante il test.

**Group** (`AdminGroupsController`): Name, **Help** (attenzione:
l'etichetta nel form è "Help" ma il campo/colonna è `description` — è
comunque una descrizione libera del gruppo).

**User** (`AdminCmsUsersController`): Name, Email (univoca), Privilege
(ruolo), Tenant, Primary Group (cascading sul Tenant scelto), Expiry date
(`data_scadenza`, opzionale), Status (default "Active", l'unica altra
opzione nel menu a tendina è "Inactive"), Photo (upload, opzionale),
Language (en/it), Password + Password Confirmation (vuoti = "nessun
cambio" in edit, vanno sempre compilati in creazione). Nessun campo
"username": il login è sempre via email.

**Privilege/Role** (`PrivilegesController`, vista `privileges.blade.php`):
Name, tipo privilegio — radio **Superadmin / Tenant Admin / Nessuno**
("Nessuno" = ruolo standard con permessi CRUD granulari per modulo,
assegnati più sotto nella stessa pagina), Theme Color — select con 12
opzioni fisse: `skin-blue(-light)`, `skin-yellow(-light)`,
`skin-green(-light)`, `skin-purple(-light)`, `skin-red(-light)`,
`skin-black(-light)`. Colora la barra dell'header (assegnare colori
diversi per ruolo aiuta anche a riconoscere a vista con quale utente si è
loggati durante il giro di test finale).

**Credenziali di test**: per semplicità, usa **la stessa password per
tutti gli utenti creati in questo scenario** (es. `Test#2026!`) e tienile
elencate in una tabella "Credenziali" per ogni tenant/reparto, così il
giro finale di login (punto 9/10 dell'ordine di esecuzione) non richiede
di andare a recuperarle una per una.

## Scenario: 3 tenant, apposta asimmetrici

| Tenant                 | Description                            | Background Color        | Font Color | Domain name         | Gruppi                                | Utenti (indicativo) | Ruolo nello scenario          |
| ---------------------- | -------------------------------------- | ----------------------- | ---------- | ------------------- | ------------------------------------- | ------------------- | ----------------------------- |
| **Novara Energia**     | Utility energetica, sede a Novara      | `#0F4C81` (blu)         | `#FFFFFF`  | `novaraenergia`     | Direzione, Operativo, Finance         | 5-6                 | caso medio                    |
| **Bluewave Logistics** | Logistica e trasporti multi-sede       | `#1B7A5E` (verde acqua) | `#FFFFFF`  | `bluewavelogistics` | Direzione, Magazzino, Commerciale, IT | 7-8                 | caso grande, un gruppo in più |
| **Portosole SpA**      | Cantieristica navale, sede a Portosole | `#7A1B3D` (bordeaux)    | `#FFFFFF`  | `portosolespa`      | Direzione (solo)                      | 2                   | caso minimo                   |

### Groups — descrizioni

| Tenant | Group | Help/Description |
| --- | --- | --- |
| Novara Energia | Direzione | Vertice aziendale, visibilità completa sul tenant |
| Novara Energia | Operativo | Gestione impianti e operatività quotidiana |
| Novara Energia | Finance | Contabilità e controllo di gestione |
| Bluewave Logistics | Direzione | Vertice aziendale, visibilità completa sul tenant |
| Bluewave Logistics | Magazzino | Gestione stock e movimentazione merci |
| Bluewave Logistics | Commerciale | Vendite e relazioni clienti |
| Bluewave Logistics | IT | Supporto sistemi informativi interni |
| Portosole SpA | Direzione | Unico gruppo del tenant, vertice aziendale |

### Roles/Privileges

| Role | Tipo | Theme Color |
| --- | --- | --- |
| Superadmin | Superadmin | `skin-black` (già esistente di default) |
| Tenant Admin | Tenant Admin | `skin-blue` (riusato su tutti e 3 i tenant — il tenant è un campo dello user, non del ruolo) |
| Manager | Nessuno (permessi CRUD custom) | `skin-purple` |
| Viewer | Nessuno (solo lettura) | `skin-green` |

### Users — esempi concreti (completare il resto sullo stesso modello)

| Tenant | Name | Email | Password | Privilege | Primary Group | Gruppi secondari | Note edge case |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Novara Energia | Elena Ricci | elena.ricci@novaraenergia.test | `Test#2026!` | Tenant Admin | Direzione | — | capo reparto |
| Novara Energia | Marco Sala | marco.sala@novaraenergia.test | `Test#2026!` | Viewer | Operativo | — | utente standard |
| Bluewave Logistics | Chiara Neri | chiara.neri@bluewavelogistics.test | `Test#2026!` | Tenant Admin | Direzione | — | capo reparto |
| Bluewave Logistics | Paolo Conti | paolo.conti@bluewavelogistics.test | `Test#2026!` | Manager | Commerciale | **IT** (secondario) | verifica unione menu Commerciale+IT |
| Bluewave Logistics | Giulia Ferri | giulia.ferri@bluewavelogistics.test | `Test#2026!` | Manager | IT | — | ruolo "Manager" → deve atterrare su `home.blade.php` (nessun `is_dashboard`) |
| Bluewave Logistics | Luca Bianchi | luca.bianchi@bluewavelogistics.test | `Test#2026!` | Viewer | Magazzino | — | `data_scadenza` = ieri → login deve essere bloccato |
| Bluewave Logistics | Sara Verdi | sara.verdi@bluewavelogistics.test | `Test#2026!` | Viewer | Magazzino | — | `data_scadenza` = +3 giorni → deve comparire nella notifica di scadenza |
| Portosole SpA | Davide Moro | davide.moro@portosolespa.test | `Test#2026!` | Tenant Admin | Direzione | — | capo reparto |
| Portosole SpA | Anna Costa | anna.costa@portosolespa.test | `Test#2026!` | Viewer | Direzione | — | Status = **Inactive** → login deve essere bloccato |

**Complicazioni sui permessi**:
- Un utente Bluewave con `primary_group` = Commerciale ma membro
  secondario anche di IT → verifica che veda l'unione dei menu/dashboard
  assegnati ai due gruppi.
- Ruolo aggiuntivo **"Manager"** (non tenant admin, non superadmin):
  permessi CRUD granulari diversi per modulo via Privileges, non solo il
  tris superadmin/tenantadmin/viewer.
- Al ruolo "Manager" **non** viene assegnato alcun menu `is_dashboard=1`,
  per far scattare *di proposito* il fallback `home.blade.php`.
- Edge case scadenza/stato utente: un utente con `data_scadenza` già
  passata (deve bloccare il login), uno in scadenza a breve (per vedere la
  tabella HTML reale generata da `build_utenti_scadenza_tenantadmin()`),
  uno con status disattivato.

**Obiettivo di test critico**: un utente Bluewave non deve mai vedere
nulla di Novara Energia o Portosole (isolamento tenant); un utente
"Operativo" non deve vedere ciò che è riservato a "Direzione" (isolamento
gruppo); un utente multi-gruppo deve vedere l'unione corretta.

## Module Generator — due moduli collegati

**⚠️ Attenzione ai tipi colonna** (bug reale trovato e corretto il
2026-09-04 su un modulo generato "Ordini Vendita"): in fase di creazione
tabella il Module Generator offre solo **3 tipi colonna**: Testo,
Numero, Booleano. Un campo che nel form userà un widget "Select" con
etichette testuali (es. "Attivo"/"Scaduto") **deve** avere colonna
**Testo**, mai Numero — creandolo come Numero, ogni salvataggio fallisce
con `SQLSTATE[HY000]: ... Incorrect integer value` perché il widget invia
la label testuale, non un intero. Il tipo colonna e il tipo di widget del
form sono due scelte **indipendenti**, fatte in due passaggi diversi del
generator: sceglierne uno coerente con l'altro è responsabilità di chi
compila il modulo, il generator non lo valida.

**Contratti**:

| Campo | Tipo colonna | Widget form | Note |
| --- | --- | --- | --- |
| Nome | Testo | Testo | es. "Fornitura energia — Comune di Novara" |
| Tenant | Numero (id) | Select → tabella `tenants` | relazione, non testo libero |
| Data Inizio | **Testo** (non Numero) | Data | es. `2026-01-15` |
| Data Scadenza | **Testo** (non Numero) | Data | es. `2026-12-31` |
| Stato | **Testo** (non Numero — vedi avviso sopra) | Select, `dataenum`: `Attivo;In Rinnovo;Scaduto;Annullato` | |
| Allegato | Testo | Upload | PDF del contratto, opzionale |

**Fatture** (collegata a Contratti via relazione `datamodal`/child, per
testare il master-detail generato, non solo CRUD piatto):

| Campo | Tipo colonna | Widget form | Note |
| --- | --- | --- | --- |
| Numero Fattura | Testo | Testo | es. `FT-2026-0001` |
| Contratto | Numero (id) | datamodal/child → Contratti | relazione master-detail |
| Importo | Numero | Numero | es. `12500` |
| Data Fattura | **Testo** (non Numero) | Data | es. `2026-03-10` |
| Pagata | Booleano | Checkbox | |

## Statistic Builder — dashboard differenziate per gruppo

- Bluewave ha **due** dashboard: "Esecutiva" (visibile solo a Direzione) e
  "Operativa" (visibile a Magazzino + Commerciale) — testa il filtro per
  gruppo sul menu, non solo l'isolamento tra tenant.
- Novara Energia e Portosole: una dashboard ciascuna.
- I widget (Table / Small Box / Chart Bar) attingono a dati veri di
  Contratti/Fatture appena creati (es. "contratti attivi", "fatturato per
  mese"), non a numeri finti scollegati.
- Widget da coprire (7, esclude Qlik Widget): Small Box, Table, Chart
  Area, Chart Line, Chart Bar, Panel Area, Module Panel.

**Esempi concreti di widget** (SQL Query Line / Line Area Name, come da
form di configurazione del componente):
- *Small Box* "Contratti attivi": `SELECT COUNT(*) AS value FROM contratti WHERE stato='Attivo'`.
- *Table* "Contratti in scadenza": righe di `contratti` con `data_scadenza` nei prossimi 30 giorni.
- *Chart Bar/Line/Area* "Fatturato mensile per tenant" — una query per
  serie (una per tenant/categoria), come nel widget di esempio già
  presente: `SELECT DATE_FORMAT(STR_TO_DATE(data_fattura,'%Y-%m-%d'),'%Y-%m') AS label, SUM(importo) AS value FROM fatture WHERE tenant=1 GROUP BY label ORDER BY label` (una query analoga per ciascun tenant, "Line Area Name" = nomi tenant separati da `;`).
  **Verifica di regressione mirata**: costruisci deliberatamente questo
  widget in modo che le serie abbiano un numero diverso di mesi con dati
  (es. un tenant con fatture solo negli ultimi 3 mesi, un altro con 8) —
  bug reale (`Undefined array key`, indice posizionale invece che per
  label) trovato e corretto il 2026-09-04 su Chart Area/Line/Bar, va
  verificato che non si ripresenti.

## API Generator — un flusso, non solo la generazione

- API CRUD completa su **Contratti**.
- API sola lettura su **Tenants**.
- Sequenza reale via curl: list → create → get → update → delete su
  Contratti, verificando autenticazione (API key/secret key).
- Esempio corpo POST per la create: `{"nome": "Fornitura energia — Comune di Novara", "tenant": 1, "data_inizio": "2026-01-15", "data_scadenza": "2026-12-31", "stato": "Attivo"}`.
- Verifica anche il caso negativo: chiamata senza API key/secret (o con
  credenziali sbagliate) deve rispondere con un errore di autenticazione,
  non con i dati.

## Il resto — checklist trasversale

- Login / logout / lockscreen / password dimenticata (incluso il login
  brandizzato per tenant).
- Bulk action su Tenants/Users (azione multipla su selezione).
- Export/Import CSV su un modulo che li ha abilitati (Tenants li ha
  disattivati di default — da verificare quale modulo generato li ha
  attivi).
- Detail view (Tenants ha `button_detail=true`, distinta dalla edit) e
  filtro avanzato.
- Access Log: verificare che le azioni sopra risultino davvero loggate.
- Settings: modifica di almeno due gruppi di impostazioni diversi.
- Pagine di errore (403/404/500).
- Pannello notifiche in header: verificare se è funzionante o solo
  placeholder.
- Ricerca/ordinamento/paginazione a occhio su un paio di liste (già
  coperte da test automatici, ma da vedere anche visivamente).
- Sessioni multiple in due browser diversi, per un controllo a occhio
  sull'isolamento sessione.

## Ordine di esecuzione (bottom-up)

1. Roles/Privileges (incluso "Manager") + menu-per-privilegio.
2. I 3 Tenant (branding diverso per il login personalizzato).
3. Groups.
4. Users (incluso il caso multi-gruppo e i casi di scadenza/status).
5. Statistic Builder (dashboard differenziate per gruppo su Bluewave,
   singola su Novara/Portosole) — anche se i dati di Contratti/Fatture non
   esistono ancora, si può tornare a completare i widget dopo il punto 6.
6. Module Generator (Contratti + Fatture collegate).
7. API Generator (Contratti CRUD, Tenants sola lettura).
8. Menu Management (collegare tutto per privilegio, incluso l'edge case
   "Manager" senza dashboard).
9. Giro finale: login come ciascuna persona simulata, navigazione
   completa, verifica isolamento tenant/gruppo e multi-gruppo.

---

# Scenario B — singolo cliente, i tenant rappresentano i reparti

Variante più complicata: un solo cliente reale, dove ogni **Tenant**
rappresenta un **reparto/divisione interna** dell'azienda invece che un
cliente distinto. Usa lo stesso modello dati di CustomerHive (documentato
sopra) in un modo diverso da quello per cui è pensato — deliberato, per
mettere sotto stress un meccanismo che nello Scenario A non emerge:
**dashboard condivise tra più reparti**.

## Meccanismo reale verificato per la condivisione cross-reparto

Il filtro di visibilità dei menu (`CRUDBooster::sidebarMenu()`,
`app/Helpers/CRUDBooster.php:649`) funziona così, verificato riga per
riga nel codice:

```
menu visibile SE:
  menu_tenants.tenant_id == tenant dell'utente corrente (SEMPRE richiesto)
  E, solo se l'utente NON è superadmin né tenant admin:
  menu_groups.group_id IN (gruppi dell'utente, primario + secondari)
```

Punti chiave per costruire lo scenario:
- **`menu_tenants` e `menu_groups` sono relazioni sul singolo menu**, non
  sul gruppo: un menu/dashboard condiviso tra più reparti si ottiene
  collegando **quel menu** a **più tenant_id contemporaneamente** in
  `menu_tenants`, non facendo appartenere un gruppo a più tenant.
- **Un utente ha un solo tenant** (il suo reparto "di casa") — non può mai
  "stare in due reparti" contemporaneamente. Vede un menu cross-reparto
  solo se quel menu è stato taggato anche con il tenant_id del suo reparto
  *e* lui è nel gruppo giusto.
- **Il Tenant Admin salta il filtro per gruppo**: vede tutti i menu
  taggati sul proprio reparto, a prescindere dal gruppo — un capo reparto
  vede tutte le dashboard del suo reparto, un dipendente normale solo
  quelle del proprio team.
- Il campo `domain_name`/branding per-tenant (pensato per un vero login
  brandizzato per cliente) qui non ha un significato realistico — si può
  lasciare vuoto, oppure usarlo solo per assegnare un colore diverso per
  reparto e riconoscerli a colpo d'occhio durante il test (trucco solo per
  il test, non un caso d'uso reale).

## L'azienda simulata: Meridiana Manifatture S.p.A.

Azienda manifatturiera di medie dimensioni, un solo cliente reale.

| Reparto (= Tenant) | Description | Background Color* | Domain name | Team/gruppi interni (= Group) |
|---|---|---|---|---|
| **Direzione Generale** | Vertice aziendale, presidenza e comitato esecutivo | `#16324F` | `direzionegenerale` | Comitato Esecutivo |
| **Produzione** | Linee di produzione e manutenzione impianti | `#B85C38` | `produzione` | Linea A, Linea B, Manutenzione |
| **Logistica & Magazzino** | Gestione stock, spedizioni e magazzino centrale | `#3D8361` | `logisticamagazzino` | Magazzino Centrale, Spedizioni |
| **Vendite & Marketing** | Vendite Italia/estero e marketing | `#7A4EAB` | `venditemarketing` | Vendite Italia, Vendite Estero, Marketing |
| **Amministrazione & Finance** | Contabilità e controllo di gestione | `#B8860B` | `amminfinance` | Contabilità, Controllo di Gestione |
| **IT & Sistemi Informativi** | Sviluppo e infrastruttura interna | `#1F6F8B` | `itsistemi` | Sviluppo, Infrastruttura |
| **Risorse Umane** | Selezione, formazione e amministrazione del personale | `#A13D63` | `risorseumane` | Selezione & Formazione, Amministrazione del Personale |

\* Come già notato sopra, il branding per-tenant qui non rappresenta un
vero login personalizzato per cliente — il colore serve solo a
riconoscere i reparti a colpo d'occhio durante il test (Font Color:
`#FFFFFF` per tutti, per contrasto).

7 reparti, ~15 team — volutamente più ricco dello Scenario A.

### Groups — descrizioni

| Reparto | Group | Help/Description |
| --- | --- | --- |
| Direzione Generale | Comitato Esecutivo | Presidenza e direzione generale |
| Produzione | Linea A | Prima linea di produzione |
| Produzione | Linea B | Seconda linea di produzione |
| Produzione | Manutenzione | Manutenzione impianti e macchinari |
| Logistica & Magazzino | Magazzino Centrale | Gestione stock e inventario |
| Logistica & Magazzino | Spedizioni | Spedizioni e logistica in uscita |
| Vendite & Marketing | Vendite Italia | Rete vendita mercato domestico |
| Vendite & Marketing | Vendite Estero | Rete vendita mercati esteri |
| Vendite & Marketing | Marketing | Comunicazione e marketing prodotto |
| Amministrazione & Finance | Contabilità | Contabilità generale e fornitori |
| Amministrazione & Finance | Controllo di Gestione | Budget e reportistica finanziaria |
| IT & Sistemi Informativi | Sviluppo | Sviluppo software interno |
| IT & Sistemi Informativi | Infrastruttura | Reti, server, postazioni di lavoro |
| Risorse Umane | Selezione & Formazione | Recruiting e formazione del personale |
| Risorse Umane | Amministrazione del Personale | Buste paga, contratti, presenze |
| *(cross-funzionale, nessun tenant proprio)* | Comitato Direzionale | Dirigenti senior di più reparti, gruppo secondario per accedere alla Dashboard Direzionale |

## Il gruppo cross-funzionale: "Comitato Direzionale"

Il pezzo di complessità in più rispetto allo Scenario A:

- Un gruppo **"Comitato Direzionale"**, i cui membri sono alcune persone
  senior *già assegnate* ad altri reparti come tenant primario (es. il
  Direttore Generale in Direzione Generale, il Direttore di Produzione in
  Produzione, il Direttore Commerciale in Vendite, il CFO in
  Amministrazione) — per ciascuno di loro, "Comitato Direzionale" è un
  **gruppo secondario** (pivot `UsersGroup`), non il primario.
- Una dashboard Statistic Builder **"Dashboard Direzionale"**, il cui menu
  viene collegato (in `menu_tenants`) a **tutti e 4** i tenant coinvolti
  (Direzione Generale, Produzione, Vendite, Amministrazione) e (in
  `menu_groups`) al solo gruppo "Comitato Direzionale".
- **Risultato atteso**: ciascuno dei 4 membri la vede (perché il proprio
  tenant è tra quelli taggati e sono nel gruppo giusto), un normale addetto
  della Linea A **non** la vede (è nel tenant giusto — Produzione — ma non
  nel gruppo "Comitato Direzionale"): questo è il test negativo più
  importante di questo scenario.

### Roles/Privileges (stessa logica dello Scenario A)

| Role | Tipo | Theme Color |
| --- | --- | --- |
| Superadmin | Superadmin | `skin-black` (già esistente di default) |
| Tenant Admin | Tenant Admin | `skin-blue` (riusato su tutti i 7 reparti) |
| Manager | Nessuno (permessi CRUD custom) | `skin-purple` |
| Viewer | Nessuno (solo lettura) | `skin-green` |

### Users — esempi concreti (i 4 membri del Comitato + edge case + un dipendente base per reparto)

Stessa password condivisa per tutti gli utenti di test:
`Test#2026!`.

| Reparto | Name | Email | Privilege | Primary Group | Gruppi secondari | Note |
| --- | --- | --- | --- | --- | --- | --- |
| Direzione Generale | Roberto Fabbri | roberto.fabbri@direzionegenerale.test | Tenant Admin | Comitato Esecutivo | **Comitato Direzionale** | membro 1/4 — Direttore Generale |
| Produzione | Simona Galli | simona.galli@produzione.test | Tenant Admin | Linea A | **Comitato Direzionale** | membro 2/4 — Direttore di Produzione, deve vedere anche Linea B/Manutenzione senza esserne membro |
| Vendite & Marketing | Filippo Testa | filippo.testa@venditemarketing.test | Tenant Admin | Vendite Italia | **Comitato Direzionale** | membro 3/4 — Direttore Commerciale |
| Amministrazione & Finance | Ilaria Greco | ilaria.greco@amminfinance.test | Tenant Admin | Contabilità | **Comitato Direzionale** | membro 4/4 — CFO |
| Produzione | Matteo Rinaldi | matteo.rinaldi@produzione.test | Viewer | Linea A | — | addetto normale — **non** deve vedere la Dashboard Direzionale (test negativo chiave) |
| Logistica & Magazzino | Nadia Pellegrini | nadia.pellegrini@logisticamagazzino.test | Viewer | Spedizioni | — | **stagionale**: `data_scadenza` = +5 giorni, per vedere la notifica reale di scadenza |
| Risorse Umane | Enzo Marini | enzo.marini@risorseumane.test | Viewer | Amministrazione del Personale | — | **ex-dipendente**: Status = **Inactive**, lasciato nel sistema (non cancellato) |
| IT & Sistemi Informativi | Vera Longo | vera.longo@itsistemi.test | Manager | Sviluppo | — | dipendente base per reparto non ancora coperto sopra |

## Ruoli da testare

- **Superadmin** (lato CustomerHive/IT interno).
- **Tenant Admin per reparto** (capo reparto) — es. il Direttore di
  Produzione vede tutte le dashboard di Linea A/Linea B/Manutenzione senza
  dover essere membro di ciascun team (bypassa il filtro per gruppo, vedi
  sopra).
- **Manager** (team leader) — permessi CRUD granulari via Privileges,
  soggetto al filtro per gruppo.
- **Viewer** (dipendente base).

## Edge case da includere

- Un dipendente **stagionale/interinale** in Logistica con `data_scadenza`
  vicina (per vedere la notifica reale di scadenza) — vedi Nadia
  Pellegrini nella tabella Users sopra.
- Un reparto di nuova creazione, es. **"Sostenibilità & ESG"** (Description:
  "Reparto ESG di nuova costituzione, non ancora operativo", Background
  Color `#5C5C5C`, Domain name `sostenibilitaesg`), creato ma **non
  ancora configurato**: crea il tenant e almeno un utente Viewer al suo
  interno (es. Tommaso Villa, tommaso.villa@sostenibilitaesg.test), ma
  **non** assegnare alcun menu `is_dashboard=1` al suo ruolo — fa scattare
  di proposito il fallback `home.blade.php`, simulando un reparto appena
  aggiunto e non ancora messo a regime.
- Un ex-dipendente con status disattivato, lasciato nel sistema (caso
  reale comune: l'account si disattiva, non si cancella) — vedi Enzo
  Marini nella tabella Users sopra.

## Module Generator, Statistic Builder, API Generator

Stessa struttura dello Scenario A (moduli **Contratti**/**Fatture**
collegati, dashboard Statistic Builder, API su Contratti), con
un'interpretazione più coerente al contesto: il campo "tenant" di ogni
Contratto indica **quale reparto lo possiede** (es. Vendite = contratti
clienti, Amministrazione = contratti fornitori, IT = licenze software) —
lo stesso meccanismo tecnico dello Scenario A, ma il dato che rappresenta
ha più senso in questa cornice a singolo cliente.

Dashboard Statistic Builder da creare: una per reparto (7) più la
"Dashboard Direzionale" condivisa (8 totali) — usando gli stessi 7 tipi di
widget non-Qlik già visti nello Scenario A.

**Campi Contratti/Fatture**: stessa struttura e stesso avviso sui tipi
colonna dello Scenario A (vedi tabella completa lì) — solo il significato
del campo "Tenant" cambia (reparto proprietario, non cliente). Esempio
concreto: Contratto "Fornitura energia elettrica stabilimento" → Tenant
= Amministrazione & Finance, Data Inizio `2026-01-01`, Data Scadenza
`2026-12-31`, Stato `Attivo`.

**Esempio widget "Dashboard Direzionale"**: una *Table* con i contratti in
scadenza nei prossimi 90 giorni su tutti e 4 i tenant coinvolti, più uno
*Small Box* "Fatturato totale anno corrente" — dati aggregati che
avrebbero senso solo per chi ha visibilità cross-reparto, a differenza
delle dashboard di singolo reparto.

## Ordine di esecuzione (bottom-up)

1. Roles/Privileges (Superadmin/Tenant Admin/Manager/Viewer).
2. I 7 reparti come Tenant (branding minimo o solo colore distintivo per
   riconoscerli a vista durante il test).
3. I ~15 team come Group, collegati al proprio reparto.
4. Il gruppo cross-funzionale "Comitato Direzionale" (senza tenant proprio
   — è un gruppo a cui appartengono utenti di reparti diversi).
5. Users: i dipendenti per team, i 4 dirigenti con "Comitato Direzionale"
   come gruppo secondario, gli edge case (stagionale in scadenza,
   ex-dipendente disattivato).
6. Module Generator (Contratti + Fatture, reinterpretati per reparto).
7. Statistic Builder: 7 dashboard di reparto + 1 "Dashboard Direzionale"
   condivisa (menu taggato su più tenant + sul gruppo Comitato).
8. API Generator (Contratti CRUD, Tenants/reparti sola lettura).
9. Menu Management: collegare tutto, incluso il reparto "Sostenibilità &
   ESG" lasciato deliberatamente senza dashboard.
10. Giro finale: login come ciascun ruolo/persona, con **particolare
    attenzione al test negativo** — un addetto Linea A non deve vedere la
    Dashboard Direzionale, un capo reparto deve vedere tutto il proprio
    reparto senza essere in ogni singolo team.
