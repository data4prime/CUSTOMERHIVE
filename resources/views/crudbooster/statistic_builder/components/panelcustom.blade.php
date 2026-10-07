@if($command=='layout')
@php
    // Link "Apri a pagina intera": stessa route del modulo incorporato,
    // senza ?embed=1. Niente link se il modulo non esiste piu'.
    $fullPageUrl = (!empty($config->value) && \Illuminate\Support\Facades\Route::has($config->value))
        ? route($config->value)
        : null;
@endphp
    <div id='{{$componentID}}' class='border-box'>

        @if(empty($config->name))
        @include('crudbooster::statistic_builder.components._empty_widget_state', [
            'icon' => '▦',
            'title' => trans('crudbooster.module_widget_not_configured'),
            'subtitle' => trans('crudbooster.module_widget_select_hint'),
            'link' => $editUrl ?? null,
        ])
        @else
        <div class="card card-default ch-module-card">
            <div class="card-header ch-module-card-header">
                <span>[name]</span>
                @if($fullPageUrl)
                <a href="{{ $fullPageUrl }}" class="ch-module-open" title="{{ trans('crudbooster.module_widget_open_full') }}">
                    <i class="bi bi-box-arrow-up-right"></i> {{ trans('crudbooster.module_widget_open_full') }}
                </a>
                @endif
            </div>
            <div class="card-body">
                [value]
            </div>
        </div>
        @endif

        {{-- Il modulo vive in un iframe (?embed=1, vedi CBBackend e
             docs/refactoring/162-*/163-*): la card riempie l'altezza decisa
             nel builder (griglia) e l'iframe riempie la card; nelle aree
             legacy, senza altezza definita, l'iframe ha un'altezza fissa. --}}
        <style>
            .ch-module-card { display: flex; flex-direction: column; height: 100%; }
            .border-box .card.card-default.ch-module-card .card-body { flex: 1 1 auto; min-height: 0; padding: 0; overflow: hidden; }
            .ch-module-card-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
            .ch-module-open { font-size: 12px; font-weight: 600; white-space: nowrap; text-decoration: none; }
            .ch-module-frame { display: block; width: 100%; height: 600px; border: 0; }
            .ch-grid-view .ch-module-frame, #ch-grid-canvas .ch-module-frame { height: 100%; }
            /* theme.css (.border-box iframe) darebbe all'iframe bordo e raggio: qui
               la cornice e' gia' quella del widget, un raggio proprio lascia angoli visibili */
            .border-box .ch-module-frame { border: 0; border-radius: 0; }
        </style>

        <div class='action float-end'>
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Module Panel' class='btn-edit-component'><i
                        class='bi bi-pencil-fill'></i></a> &nbsp;
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i class='bi bi-trash-fill'></i></a>
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
        'getIndex' => trans('crudbooster.module_widget_action_list'),
        'getAdd' => trans('crudbooster.module_widget_action_add'),
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

        // Token API: pagina di generazione token, non ha senso incorporarla
        // in un widget di dashboard (espone un'azione di sicurezza, non un
        // contenuto da visualizzare).
        if ($controllerName === 'ApiTokensController') {
            continue;
        }

        // Qlik/Chat AI sono funzionalita' a licenza: un modulo il cui
        // controller le richiede non va offerto qui se la licenza
        // corrente non le include, altrimenti si sceglierebbe un modulo
        // che poi non funziona davvero.
        if (stripos($controllerName, 'qlik') !== false && !\App\Helpers\LicenseHelper::isActiveQlik()) {
            continue;
        }
        if ($controllerName === 'AdminChatAIController' && !\App\Helpers\LicenseHelper::isActiveChatAI()) {
            continue;
        }

        $moduleLabel = $moduleNames[$controllerName] ?? trim(preg_replace('/(?<!^)([A-Z])/', ' $1', preg_replace('/^Admin|Controller$/', '', $controllerName)));

        $moduleOptions[] = [
            'value' => $route->getName(),
            'label' => $moduleLabel . ' - ' . ($actionLabels[$method] ?? $method),
        ];
    }
    usort($moduleOptions, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));
@endphp
    <form method='post'>
        <input type='hidden' name='_token' value='{{csrf_token()}}'/>
        <input type='hidden' name='componentid' value='{{$componentID}}'/>
        <div class="mb-3 row">
            <label>{{ trans('crudbooster.module_widget_name') }}</label>
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
            <label>{{ trans('crudbooster.module_widget_module_to_show') }}</label>
            <select name='config[value]' id='ch-module-select-{{$componentID}}' class='form-control' style='width:100%'>
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

    {{-- Select "Modulo da mostrare" ricercabile (select2), stesso schema
         di smallbox.blade.php (icona) e _query_builder_fields.blade.php
         (dataset): questa form viene iniettata via $.html() nella modale
         del builder legacy o nella sidebar del builder a griglia, quindi
         CSS e script vanno inclusi qui e non in uno stack di fine pagina. --}}
    <link rel='stylesheet' href='{{ asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css") }}' />
    <script>
    (function () {
        // Caricamento pigro condiviso di select2 (stessa definizione di
        // smallbox/_query_builder_fields: chiunque la crei per primo la
        // usa, nessun secondo <script src> in corsa).
        if (!window.__chEnsureSelect2) {
            window.__chEnsureSelect2 = function (callback) {
                if (window.jQuery && $.fn.select2) {
                    callback();
                    return;
                }
                if (window.__chSelect2Callbacks) {
                    window.__chSelect2Callbacks.push(callback);
                    return;
                }
                window.__chSelect2Callbacks = [callback];
                var script = document.createElement('script');
                script.src = '{{ asset("vendor/crudbooster/assets/select2/dist/js/select2.full.js") }}';
                script.onload = function () {
                    window.__chSelect2Callbacks.forEach(function (cb) { cb(); });
                    window.__chSelect2Callbacks = null;
                };
                document.body.appendChild(script);
            };
        }

        function initModuleSelect() {
            var options = {
                width: '100%',
                language: {
                    noResults: function () { return {!! json_encode(trans('crudbooster.module_widget_no_results')) !!}; },
                    searching: function () { return {!! json_encode(trans('crudbooster.module_widget_searching')) !!}; }
                }
            };
            // dropdownParent solo se la modale legacy esiste (vedi
            // smallbox.blade.php): su un elemento inesistente select2 fallisce.
            var $modal = $('#modal-statistic');
            if ($modal.length) {
                options.dropdownParent = $modal;
            }
            $('#ch-module-select-{{ $componentID }}').select2(options);
        }
        window.__chEnsureSelect2(initModuleSelect);
    })();
    </script>
@elseif($command=='showFunction')
    <?php


    if($key == 'value') {





    // Il modulo scelto puo' non esistere piu' (rimosso/rinominato dopo il
    // salvataggio del widget): route() lancerebbe RouteNotFoundException
    // e manderebbe in errore l'intera dashboard, non solo questo widget.
    $moduleMissing = !\Illuminate\Support\Facades\Route::has($value);

    if ($moduleMissing) {
        echo "<p class='text-muted'>" . e(trans('crudbooster.module_widget_unavailable')) . "</p>";
    } else {
        // Il modulo gira in un iframe con layout ridotto (?embed=1, vedi
        // CBBackend): isolato dalla dashboard, e le azioni al suo interno
        // (filtri, paginazione, apri/salva record) restano nel widget.
        $frameUrl = route($value) . '?embed=1';
        echo "<iframe class='ch-module-frame' src='" . e($frameUrl) . "' title='" . e($config->name ?? '') . "' loading='lazy'></iframe>";
    }
    ?>

    <?php
    }else {
        echo $value;
    }
    ?>
@endif	