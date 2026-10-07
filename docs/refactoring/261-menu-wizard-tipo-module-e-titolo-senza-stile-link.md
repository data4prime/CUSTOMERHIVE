# 261 - Voce di menu del wizard di tipo Module; colonna titolo senza stile link

- **Data**: 2026-10-07
- **Stato**: Completato (non provato a vista nel browser)
- **Area**: Module generator / Menu
- **File/aree di codice coinvolte**: `ModulsController::postStep1`, migrazione `2026_10_07_130000_convert_wizard_route_menus_to_module`, `public/css/theme.css`

## Contesto

Con l'icona delle voci Module legata al modulo (259), le voci create dal wizard come `Route` restavano fuori dalla sincronizzazione. Inoltre la colonna titolo in lista (260) non deve avere lo stile dei link.

## Situazione prima

Il passo 1 creava la voce con `type = Route`, `path = <Controller>GetIndex`. La colonna titolo era un link con lo stile standard.

## Situazione dopo

- Il wizard crea la voce come `Module` con `path = <path modulo>?m=<id voce>` (come Menu Management).
- La migrazione converte le voci `Route` esistenti che puntano alla route index di un modulo generato (stessa destinazione) e ne allinea l'icona.
- `a.ch-title-link`: colore del testo, nessuna sottolineatura (solo al passaggio del mouse).

## Test

`php -l`, migrazione locale (3 voci convertite). Test automatici non eseguiti.

## Rischi e note

Le voci convertite ora ricevono `?m=ID` (layout di destinazione). Nessun altro cambio di destinazione.

## Rollback

Ripristinare `postStep1`; le voci convertite restano `Module` (stessa pagina).
