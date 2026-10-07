{{--
  Standard unico per i campi checkbox (stile in public/css/ch-components.css).

  Parametri:
    name      (string)  attributo name
    value     (string)  attributo value (default '1')
    label     (string)  testo accanto al controllo (opzionale, viene escapato)
    checked   (bool)    selezionata
    disabled  (bool)    disabilitata
    switch    (bool)    true = switch (singolo booleano); false = checkbox quadrata (scelte multiple)
    input_id  (string)  id dell'input (opzionale, generato se manca)
    input_class (string)  classi aggiuntive sull'input (opzionale)
    input_attrs (array)   attributi extra sull'input, es. ['data-k' => 'x'] (opzionale, escapati)
    aria      (string)  aria-label quando non c'e' un testo visibile (opzionale)
    data_val  (string)  data-val sul contenitore (opzionale)
--}}
@php
    $chId = !empty($input_id) ? $input_id : 'chk-' . substr(md5(($name ?? '') . '|' . ($value ?? '1') . '|' . microtime(true) . mt_rand()), 0, 10);
    $chSwitch = !empty($switch);
    $chAttrs = '';
    foreach ((array) ($input_attrs ?? []) as $chK => $chV) {
        $chAttrs .= ' ' . e($chK) . '="' . e($chV) . '"';
    }
@endphp
<div class="form-check {{ $chSwitch ? 'form-switch' : '' }} {{ !empty($disabled) ? 'disabled' : '' }}"@if(isset($data_val)) data-val="{{ $data_val }}"@endif>
    <input class="form-check-input {{ $input_class ?? '' }}" type="checkbox"@if($chSwitch) role="switch"@endif
           id="{{ $chId }}" name="{{ $name }}" value="{{ $value ?? '1' }}"
           @if(!empty($checked)) checked @endif @if(!empty($disabled)) disabled @endif
           @if(!empty($aria) && (!isset($label) || $label === '')) aria-label="{{ $aria }}" @endif{!! $chAttrs !!}>
    @if(isset($label) && $label !== '')
        <label class="form-check-label" for="{{ $chId }}">{{ $label }}</label>
    @endif
</div>
