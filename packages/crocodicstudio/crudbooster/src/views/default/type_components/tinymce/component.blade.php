
<div class='mb-3 row' id='mb-3 row-{{$name}}' style="{{@$form['style']}}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}</label>

    <div class="{{$col_width?:'col-sm-10'}}">
        <textarea id="myeditorinstance{{$form['name']}}" {{$required}} {{$readonly}} {{$disabled}} name="{{$form['name']}}">
            {{ $value }}
        </textarea>
        <div class="text-danger">{{ $errors->first($name) }}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>
