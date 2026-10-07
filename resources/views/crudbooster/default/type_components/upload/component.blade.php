<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}' style="{{@$form['style']}}">
    <label class='col-sm-2 col-form-label'>{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        @if($value)
            <?php
            // $value e' un path relativo alla public root (es. "/storage/uploads/..."),
            // non un URL assoluto (vedi CRUDBooster::uploadFile()) - il controllo va
            // fatto sul disco, non con una richiesta HTTP: oltre a essere piu' lento,
            // un self-request non funziona quando l'host/porta visti dal browser
            // differiscono da quelli raggiungibili dal server stesso (es. sviluppo
            // locale via Docker con port mapping).
            $file_ok = file_exists(public_path($value));
            $url = $file_ok ? asset($value) : '';
            $ext = $file_ok ? pathinfo($url, PATHINFO_EXTENSION) : '';
            $is_image = in_array(strtolower($ext), ['jpg', 'png', 'gif', 'jpeg', 'bmp', 'tiff']);
            ?>
            @if($file_ok)
                <div class="ch-file-card">
                    @if($is_image)
                        <a data-lightbox='roadtrip' href='{{$url}}' class="ch-file-thumb"><img title="Image For {{$form['label']}}" src='{{$url}}'/></a>
                    @else
                        <span class="ch-file-thumb"><i class="bi bi-file-earmark"></i></span>
                    @endif
                    <div class="ch-file-meta">
                        <b>{{ basename($value) }}</b>
                        <a href='{{$url}}'>{{trans("crudbooster.button_download_file")}}</a>
                    </div>
                    @if(!$readonly || !$disabled)
                        <a class='btn btn-danger btn-delete btn-sm' onclick="if(!confirm('{{trans("crudbooster.delete_title_confirm")}}')) return false"
                           href='{{url(CRUDBooster::mainpath("delete-image?image=".$value."&id=".$row->id."&column=".$name))}}'><i
                                    class='bi bi-slash-circle'></i> {{trans('crudbooster.text_delete')}} </a>
                    @endif
                </div>
                <input type='hidden' name='_{{$name}}' value='{{$value}}'/>
            @else
                <p class='text-danger'><i class='bi bi-exclamation-triangle-fill'></i> {{trans("crudbooster.file_broken")}}</p>
                @if(!$readonly || !$disabled)
                    <p><a class='btn btn-danger btn-delete btn-sm' onclick="if(!confirm('{{trans("crudbooster.delete_title_confirm")}}')) return false"
                          href='{{url(CRUDBooster::mainpath("delete-image?image=".$value."&id=".$row->id."&column=".$name))}}'><i
                                    class='bi bi-slash-circle'></i> {{trans('crudbooster.text_delete')}} </a></p>
                @endif
            @endif
        @endif
        @if(!$value)
            @include('crudbooster::partials.ch_file', [
                'name' => $name,
                'ch_label' => $form['label'],
                'required' => !empty($required), 'readonly' => !empty($readonly), 'disabled' => !empty($disabled),
            ])
            <p class='help-block'>{{ @$form['help'] }}</p>
        @else
            <p class='text-muted'><em>{{trans("crudbooster.notice_delete_file_upload")}}</em></p>
        @endif
        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):"" !!}</div>

    </div>

</div>
