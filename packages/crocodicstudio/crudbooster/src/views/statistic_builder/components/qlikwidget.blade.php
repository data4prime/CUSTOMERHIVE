@php 
use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;

use crocodicstudio\crudbooster\controllers\QlikMashupController;


    $mashup = QlikMashupController::getMashupFromCompID($componentID);
    $token = HelpersQlikHelper::getJWTToken(1, 3);
    $conf = QlikMashupController::getConf(3);




@endphp 
@if($command=='layout')

<style>
.qlikwidget {
	height: 50vh;
}
</style>


<div id='{{$componentID}}' class='border-box'>

<!--<h1>{{isset($mashup) ? $mashup->mashupname : 'Choose Conf'}}</h1>-->
<iframe src="/mashup/{{$componentID}}" frameborder="0" style="width: 100%;height: 80%;"></iframe>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Qlik Widget'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
    </div>
 @elseif($command=='configuration')

<!--javascript code che dal ifame con id configuration, prende tutti gli option con classe masterobject-option, e li inserisce nel select con id mashup_object -->
<script defer>



function update_objects(select){

    //id mashup_object
    var select = document.getElementById('mashup_object');
    //delete all options except the first one
    while (select.options.length > 1) {
        select.remove(1);
    }



    var iframe = document.getElementById('configuration');

    iframe.src = '/mashup-objects/' + select.value + '/{{$componentID}}'+'/{{$config->object}}';




}
</script>


<div id='{{$componentID}}'>
<iframe src='/mashup-objects/{{$mashup->id}}/{{$componentID}}/{{$config->object}}'  id="configuration" frameborder="0" style="width: 100%;"></iframe>



<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="form-group">
     


    <div class="form-group">
        <label>Mashup</label>
<select onchange="update_objects(this)" class='form-control' required name='config[mashups]'>
            <option value='0'>Choose App</option>
            @foreach($mashups as $m)
            @if(isset($config) && $m->id == $config->mashups)
            <option selected value='{{$m->id}}'>{{$m->mashupname}}</option>
            @else
            <option  value='{{$m->id}}'>{{$m->mashupname}}</option>
            @endif
            @endforeach
        </select>
    </div>

<div class="form-group">
        <label>Mashup Object</label>
        <select id="mashup_object" disabled  class='form-control' required name='config[object]'>
            <option value='0'>Choose Object</option>

        </select>
        <input type="hidden" id="mashup_object_hidden" value="{{$config->object}}">
    </div>



</form>
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