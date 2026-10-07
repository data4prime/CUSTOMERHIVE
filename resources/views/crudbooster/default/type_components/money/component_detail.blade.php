@php
    // Importo con simbolo valuta (se scelta nel module generator) e cifre decimali configurate.
    $detailDecimals = max(0, min(6, (int) ($form['decimals'] ?? 0)));
    $detailCurrency = array_key_exists('currency', $form) ? $form['currency'] : (array_key_exists('prefix', $form) ? null : 'EUR');
@endphp
{{ $detailCurrency !== null
    ? \App\Helpers\NumberFormat::money($value, $detailDecimals, $detailCurrency)
    : trim(((string) ($form['prefix'] ?? '')) . ' ' . \App\Helpers\NumberFormat::format($value, $detailDecimals)) }}