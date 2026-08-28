<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}' style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
        @endif
    </label>

    <div class="{{isset($col_width) ? $col_width :'col-sm-10'}}">

        <div id="{{$name}}"></div>
        <textarea name="{{$name}}" style="display:none"></textarea>

        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):"" !!}</div>
        <p class='help-block'>{{ isset($form['help']) ?  $form['help'] : '' }}</p>

    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        console.log(document.getElementById('{{$name}}'));
        // Set an option globally
        JSONEditor.defaults.options.theme = 'bootstrap2';
        JSONEditor.plugins.select2.enable = false;
        JSONEditor.plugins.selectize.enable = true;//to avoid select2

        // Set an option during instantiation
        var editor = new JSONEditor(document.getElementById('{{$name}}'), {
            theme: 'bootstrap2',
            startval: @php echo json_encode(json_decode($value, false)) @endphp,
            schema: @php echo json_encode(json_decode(isset($form["schema"]) ? $form["schema"] : '', false)) @endphp
        });

        $('[name="{{$name}}"]').parents('form').on('submit', function () {
            $('[name="{{$name}}"]').val(JSON.stringify(editor.getValue()));
            return true;
        })
    });

</script>