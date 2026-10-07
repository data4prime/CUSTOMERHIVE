@extends("crudbooster::module_generator.template")
@section("inner_content")

@php
    // Interruttore on/off. Il campo nascosto "false" davanti alla checkbox fa
    // arrivare sempre la chiave nel POST (checkbox non spuntata = nessun
    // valore): postStep5() scrive solo le chiavi presenti, quindi una chiave
    // assente lascerebbe la proprieta' al default della classe base invece di
    // "false". Valori inviati identici a quelli delle vecchie radio.
    $sw = function ($key, $on, $labelKey, $descKey = null) {
        $id = 'cfg_' . $key;
        $html  = '<div class="form-check form-switch mb-3">';
        $html .= '<input type="hidden" name="' . $key . '" value="false">';
        $html .= '<input class="form-check-input cfg-switch" type="checkbox" role="switch" id="' . $id . '" name="' . $key . '" value="true"' . ($on ? ' checked' : '') . '>';
        $html .= '<label class="form-check-label" for="' . $id . '">' . e(trans('crudbooster.' . $labelKey));
        if ($descKey) {
            $html .= '<div class="form-text mt-0">' . e(trans('crudbooster.' . $descKey)) . '</div>';
        }
        $html .= '</label></div>';
        return new \Illuminate\Support\HtmlString($html);
    };

    $orderby = $cb_orderby ?? '';
    if (is_array($orderby)) {
        $parts = [];
        foreach ($orderby as $k => $v) {
            $parts[] = $k . ',' . $v;
        }
        $orderby = implode(';', $parts);
    }

    // Righe di interruttori affiancati: un solo form-check per colonna, descrizione sotto l'etichetta.
    $swRow = function (array $items) use ($sw) {
        $html = '<div class="row g-3 mb-1">';
        foreach ($items as $it) {
            $html .= '<div class="col">' . $sw(...$it) . '</div>';
        }
        $html .= '</div>';
        return new \Illuminate\Support\HtmlString($html);
    };

    $styles = [
        'button_icon'      => 'mg_cfg_style_icon',
        'button_icon_text' => 'mg_cfg_style_icon_text',
        'button_text'      => 'mg_cfg_style_text',
        'button_dropdown'  => 'mg_cfg_style_dropdown',
    ];
@endphp

@push('bottom')
<script>
    // Campo titolo candidato: select ricercabile anche con poche colonne.
    // Dopo lo script generico di template.blade.php ($('.select2').select2()): il contenitore che
    // select2 crea ha a sua volta classe "select2" e, se inizializzato prima, verrebbe ripreso e
    // ridotto a 1px (campo che appare vuoto).
    $(function () {
        setTimeout(function () {
            var el = document.getElementById('titleField');
            if (el && window.chSelect) { window.chSelect(el, { minimumResultsForSearch: 0 }); }
        }, 50);
    });
</script>
@endpush

@push('head')
<style>
    .cfg-style-card { display: block; border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); padding: .6rem; cursor: pointer; background: var(--ch-surface); height: 100%; }
    .cfg-style-card.sel { border-color: var(--ch-accent); background: var(--ch-bg); box-shadow: 0 0 0 2px var(--ch-accent-soft); }
    .cfg-style-card .cfg-style-preview { min-height: 32px; }
</style>
@endpush

<form method='post' action="{{Route('ModulsControllerPostStep5')}}">
    <div class="card card-default">
    <div class="card-header mb-3">
        <h5 class="card-title">{{ trans('crudbooster.mg_cfg_title') }}</h5>
    </div>
        {{csrf_field()}}
        <input type="hidden" name="id" value='{{ $id }}'>
        <div class="card-body">

            <div class="row">
                <div class="col-12 mb-2"><h6 class="text-muted text-uppercase small">{{ trans('crudbooster.mg_cfg_list_title') }}</h6></div>
                <div class="col-12">
                    <div class="mb-3">
                        <label for="titleField" class="form-label">{{ trans('crudbooster.mg_cfg_title_field') }}</label>
                        @php
                            // Il valore attuale resta selezionabile anche se non e' (piu') una colonna della tabella.
                            $titleCur = (string) ($cb_title_field ?? '');
                            $titleOpts = collect($title_candidates ?? [])->push($titleCur)->filter(fn ($v) => $v !== '')->unique()->values();
                        @endphp
                        <select id="titleField" name="title_field" class="form-select">
                            @if($titleCur === '')<option value="" selected></option>@endif
                            @foreach($titleOpts as $opt)
                            <option value="{{ $opt }}" {{ $opt === $titleCur ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ trans('crudbooster.mg_cfg_title_field_help') }}</div>
                    </div>
                </div>
                @if(!empty($wizard_v2))
                {{-- Limit e Order By si impostano nel passo Lista; qui restano come
                     valori nascosti perche' postStep5() riscrive il blocco
                     CONFIGURATION solo con le chiavi presenti nel POST. --}}
                <input type="hidden" name="limit" value="{{ $cb_limit ?? '' }}">
                <input type="hidden" name="orderby" value="{{ $orderby }}">
                @else
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="limitData" class="form-label">{{ trans('crudbooster.mg_cfg_limit') }}</label>
                        <input type="number" id="limitData" name="limit" value="{{ $cb_limit ?? '' }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="mb-3">
                        <label for="orderBy" class="form-label">{{ trans('crudbooster.mg_cfg_orderby') }}</label>
                        <input type="text" id="orderBy" name="orderby" value="{{ $orderby }}" class="form-control">
                        <div class="form-text">{{ trans('crudbooster.mg_cfg_orderby_help') }}</div>
                    </div>
                </div>
                @endif
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold"><i class="bi bi-grid-3x3-gap-fill"></i> {{ trans('crudbooster.mg_cfg_group_top') }}</div>
                        <div class="card-body">
                            {{ $swRow([
                                ['button_add', !empty($cb_button_add), 'mg_cfg_button_add', 'mg_cfg_button_add_desc'],
                                ['button_filter', !empty($cb_button_filter), 'mg_cfg_button_filter', 'mg_cfg_button_filter_desc'],
                            ]) }}
                            {{ $swRow([
                                ['button_import', !empty($cb_button_import), 'mg_cfg_button_import', 'mg_cfg_button_import_desc'],
                                ['button_export', !empty($cb_button_export), 'mg_cfg_button_export', 'mg_cfg_button_export_desc'],
                            ]) }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold"><i class="bi bi-three-dots"></i> {{ trans('crudbooster.mg_cfg_group_rows') }}</div>
                        <div class="card-body">
                            {{ $sw('button_table_action', !empty($cb_button_table_action), 'mg_cfg_button_table_action', 'mg_cfg_button_table_action_desc') }}
                            <hr>
                            {{ $swRow([
                                ['button_detail', !empty($cb_button_detail), 'mg_cfg_button_detail'],
                                ['button_edit', !empty($cb_button_edit), 'mg_cfg_button_edit'],
                                ['button_delete', !empty($cb_button_delete), 'mg_cfg_button_delete'],
                            ]) }}
                            <hr>
                            <div class="small fw-semibold mb-2">{{ trans('crudbooster.mg_cfg_button_style') }}</div>
                            <div class="row g-2">
                                @foreach($styles as $value => $labelKey)
                                <div class="col-6">
                                    <label class="cfg-style-card {{ (($cb_button_action_style ?? '') == $value) ? 'sel' : '' }}">
                                        <input type="radio" class="d-none cfg-style-radio" name="button_action_style" value="{{ $value }}" {{ (($cb_button_action_style ?? '') == $value) ? 'checked' : '' }}>
                                        <div class="small mb-1">{{ trans('crudbooster.' . $labelKey) }}</div>
                                        <div class="cfg-style-preview" data-style="{{ $value }}"></div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold"><i class="bi bi-lock-fill"></i> {{ trans('crudbooster.mg_cfg_group_selection') }}</div>
                        <div class="card-body">
                            {{ $sw('button_bulk_action', !empty($cb_button_bulk_action), 'mg_cfg_button_bulk', 'mg_cfg_button_bulk_desc') }}
                            <hr>
                            {{ $sw('global_privilege', !empty($cb_global_privilege), 'mg_cfg_global_privilege', 'mg_cfg_global_privilege_desc') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @include('crudbooster::module_generator._nav', [
        'nav_back' => CRUDBooster::mainpath('step4') . '/' . $id,
        'nav_back_ajax' => true,
        'nav_next' => trans('crudbooster.mg_cfg_save'),
        'nav_next_name' => 'submit',
    ])
</form>

@push('bottom')
<script>
    (function () {
        // Anteprima dei pulsanti di riga per ciascuno stile: segue gli
        // interruttori Dettaglio / Modifica / Elimina.
        var labels = {!! json_encode([
            'detail' => trans('crudbooster.action_detail_data'),
            'edit' => trans('crudbooster.action_edit_data'),
            'delete' => trans('crudbooster.action_delete_data'),
            'actions' => trans('crudbooster.mg_cfg_group_rows'),
        ]) !!};
        var defs = [
            {key: 'button_detail', icon: 'bi-eye-fill', label: labels.detail, cls: 'btn-info'},
            {key: 'button_edit', icon: 'bi-pencil-fill', label: labels.edit, cls: 'btn-success'},
            {key: 'button_delete', icon: 'bi-trash-fill', label: labels.delete, cls: 'btn-danger'}
        ];

        function isOn(key) {
            var el = document.getElementById('cfg_' + key);
            return !!(el && el.checked);
        }

        function makeBtn(def, style) {
            var b = document.createElement('span');
            b.className = 'btn btn-sm ' + def.cls + ' me-1';
            if (style !== 'button_text') {
                var i = document.createElement('i');
                i.className = 'bi ' + def.icon;
                b.appendChild(i);
            }
            if (style !== 'button_icon') {
                b.appendChild(document.createTextNode((style === 'button_icon_text' ? ' ' : '') + def.label));
            }
            return b;
        }

        function renderPreviews() {
            document.querySelectorAll('.cfg-style-preview').forEach(function (box) {
                var style = box.getAttribute('data-style');
                box.textContent = '';
                var active = defs.filter(function (d) { return isOn(d.key); });
                if (!active.length) { box.textContent = '—'; return; }
                if (style === 'button_dropdown') {
                    var dd = document.createElement('span');
                    dd.className = 'btn btn-sm btn-secondary';
                    dd.appendChild(document.createTextNode(labels.actions + ' '));
                    var c = document.createElement('i');
                    c.className = 'bi bi-caret-down-fill';
                    dd.appendChild(c);
                    box.appendChild(dd);
                    return;
                }
                active.forEach(function (d) { box.appendChild(makeBtn(d, style)); });
            });
        }

        document.querySelectorAll('.cfg-switch').forEach(function (el) {
            el.addEventListener('change', renderPreviews);
        });
        document.querySelectorAll('.cfg-style-radio').forEach(function (el) {
            el.addEventListener('change', function () {
                document.querySelectorAll('.cfg-style-card').forEach(function (c) { c.classList.remove('sel'); });
                el.closest('.cfg-style-card').classList.add('sel');
            });
        });
        renderPreviews();
    })();
</script>
@endpush

@endsection
