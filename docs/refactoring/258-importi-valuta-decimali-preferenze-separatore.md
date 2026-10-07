# 258 - Importi con valuta/decimali, preferenza separatore decimale, colonne di sistema in lista

- **Data**: 2026-10-07
- **Stato**: Completato (non committato, non provato a vista nel browser)
- **Area**: Module generator / Liste e dettagli / Profilo utente
- **File/aree di codice coinvolte**:
  - `app/Helpers/NumberFormat.php` (nuovo), migrazione `2026_10_07_100000_add_decimal_separator_to_cms_users.php`
  - `app/Helpers/ModuleGeneratorList.php`, `ModuleGeneratorFields.php`
  - `app/Http/Controllers/System/CBController.php`, `AdminCmsUsersController.php`
  - `type_components/{money,percent,number}/*`, `module_generator/step2_v2`, `step3_v2`, `users/profile.blade.php`, lang en/it

## Contesto

Richieste: relazione automatica per le colonne di sistema (es. "Created by" ->
nome utente); importi non sempre in euro (select valute); simbolo anche nel
dettaglio; cifre decimali configurabili; niente colonne Tenant/Group aggiunte da
sole nelle liste dei moduli custom; preferenza utente per il separatore decimale.

## Situazione prima

- Lista: `created_by`/`updated_by`/`deleted_by` mostravano l'id; Tenant e Group
  venivano aggiunti a tutti i moduli MG_ (`ModuleHelper::add_default_column_headers`).
- Formato lista "Importo": fisso `€ 1.234,50`; campo form `money`: prefisso `€`
  di default, solo interi (`preg_replace('/[^\d-]+/')`), thousands `,` fissi;
  dettaglio `number_format($value)` senza simbolo; percentuale/numero: valore grezzo.
- Nessuna preferenza utente sui formati.

## Situazione dopo

- `NumberFormat`: separatore dell'utente (`cms_users.decimal_separator`, `,`
  default = italiano, `.` inglese), `format/money/formatNatural/parse`, elenco valute.
- Profilo > nuova sezione **Preferenze** (`postProfilePreferences`, route
  `users/profile-preferences`): scelta separatore, ricarica la pagina se cambia.
- Campo `money`/`percent` nel module generator (Avanzate): **valuta** (select, solo
  money) e **cifre decimali** (0-6) -> chiavi `currency` e `decimals` nel form del
  controller. Colonna nuova creata con la scala giusta; colonne esistenti non toccate.
  Senza le chiavi resta il comportamento di sempre (euro, intero).
- Form money: simbolo dalla valuta, `priceFormat` con separatori dell'utente, valore
  di partenza normalizzato; con `decimals` il valore inviato viene riletto con
  `NumberFormat::parse` (senza `decimals`: stessa logica intera di prima).
- Dettaglio money: simbolo + decimali + separatori; percent/number: separatore dell'utente.
- Lista: formato `money` (valuta + decimali scelti nel passo Lista, prefilled dal
  campo del form); `money_eur` letto come `money` EUR. Colonne numero/importo/percentuale
  senza format proprio (e senza callback) mostrate col separatore dell'utente.
- Colonne di sistema `created_by/updated_by/deleted_by` (-> `cms_users,name`),
  `tenant` (-> `tenants,name`), `group` (-> `groups,name`): join automatico a runtime
  se la colonna e' in lista senza join; nel passo Lista il collegamento e' pre-compilato.
- Tenant/Group **non** vengono piu' aggiunti da soli: si attivano dal passo Lista.

## Motivazione

Dati leggibili (nomi invece di id), importi corretti per valuta/precisione, formato
numerico personale.

## Test

`php -l`, `migrate` sul DB locale Docker, `view:cache`, smoke test degli helper
(`$ 1.234,50`, `12,5`, `1234.56`, `£ 9.876,500`). NON provato a vista nel browser ne'
con la suite di test.

## Rischi e note

- **Cambi di comportamento visibili**: (1) le liste dei moduli MG_ esistenti non
  mostrano piu' Tenant/Group se non sono nel passo Lista; (2) numeri/importi nelle
  liste e nei dettagli ora usano il separatore dell'utente (prima grezzo / `,`
  fisso); (3) il dettaglio money ora mostra il simbolo.
- I campi `type=number` del form restano controllati dal browser (separatore del
  locale del browser): solo money usa il separatore dell'utente nell'input.
- Un campo money con `decimals` > 0 gia' presente in controller dei clienti
  (chiave storica `decimals` per il solo `priceFormat`) ora salva davvero i decimali.
- `ModuleHelper::add_default_column_headers` non e' piu' chiamata (resta definita).
- Il test `ModuleGeneratorCrudTest` non e' stato lanciato.

## Rollback

Ripristinare i file elencati; la colonna `decimal_separator` puo' restare (innocua)
oppure `php artisan migrate:rollback --step=1`.
