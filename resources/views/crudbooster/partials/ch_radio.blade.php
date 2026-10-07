{{--
  Standard unico per i gruppi di radio (stile in public/css/ch-components.css).
  Da 2 a 8 opzioni brevi (etichette <= 24 caratteri, <= 70 in totale) -> controllo segmentato; altrimenti elenco di radio tonde.

  Parametri:
    name         (string)  attributo name
    rd_options   (array)   [['value' => .., 'label' => .., 'checked' => bool, 'data_val' => ..], ...]
    disabled     (bool)    gruppo disabilitato
    input_class  (string)  classi aggiuntive su ogni <input> (opzionale)
    input_attrs  (array)   attributi extra su ogni <input>, es. ['data-o' => 'x'] (opzionale, escapati)
    rd_mode      (string)  'auto' (default) | 'seg' | 'list'
    rd_small     (bool)    variante piccola del segmentato (opzionale)
    rd_block     (bool)    segmentato a tutta larghezza (opzionale)
--}}
@php
    $rdOptions = array_values((array) ($rd_options ?? []));
    $rdMode = $rd_mode ?? 'auto';
    if ($rdMode === 'auto') {
        $rdMax = 0; $rdTot = 0;
        foreach ($rdOptions as $rdO) {
            $rdLen = mb_strlen((string) ($rdO['label'] ?? '')); $rdMax = max($rdMax, $rdLen); $rdTot += $rdLen;
        }
        $rdMode = (count($rdOptions) >= 2 && count($rdOptions) <= 8 && $rdMax <= 24 && $rdTot <= 70) ? 'seg' : 'list';
    }
    $rdAttrs = '';
    foreach ((array) ($input_attrs ?? []) as $rdK => $rdV) {
        $rdAttrs .= ' ' . e($rdK) . '="' . e($rdV) . '"';
    }
    $rdSeed = ($name ?? '') . microtime(true) . mt_rand();
@endphp
@if($rdMode === 'seg')
<div class="ch-seg {{ !empty($rd_small) ? 'ch-seg-sm' : '' }} {{ !empty($rd_block) ? 'ch-seg-block' : '' }}" role="radiogroup">
    @foreach($rdOptions as $rdI => $rdO)
        <label>
            <input type="radio" class="{{ $input_class ?? '' }}" name="{{ $name }}" value="{{ $rdO['value'] }}"
                   @if(!empty($rdO['checked'])) checked @endif @if(!empty($disabled)) disabled @endif{!! $rdAttrs !!}>
            <span>{{ $rdO['label'] }}</span>
        </label>
    @endforeach
</div>
@else
@foreach($rdOptions as $rdI => $rdO)
    @php $rdId = 'rd-' . substr(md5($rdSeed . $rdI), 0, 10); @endphp
    <div class="form-check {{ !empty($disabled) ? 'disabled' : '' }}"@if(isset($rdO['data_val'])) data-val="{{ $rdO['data_val'] }}"@endif>
        <input class="form-check-input {{ $input_class ?? '' }}" type="radio" id="{{ $rdId }}" name="{{ $name }}" value="{{ $rdO['value'] }}"
               @if(!empty($rdO['checked'])) checked @endif @if(!empty($disabled)) disabled @endif{!! $rdAttrs !!}>
        <label class="form-check-label" for="{{ $rdId }}">{{ $rdO['label'] }}</label>
    </div>
@endforeach
@endif
