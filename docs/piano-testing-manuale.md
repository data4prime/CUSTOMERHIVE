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

## Scenario: 3 tenant, apposta asimmetrici

| Tenant | Gruppi | Utenti (indicativo) | Ruolo nello scenario |
|---|---|---|---|
| **Novara Energia** | Direzione, Operativo, Finance | 5-6 | caso medio |
| **Bluewave Logistics** | Direzione, Magazzino, Commerciale, IT | 7-8 | caso grande, un gruppo in più |
| **Portosole SpA** | Direzione (solo) | 2 | caso minimo |

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

- **Contratti**: nome, tenant collegato, data inizio/scadenza, stato,
  allegato.
- **Fatture**: collegata a Contratti via relazione (field-type
  `datamodal`/child) — per testare il master-detail generato, non solo
  CRUD piatto.

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

## API Generator — un flusso, non solo la generazione

- API CRUD completa su **Contratti**.
- API sola lettura su **Tenants**.
- Sequenza reale via curl: list → create → get → update → delete su
  Contratti, verificando autenticazione (API key/secret key).

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

| Reparto (= Tenant) | Team/gruppi interni (= Group) |
|---|---|
| **Direzione Generale** | Comitato Esecutivo |
| **Produzione** | Linea A, Linea B, Manutenzione |
| **Logistica & Magazzino** | Magazzino Centrale, Spedizioni |
| **Vendite & Marketing** | Vendite Italia, Vendite Estero, Marketing |
| **Amministrazione & Finance** | Contabilità, Controllo di Gestione |
| **IT & Sistemi Informativi** | Sviluppo, Infrastruttura |
| **Risorse Umane** | Selezione & Formazione, Amministrazione del Personale |

7 reparti, ~15 team — volutamente più ricco dello Scenario A.

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
  vicina (per vedere la notifica reale di scadenza).
- Un reparto di nuova creazione, es. **"Sostenibilità & ESG"**, creato ma
  **non ancora configurato** (nessun menu `is_dashboard=1` assegnato ai
  suoi utenti) — fa scattare di proposito il fallback `home.blade.php`,
  simulando un reparto appena aggiunto e non ancora messo a regime.
- Un ex-dipendente con status disattivato, lasciato nel sistema (caso
  reale comune: l'account si disattiva, non si cancella).

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
