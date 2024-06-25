@php 
use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;

use crocodicstudio\crudbooster\controllers\QlikMashupController;

use crocodicstudio\crudbooster\helpers\CRUDBooster;


    $mashup = QlikMashupController::getMashupFromCompID($componentID);
    $token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
    //$conf = QlikMashupController::getConf(3);

    if (!$mashup) {

        $mashup = new \stdClass();
        $mashup->id = 0;
        $mashup->mashupname = 'Choose Conf';
    

    }




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

    
    var select = document.getElementById('mashup_app');
    var mashup_id = select.value;

    //id mashup_object
    var select = document.getElementById('mashup_object');
    //delete all options except the first one
    while (select.options.length > 1) {
        select.remove(1);
    }



    var iframe = document.getElementById('configuration');

    iframe.src = '/mashup-objects/' + mashup_id + '/{{$componentID}}'+'/{{$config->object}}';




}
</script>


<div id='{{$componentID}}'>
<iframe src='/mashup-objects/{{$mashup->id}}/{{$componentID}}/{{$config->object}}'  id="configuration" frameborder="0" style="width: 100%;height:30px;"></iframe>



<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="form-group">
     


    <div class="form-group">
        <label>Qlik App</label>
<select id="mashup_app" onchange="update_objects(this)" class='form-control' required name='config[mashups]'>
            <option value='0'>Choose App</option>
            @foreach($mashups as $m)
            @if(isset($config) && $m->id == $config->mashups)
            <option selected value='{{$m->id}}'>{{$m->mashupname}}</option>
            @else
            <option  value='{{$m->id}}'>{{$m->mashupname}}</option>
            @endif
            @endforeach
        </select>
<input type="hidden" id="mashup_app_hidden" value="{{$m->id}}">
    </div>

<div class="form-group">
        <label>App Object</label>
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