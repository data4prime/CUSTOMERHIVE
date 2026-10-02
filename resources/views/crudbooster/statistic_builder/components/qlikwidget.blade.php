
@if($command=='layout')

<style>
.qlikwidget {
	height: 50vh;
}
</style>


<div id='{{$componentID}}' class='border-box'>



@if (isset($config->qlik_mode) && $config->qlik_mode === 'sheet' && !empty($config->item))
{{-- Modalita' foglio: pagina dedicata che fa il login a Qlik e incorpora l'URL dell'item --}}
<iframe class="ch-module-frame" src="/mashup-sheet/{{$componentID}}" frameborder="0" style="width: 100%;height:80%;"></iframe>
@elseif (isset($config->qlik_mode) && $config->qlik_mode === 'sheet')
@include('crudbooster::statistic_builder.components._empty_widget_state', [
    'icon' => 'Q',
    'title' => trans('crudbooster.qlik_widget_not_configured'),
    'subtitle' => trans('crudbooster.qlik_widget_not_configured_hint'),
    'link' => $editUrl ?? null,
])
@elseif (isset($mashup->id) && $mashup->id != 0)
<iframe class="ch-module-frame" src="/mashup/{{$componentID}}" frameborder="0" style="width: 100%;height:80%;"></iframe>
@else
@include('crudbooster::statistic_builder.components._empty_widget_state', [
    'icon' => 'Q',
    'title' => trans('crudbooster.qlik_widget_not_configured'),
    'subtitle' => trans('crudbooster.qlik_widget_not_configured_hint'),
    'link' => $editUrl ?? null,
])
@endif




    <div class='action float-end'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='{{ trans('crudbooster.qlik_widget') }}'
            class='btn-edit-component'><i class='bi bi-pencil-fill'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='bi bi-trash-fill'></i></a>
    </div>
    </div>
 @elseif($command=='configuration')



@php 



@endphp 


<script defer>



function update_objects(select){

    
    var select = document.getElementById('mashup_app');
    var mashup_id = select.value;


    var select = document.getElementById('mashup_object');
    //delete all options except the first two
    while (select.options.length > 1) {
        select.remove(1);
    }



    var iframe = document.getElementById('configuration');

    iframe.src = '/mashup-objects/' + mashup_id + '/{{$componentID}}'+'/{{isset($config->object) ? $config->object : "empty"}}';




}
</script>


<div id='{{$componentID}}' style="width: 100%;height: 100%;">

<iframe src='/mashup-objects/{{ isset($mashup->id) ? $mashup->id : 0}}/{{$componentID}}/{{isset($config->object) ? $config->object : "empty"}}'  id="configuration" frameborder="0" style="width: 100%;height:30px;"></iframe>


@if(App\Helpers\LicenseHelper::isActiveQlik())
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
        <label for="qlik_mode">{{ trans('crudbooster.qlik_widget_mode') }}</label>
        <select id="qlik_mode" class='form-control' name='config[qlik_mode]'>
            <option value='master_object' @if(!isset($config->qlik_mode) || $config->qlik_mode !== 'sheet') selected @endif>{{ trans('crudbooster.qlik_widget_mode_master') }}</option>
            <option value='sheet' @if(isset($config->qlik_mode) && $config->qlik_mode === 'sheet') selected @endif>{{ trans('crudbooster.qlik_widget_mode_sheet') }}</option>
        </select>
    </div>
    <div class="mb-3 row">
        <label for="mashup_app">{{ trans('crudbooster.qlik_app') }}</label>
<select id="mashup_app" onchange="update_objects(this)" class='form-control' required name='config[mashups]'>
            <option value='0'>{{ trans('crudbooster.qlik_choose_app') }}</option>
            @foreach(collect($mashups)->sortBy(function ($m) { return mb_strtolower((string) $m->appname); }) as $m)
            @if(isset($config->mashups) && $m->id == $config->mashups)
            <option selected value='{{$m->id}}'>{{$m->appname}}</option>
            @else
            <option  value='{{$m->id}}'>{{$m->appname}}</option>
            @endif
            @endforeach
        </select>
<input type="hidden" id="mashup_app_hidden" value="{{ isset($config->mashups) ?  $config->mashups : '' }}">
    </div>

<div class="mb-3 row">
        <label for="mashup_object">{{ trans('crudbooster.qlik_app_object') }}</label>
        <select id="mashup_object" disabled  class='form-control' required name='config[object]'>
            <option value='0'>{{ trans('crudbooster.qlik_choose_object') }}</option>
            <!--<option value='CurrentSelections'>Current Selections</option>-->

        </select>
        <input type="hidden" id="mashup_object_hidden" value="{{ isset($config->object) ? $config->object : ''}}">
    </div>



<div class="mb-3 row" id="qlik_sheet_block" style="display:none">
    <label for="qlik_item">{{ trans('crudbooster.qlik_widget_sheet') }}</label>
    <select id="qlik_item" class='form-control'></select>
    <small id="qlik_item_hint" class="text-muted"></small>
</div>
</form>
@endif
</div>
<script>
(function () {
    // Modalita' "foglio": app da qlik_apps e fogli dagli item collegati a quell'app.
    // Testi tradotti passati da Blade, mai scritti in chiaro qui.
    var ITEMS = {!! json_encode(\App\Services\QlikSync\QlikSyncUi::sheetItemsByApp()) !!};
    var I18N = {!! json_encode(['choose_sheet' => trans('crudbooster.qlik_widget_choose_sheet'), 'no_sheets' => trans('crudbooster.qlik_widget_no_sheets')]) !!};
    var chosenItem = {!! json_encode((string) ($config->item ?? '')) !!};

    var modeSel = document.getElementById('qlik_mode');
    var appSel = document.getElementById('mashup_app');
    var itemSel = document.getElementById('qlik_item');
    var itemHint = document.getElementById('qlik_item_hint');
    var sheetBlock = document.getElementById('qlik_sheet_block');
    var objSel = document.getElementById('mashup_object');
    if (!modeSel || !appSel || !itemSel || !objSel) { return; }
    var legacyRow = objSel.closest('.row');
    var loaderFrame = document.getElementById('configuration');
    var origUpdate = window.update_objects;

    function addOption(select, value, text, selected) {
        var o = document.createElement('option');
        o.value = value;
        o.textContent = text;
        if (selected) { o.selected = true; }
        select.appendChild(o);
    }

    // App e fogli: un unico campo con ricerca integrata (select2, come il widget Modulo).
    function select2Options(noResults) {
        var options = {
            width: '100%',
            language: {
                noResults: function () { return noResults; },
                searching: function () { return {!! json_encode(trans('crudbooster.qlik_widget_app_searching')) !!}; }
            }
        };
        var $modal = $('#modal-statistic');
        if ($modal.length) { options.dropdownParent = $modal; }
        return options;
    }
    if (!window.__chEnsureSelect2) {
        window.__chEnsureSelect2 = function (callback) {
            if (window.jQuery && $.fn.select2) { callback(); return; }
            if (window.__chSelect2Callbacks) { window.__chSelect2Callbacks.push(callback); return; }
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
    var itemSelect2Ready = false;
    window.__chEnsureSelect2(function () {
        $(appSel).select2(select2Options({!! json_encode(trans('crudbooster.qlik_widget_app_no_results')) !!}));
        $(itemSel).select2(select2Options({!! json_encode(trans('crudbooster.qlik_widget_sheet_no_results')) !!}));
        itemSelect2Ready = true;
        $(itemSel).trigger('change.select2');
    });

    function fillItems() {
        var list = ITEMS[appSel.value] || [];
        while (itemSel.firstChild) { itemSel.removeChild(itemSel.firstChild); }
        addOption(itemSel, '', I18N.choose_sheet, false);
        list.forEach(function (it) {
            var id = String(it.id);
            addOption(itemSel, id, it.title, id === chosenItem);
        });
        itemHint.textContent = (list.length === 0 && appSel.value && appSel.value !== '0') ? I18N.no_sheets : '';
        if (itemSelect2Ready) { $(itemSel).trigger('change.select2'); }
    }
    // select2 emette il change come evento jQuery: un listener nativo non lo vedrebbe.
    $(itemSel).on('change', function () { chosenItem = itemSel.value; });

    function applyMode() {
        var sheet = modeSel.value === 'sheet';
        sheetBlock.style.display = sheet ? '' : 'none';
        legacyRow.style.display = sheet ? 'none' : '';
        if (loaderFrame) { loaderFrame.style.display = sheet ? 'none' : ''; }
        if (sheet) {
            // il vecchio select oggetti non deve ne' essere inviato ne' bloccare il salvataggio
            objSel.removeAttribute('name');
            objSel.removeAttribute('required');
            itemSel.setAttribute('name', 'config[item]');
            itemSel.setAttribute('required', 'required');
            fillItems();
        } else {
            objSel.setAttribute('name', 'config[object]');
            objSel.setAttribute('required', 'required');
            itemSel.removeAttribute('name');
            itemSel.removeAttribute('required');
            if (origUpdate && appSel.value && appSel.value !== '0') { origUpdate(appSel); }
        }
    }

    // Al cambio di app: in modalita' foglio si aggiornano i fogli, altrimenti resta il comportamento di prima.
    window.update_objects = function (select) {
        if (modeSel.value === 'sheet') {
            chosenItem = '';
            fillItems();
        } else if (origUpdate) {
            origUpdate(select);
        }
    };

    modeSel.addEventListener('change', applyMode);
    applyMode();
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
            echo 'ERROR';
        }
    } else {
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