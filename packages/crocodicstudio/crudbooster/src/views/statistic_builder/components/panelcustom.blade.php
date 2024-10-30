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

//remove post routes
foreach($routeCollection as $key => $value) {
    //dd($value);
}


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
            <label>Route</label>
            <select name='config[value]' class='form-control'>
                @foreach($routeCollection as $value)
                    @php
                    $action = $value->getAction('controller');
                    $controller = class_basename($action); 
                    $method = $value->getAction('method'); 
                    echo $method;

                    //remove @ char from method 
                    $method = str_replace('@', '', $method);
                @endphp

                <option {{(@$config->route == $value->getName()) ? "selected" : ""}} 
                        value='{{$value->getName()}}'>
                    {{ $controller . '@' . $method }} <!-- Mostra controller@method -->
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