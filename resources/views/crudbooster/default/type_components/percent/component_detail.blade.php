@php
    $pctText = isset($form['decimals']) ? \App\Helpers\NumberFormat::format($value, (int) $form['decimals']) : \App\Helpers\NumberFormat::formatNatural($value);
@endphp
{{ $pctText !== '' && is_numeric($value) ? $pctText . '%' : $pctText }}
