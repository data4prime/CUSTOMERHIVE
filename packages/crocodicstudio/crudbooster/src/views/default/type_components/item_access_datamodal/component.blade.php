<div class='mb-3 row form-datepicker {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}'
    id='mb-3 row-{{$name}}' style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">

        <?php
        $datamodal_value = "";
        $datamodal_field = explode(',', $form['datamodal_columns'])[0];
        if (!empty($datamodal_field)) {
            $datamodal_value = DB::table($form['datamodal_table'])->where('id', $value)->first();
            if(!empty($datamodal_value)) {
                $datamodal_value = DB::table($form['datamodal_table'])->where('id', $value)->first()->$datamodal_field;
            }
        }





        ?>

        <div id='{{$name}}' class="input-group">
            <input type="hidden" name="{{$name}}" class="input-id" value="{{$value}}">
            <input type="text" class="form-control input-label {{$required?" required":""}}" {{$required?"required":""}}
                value="{{$datamodal_value}}" readonly>
            <span class="input-group-btn">
                <button class="btn btn-primary" onclick="showModal{{$name}}()" type="button"><i
                        class='fa fa-search'></i> {{trans('crudbooster.datamodal_browse_data')}}</button>
                <?php if( isset($form['datamodal_module_path']) && strlen($form['datamodal_module_path']) > 1){ ?>
                <a class="btn btn-info" href="{{CRUDBooster::adminPath()}}/{{$form['datamodal_module_path']}}"
                    target="_blank"><i class='fa fa-edit'></i> {{$form['label']}}</a>
                <?php } ?>
            </span>
        </div><!-- /input-group -->

        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>

@push('bottom')
<script type="text/javascript">
    var url_{{ $name }} = "{{CRUDBooster::mainpath('modal-data')}}?table={{$form['datamodal_table']}}&columns=id,{{$form['datamodal_columns']}}&name_column={{$name}}&where={{urlencode($form['datamodal_where'])}}&select_to={{ urlencode($form['datamodal_select_to']) }}&columns_name_alias={{ urlencode($form['datamodal_columns_alias']) }}&type={{ urlencode($form['type']) }}&type={{ urlencode($form['type']) }}";

    function showModal{{ $name }}() {
        $('#iframe-modal-{{$name}}').attr('src', url_{{ $name }});
    $('#modal-datamodal-{{$name}}').modal('show');
        }

    function hideModal{{ $name }}() {
        $('#modal-datamodal-{{$name}}').modal('hide');
    }

    function selectAdditionalData{{ $name }}(select_to_json) {
        $.each(select_to_json, function (key, val) {
            console.log('#' + key + ' = ' + val);
            if (key == 'datamodal_id') {
                $('#{{$name}} .input-id').val(val);
            }
            if (key == 'datamodal_label') {
                $('#{{$name}} .input-label').val(val);
            }
            //#RAMA scrivi il valore della description nell'input authorize access
            if (key == 'datamodal_description') {
                $('input[name=description]').val(val);
            }
            $('#' + key).val(val).trigger('change');
        })
            hideModal{{ $name }}();
    }
</script>


<div id='modal-datamodal-{{$name}}' class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog {{ isset($form['datamodal_size']) && isset($form['datamodal_size']) && $form['datamodal_size']=='large'?'modal-lg':'' }} "
        role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class='fa fa-search'></i> {{trans('crudbooster.datamodal_browse_data')}} |
                    {{$form['label']}}</h4>
            </div>
            <div class="modal-body">
                <iframe id='iframe-modal-{{$name}}' style="border:0;height: 430px;width: 100%" src=""></iframe>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

@endpush