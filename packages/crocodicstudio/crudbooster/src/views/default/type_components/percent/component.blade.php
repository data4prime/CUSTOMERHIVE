<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}} ">
        <div class="input-group">
            <input type='number' step="{{isset($form['step'])? $form['step']:'1'}}" title="{{$form['label']}}"
                {{$required}} {{$readonly}} {!!$placeholder!!} {{$disabled}}
                {{ isset($validation['min'])? "min=".$validation['min']:""}}
                 {{isset($validation['max'])?" max=".$validation['max']:""}}
                 class='form-control'
                 name=" {{$name}}" id="{{$name}}" value='{{$value}}' />
            <span class="input-group-text"><b>%</b></span>
        </div>
        <div class="text-danger">{!! $errors->first($name)?"<i class='fa fa-info-circle'></i> ".$errors->first($name):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>