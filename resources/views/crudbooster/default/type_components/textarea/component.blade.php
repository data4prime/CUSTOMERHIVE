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
            'kind' => 'textarea',
            'value' => $value,
            'placeholder' => $form['placeholder'] ?? '',
            'required' => !empty($required), 'readonly' => !empty($readonly), 'disabled' => !empty($disabled),
            'maxlength' => $validation['max'] ?? null,
            'rows' => 5,
        ])
        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):"" !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>
