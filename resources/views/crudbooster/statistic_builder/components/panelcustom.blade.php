@if($command=='layout')
    <div id='{{$componentID}}' class='border-box'>

        <div class="card card-default">
            <div class="card-header">
                [name]
            </div>
            <div class="card-body">
                [value]
            </div>
        </div>

        <div class='action pull-right'>
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Module Panel' class='btn-edit-component'><i
                        class='fa fa-pencil'></i></a> &nbsp;
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i class='fa fa-trash'></i></a>
        </div>
    </div>
@elseif($command=='configuration')
@php
    $routeCollection = Illuminate\Support\Facades\Route::getRoutes();

    // Nome leggibile del modulo (lo stesso mostrato in sidebar/menu) per
    // ogni controller, cosi' la select mostra "Users Management - Lista"
    // invece del nome tecnico "AdminCmsUsersController@getIndex".
    $moduleNames = Illuminate\Support\Facades\DB::table('cms_moduls')->pluck('name', 'controller');

    $actionLabels = [
        'getIndex' => 'Lista',
        'getAdd' => 'Aggiungi',
    ];

    // Costruiamo prima l'elenco completo (valore + etichetta), poi lo
    // ordiniamo alfabeticamente per etichetta: con decine di moduli
    // l'ordine "come li trova il router" era illeggibile.
    $moduleOptions = [];
    foreach ($routeCollection as $route) {
        $action = $route->getAction('controller');
        if (!$action) {
            continue;
        }

        $controllerAndMethod = class_basename($action);
        $parts = explode('@', $controllerAndMethod);
        $controllerName = $parts[0];
        $method = $parts[1] ?? '';

        if ($method !== 'getIndex' && $method !== 'getAdd') {
            continue;
        }

        $moduleLabel = $moduleNames[$controllerName] ?? trim(preg_replace('/(?<!^)([A-Z])/', ' $1', preg_replace('/^Admin|Controller$/', '', $controllerName)));

        $moduleOptions[] = [
            'value' => $route->getName(),
            'label' => $moduleLabel . ' - ' . ($actionLabels[$method] ?? $method),
        ];
    }
    usort($moduleOptions, fn ($a, $b) => strcasecmp($a['label'], $b['label']));
@endphp
    <form method='post'>
        <input type='hidden' name='_token' value='{{csrf_token()}}'/>
        <input type='hidden' name='componentid' value='{{$componentID}}'/>
        <div class="mb-3 row">
            <label>Name</label>
            <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}'/>
        </div>

        <!--<div class="mb-3 row">
            <label>Type</label>
            <select name='config[type]' class='form-control'>
                <option {{(@$config->type == 'controller')?"selected":""}} value='controller'>Controller & Method</option>
                <option {{(@$config->type == 'route')?"selected":""}} value='route'>Route Name</option>
            </select>
        </div>-->

        <div class="mb-3 row">
            <label>Modulo da mostrare</label>
            <select name='config[value]' class='form-control'>
@foreach($moduleOptions as $opt)
            <option value="{{ $opt['value'] }}" {{ @$config->value == $opt['value'] ? 'selected' : '' }}>
                {{ $opt['label'] }}
            </option>
@endforeach


            </select>
        </div>

        <!--<div class="mb-3 row">
            <label>Value</label>
            <input name='config[value]' type='text' class='form-control' value='{{@$config->value}}'/>
            <div class='help-block'>You must enter the valid value related with current TYPE unless, widget will not work</div>
        </div>-->

    </form>
@elseif($command=='showFunction')
    <?php


    if($key == 'value') {





$url = route($value);




    echo "<div id='content-$componentID'></div>";
    ?>

<script>
    $(function () {
        const $content = $('#content-{{$componentID}}');
        const loadingMessage = "<i class='fa fa-spin fa-spinner'></i> Please wait, loading...";

        // Mostra il messaggio di caricamento
        $content.html(loadingMessage);

        $.get('{{$url}}')
            .done(function (response) {
                const contentSection = $(response).find('#content_section').html();

                // Sostituisci il testo specificato
                const updatedContent = contentSection.replace('Back To List Data', 'Go to');

                // Aggiorna il contenuto
                $content.html(updatedContent);
            })
            .fail(function (jqXHR) {
                // Gestione degli errori
                let errorMessage;

                if (jqXHR.status === 404) {
                    errorMessage = "<p>404 Not Found: The requested resource could not be found.</p>";
                } else if (jqXHR.status === 500) {
                    errorMessage = "<p>500 Internal Server Error: Something went wrong on the server.</p>";
                } else {
                    errorMessage = "<p>Error loading content. Please check routes!</p>";
                }

                // Mostra il messaggio di errore
                $content.html(errorMessage);
            });
    });
</script>



    <?php
    }else {
        echo $value;
    }
    ?>
@endif	