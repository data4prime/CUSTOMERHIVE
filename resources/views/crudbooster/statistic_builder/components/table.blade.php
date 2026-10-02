@if($command=='layout')
<?php 
//var_dump(json_encode(Session::all()))
?>

<div id='{{$componentID}}' class='border-box'>

    @if(empty($config->name))
    @include('crudbooster::statistic_builder.components._empty_widget_state', [
        'icon' => '▤',
        'title' => 'Tabella non configurata',
        'subtitle' => 'Seleziona per collegare una query o un dataset',
        'link' => $editUrl ?? null,
    ])
    @else
    <div class="card card-default">
        <div class="card-header">
            [name]
        </div>
        <div class="card-body table-responsive no-padding">
            [sql]
        </div>
    </div>
    @endif

    <div class='action float-end'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Table'
            class='btn-edit-component'><i class='bi bi-pencil-fill'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='bi bi-trash-fill'></i></a>
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

    {{-- Sorgente dati in popup: SQL libera (come prima) oppure "Elenco
         record" (colonne/ordinamento/filtri su una tabella mg_*, con
         scoping per ruolo/tenant/gruppi - DashboardDatasetRegistry::
         executeRows()). Query guidata a due colonne non offerta qui. --}}
    @include('crudbooster::statistic_builder.components._source_config', [
        'componentID' => $componentID,
        'config' => $config,
        'sqlLabel' => 'SQL Query',
        'sqlPlaceholder' => 'E.g : select column_id,column_name from view_table_name',
        'sqlHelp' => "Make sure the sql query are correct unless the widget will be broken. Mak sure give the alias name each column. You may use alias [SESSION_NAME] to get the session. We strongly recommend that you use a view table.",
        'builderSupported' => false,
        'recordsSupported' => true,
    ])

</form>
@elseif($command=='showFunction')
<?php
    if($key == 'sql') {
    $sql = null;
    $sqlError = null;
    $recordsColumns = null;
    $pageLength = 10;
    if (is_array($value) && isset($value['columns'])) {
        // Modalita' 'records' ("Elenco record"): $value = ['columns' =>
        // [nome => etichetta], 'rows' => [...]] da
        // DashboardDatasetRegistry::executeRows(), gia' scopato sull'utente.
        $recordsColumns = $value['columns'];
        $sql = array_map(fn ($row) => (object) $row, $value['rows']);
        $requestedLength = (int) ($config->page_length ?? 10);
        $pageLength = in_array($requestedLength, \App\Dashboards\DashboardDatasetRegistry::ROWS_PAGE_LENGTHS, true) ? $requestedLength : 10;
    } elseif (is_array($value)) {
        // Modalita' 'builder' (query guidata): $value e' gia' l'elenco di
        // righe {label, value} risolto da DashboardDatasetRegistry::execute(),
        // non SQL da eseguire - vedi StatisticBuilderController.
        $sql = array_map(fn ($row) => (object) $row, $value);
    } else {
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
        $sql = DB::select($value);
    } catch (\Exception $e) {
        //prima: die('ERROR') interrompeva l'intera risposta AJAX di
        //getViewComponent() (niente piu' JSON valido), facendo sparire
        //il widget dall'area invece di mostrare l'errore
        $sqlError = $e->getMessage();
    }
    }
    ?>

@if($sqlError)
<div class="alert alert-danger table-widget-sql-error" style="margin:15px;">{{ $sqlError }}</div>
@elseif($sql || $recordsColumns)
<table id="table-widget-{{ $componentID }}" class='table table-striped'>
    <thead>
        <tr>
            @if($recordsColumns)
            @foreach($recordsColumns as $columnLabel)
            <th>{{ $columnLabel }}</th>
            @endforeach
            @else
            @foreach($sql[0] as $key=>$val)
            <th>{{$key}}</th>
            @endforeach
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($sql as $row)
        <tr>
            @if($recordsColumns)
            @foreach(array_keys($recordsColumns) as $columnKey)
            <td>{{ $row->$columnKey }}</td>
            @endforeach
            @else
            @foreach($row as $key=>$val)
            <td>{{$val}}</td>
            @endforeach
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
<script type="text/javascript">
    (function initTableWidget(attempt) {
        // Nella vista di sola lettura (show_grid, dentro admin_template) il
        // markup del widget e' nel contenuto, mentre jQuery e DataTables
        // sono caricati DOPO, in fondo alla pagina (admin_template_plugins):
        // questo script girava prima e `$` non esisteva ancora, quindi la
        // tabella restava senza paginazione/ricerca. Si riprova finche' non
        // sono disponibili (tetto ~5s); nel builder, dove sono gia' caricati,
        // parte subito.
        if (!window.jQuery || !jQuery.fn.DataTable) {
            if ((attempt || 0) < 100) {
                setTimeout(function () { initTableWidget((attempt || 0) + 1); }, 50);
            }
            return;
        }
        var $ = jQuery;
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
            @if($recordsColumns)
            pageLength: {{ $pageLength }},
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            @else
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            @endif
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