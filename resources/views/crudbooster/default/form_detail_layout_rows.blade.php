@foreach($items as $item)
<?php
    $iname = (string) ($item['name'] ?? '');
    $iw = in_array((int) ($item['w'] ?? 12), [12, 6, 4, 3]) ? (int) $item['w'] : 12;
?>
@if(!empty($item['sys']))
@if(in_array($iname, \App\Helpers\ModuleGeneratorLayout::SYSTEM_FIELDS, true))
<div class="cbd-field" style="--cbw: {{ $iw }}">
    <table class="table cbd-table"><tr><td>{{ trans('crudbooster.mg_lay_sys_' . $iname) }}</td><td>{{ \App\Helpers\ModuleGeneratorLayout::systemValue(isset($row) ? $row : null, $iname) }}</td></tr></table>
</div>
@endif
@elseif(isset($byName[$iname]))
{{-- Stessa larghezza del form: ogni campo e' una mini tabella etichetta/valore, con l'etichetta sopra il valore (vedi CSS in form_detail_layout). --}}
<div class="cbd-field" style="--cbw: {{ $iw }}">
    <table class="table cbd-table">
        @include('crudbooster::default.form_detail_row', ['form' => $byName[$iname]])
    </table>
</div>
@endif
@endforeach
