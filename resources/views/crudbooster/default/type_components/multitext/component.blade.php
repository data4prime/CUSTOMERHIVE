@php
    // Attributi comuni a ogni riga (la prima e quelle aggiunte da JS).
    $mtAttrs = (!empty($required) ? ' required' : '') . (!empty($readonly) ? ' readonly' : '') . (!empty($disabled) ? ' disabled' : '')
        . (!empty($form['placeholder']) ? ' placeholder="' . e($form['placeholder']) . '"' : '')
        . (isset($validation['max']) ? ' maxlength="' . (int) $validation['max'] . '"' : '');
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{isset($form['style']) ? $form['style'] : ''}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}} input_fields_wrap {{$name}}">

        <div class="ch-multi-row">
            <div class="input-group ch-input">
                <input type='text' title="{{$form['label']}}"{!! $mtAttrs !!} class='form-control {{$name}} first_value'
                       name="{{$name}}[]" id="{{$name}}" value='{{$value}}' />
            </div>
            @if(empty($disabled) && empty($readonly))
            <button type="button" class="ch-sq-btn add_field_button {{$name}}" aria-label="+"><i class='bi bi-plus-lg'></i></button>
            @endif
        </div>

        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>

    </div>

    @push('bottom')
    <script>
        $(document).ready(function () {

            var max_fields_{{ $name }} = parseInt("{{ @$form['max_fields'] }}") || 5;
            var wrapper_{{ $name }} = $(".input_fields_wrap").filter(".{{$name}}");
            var add_button_{{ $name }} = $(".add_field_button").filter(".{{$name}}");
            var attrs_{{ $name }} = {!! json_encode($mtAttrs) !!};

            function rowHtml_{{ $name }}(value) {
                var v = String(value === undefined ? '' : value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                return '<div class="ch-multi-row"><div class="input-group ch-input"><input class="form-control" type="text" name="{{$name}}[]"'
                    + attrs_{{ $name }} + ' value="' + v + '"/></div>'
                    + '<button type="button" class="ch-sq-btn remove_field {{$name}}" aria-label="-"><i class="bi bi-x-lg"></i></button></div>';
            }

            function count_{{ $name }}() { return wrapper_{{ $name }}.find('.ch-multi-row').length; }

            $(add_button_{{ $name }}).click(function (e) {
                e.preventDefault();
                if (count_{{ $name }}() < max_fields_{{ $name }}) {
                    $(wrapper_{{ $name }}).find('.ch-multi-row').last().after(rowHtml_{{ $name }}(''));
                }
            });

            $(wrapper_{{ $name }}).on("click", ".remove_field", function (e) {
                e.preventDefault();
                $(this).closest('.ch-multi-row').remove();
            });

            // I valori salvati sono separati da "|": il primo va nel campo gia' presente,
            // gli altri in righe aggiunte.
            var val = {!! json_encode((string) $value) !!}.split("|");
            $(".first_value").filter(".{{$name}}").val(val[0]);
            for (var i = 1; i < val.length; i++) {
                $(wrapper_{{ $name }}).find('.ch-multi-row').last().after(rowHtml_{{ $name }}(val[i]));
            }
        });
    </script>
    @endpush
</div>
