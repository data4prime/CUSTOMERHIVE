# 326 - Open redirect negli add_* di Groups/Users/Tenants: return_url/ref_mainpath filtrati con SafeRedirectTarget (B2)

- **Data**: 2026-10-30
- **Stato**: Completato (144 test verdi nel container, suite completa non lanciata)
- **Area**: Sicurezza / Controller di sistema
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/AdminGroupsController.php` (`add_member`, `add_item`, `add_tenant`)
  - `app/Http/Controllers/System/AdminCmsUsersController.php` (`add_group`)
  - `app/Http/Controllers/System/AdminTenantsController.php` (`add_group`)
  - `app/Http/Controllers/System/Concerns/SafeRedirectTarget.php` (due controlli nuovi nel filtro, docblock)
  - `tests/Feature/SafeRedirectAddActionsTest.php` (nuovo)

## Contesto

Seguito dell'analisi dei controller di sistema iniziata con il
[324](324-rotte-custom-sotto-login.md) (B1) e proseguita con il
[325](325-uploads-rotta-non-serve-file-reali-500-diventa-404.md) (B1b). Qui si
chiude B2: l'open redirect nei cinque metodi `add_*` che, dopo il salvataggio,
rimandano l'utente a un URL letto dal POST.

Vincolo dato dalla persona: in altri punti dell'app si possono inserire link
esterni legittimi (menu management e altri), e quella logica **non va
toccata**. Per questo B2 è limitato a questi cinque metodi.

## Situazione prima

I cinque metodi leggevano `$_POST['return_url']` e `$_POST['ref_mainpath']`
senza alcun filtro e li passavano a `redirect()`. Con `return_url` verso un
host esterno, un admin autenticato veniva rimandato dopo il salvataggio su un
sito arbitrario (scenario phishing). I form reali mandano sempre un
`return_url` generato dal controller con `CRUDBooster::mainpath(...)` (circa
`AdminGroupsController` 454/562/664, `AdminCmsUsersController` 1429,
`AdminTenantsController` 538), quindi interno: il problema era sfruttabile solo
con un POST costruito a mano.

Il trait `SafeRedirectTarget` esisteva già ed era usato solo da
`AdminChatAIController` e `AdminQlikItemsController`. Il suo filtro, però,
aveva due falle sui valori malformati:

- `parse_url('http:///evil.com')` restituisce `false` e il filtro lo
  accettava, mentre i browser lo aprono come `http://evil.com`;
- `'/\t/evil.com'`: i browser eliminano tab e a capo dall'URL, quindi diventa
  `//evil.com`, mentre `parse_url` lo vede come un path (`/_/evil.com`).

Il caso del tab era il solo realmente sfruttabile (vedi "Rischi e note" per
LF/CR).

## Situazione dopo

- I tre controller usano `use SafeRedirectTarget;`. In ciascun metodo:

  ```php
  $return_url   = $this->safeRedirectTarget(request()->input('return_url'), '');
  $ref_mainpath = $this->safeRedirectTarget(request()->input('ref_mainpath'), '');
  if (empty($return_url)) {
      $return_url = $ref_mainpath ?: CRUDBooster::mainpath();
  }
  ```

  Il vecchio blocco finale `//redirect if (empty($return_url)) {...}` è
  ridondante ed è sostituito dal solo `return redirect($return_url);`.
  `$_POST['name'|'title']` non è toccato; il ramo "campo vuoto"
  `redirect($return_url . '/alert/1')` è invariato nel testo.
- Nel trait, dopo il controllo iniziale su `''` / `//` / backslash, due
  controlli nuovi che portano al fallback:
  1. `preg_match('/[\x00-\x20\x7f]/', $url)`: spazi e caratteri di controllo
     dentro l'URL;
  2. `parse_url($url) === false`: URL non parsabili (`http:///evil.com`).

  Il resto del filtro è invariato; docblock aggiornato (cita questo doc, 326).
- Il trait è condiviso con ChatAI e Qlik Items: per loro il filtro è solo più
  restrittivo su input malformati.
- Non toccati: menu management, form CRUD standard/`CBController`,
  `ModulsController`, `CRUDBooster::redirect` (ha un suo filtro,
  `sanitizeRedirectUrl`).

**Cambi di comportamento visibili** (l'UI normale non ne genera mai):

1. `return_url`/`ref_mainpath` esterni, protocol-relative, con backslash, con
   spazi/caratteri di controllo o non parsabili non vengono più seguiti: si
   ripiega su `ref_mainpath` filtrato, poi su `CRUDBooster::mainpath()` del
   modulo.
2. Con `return_url` assente dal POST prima `$_POST['return_url']` dava errore
   (warning, quindi 500); ora è vuoto e si ripiega.
3. Nel ramo "campo vuoto" con `return_url` vuoto prima si andava a
   `/alert/1`, ora a `<mainpath>/alert/1`.
4. Su ChatAI e Qlik Items (stesso trait) il filtro è più restrittivo solo su
   input malformati.

## Motivazione

Chiude un open redirect post-autenticazione in cinque punti riusando un filtro
già presente nel repo invece di scriverne uno nuovo, e allinea questi
controller a ChatAI/Qlik Items. Il perimetro ristretto rispetta il vincolo
sui link esterni legittimi in menu management e altrove. Durante la review è
emerso il bypass nel trait (sopra): la persona ha approvato di modificarlo,
mentre in origine era previsto di toccare solo il commento.

## Test

Verificato davvero:

- Test scritti prima della modifica: `SafeRedirectAddActionsTest` 20 rossi /
  15 verdi (rossi = 4 scenari con host esterno x 5 endpoint; verdi = 3
  scenari di comportamento invariato x 5).
- Dopo la modifica dei controller: 97 verdi insieme a
  `PublicSystemRoutesAuthTest`, `GroupsCrudTest`, `UsersCrudTest`,
  `TenantsCrudTest`.
- Aggiunto prima `test_return_url_malformati_non_vengono_seguiti` (5 rossi),
  poi la correzione nel trait.
- Stato finale: 144 test verdi (486 asserzioni) = `SafeRedirectAddActionsTest`
  + `ChatAIAuthorizationTest` + `QlikItemPublicAccessTest` +
  `QlikItemVisibilityTest` + `PublicSystemRoutesAuthTest` + `GroupsCrudTest` +
  `UsersCrudTest` + `TenantsCrudTest`.
- Larastan: No errors. `php -l` ok sui 4 file PHP. Pint segnala solo stile
  storico dei file (tab, `use \`), non toccato.

`SafeRedirectAddActionsTest`: data provider sui 5 endpoint (superadmin,
licenza Qlik finta con `Storage::fake('license')` come `ChatAIAuthorizationTest`
per `add_item`, `$_POST` impostato a mano). Casi: host esterno (return_url e
ref_mainpath), campo vuoto con return_url esterno, protocol-relative,
return_url malformati (9 valori: `http:///`, `https:///`, tab, LF, CR, spazio
interno, `javascript:`, `data:`, backslash), esterno con `ref_mainpath` valido
(usa il ref) e tre casi invariati (relativo interno, assoluto stesso host,
senza return_url -> ref_mainpath).

Non verificato: suite completa; comportamento dietro reverse proxy in
produzione.

## Rischi e note

Esito review (`ch-reviewer`): primo giro, nessun rilievo sui 5 metodi; rilievo
strutturale sul trait (bypass) corretto e testato; secondo giro sul solo trait,
nessun rilievo. Il reviewer ha verificato che `/%09/evil.com` e
`%2F%2Fevil.com` sono innocui (il browser non decodifica `%XX` nel path), che
`http:/evil.com`, `http:evil.com`, schemi maiuscoli, userinfo,
`localhost.evil.com` e IDN/unicode finiscono nel fallback, e che LF/CR nell'header
`Location:` sono rifiutati da `header()` di PHP; il tab, che Symfony non
valida, era l'unico caso sfruttabile ed ora è bloccato. `mainpath()` usa
`route()`, che codifica i parametri: un URL legittimo non contiene spazi o
caratteri di controllo.

- Il filtro confronta solo l'host (case-sensitive: `http://LOCALHOST/` ripiega,
  troppo restrittivo ma innocuo) e **non** porta/scheme (`http://localhost:9999/x`
  passa: stesso host, non è un open redirect verso un dominio esterno).
- Dietro reverse proxy `request()->getHost()` dipende dall'header Host: se
  differisse da `url()`, un `return_url` legittimo verrebbe scartato e si
  ripiegherebbe su `ref_mainpath`/`mainpath()` (sempre nel modulo giusto, non si
  rompe nulla). **Da verificare in produzione**: in `Kernel.php` non c'è
  `TrustProxies`.
- **Residuo non affrontato (fuori perimetro per scelta, da decidere)**:
  `hook_before_add` di `AdminCmsUsersController` (~riga 1300) e
  `AdminTenantsController` (~riga 383) usano
  `CRUDBooster::redirect(g('return_url') ?: CRUDBooster::referer(), ...)`.
  `CRUDBooster::redirect` ha un filtro proprio (`sanitizeRedirectUrl`: accetta
  `^/(?!/)` oppure host uguale), diverso da quello del trait e potenzialmente
  con le stesse debolezze (es. `/\t/host` passerebbe perché inizia con `/` e non
  con `//`); **non verificato**. Stesso discorso per gli altri usi di
  `CRUDBooster::redirect` con `return_url` (`ModulsController` 358/2592,
  `CBController` 1600/1828/1901). Possibile follow-up: allineare
  `sanitizeRedirectUrl` al trait, attenzione al vincolo sui link esterni
  legittimi.
- Un controller custom di un cliente che estende questi tre e definisce già un
  metodo `safeRedirectTarget` o usa lo stesso trait avrebbe un conflitto:
  rischio basso.
- Un test (esterni) è durato ~44 s una volta; causa non trovata, probabilmente
  costo di `RefreshDatabase` su MySQL con il data provider; non è una chiamata di
  rete (token licenza in cache).
- I rilievi su altri punti di `CBController` (S3 `postDataQuery` ecc.) restano
  aperti come da decisione della persona.

## Rollback

Rimuovere `use SafeRedirectTarget;` e l'import dai tre controller
(`AdminGroupsController`, `AdminCmsUsersController`, `AdminTenantsController`),
ripristinare le letture `$_POST['return_url'|'ref_mainpath']` e il blocco finale
`//redirect`, ripristinare il filtro originale del trait
`SafeRedirectTarget`, eliminare `tests/Feature/SafeRedirectAddActionsTest.php`.
Nessuna migrazione coinvolta.
