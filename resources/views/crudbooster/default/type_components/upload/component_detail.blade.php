<?php
$ext = pathinfo($value, PATHINFO_EXTENSION);
$images_type = array('jpg', 'png', 'gif', 'jpeg', 'bmp', 'tiff');
// $value e' un path relativo alla public root (es. "/storage/uploads/..."),
// non relativo al disco 'local' (Storage::exists) ne' un path assoluto sul
// filesystem (file_exists) - va risolto con public_path(), stesso fix gia'
// fatto in component.blade.php (vedi docs/refactoring/007-*). Il controllo
// su $value non vuoto e' necessario: public_path('') e' la cartella public
// stessa, che esiste sempre - senza guardia, un campo senza foto proverebbe
// comunque a mostrare un'immagine.
// Coerenza con la lista (CBController, colonna con 'image'=>1): se non
// c'e' un file caricato, mostra la stessa immagine di riserva invece di
// niente - ma solo per cms_users.photo, l'unico campo dove questo ha
// senso (UserHelper::icon() cerca uno user per id: su un'altra tabella
// mostrerebbe l'avatar di uno user scelto a caso in base all'id).
if ((!$value || !file_exists(public_path($value))) && $table === 'cms_users' && $name === 'photo'):
$pic = UserHelper::icon(@$row->id);
?>
<a data-lightbox='roadtrip' href='{{$pic}}'><img style='max-width:150px' title="Image For {{$form['label']}}" src='{{$pic}}'/></a>
<?php elseif($value && file_exists(public_path($value))):
if(in_array(strtolower($ext), $images_type)):?>
<a data-lightbox='roadtrip' href='{{asset($value)}}'><img style='max-width:150px' title="Image For {{$form['label']}}" src='{{asset($value)}}'/></a>
<?php else:?>
<a href='{{asset($value)}}?download=1' target="_blank">{{trans("crudbooster.button_download_file")}}: {{basename($value)}} <i class="fa fa-download"></i></a>
<?php endif;?>
<?php endif;?>
