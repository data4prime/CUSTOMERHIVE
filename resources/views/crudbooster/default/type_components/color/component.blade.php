@php
    $colorValue = $value ?: '#ffffff';
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>
        {{$form['label']}}
        @if($required)
        <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
        @endif
    </label>

    <div class="{{ isset($col_width)? $col_width:'col-sm-10'}}">
        <div class="input-group ch-input ch-color">
            <label class="ch-swatch-btn">
                <span class="ch-swatch" style="background: {{ $colorValue }}"></span>
                <input type='color' title="{{$form['label']}}" {{$required}} {{$readonly}} {{$disabled}}
                       class='ch-color-input' name="{{$name}}" id="{{$name}}" value='{{ $colorValue }}' />
            </label>
            <input type="text" class="form-control" data-ch-hex="{{$name}}" value="{{ $colorValue }}" maxlength="7"
                   placeholder="#000000" spellcheck="false" autocomplete="off" {{$readonly}} {{$disabled}}>
        </div>

        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>

    </div>
</div>
