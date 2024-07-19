<div class='form-group {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{isset($form['style']) ? $form['style'] : ''}}">
    <label class='control-label col-sm-2'>{{$form['label']}}
        @if(isset($required))
        <span class='text-danger' title='{!! trans(' crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}} input_fields_wrap {{$name}}">

        <div class="input-group">
            <input type='text' title="{{$form['label']}}" {{$required}} {{$readonly}} {!!$placeholder!!} {{$disabled}}
                {{isset($validation['max'])?"maxlength=".$validation['max']:""}} class='form-control {{$name}} first_value'
                   name=" {{$name}}[]" id="{{$name}}" value='{{$value}}' /> <span class="input-group-addon"
                style="padding: 1px;"><button type="button" class="add_field_button {{$name}}  btn btn-danger  btn-xs"><i
                        class='fa fa-plus'></i></button></span>
        </div>

        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>

    </div>

    @push('bottom')
    <script>
        $(document).ready(function () {

        var max_fields_{{ $name }} = "{{ @$form['max_fields'] }}";
        var max_fields_{{ $name }} = parseInt(max_fields_{{ $name }}) ? max_fields_{{ $name }} : 5;
        var wrapper_{{ $name }} = $(".input_fields_wrap").filter(".{{$name}}");
        var add_button_{{ $name }} = $(".add_field_button").filter(".{{$name}}");

        var count_{{ $name }} = 1;
        $(add_button_{{ $name }}).click(function (e) {
            console.log(add_button_{{ $name }});
            e.preventDefault();
            if (count_{{ $name }} < max_fields_{{ $name }} ) {
                count_{{ $name }} ++;
                $(wrapper_{{ $name }}).append('<div><input class="form-control" {{$required}} {{$readonly}} {!!$placeholder!!} {{$disabled}} {{isset($validation['max'])?"maxlength=".$validation['max']:""}} type="text" name="{{$name}}[]"/><a href="#" class="remove_field {{$name}}"><i class="fa fa-minus"></a></div>'); //add input box
            }
        });

        $(wrapper_{{ $name }}).on("click", ".remove_field ", function (e) {
            e.preventDefault();
            $(this).parent('div').remove();
            count_{{ $name }}--;
        })

        function Load() {
            var val = "{{$value}}";
            val = val.split("|");
            $(".first_value").filter(".{{$name}}").val(val[0]);
            for (i = 1; i < val.length; i++) {
                $(wrapper_{{ $name }}).append(' <div > <input class="form-control" {{$required}} {{$readonly}} {!!$placeholder!!} {{$disabled}} {{isset($validation['max'])?"maxlength=".$validation['max']:""}}  type="text" name="{{$name}}[]" value="' + val[i] + '"/><a href="#" class="remove_field {{$name}}"><i class="fa fa-minus"></a></div>'); //add input box
        }
                }

        Load();
            });
    </script>
    @endpush
</div>