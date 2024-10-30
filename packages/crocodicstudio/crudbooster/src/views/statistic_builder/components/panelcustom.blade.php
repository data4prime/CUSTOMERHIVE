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
            <select name='config[route]' class='form-control'>
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
    if ($config->type == 'controller') {
        $url = action($value);
    } elseif ($config->type == 'route') {
        $url = route($value);
    }
    echo "<div id='content-$componentID'></div>";
    ?>

    <script>
        $(function () {
            $('#content-{{$componentID}}').html("<i class='fa fa-spin fa-spinner'></i> Please wait loading...");
            $.get('{!! route($url) !!}', function (response) {

                //from respose, we need to get the content_section id
                var content_section = $(response).find('#content_section').html();

                //replace Back To List Data to Go to in content_section
                content_section = content_section.replace('Back To List Data', 'Go to');


                $('#content-{{$componentID}}').html(content_section);


            });
        })
    </script>

    <?php
    }else {
        echo $value;
    }
    ?>
@endif	