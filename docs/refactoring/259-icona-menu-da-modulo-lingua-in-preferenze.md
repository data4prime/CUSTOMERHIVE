# 259 - Icona delle voci di menu "Module" dal modulo; Lingua nelle Preferenze del profilo

- **Data**: 2026-10-07
- **Stato**: Completato (non committato, non provato a vista nel browser)
- **Area**: Menu / Module generator / Profilo
- **File/aree di codice coinvolte**: `app/Helpers/MenuHelper.php` (`sync_module_icon`), `ModulsController` (`postStep1`, `hook_after_edit`), `MenusController` (`hook_before_add/edit`), `menus/_form_compact.blade.php`, migrazione `2026_10_07_110000_sync_module_menu_icons`, `AdminCmsUsersController` (`postProfileGeneral/Preferences`), `users/profile.blade.php`, lang en/it, `UserProfileSectionsTest`

## Contesto

L'icona si impostava sia nel module generator sia nella voce di menu: due valori scollegati.

## Situazione prima

Il wizard copiava l'icona nel menu solo alla creazione; poi le due icone divergevano. La Lingua era in "Generale" del profilo.

## Situazione dopo

- Voce di menu di tipo Module: icona in sola lettura (preso dal modulo scelto, con nota), `is_custom` forzato a 0 lato server in add/edit.
- Salvando il modulo (wizard passo 1 o form di modifica) `MenuHelper::sync_module_icon` aggiorna le voci che puntano al modulo (`path` o `path?m=ID`, anche col vecchio path se e' cambiato).
- Migrazione una tantum che allinea le voci Module esistenti.
- Lingua spostata in Preferenze (`profile-preferences` salva `lang` + `decimal_separator`); `profile-general` non tocca piu' la lingua.

## Motivazione

Una sola fonte per l'icona dei moduli.

## Test

`php -l`, migrazione locale, `view:cache`. Test del profilo aggiornato/aggiunto ma NON eseguito.

## Rischi e note

- **Cambio visibile**: la migrazione sovrascrive l'icona delle voci Module gia' esistenti con quella del modulo (anche per i clienti, al prossimo migrate).
- Modificare l'icona direttamente dalla lista Menu non e' piu' possibile per le voci Module.

## Rollback

Ripristinare i file; le icone precedenti delle voci non sono conservate.
