@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>

    <div class="card card-default">
        <div class="card-header">
            [name]
        </div>
        <div class="card-body">
            [sql]
        </div>
    </div>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Chart Line'
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
    <div class="mb-3 row">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="mb-3 row">
        <label>SQL Query Line</label>
        <textarea name='config[sql]' required rows="4" class='form-control'>{{@$config->sql}}</textarea>
        <div class="block-help">
            Use column name 'label' as line chart label. Use name 'value' as value of line chart. Sparate with ; each
            sql line. Use [SESSION_NAME] to use
            alias session.
        </div>
    </div>

    <div class="mb-3 row">
        <label>Line Area Name</label>
        <input class="form-control" required name='config[area_name]' type='text' value='{{@$config->area_name}}' />
        <div class="block-help">You can naming each line area. Write name sparate with ;</div>
    </div>

    <div class="mb-3 row">
        <label>Goals Value</label>
        <input class="form-control" name='config[goals]' type='number' value='{{@$config->goals}}' />
    </div>
</form>
@elseif($command=='showFunction')

@if($key == 'sql')
<?php
        $sqls = explode(';', $value);
        $dataPoints = array();
        $datax = array();

        foreach ($sqls as $i => $sql) {

            $datamerger = array();

            $sessions = Session::all();
            foreach ($sessions as $k => $val) {
                if (gettype($val) == gettype($sql)) {
                    $sql = str_replace("[".$key."]", $val, $sql);
                }
            }

            try {
                $query = DB::select($sql);
                foreach ($query as $r) {
                    $datax[] = $r->label;
                    // Indicizzato per label (non per posizione): ogni
                    // query puo' restituire un numero diverso di righe
                    // (es. una categoria ha meno mesi con ordini di
                    // un'altra) - un indice puramente posizionale andava
                    // fuori dai limiti dell'array piu' corto ("Undefined
                    // array key") non appena le serie divergevano.
                    $datamerger[$r->label] = $r->value;
                }
            } catch (\Exception $e) {

            }

            $dataPoints[$i] = $datamerger;
        }

        $datax = array_values(array_unique($datax));

        $area_name = explode(';', $config->area_name);
        $area_name_safe = $area_name;
        foreach ($area_name_safe as &$a) {
            $a = str_slug($a, '_');
        }

        $data_result = array();
        foreach ($datax as $i => $d) {
            $dr = array();
            $dr['y'] = $d;
            foreach ($area_name as $e => $name) {
                $name = str_slug($name, '_');
                // 0 se questa serie non ha un valore per questa label
                // (vedi commento sopra), invece di un accesso fuori
                // dai limiti dell'array.
                $dr[$name] = $dataPoints[$e][$d] ?? 0;
            }
            $data_result[] = $dr;
        }

        $data_result = json_encode($data_result);
        // $data_result = preg_replace('/"([a-zA-Z_]+[a-zA-Z0-9_]*)":/','$1:',$data_result);

        ?>
<div id="chartContainer-{{$componentID}}" style="height: 250px;"></div>


<script type="text/javascript">

    $(function () {
        new Morris.Line({
            element: 'chartContainer-{{$componentID}}',
            data: $.parseJSON("{!! addslashes($data_result) !!}"),
            xkey: 'y',
            ykeys: {!! json_encode($area_name_safe) !!},
        labels: {!! json_encode($area_name)!!},
        parseTime: false,
        resize: true,
        @if ($config -> goals)
        goals: [{{ $config-> goals}}],
            @endif
    behaveLikeLine: true,
        hideHover: 'auto',
        /*
         * Colori/griglia allineati ai token di public/css/theme.css
         * (--ch-accent/--ch-text-muted/--ch-success/--ch-warning e
         * --ch-border/--ch-text-muted) - Morris.js disegna con Raphael
         * (SVG via attributi inline), niente CSS var() qui: i valori
         * vanno ripetuti a mano come esadecimali letterali.
         */
        lineColors: ['#4f46e5', '#8b8b96', '#0f9d58', '#b7791f'],
        gridLineColor: '#e6e6ea',
        gridTextColor: '#8b8b96',
        gridTextFamily: "'Plus Jakarta Sans', sans-serif",
        gridTextSize: 11
                });
            })
</script>
@else

{!! $value !!}
@endif
@endif

<script defer>
if (!window.location.href.includes('statistic_builder/builder')) {
    //jquery get div with action class
    var action = $('#{{$componentID}}').find('.action');
    //make it disappear
    action.hide();


    

}
</script>