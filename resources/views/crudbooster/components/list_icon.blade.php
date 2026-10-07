@php
    // Le icone gia' salvate come "fa fa-*" vengono tradotte nel nome
    // equivalente di Bootstrap Icons, cosi' risultano preselezionate.
    $currentIcon = isset($row) && isset($row->icon) ? \App\Helpers\IconMap::toBi($row->icon) : '';
@endphp
{{-- Selettore di icone (intervento 236): stesso valore inviato di prima
     (name="icon", "bi bi-<nome>"), ma pannello con ricerca e griglia al posto
     della select a tendina. Vedi partials/ch_icon_picker. --}}
@include('crudbooster::partials.ch_icon_picker', ['name' => 'icon', 'current' => $currentIcon, 'prefix' => 'bi bi-', 'icons' => $fontawesome])
