
@if($command=='layout')

<style>
.qlikwidget {
	height: 50vh;
}
</style>


<div id='{{$componentID}}' class='border-box'>



@if (isset($mashup->id) && $mashup->id != 0)

@php $h = 'width: 100%;height:80%;'; @endphp
@else 
@php $h = 'width: 100%;height:30px;'; @endphp

@if(crocodicstudio\crudbooster\helpers\LicenseHelper::isActiveQlik())

<p>Set up the widget<br>from Statistic Builder</p>

<img style="width: 20%;" src='/images/qlik_logo.png' />
@endif


@endif 
<iframe src="/mashup/{{$componentID}}" frameborder="0" style="{{ $h }}"></iframe>




    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Qlik Widget'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
    </div>
 @elseif($command=='configuration')



@php 



@endphp 


<script defer>



function update_objects(select){

    
    var select = document.getElementById('mashup_app');
    var mashup_id = select.value;


    var select = document.getElementById('mashup_object');
    //delete all options except the first two
    while (select.options.length > 1) {
        select.remove(1);
    }



    var iframe = document.getElementById('configuration');

    iframe.src = '/mashup-objects/' + mashup_id + '/{{$componentID}}'+'/{{isset($config->object) ? $config->object : "empty"}}';




}
</script>


<div id='{{$componentID}}' style="width: 100%;height: 100%;">

<iframe src='/mashup-objects/{{ isset($mashup->id) ? $mashup->id : 0}}/{{$componentID}}/{{isset($config->object) ? $config->object : "empty"}}'  id="configuration" frameborder="0" style="width: 100%;height:30px;"></iframe>


@if(crocodicstudio\crudbooster\helpers\LicenseHelper::isActiveQlik())
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
     


    <div class="mb-3 row">
        <label>Qlik App</label>
<select id="mashup_app" onchange="update_objects(this)" class='form-control' required name='config[mashups]'>
            <option value='0'>Choose App</option>
            @foreach($mashups as $m)
            @if(isset($config) && $m->id == $config->mashups)
            <option selected value='{{$m->id}}'>{{$m->appname}}</option>
            @else
            <option  value='{{$m->id}}'>{{$m->appname}}</option>
            @endif
            @endforeach
        </select>
<input type="hidden" id="mashup_app_hidden" value="{{ isset($config->mashups) ?  $config->mashups : '' }}">
    </div>

<div class="mb-3 row">
        <label>App Object</label>
        <select id="mashup_object" disabled  class='form-control' required name='config[object]'>
            <option value='0'>Choose Object</option>
            <!--<option value='CurrentSelections'>Current Selections</option>-->

        </select>
        <input type="hidden" id="mashup_object_hidden" value="{{ isset($config->object) ? $config->object : ''}}">
    </div>



</form>
@endif
</div>
@elseif($command=='showFunction')
<?php

    if ($key == 'sql') {
        try {
            $sessions = Session::all();
            foreach ($sessions as $key => $val) {
                if (gettype($val) == gettype($value)) {
                    $value = str_replace("[".$key."]", $val, $value);
                }
                
            }
            echo reset(DB::select(DB::raw($value))[0]);
        } catch (\Exception $e) {
            echo 'ERROR';
        }
    } else {
        echo $value;
    }

    ?>
@endif

<script defer>
if (!window.location.href.includes('statistic_builder/builder')) {
    //jquery get div with action class
    var action = $('#{{$componentID}}').find('.action');
    //make it disappear
    action.hide();


    

}
</script>