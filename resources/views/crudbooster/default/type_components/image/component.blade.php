{{--
  Campo "immagine" (foto profilo, logo, icona...): anteprima grande tonda o quadrata con
  "Cambia immagine" e "Elimina", e anteprima immediata della nuova scelta (asset.blade.php).
  Si salva come 'upload' (stesso name, stesso file, stesso ridimensionamento, vedi CBController).
  Il disegno e' in partials/ch_image.blade.php (condiviso con il profilo personale).

  Opzioni del campo: shape ('circle' default | 'square'), size (px, default 96),
  icon (icona Bootstrap del segnaposto, default 'bi-image'), accept (default 'image/*'),
  resize_width / resize_height, validation (es. 'image|max:1000'), help.
  Solo per cms_users.photo: senza file caricato mostra le iniziali del nome
  (UserHelper::initials()), o l'icona del segnaposto in creazione.
--}}
@php
    // $value e' un path relativo alla public root (vedi CRUDBooster::uploadFile()): il
    // controllo e' sul disco, come in type_components/upload.
    $imgOk = !empty($value) && file_exists(public_path($value));
    $imgUrl = $imgOk ? asset($value) : '';
    // Senza foto (solo cms_users.photo): le iniziali del nome, non l'avatar di default.
    // In creazione non c'e' ancora un nome: resta l'icona del segnaposto.
    $imgInitials = ($table === 'cms_users' && $name === 'photo') ? \App\Helpers\UserHelper::initials(@$row->name) : '';
    $imgDelete = ($imgOk && isset($row->id))
        ? url(CRUDBooster::mainpath('delete-image?' . http_build_query(['image' => $value, 'id' => $row->id, 'column' => $name])))
        : '';
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}' style="{{@$form['style']}}">
    <label class='col-sm-2 col-form-label'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        @include('crudbooster::partials.ch_image', [
            'name' => $name, 'ch_label' => $form['label'], 'src' => $imgUrl, 'has_file' => $imgOk,
            'shape' => $form['shape'] ?? 'circle', 'size' => $form['size'] ?? 96, 'icon' => $form['icon'] ?? '',
            'accept' => $form['accept'] ?? 'image/*', 'help' => $form['help'] ?? '',
            'required' => !empty($required), 'readonly' => !empty($readonly), 'disabled' => !empty($disabled),
            'delete_url' => $imgDelete, 'initials' => $imgInitials,
        ])
        @if($imgOk)<input type='hidden' name='_{{$name}}' value='{{$value}}'/>@endif
        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):"" !!}</div>
    </div>
</div>
