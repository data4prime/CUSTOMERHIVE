@foreach($items as $item)
<?php
    $iname = (string) ($item['name'] ?? '');
    $iw = in_array((int) ($item['w'] ?? 12), [12, 6, 4, 3]) ? (int) $item['w'] : 12;
?>
@if(!empty($item['sys']))
<div class="cb-field" style="--cbw: {{ $iw }}">
    <div class="mb-3">
        <label class="form-label">{{ trans('crudbooster.mg_lay_sys_' . $iname) }} <i class="bi bi-lock-fill text-muted"></i></label>
        <input type="text" class="form-control" disabled value="{{ \App\Helpers\ModuleGeneratorLayout::systemValue($row ?? null, $iname) }}">
    </div>
</div>
@elseif(isset($byName[$iname]))
<?php
    $lf_form = $byName[$iname]['form'];
    $lf_index = $byName[$iname]['index'];
?>
<div class="cb-field" style="--cbw: {{ $iw }}">
    @include('crudbooster::default.form_field', ['form' => $lf_form, 'index' => $lf_index, 'header_group_class' => 'header-group-' . $lf_index, 'no_system_box' => true])
</div>
@endif
@endforeach
