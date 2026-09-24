# 087 - Dettaglio modulo: `dataenum` con etichetta diversa dal valore mostrava il valore grezzo

- **Data**: 2026-09-24
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `resources/views/crudbooster/default/type_components/select/component_detail.blade.php`
  - `resources/views/crudbooster/default/type_components/radio/component_detail.blade.php`

## Contesto

Segnalato dall'utente: `/admin/users/detail/8` mostra "1"/"0" per il
campo "API client" (aggiunto in [086](086-api2-sanctum.md)) invece di
"Yes"/"No". Chiesto di controllare se ci sono altri casi analoghi.

## Situazione prima

Un campo form `'type' => 'select'` con `'dataenum' => ['0|No', '1|Yes']`
(sintassi "valore|Etichetta", usata quando il valore salvato a DB non
coincide con l'etichetta da mostrare) viene gestito correttamente dal
form di modifica (`type_components/select/component.blade.php` fa il
parsing di `strpos($d, '|')` e mostra l'etichetta giusta), ma **non**
dalla pagina di **dettaglio**
(`type_components/select/component_detail.blade.php`): per `dataenum`
faceva semplicemente `echo $value;` — il valore grezzo salvato a DB,
senza risolverlo nell'etichetta.

Stesso identico problema per `'type' => 'radio'`
(`type_components/radio/component_detail.blade.php`): il ramo finale
(nessun `datatable`/`dataquery`) faceva `explode(";", $value)` e stampava
i pezzi grezzi come badge, senza consultare `dataenum`.

**Campi realmente affetti oggi** (verificato con una ricerca su tutti i
controller di sistema per `dataenum` con sintassi `valore|Etichetta` —
gli usi con valore ed etichetta coincidenti, es. `['Yes', 'No']` o
`'GET;POST'`, non erano toccati dal bug: il valore grezzo *è già*
l'etichetta corretta):

| Modulo | Campo | Valori prima → dopo |
|---|---|---|
| Users (`AdminCmsUsersController`) | API client (086) | `1`/`0` → Yes/No |
| Users (`AdminCmsUsersController`) | Language | `en`/`it` → English/Italiano |
| Menu Management (`MenusController`) | icon type | `1`/`0` → Custom/Font Awesome |
| Menu Management (`MenusController`) | is_active (riga ~593) | `1`/`0` → Active/InActive |
| Menu Management (`MenusController`) | riga ~602 (`type=>select`) | `1`/`0` → Yes/No |
| Menu Management (`MenusController`) | riga ~611 (`type=>select`) | `1`/`0` → Yes/No |
| Menu Management (`MenusController`) | Target Layout (`type=>radio`) | `0`/`1`/`2` → Standard/Full Screen/Fill Content |
| Module Generator (`ModulsController`) | riga ~135 | `0`/`1` → No/Yes |

**Bug scollegato trovato verificando "Target Layout"**: la pagina di
dettaglio di Menu Management andava in **500**
("Undefined array key \"datatable\"") già prima di questo intervento —
`type_components/radio/component_detail.blade.php` aveva un
`elseif ($form['datatable'])` senza `isset()` (a differenza del branch
gemello subito sopra, che ce l'ha), quindi qualunque campo `radio` senza
quella chiave (l'unico in tutto il repo: Target Layout, che ha solo
`dataenum`) crashava. Corretto nello stesso giro (necessario anche solo
per poter verificare il fix sopra).

## Situazione dopo

Entrambi i template ora risolvono `dataenum` con lo stesso parsing
"valore|Etichetta" già usato dal form di modifica: se il valore
corrisponde a un `valore` della lista, mostra l'`Etichetta`
corrispondente; altrimenti mostra il valore così com'è (fallback
invariato per i casi dove valore ed etichetta già coincidono — nessun
cambiamento per quelli).

## Motivazione

Comportamento visibile scorretto (valori grezzi al posto di etichette
leggibili) su più pagine di dettaglio già esistenti, non solo sul campo
appena aggiunto in 086 — fix centralizzato nel componente condiviso
invece che con un `callback_php` per ogni singolo campo, coerente e
riutilizzabile per qualunque futuro campo `dataenum` con etichetta
diversa dal valore.

## Test

Manuali sull'ambiente Docker locale (login con l'utente superadmin,
password nota — vedi memoria di sessione):
- `php -l` su entrambi i file.
- `/admin/users/detail/8`: "API client" → "Yes" (era "1"), "Language" →
  "English" (era "en").
- `/admin/menu_management/detail/1`: prima **500**, dopo il fix del bug
  scollegato → 200, "Target Layout" → "Standard" (badge, era il valore
  grezzo "0").
- Non riverificate singolarmente le altre righe di `MenusController`/
  `ModulsController` elencate sopra (stesso identico componente
  condiviso, stessa logica) — il fix è nel componente, non per-campo.

## Rischi e note

- **Trovato ma non toccato, stessa classe di bug, superficie
  d'uso non confermata dal vivo**: `type_components/child/
  component_detail.blade.php` (righe ~57-61) ha lo stesso pattern
  `echo $d->{$col['name']}` per una colonna `type=>select` con
  `dataenum` dentro un sub-form (`type=>child`) — nessuna colonna
  `child` con `dataenum` in sintassi `valore|Etichetta` trovata
  attualmente configurata nei controller di sistema (l'unico uso attivo
  di `type=>child` visto, Qlik Users in `AdminCmsUsersController`, usa
  colonne `datamodal`/`text`, non `select`+`dataenum`), quindi nessun
  campo reale ne risente oggi. Da correggere insieme se/quando emerge un
  caso vero.
- `type_components/checkbox/component_detail.blade.php` mostra sempre un
  badge Sì/No per valore (ignora `dataenum` di proposito, design
  diverso) — non è lo stesso bug, e **nessun controller di sistema usa
  oggi `type=>checkbox`** (verificato), quindi nessun impatto pratico.

## Rollback

`git revert` del commit — nessuna migration né dato coinvolto, solo
template di rendering.
