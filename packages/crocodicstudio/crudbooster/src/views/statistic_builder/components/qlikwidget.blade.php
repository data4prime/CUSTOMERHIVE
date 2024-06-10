@if($command=='layout')

<div id='{{$componentID}}' class='border-box'>

    <div class="qlikwidget [color]">
  <div id="chart1"></div>
  <div id="chart2"></div>
<a href="[link]" class="qlikwidget-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
</div>


<!--<iframe src="/mashup" frameborder="0"></iframe>-->
    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Qlik Widget'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
    </div>
 @elseif($command=='configuration')
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="form-group">
     


    <div class="form-group">
        <label>Mashup</label>
        <select class='form-control' required name='config[mashups]'>
            <option value=''>Choose Mashup</option>
            @foreach($mashups as $mashup)
            <option @php echo  $mashup->id == $config['mashups'] ? 'selected' : ''  @endphp value='{{$mashup->id}}'>{{$mashup->mashupname}}</option>
            @endforeach
        </select>
    </div>



</form>
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