{{--
  Anteprima immagine con azioni (foto profilo, logo...): usata dal type_component "image"
  e dal profilo personale. Il comportamento dell'anteprima e' in
  type_components/image/asset.blade.php (va incluso una volta nella pagina).

  Parametri:
    name        nome dell'<input type="file">
    ch_label    etichetta / alt
    src         URL dell'immagine da mostrare ('' = segnaposto con icona)
    has_file    (bool) c'e' un file caricato: il pulsante diventa "Cambia"
    shape       'circle' (default) | 'square'
    size        px, 48-240 (default 96)
    icon        classe Bootstrap Icons del segnaposto (default 'bi-image')
    accept      default 'image/*'
    required, readonly, disabled   (bool)
    delete_url  (opzionale) link "Elimina" per il file caricato
    help        (opzionale) testo sotto i pulsanti
    change_label (opzionale) testo del pulsante quando c'e' gia' un file
    initials    (opzionale) iniziali mostrate al posto dell'icona quando src e' vuoto
--}}
@php
    $ciShape = (($shape ?? 'circle') === 'square') ? 'square' : 'circle';
    $ciSize = max(48, min(240, (int) ($size ?? 96)));
    $ciIcon = preg_match('/^bi-[a-z0-9-]+$/', (string) ($icon ?? '')) ? $icon : 'bi-image';
    $ciSrc = (string) ($src ?? '');
    $ciHas = !empty($has_file);
    $ciLocked = !empty($readonly) || !empty($disabled);
@endphp
<div class="ch-image" data-ch-image style="--ch-image-size: {{ $ciSize }}px">
    <div class="ch-image-preview is-{{ $ciShape }}">
        <img data-ch-image-img src="{{ $ciSrc }}" alt="{{ $ch_label ?? '' }}" @if($ciSrc === '') hidden @endif>
        @if(!empty($initials))
        <span class="ch-image-ph ch-image-ini" data-ch-image-ph @if($ciSrc !== '') hidden @endif>{{ $initials }}</span>
        @else
        <i class="bi {{ $ciIcon }} ch-image-ph" data-ch-image-ph @if($ciSrc !== '') hidden @endif></i>
        @endif
    </div>
    <div class="ch-image-side">
        @if(!$ciLocked)
        <div class="d-flex flex-wrap gap-2">
            <input type="file" id="{{ $name }}" name="{{ $name }}" class="ch-file-input" data-ch-image-input
                   accept="{{ $accept ?? 'image/*' }}" title="{{ $ch_label ?? '' }}"
                   @if(!empty($required) && !$ciHas) required @endif>
            <label class="btn btn-secondary btn-sm mb-0" for="{{ $name }}">
                <i class="bi bi-image"></i> {{ $ciHas ? ($change_label ?? trans('crudbooster.image_change')) : trans('crudbooster.chose_an_image') }}
            </label>
            @if($ciHas && !empty($delete_url))
            <a class="btn btn-outline-danger btn-sm" onclick="if(!confirm('{{ trans("crudbooster.delete_title_confirm") }}')) return false"
               href="{{ $delete_url }}"><i class="bi bi-trash-fill"></i> {{ trans('crudbooster.text_delete') }}</a>
            @endif
        </div>
        @endif
        <div class="ch-image-fname form-text" data-ch-image-fname hidden></div>
        @if(!empty($help))<div class="form-text">{{ $help }}</div>@endif
    </div>
</div>
