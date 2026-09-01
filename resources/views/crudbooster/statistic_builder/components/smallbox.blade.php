@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>

    <div class="small-box" style="background-color: [color]">
        <div class='inner inner-box'>
            <h3 class="small-box-sql-value">[sql]</h3>
            <p>[name]</p>
        </div>
        <div class="icon">
            <ion-icon name="[icon]"></ion-icon>
            <!--<i class="ion [icon]"></i>-->
        </div>
        <a href="[link]" class="small-box-footer">Dettagli <i class="fa fa-arrow-circle-right"></i></a>
    </div>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Small Box'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
</div>

<style>
    /* .small-box-sql-error compare al posto del numero quando la query SQL
       del widget fallisce - testo piu' piccolo e a capo, altrimenti un
       messaggio d'errore lungo rompe il layout della card (pensata per un
       numero corto) */
    .small-box-sql-value .small-box-sql-error {
        display: block;
        font-size: 13px;
        line-height: 1.3;
        white-space: normal;
        word-break: break-word;
    }
</style>

<script defer>
if (!window.location.href.includes('statistic_builder/builder')) {
    //jquery get div with action class
    var action = $('#{{$componentID}}').find('.action');
    //make it disappear
    action.hide();


    

}
</script>

@elseif($command=='configuration')
<?php
    //elenco delle icone Ionicons davvero disponibili, letto dal font/CSS gia'
    //vendorizzato (public/vendor/crudbooster/ionic/), cosi' l'elenco nella
    //select coincide sempre con quello che il widget puo' effettivamente
    //mostrare - niente lista statica da tenere allineata a mano.
    $ionIconsCssPath = public_path('vendor/crudbooster/ionic/css/ionicons.min.css');
    $ionIconNames = [];
    if (file_exists($ionIconsCssPath)) {
        preg_match_all('/\.(ion-[a-z0-9-]+):before/', file_get_contents($ionIconsCssPath), $ionIconsMatches);
        $ionIconNames = array_unique($ionIconsMatches[1]);
        sort($ionIconNames);
    }
?>
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="mb-3 row">
        <label>Icon By Ionicons</label>
        <select class="form-control" id="smallbox-icon-select" required name='config[icon]' style="width:100%">
            <option value="">-- seleziona un'icona --</option>
            @foreach($ionIconNames as $ionIconName)
            <option value="{{ $ionIconName }}" {{ (@$config->icon == $ionIconName) ? 'selected' : '' }}>{{ $ionIconName }}</option>
            @endforeach
        </select>
        <div class="help-block">Cerca digitando, es. "bag". Anteprima su <a target='_blank'
            href='http://ionicons.com/'>ionicons.com</a></div>
    </div>

    <div class="mb-3 row">
        <label>Color</label>
        <input type='color' class='form-control' name='config[color]' value='{{ @$config->color ?: "#00c0ef" }}' />
    </div>

    <div class="mb-3 row">
        <label>Link</label>
        <input class="form-control" required name='config[link]' type='text' value='{{@$config->link}}' />
    </div>

    <div class="mb-3 row">
        <label>Count (SQL QUERY)</label>
        <textarea name='config[sql]' rows="5" class='form-control'>{{@$config->sql}}</textarea>
        <div class="help-block">Make sure the sql query are correct unless the widget will be broken. Mak sure give the
            alias name each column. You may use
            alias [SESSION_NAME] to get the session
        </div>
    </div>

</form>

<link rel='stylesheet' href='{{ asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css") }}' />
<script>
    (function () {
        // Questa form viene iniettata via $.html() dentro #modal-statistic
        // (vedi statistic_builder/index.blade.php), non passa dal layout
        // standard: un push in coda pagina qui non avrebbe nessuno stack
        // ad ascoltarlo, quindi lo script per il select2 va incluso ed
        // eseguito direttamente qui.
        function initIconSelect() {
            $('#smallbox-icon-select').select2({
                width: '100%',
                dropdownParent: $('#modal-statistic')
            });
        }
        if (window.jQuery && $.fn.select2) {
            initIconSelect();
            return;
        }
        var script = document.createElement('script');
        script.src = '{{ asset("vendor/crudbooster/assets/select2/dist/js/select2.full.js") }}';
        script.onload = initIconSelect;
        document.body.appendChild(script);
    })();
</script>
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
            echo reset(DB::select($value)[0]);
        } catch (\Exception $e) {
            echo "<span class='small-box-sql-error'>" . e($e->getMessage()) . "</span>";
        }
    } else {
        echo $value;
    }

    ?>
@endif