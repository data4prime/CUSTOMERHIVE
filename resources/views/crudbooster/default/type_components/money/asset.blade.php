@push('bottom')
<script>
    $(function () {
        @foreach($forms as $form)
        @if ($form['type'] == $type)
            $('.inputMoney#{{ $form['name'] }}').priceFormat({!! json_encode(array_merge(array(
                'prefix' 				=> '',
                'thousandsSeparator'    => isset($form['dec_point']) ? $form['dec_point'] : ',',
                'centsLimit'          	=> isset($form['decimals']) ? $form['decimals'] : '0',
                'clearOnEmpty'         	=> false,
            ), isset($form['priceformat_parameters']) ? (array) $form['priceformat_parameters'] : array()
            ))!!});
    @endif
    @endforeach
        });
</script>
@endpush