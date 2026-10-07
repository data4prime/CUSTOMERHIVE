# 242 - Dettaglio ruolo: stessa struttura della modifica, in sola lettura

- **Data**: 2026-10-07
- **Stato**: Completato (da provare a vista)
- **Area**: Frontend / Amministrazione / Ruoli
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/PrivilegesController.php`
  - `resources/views/crudbooster/privileges.blade.php`
  - `resources/lang/{en,it}/crudbooster.php` (`adm_role_detail`)

## Contesto

`/admin/privileges/detail/ID` differiva molto dal mockup dei ruoli (due card:
dati del ruolo e permessi per modulo, intervento 232).

## Situazione prima

Il dettaglio usava la vista generica `form_detail`: tre righe (Name, Privilege,
Theme Color come testo `skin-*`) e nessuna matrice dei permessi; titolo
"Edit role".

## Situazione dopo

`PrivilegesController::getDetail()` riusa `getEdit($id, true)` e la vista
`privileges.blade.php` con `$readonly`, che ha un ramo dedicato: due card come
la modifica, ma con valori come **testo** (non campi disabilitati): nome,
livello come etichetta, pallino + nome del colore; matrice permessi con
spunte `bi-check-lg` / trattino, senza ricerca, preset o checkbox (e nascosta
per i superadmin, che hanno tutto). In fondo "Indietro" e "Modifica ruolo"
(solo con permesso di update). Titolo "Ruolo"/"Role". Stessi controlli di
accesso di `getEdit`.

**Seguito (stesso giorno)**: tolti i preset "Nessuno / Sola lettura / Editor /
Tutto" dalla matrice di modifica (pulsanti + JS; restano ricerca e caselle
colonna/riga; le chiavi `adm_preset_*` restano nei file lingua, inutilizzate).
Matrice del dettaglio rifatta: intestazioni in maiuscoletto, spunte in
cerchio verde / puntino tenue, moduli non visibili in grigio, contatore
"abilitati / totali" nell'intestazione della card, tabella a filo card.

## Motivazione

Coerenza con il mockup e con il dettaglio di utenti/tenant.

## Test

- `php -l` su controller e file lingua, `view:clear`: puliti.
- Non verificato a vista, suite non lanciata.

## Rischi e note

- Cambia comportamento visibile solo della pagina di dettaglio ruolo
  (`button_detail` e' false: raggiungibile solo da URL diretto).
- Il mockup non e' un file nel repo: confronto fatto sulla descrizione del 232.

## Rollback

Ripristinare i tre file da git.

## Seguito: item eliminati nella pagina `groups/items`

`/admin/groups/items/1` mostrava 136 item contro gli 8 di `/admin/qlik_items`:
132 `qlik_items` sono soft-deleted (`deleted_at` 2026-10-01) ma le righe in
`items_allowed` restano, e la query (e il conteggio "Item" nella lista gruppi)
non filtrava `qlik_items.deleted_at`. Ora entrambe escludono gli eliminati
(`AdminGroupsController::items()` e la colonna conteggio). Comportamento
visibile: la pagina e il conteggio scendono a 8. Le righe orfane in
`items_allowed` non sono toccate (altre query, es. ChatAI, non verificate).

## Seguito: tabelle delle pagine relazione dei gruppi come nel mockup

Dal mockup (artifact "Confronto UI", `pageGroupSub`): la tabella sta in una card
(raggio 20px, ombra, 8px di margine interno) e l'intestazione e' una
"pillola" su fondo diverso dalle righe (`--ch-thead`, bordi, angoli 12px),
righe con filo e hover. Aggiunto in `public/css/ch-ui2.css` (sezione tabelle)
per `.rel-table`, attivo solo con `CH_UI_V2`; `groups/items|tenant|members`
senza `.box` (vedi sopra). Non verificato a vista.

**Verifica nel browser (2026-10-07)**: confronto con i valori del mockup
(`.card`, `.tbl`, `.fh`, `.bk`). Differenze trovate e corrette in
`ch-ui2.css`: celle con sfondo della pagina (ora trasparente), colore testo
`--ch-text`, padding 15px 12px, corpo 13.5px, seconda colonna in grassetto
600, padding della card 20px 8px 8px, titolo `rel-title` 24px/800, briciola
`rel-crumb` a pillola (classi aggiunte in `groups/_page_head`).

**Seguito: `/admin/groups/edit/ID` e `/detail/ID`** (stesso confronto con il
mockup, `group_edit`/`group_detail`): card dei campi (raggio 20px, padding 26px,
righe senza margini negativi), etichette sopra il campo, barra pulsanti senza
fondo, dettaglio come elenco chiave/valore (etichetta maiuscola tenue, valore
in grassetto, filo tra le righe, niente zebra). Testata di
`flat_form_header` con le classi `rel-crumb`/`rel-title` (briciola a pillola,
titolo 24px/800): vale per tutte le pagine nuovo/modifica/dettaglio con
`FlatForm` (tenant, ruoli, utenti, gruppi), solo con `CH_UI_V2`. CSS in
`ch-ui2.css`; la sezione `.flat-card` riguarda i form senza schede/blocchi.

Nota: una diagnosi iniziale sulle intestazioni di `groups/items`
(chiavi `title`/`subtitle` presunte inesistenti) era sbagliata: le chiavi
esistono (apici doppi in `crudbooster.php`). Modifica annullata, nessuna
chiave nuova.

**Seguito: `/admin/users/groups/ID`**: la pagina aveva ancora la struttura
vecchia (card "Aggiungi gruppo" sempre aperta, tabella a zebra con bordi e
cestino). Riscritta `users/groups.blade.php` con gli stessi partial delle
pagine relazione dei gruppi (`groups/_page_head`, `_add_card`, `_remove`,
`_remove_script`): testata con briciola e titolo "Gruppi N", form dietro il
pulsante "Aggiungi gruppo", tabella `.rel-table`, gruppo principale come
etichetta "Non rimovibile", rimozione con conferma sulla riga. Stessi campi,
stessa action e stessi GET di prima. Non provati aggiunta/rimozione.

**Seguito: "Aggiungi gruppo" di `users/groups` in modale**, come nel mockup
(`PICK.ugroup`): al posto del form dentro una card, una modale con ricerca e
l'elenco dei gruppi non ancora assegnati; un clic sul gruppo invia lo stesso
POST di prima a `add_group` (campo `name` = id gruppo). Stesso schema di
`tenants/group`. `AdminCmsUsersController::groups()` passa `available_groups`
(con lo stesso scoping del vecchio popup: un tenant admin vede solo i gruppi
del proprio tenant) e `modal_title` (`adm_user_add_group_title`, en/it).
Tolto il `.box` residuo anche da `tenants/group`. Aggiunta di un gruppo non
provata fino in fondo (modale aperta e popolata, nessun POST eseguito).

**Seguito: foto utente mancante = iniziali** (`/admin/users/detail/ID` e
`/edit/ID`): il componente `type_components/image` (form e dettaglio) per
`cms_users.photo` senza file caricato mostrava l'avatar di default per ruolo
(`UserHelper::icon()`); ora mostra le iniziali del nome, come lista utenti e
profilo. Nuovo `UserHelper::initials($name)`. In creazione (nessun nome) resta
l'icona del segnaposto. Con una foto caricata non cambia nulla; scegliendo un
file nel form l'anteprima sostituisce le iniziali (JS gia' esistente).

**Seguito: `/admin/menu_management` come nel mockup** (`pageMenu`): schede
bianche (raggio 20px, ombra) con pallino verde/rosso/viola accanto al titolo al
posto delle intestazioni a tinta piena; righe con tessera dell'icona, nome in
grassetto, azioni modifica/elimina tenui e seconda riga (ruoli a sinistra,
tenant a destra); voce "Home" evidenziata con sfumatura. HTML in
`MenuHelper::menu_to_html()` (stessi link, stesso `li > div` del drag&drop;
nome, ruoli e tenant ora con `e()`), CSS nella vista. Il drag&drop non e' stato
riprovato a mano.

**Seguito: form dei menu compatto con Color a pallini** (`Aggiungi Menu` in
`/admin/menu_management` e `/admin/menu_management/edit/ID`): nuovo partial
`crudbooster/menus/_form_compact.blade.php`. Campi su griglia a 6 colonne con
etichetta sopra: Tenant|Group, Custom Icon|Icon, Active|Home page|Nuova scheda
sulla stessa riga; i campi e i `name` non cambiano. Color: pallini (JS) che
pilotano la select `#color` esistente, che resta nascosta: stessi valori
salvati (`normal`, `red`, `green`, `aqua`, `light-blue`, `yellow`, `muted`).
Altezza del modulo da ~850px a ~560px. Non provati salvataggio e modifica.

Ordine dei campi del form menu (CSS `order` nel partial, i campi nel controller
non sono stati spostati): Name, Type (+ campo dipendente dal tipo), Privileges,
Tenant | Group, Custom Icon | Icon, Color, Active | Home | Nuova scheda.
Il modulo "Aggiungi Menu" ora sta in una schermata senza scroll.

**Seguito: eliminazione voce di menu senza SweetAlert**: il cestino in
`/admin/menu_management` apre sulla riga "Eliminare? Sì! / Annulla" (come nel
mockup, `rm2`); "Sì!" e' il link GET `menu_management/delete/ID` di prima. HTML
in `MenuHelper::menu_to_html()`, gestori `mmAsk()`/`mmCancel()` come `onclick`
inline (il drag&drop dei menu ferma la propagazione dei clic verso `document`,
un gestore delegato non scattava). Nuova chiave `adm_delete_confirm` (en/it).
Non eliminata nessuna voce nella prova.

**Seguito: dettaglio standard come elenco chiave/valore** (es.
`/admin/qlik_confs/detail/ID`): in `ch-ui2.css` il `#table-detail` dei moduli
senza schede/blocchi (card standard) non e' piu' una tabella a zebra ma un
elenco con etichetta maiuscola tenue (colonna 200px), valore in grassetto e
filo tra le righe, come `kvq`/`kvt` del mockup. Annulla la regola inline
`width: 25%` di `form_detail.blade.php` sulla prima colonna. Vale per tutti i
moduli con questo tipo di dettaglio (apps, items, configurazioni...), solo con
`CH_UI_V2`. Non applicata l'intestazione della card ("Dettaglio Qlik
Configuration" dentro il pannello nel mockup): nascosta di proposito da ch-ui2
(titolo gia' in pagina).

Link "Scarica file" del dettaglio (`type_components/upload/component_detail`):
classe `ch-dl`, in ch-ui2 una pillola (sfondo accento tenue, senza
sottolineatura, 13px/600, icona), come `.dl` del mockup.

**Seguito: dettaglio a due colonne (Qlik Configuration)**: nuova opzione per
campo `'detail_half' => true` nel `$this->form` di un controller; la riga di
`form_detail_row` riceve la classe `dt-half` e `data-field`, e in ch-ui2
(`#table-detail:has(tr.dt-half)`) le righe segnate stanno due per riga (etichetta
120px), le altre a tutta larghezza; sotto 768px tornano a una colonna. Applicata
a `QlikConfController`: Type|Auth, Port|Endpoint, Key ID|Issuer, Private
Key|Debug, Tenant|Group. Altezza del dettaglio da ~944px a ~452px. Solo il
dettaglio; il form di modifica non cambia.

**Seguito: stessa disposizione a due colonne nella modifica**
(`/admin/qlik_confs/edit/ID`): `default/form_body.blade.php`, se almeno un campo
ha `'detail_half' => true`, emette una piccola `<style>` (solo con `ch-ui2`) che
mette `#parent-form-area` su una griglia a due colonne con l'etichetta sopra; i
campi segnati stanno a meta' riga, gli altri a tutta. Selettori per id
(`#form-group-<nome>`), nessun cambiamento per i moduli senza il flag. Nessuna
logica di salvataggio toccata.

Dettaglio raggruppato (`#table-detail:has(tr.dt-half)`): etichetta sopra e
valore subito sotto, per tutte le righe, al posto di etichetta e valore sulla
stessa riga (con due colonne non era chiaro a quale etichetta appartenesse
ogni valore).

**Seguito: "Stato Qlik" come nel mockup** (`/admin/qlik_apps` e `/admin/qlik_items`):
il badge "Presente su Qlik" / "non piu' presente" passa da `.badge` con colori
in linea a `ch-pill ch-pill-dot ch-pill-ok|warn` (pillola tenue con puntino,
`.tg` del mockup); nuova variante `.ch-pill-dot` in `ch-components.css`. Testi
e logica (is_missing / last_synced_at) invariati.
