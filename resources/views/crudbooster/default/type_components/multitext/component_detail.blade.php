@php
    // I valori sono salvati separati da "|": in dettaglio si mostrano come elenco.
    $mtItems = array_values(array_filter(array_map('trim', explode('|', (string) $value)), 'strlen'));
@endphp
@if(count($mtItems))
<ul class="mb-0 ps-3">
    @foreach($mtItems as $mtItem)
    <li>{{ $mtItem }}</li>
    @endforeach
</ul>
@endif
