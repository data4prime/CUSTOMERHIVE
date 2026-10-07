<?php
// Dettaglio del campo "immagine": stessa anteprima del form (tonda o quadrata), senza azioni.
// $value e' un path relativo alla public root; il controllo su $value non vuoto e'
// necessario (public_path('') e' la cartella public, che esiste sempre).
$imgOk = !empty($value) && file_exists(public_path($value));
$imgUrl = $imgOk ? asset($value) : '';
// Senza foto, solo per cms_users.photo: le iniziali del nome (niente avatar di default).
$imgInitials = (!$imgOk && $table === 'cms_users' && $name === 'photo') ? UserHelper::initials(@$row->name) : '';
$imgShape = (($form['shape'] ?? 'circle') === 'square') ? 'square' : 'circle';
$imgSize = max(48, min(240, (int) ($form['size'] ?? 96)));
$imgIcon = preg_match('/^bi-[a-z0-9-]+$/', (string) ($form['icon'] ?? '')) ? $form['icon'] : 'bi-image';
?>
<div class="ch-image" style="--ch-image-size: {{ $imgSize }}px">
    <div class="ch-image-preview is-{{ $imgShape }}">
        @if($imgOk)
        <a data-lightbox='roadtrip' href='{{ $imgUrl }}'><img src="{{ $imgUrl }}" alt="{{ $form['label'] }}"></a>
        @elseif($imgInitials !== '')
        <span class="ch-image-ph ch-image-ini">{{ $imgInitials }}</span>
        @else
        <i class="bi {{ $imgIcon }} ch-image-ph"></i>
        @endif
    </div>
</div>
