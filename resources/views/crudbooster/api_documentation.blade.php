@extends('crudbooster::admin_template')

@section('content')

@include('crudbooster::partials.api_nav', ['active' => 'doc'])

@php
    $apiAdminBase = url(config('crudbooster.ADMIN_PATH'));
    $actionLabels = [
        'list' => trans('crudbooster.api_action_list'),
        'detail' => trans('crudbooster.api_action_detail'),
        'save_add' => trans('crudbooster.api_action_create'),
        'save_edit' => trans('crudbooster.api_action_update'),
        'delete' => trans('crudbooster.api_action_delete'),
    ];
    $actionCounts = [];
    foreach ($apis as $a) {
        $actionCounts[$a->aksi] = ($actionCounts[$a->aksi] ?? 0) + 1;
    }
    // Valore d'esempio per tipo, solo per la risposta di esempio mostrata.
    $apiSample = function ($type) {
        $type = (string) $type;
        if (in_array($type, ['integer', 'numeric', 'double'])) return 1;
        if ($type === 'boolean') return true;
        if (strpos($type, 'date') === 0) return '2026-01-31';
        return '...';
    };
@endphp

{{-- Come collegarsi --}}
<div class="api-card">
    <div class="api-card-h">
        <span>{{ trans('crudbooster.api_doc_connect_title') }}</span>
        <div class="btn-group btn-group-sm" role="group" id="api-mode">
            <button type="button" class="btn btn-primary" data-mode="bearer">{{ trans('crudbooster.api_doc_mode_bearer') }}</button>
            <button type="button" class="btn btn-secondary" data-mode="key">{{ trans('crudbooster.api_doc_mode_key') }}</button>
        </div>
    </div>
    <div class="api-card-b">
        <div class="api-grid" data-pane="bearer">
            <div>
                <span class="api-lbl">{{ trans('crudbooster.api_doc_base_url') }}</span>
                <div class="api-copy">
                    <input type="text" class="form-control" id="api-base-v2" readonly value="{{ url('api2') }}">
                    <button type="button" class="btn btn-secondary api-copy-btn" data-target="api-base-v2">{{ trans('crudbooster.api_doc_copy') }}</button>
                </div>
                <div class="api-help">
                    {!! trans('crudbooster.api_doc_api2_howto_hint', [
                        'link' => '<a href="'.url(config('crudbooster.ADMIN_PATH').'/api_tokens').'">'.trans('crudbooster.api_doc_api2_howto_hint_link_label').'</a>',
                    ]) !!}
                </div>
            </div>
            <pre class="api-code">curl {{ url('api2') }}/<span style="opacity:.6">{{ trans('crudbooster.api_doc_example_slug') }}</span> \
  -H "Authorization: Bearer &lt;token&gt;"</pre>
        </div>
        <div class="api-grid" data-pane="key" hidden>
            <div>
                <span class="api-lbl">{{ trans('crudbooster.api_doc_base_url') }}</span>
                <div class="api-copy">
                    <input type="text" class="form-control" id="api-base-v1" readonly value="{{ url('api') }}">
                    <button type="button" class="btn btn-secondary api-copy-btn" data-target="api-base-v1">{{ trans('crudbooster.api_doc_copy') }}</button>
                </div>
                <div class="api-help">{{ trans('crudbooster.api_doc_key_intro') }}</div>
            </div>
            <pre class="api-code">X-Authorization-Token: md5(SECRETKEY + TIME + USER_AGENT)
X-Authorization-Time: TIME   # {{ trans('crudbooster.api_doc_key_time') }}
X-user: {{ trans('crudbooster.api_doc_key_user') }}</pre>
        </div>
    </div>
</div>

{{-- Elenco endpoint --}}
<div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
    <input type="search" id="api-search" class="form-control" style="flex:1;min-width:200px;max-width:420px" placeholder="{{ trans('crudbooster.api_doc_search') }}">
    <div class="api-chips" id="api-filter">
        <button type="button" class="api-chip active" data-filter="">{{ trans('crudbooster.api_doc_filter_all') }} {{ count($apis) }}</button>
        @foreach($actionLabels as $key => $label)
            @if(!empty($actionCounts[$key]))
                <button type="button" class="api-chip" data-filter="{{ $key }}">{{ $label }} {{ $actionCounts[$key] }}</button>
            @endif
        @endforeach
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a class="btn btn-secondary btn-sm" target="_blank" href="{{ CRUDBooster::mainpath('download-postman') }}"><i class="bi bi-download"></i> {{ trans('crudbooster.api_doc_export_postman') }}</a>
        <a class="btn btn-primary btn-sm" href="{{ CRUDBooster::mainpath('generator') }}"><i class="bi bi-plus-lg"></i> {{ trans('crudbooster.api_tab_new_endpoint') }}</a>
    </div>
</div>

<div class="api-card" id="api-list">
    @forelse($apis as $api)
        @php
            $parameters = $api->parameters ? (unserialize($api->parameters) ?: []) : [];
            $responses = $api->responses ? (unserialize($api->responses) ?: []) : [];
            $method = strtolower($api->method_type ?: 'get');
            $usedParams = array_values(array_filter($parameters, fn ($p) => !empty($p['used'])));
            $usedResp = array_values(array_filter($responses, fn ($r) => !empty($r['used'])));

            $obj = [];
            foreach ($usedResp as $r) { $obj[$r['name']] = $apiSample($r['type'] ?? ''); }
            $sample = ['api_status' => 1, 'api_message' => 'success'];
            if ($api->aksi === 'list') $sample['data'] = [$obj];
            elseif ($api->aksi === 'detail') $sample['data'] = $obj;
            elseif ($api->aksi === 'save_add') $sample['id'] = 1;
            $sampleJson = json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        @endphp
        <div class="api-ep" data-action="{{ $api->aksi }}" data-search="{{ strtolower($api->nama.' '.$api->permalink.' '.$api->tabel) }}">
            <div class="api-ep-row" data-toggle="#api-detail-{{ $api->id }}">
                <span class="api-method {{ $method }}">{{ strtoupper($method) }}</span>
                <div>
                    <div class="api-ep-name">{{ $api->nama }}</div>
                    <div class="api-ep-sub">{{ trans('crudbooster.api_doc_table') }} <code>{{ $api->tabel }}</code></div>
                </div>
                <span class="api-ep-path">/{{ $api->permalink }}</span>
                <span class="badge text-bg-secondary api-ep-action">{{ $actionLabels[$api->aksi] ?? $api->aksi }}</span>
                <span class="api-ep-actions" data-stop>
                    <a class="btn btn-sm btn-secondary" title="{{ trans('crudbooster.api_doc_edit') }}" href="{{ CRUDBooster::mainpath('edit-api/'.$api->id) }}"><i class="bi bi-pencil"></i></a>
                    <button type="button" class="btn btn-sm btn-secondary api-del-ask" title="{{ trans('crudbooster.api_doc_delete') }}"><i class="bi bi-trash"></i></button>
                </span>
            </div>
            <div class="api-ep-confirm api-confirm px-3 py-2 d-flex justify-content-between align-items-center gap-2 flex-wrap" hidden>
                <span><strong>{{ trans('crudbooster.api_doc_delete_confirm', ['name' => $api->nama]) }}</strong> {{ trans('crudbooster.api_doc_delete_warning') }}</span>
                <span class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary api-del-cancel">{{ trans('crudbooster.api_cancel') }}</button>
                    <button type="button" class="btn btn-sm btn-danger api-del-do" data-url="{{ CRUDBooster::mainpath('delete-api/'.$api->id) }}">{{ trans('crudbooster.api_doc_delete') }}</button>
                </span>
            </div>
            <div class="api-ep-detail" id="api-detail-{{ $api->id }}" hidden>
                <div style="grid-column:1/-1">
                    <span class="api-lbl">{{ trans('crudbooster.api_doc_url') }}</span>
                    <div class="api-copy">
                        <input type="text" class="form-control" id="api-url-{{ $api->id }}" readonly value="{{ url('api2/'.$api->permalink) }}">
                        <button type="button" class="btn btn-secondary api-copy-btn" data-target="api-url-{{ $api->id }}">{{ trans('crudbooster.api_doc_copy') }}</button>
                    </div>
                </div>
                <div>
                    <span class="api-lbl">{{ trans('crudbooster.api_doc_params') }}</span>
                    <div class="table-responsive">
                        <table class="table table-sm api-table">
                            <thead><tr><th>{{ trans('crudbooster.api_doc_col_name') }}</th><th>{{ trans('crudbooster.api_doc_col_type') }}</th><th>{{ trans('crudbooster.api_doc_col_rule') }}</th><th></th></tr></thead>
                            <tbody>
                            @forelse($usedParams as $param)
                                <tr>
                                    <td class="api-mono">{{ $param['name'] }}</td>
                                    <td><span class="api-ty">{{ $param['type'] }}</span></td>
                                    <td>{{ $param['config'] ?? '' }}</td>
                                    <td>{!! !empty($param['required']) ? "<span class='badge text-bg-primary'>".e(trans('crudbooster.api_doc_required'))."</span>" : "<span class='badge text-bg-secondary'>".e(trans('crudbooster.api_doc_optional'))."</span>" !!}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center api-help">{{ trans('crudbooster.api_doc_no_params') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <span class="api-lbl">{{ trans('crudbooster.api_doc_response') }}</span>
                    <pre class="api-code">{{ $sampleJson }}</pre>
                </div>
                @if(!empty(trim(strip_tags((string) $api->keterangan))))
                    <div style="grid-column:1/-1">
                        <span class="api-lbl">{{ trans('crudbooster.api_doc_description') }}</span>
                        <div>{!! $api->keterangan !!}</div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="api-card-b text-center py-5">
            <i class="bi bi-diagram-3" style="font-size:2rem;color:var(--ch-text-muted)"></i>
            <p class="mt-2 mb-3">{{ trans('crudbooster.api_doc_no_endpoints') }}</p>
            <a class="btn btn-primary" href="{{ CRUDBooster::mainpath('generator') }}"><i class="bi bi-plus-lg"></i> {{ trans('crudbooster.api_doc_create_first') }}</a>
        </div>
    @endforelse
    <div class="api-card-b text-center api-help" id="api-no-match" hidden>{{ trans('crudbooster.api_doc_no_match') }}</div>
</div>

@push('bottom')
<script>
$(function () {
    var T = {copied: {!! json_encode(trans('crudbooster.api_doc_copied')) !!}, copy: {!! json_encode(trans('crudbooster.api_doc_copy')) !!}};

    // Modalita' di accesso (Bearer / chiave segreta)
    $('#api-mode button').on('click', function () {
        var m = $(this).data('mode');
        $('#api-mode button').removeClass('btn-primary').addClass('btn-secondary');
        $(this).removeClass('btn-secondary').addClass('btn-primary');
        $('[data-pane]').each(function () { this.hidden = ($(this).data('pane') !== m); });
    });

    // Copia negli appunti con ripiego su selezione
    $(document).on('click', '.api-copy-btn', function () {
        var btn = this, input = document.getElementById($(this).data('target'));
        var done = function () { btn.textContent = T.copied; setTimeout(function () { btn.textContent = T.copy; }, 1500); };
        input.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
        } else { document.execCommand('copy'); done(); }
    });

    // Apri/chiudi dettaglio
    $(document).on('click', '.api-ep-row', function (e) {
        if ($(e.target).closest('[data-stop]').length) return;
        var d = document.querySelector($(this).data('toggle'));
        d.hidden = !d.hidden;
    });

    // Filtro e ricerca
    var filter = '';
    function apply() {
        var q = $('#api-search').val().toLowerCase().trim(), shown = 0;
        $('#api-list .api-ep').each(function () {
            var ok = (!filter || $(this).data('action') === filter) && (!q || String($(this).data('search')).indexOf(q) !== -1);
            this.hidden = !ok; if (ok) shown++;
        });
        document.getElementById('api-no-match').hidden = shown > 0 || !$('#api-list .api-ep').length;
    }
    $('#api-search').on('input', apply);
    $('#api-filter').on('click', '.api-chip', function () {
        $('#api-filter .api-chip').removeClass('active'); $(this).addClass('active');
        filter = $(this).data('filter') || ''; apply();
    });

    // Eliminazione con conferma nella pagina
    $(document).on('click', '.api-del-ask', function () { $(this).closest('.api-ep').find('.api-ep-confirm').prop('hidden', false); });
    $(document).on('click', '.api-del-cancel', function () { $(this).closest('.api-ep-confirm').prop('hidden', true); });
    $(document).on('click', '.api-del-do', function () {
        $.get($(this).data('url'), function () { location.reload(); });
    });
});
</script>
@endpush

@endsection
