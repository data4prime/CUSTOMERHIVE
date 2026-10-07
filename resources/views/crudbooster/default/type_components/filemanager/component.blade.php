<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}' style='{{@$form["style"]}}'>
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">

        @if($value=='')
            <div class="input-group ch-input ch-file">
                <a id="lfm-{{$name}}" data-input="thumbnail-{{$name}}" data-preview="holder-{{$name}}" role="button" class="input-group-text ch-addon-pre ch-file-btn">
                    @if(@$form['filemanager_type'] == 'file')
                        <i class="bi bi-paperclip"></i> {{trans("crudbooster.chose_an_file")}}
                    @else
                        <i class='bi bi-image'></i> {{trans("crudbooster.chose_an_image")}}
                    @endif
                </a>
                <input id="thumbnail-{{$name}}" class="form-control" type="text" readonly value='{{$value}}' name="{{$name}}"
                       placeholder="{{ trans('crudbooster.file_none_selected') }}">
            </div>
        @endif

        @if($value)
            <input id="thumbnail-{{$name}}" class="form-control" type="hidden" value='{{$value}}' name="{{$name}}">
            @if(@$form['filemanager_type'] == 'file')
                @if($value)
                    <div class="ch-file-card"><span class="ch-file-thumb"><i class="bi bi-file-earmark"></i></span>
                        <div class="ch-file-meta"><a id='holder-{{$name}}' href='{{asset($value)}}' target='_blank'
                                                    title=' {{trans("crudbooster.button_download_file")}} {{ basename($value)}}'><b>{{ basename($value) }}</b></a>
                        <span>{{trans("crudbooster.button_download_file")}}</span></div>
                        <a class='btn btn-danger btn-delete btn-sm'
                                 onclick='chConfirm({   title: "{{trans("crudbooster.delete_title_confirm")}}",   text: "{{trans("crudbooster.delete_description_confirm")}}",   danger: true,   confirmText: "{{trans("crudbooster.confirmation_yes")}}",cancelText: "{{trans('crudbooster.button_cancel')}}" }, function(){  location.href="{{url($mainpath."/delete-filemanager?file=".$row->{$name}."&id=".$row->id."&column=".$name)}}" });'
                                 href='javascript:void(0)' title='{{trans('crudbooster.text_delete')}}'><i class='bi bi-slash-circle'></i></a>
                    </div>@endif
            @else
                <div class="ch-file-card"><a class="ch-file-thumb" data-lightbox="roadtrip" href="{{ ($value)?asset($value):'' }}"><img id='holder-{{$name}}'
                                                                                           {{ ($value)?'src='.asset($value):'' }}></a>
                    <div class="ch-file-meta"><b>{{ basename($value) }}</b></div>
                    @if(!$readonly || !$disabled)
                    <a class='btn btn-danger btn-delete btn-sm'
                      onclick='chConfirm({   title: "{{trans("crudbooster.delete_title_confirm")}}",   text: "{{trans("crudbooster.delete_description_confirm")}}",   danger: true,   confirmText: "{{trans("crudbooster.confirmation_yes")}}", cancelText: "{{trans('crudbooster.button_cancel')}}" }, function(){  location.href="{{url(CRUDBooster::mainpath("update-single?table=$table&column=$name&value=&id=$id"))}}" });'><i
                                class='bi bi-slash-circle'></i> {{trans('crudbooster.text_delete')}} </a>
                    @endif
                </div>
            @endif

        @endif


        <div class='help-block'>{{@$form['help']}}</div>
        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".$errors->first($name):"" !!}</div>
    </div>
</div>
@if(@$form['filemanager_type'])
    @push('bottom')
        <script type="text/javascript">$('#lfm-{{$name}}').filemanager('file', {prefix: "{{url(config('lfm.prefix'))}}"});</script>
    @endpush
@else
    @push('bottom')
        <script type="text/javascript">$('#lfm-{{$name}}').filemanager('images', {prefix: "{{url(config('lfm.prefix'))}}"});</script>
    @endpush
@endif
