<div class="mb-3 row {{$header_group_class}} {{ ($errors->first($name))?'has-error': '' }}" id="form-group-{{$name}}" style="{{@$form['style']}}">
    <label class="col-form-label col-sm-2">{{$form['label']}}
        @if($required)
            <span class='text-danger' title='{!! trans('crudbooster.this_field_is_required') !!}'>*</span>
        @endif
    </label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <input type="text" title="{{$form['label']}}" {{$required}} {{$readonly}} {!!$placeholder!!} {{$disabled}} class="form-control inputMoney"
               name="{{$name}}" id="{{$name}}" value="{{$value}}">
        <div class="text-danger">{!! $errors->first($name)?'<i class="fa fa-info-circle"></i> '.$errors->first($name):'' !!}</div>
        <p class="help-block">{{ @$form['help'] }}</p>
    </div>
</div>
