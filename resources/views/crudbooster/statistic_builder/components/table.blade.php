@if($command=='layout')
<?php 
//var_dump(json_encode(Session::all()))
?>

<div id='{{$componentID}}' class='border-box'>

    <div class="card card-default">
        <div class="card-header">
            [name]
        </div>
        <div class="card-body table-responsive no-padding">
            [sql]
        </div>
    </div>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Table'
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
        <label>SQL Query</label>
        <textarea name='config[sql]' rows="5" placeholder="E.g : select column_id,column_name from view_table_name"
            class='form-control'>{{@$config->sql}}</textarea>
        <div class='help-block'>
            Make sure the sql query are correct unless the widget will be broken. Mak sure give the alias name each
            column. You may use alias [SESSION_NAME]
            to get the session. We strongly recommend that you use a <a href='http://www.w3schools.com/sql/sql_view.asp'
                target='_blank'>view table</a>
        </div>
    </div>

</form>
@elseif($command=='showFunction')
<?php
    if($key == 'sql') {
    $sql = null;
    $sqlError = null;
    try {
        $sessions = Session::all();

        foreach ($sessions as $k => $val) {
            if (gettype($val) == gettype($value)) {
                //sostituisce il placeholder della sessione corrente (es.
                //[SESSION_NAME]), non il nome del campo - prima del fix
                //veniva usato "$key" (sempre 'sql' a questo punto, mai un
                //vero placeholder di sessione), la sostituzione non
                //scattava mai
                $value = str_replace("[".$k."]", $val, $value);
            }
        }
        $sql = DB::select(DB::raw($value));
    } catch (\Exception $e) {
        //prima: die('ERROR') interrompeva l'intera risposta AJAX di
        //getViewComponent() (niente piu' JSON valido), facendo sparire
        //il widget dall'area invece di mostrare l'errore
        $sqlError = $e->getMessage();
    }
    ?>

@if($sqlError)
<div class="alert alert-danger table-widget-sql-error" style="margin:15px;">{{ $sqlError }}</div>
@elseif($sql)
<table id="table-widget-{{ $componentID }}" class='table table-striped'>
    <thead>
        <tr>
            @foreach($sql[0] as $key=>$val)
            <th>{{$key}}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($sql as $row)
        <tr>
            @foreach($row as $key=>$val)
            <td>{{$val}}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
<script type="text/javascript">
    (function () {
        // Selettore generico "table.table" (prima) inizializzava TUTTE le
        // tabelle nella pagina, comprese quelle di altri widget Table gia'
        // presenti sulla stessa dashboard: DataTables lancia un errore
        // reinizializzando una tabella gia' trasformata, che puo' impedire
        // l'inizializzazione anche di questa (tabella "tagliata" - niente
        // ricerca/paginazione, tutte le righe renderizzate senza controllo).
        // Scoped al solo id di questo widget, con controllo anti-doppia
        // inizializzazione.
        var $table = $('#table-widget-{{ $componentID }}');
        if ($.fn.DataTable.isDataTable($table)) {
            return;
        }
        $table.DataTable({
            dom: "<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    })();
</script>
@endif
<?php
    }else {
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