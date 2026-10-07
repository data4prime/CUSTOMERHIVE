@php
    // Valuta e decimali scelti nel module generator ('currency' = codice, 'decimals' = cifre decimali).
    // Senza 'currency' resta il comportamento di sempre: prefisso '$form[prefix]' o '€'.
    $moneyDecimals = max(0, min(6, (int) ($form['decimals'] ?? 0)));
    if (array_key_exists('currency', $form)) {
        $moneyPrefix = \App\Helpers\NumberFormat::currencySymbol($form['currency']);
    } else {
        $moneyPrefix = array_key_exists('prefix', $form) ? $form['prefix'] : '€';
    }
    // Il campo parte da "1234.50" (formato DB): priceFormat lo rilegge e lo mostra col separatore dell'utente.
    $moneyValue = \App\Helpers\NumberFormat::toPlain($value, $moneyDecimals);
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
        @endif
    </label>

    <div class="{{ !empty($col_width) ? $col_width : 'col-sm-10' }}">
        @include('crudbooster::partials.ch_input', [
            'name' => $name,
            'value' => $moneyValue,
            'ch_label' => $form['label'],
            'placeholder' => $form['placeholder'] ?? '',
            'required' => !empty($required), 'readonly' => !empty($readonly), 'disabled' => !empty($disabled),
            'input_class' => 'inputMoney',
            'prefix' => $moneyPrefix, 'suffix' => $form['suffix'] ?? null,
        ])
        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):"" !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>
