<!DOCTYPE html>
<html>
<head>
    <title>{{ trans('crudbooster.api_documentation') }} {{ Session::get('appname') }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('crudbooster::partials.ch_head')
    <link href="{{ asset('css/ch-api.css') }}?v={{ @filemtime(public_path('css/ch-api.css')) }}" rel="stylesheet" type="text/css" />
    @include('crudbooster::partials.ch_scripts', ['ch_shell' => false])
</head>
<body>
@php
    $actionLabels = [
        'list' => trans('crudbooster.api_action_list'),
        'detail' => trans('crudbooster.api_action_detail'),
        'save_add' => trans('crudbooster.api_action_create'),
        'save_edit' => trans('crudbooster.api_action_update'),
        'delete' => trans('crudbooster.api_action_delete'),
    ];
    $apiSample = function ($type) {
        $type = (string) $type;
        if (in_array($type, ['integer', 'numeric', 'double'])) return 1;
        if ($type === 'boolean') return true;
        if (strpos($type, 'date') === 0) return '2026-01-31';
        return '...';
    };
    $byTable = collect($apis)->groupBy('tabel');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 py-3" style="background:var(--ch-surface);border-bottom:1px solid var(--ch-border)">
    <div>
        <div class="fw-bold" style="font-size:var(--ch-font-size-lg)">{{ trans('crudbooster.api_documentation') }} {{ Session::get('appname') }}</div>
        <div class="api-help m-0">{{ trans('crudbooster.api_pub_count', ['n' => count($apis)]) }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="badge text-bg-primary">{{ trans('crudbooster.api_doc_api2_base_url_label') }}: {{ url('api2') }}</span>
        <a class="btn btn-sm btn-secondary" target="_blank" href="{{ url('download-documentation-postman') }}"><i class="bi bi-download"></i> {{ trans('crudbooster.api_doc_export_postman') }}</a>
    </div>
</div>

<div class="api-pub">
    <nav class="api-pub-nav">
        <input type="search" id="pub-search" class="form-control form-control-sm mb-2" placeholder="{{ trans('crudbooster.api_doc_search') }}">
        @foreach($byTable as $table => $list)
            <div class="api-lbl mt-2 mb-1 px-2 pub-group">{{ $table }}</div>
            @foreach($list as $api)
                <a href="#{{ $api->permalink }}" data-slug="{{ $api->permalink }}" data-search="{{ strtolower($api->nama.' '.$api->permalink) }}">
                    <span class="api-method {{ strtolower($api->method_type ?: 'get') }}" style="min-width:44px">{{ strtoupper($api->method_type ?: 'get') }}</span>
                    <span class="text-truncate">{{ $api->nama }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <main class="api-pub-main">
        @forelse($apis as $api)
            @php
                $parameters = $api->parameters ? (unserialize($api->parameters) ?: []) : [];
                $responses = $api->responses ? (unserialize($api->responses) ?: []) : [];
                $method = strtolower($api->method_type ?: 'get');
                $usedParams = array_values(array_filter($parameters, fn ($p) => !empty($p['used'])));
                $obj = [];
                foreach ($responses as $r) { if (!empty($r['used'])) $obj[$r['name']] = $apiSample($r['type'] ?? ''); }
                $sample = ['api_status' => 1, 'api_message' => 'success'];
                if ($api->aksi === 'list') $sample['data'] = [$obj];
                elseif ($api->aksi === 'detail') $sample['data'] = $obj;
                elseif ($api->aksi === 'save_add') $sample['id'] = 1;
                $qs = '';
                if ($method === 'get' && count($usedParams)) {
                    $qs = '?'.implode('&', array_map(fn ($p) => $p['name'].'=', $usedParams));
                }
            @endphp
            <section class="pub-section" id="sec-{{ $api->permalink }}" data-slug="{{ $api->permalink }}" hidden>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="api-method {{ $method }}">{{ strtoupper($method) }}</span>
                    <h2 class="m-0" style="font-size:20px">{{ $api->nama }}</h2>
                    <span class="badge text-bg-secondary">{{ $actionLabels[$api->aksi] ?? $api->aksi }}</span>
                </div>
                @if(!empty(trim(strip_tags((string) $api->keterangan))))
                    <div class="my-3" style="max-width:65ch">{!! $api->keterangan !!}</div>
                @endif
                <div class="api-copy my-3">
                    <input type="text" class="form-control" id="pub-url-{{ $api->id }}" readonly value="{{ url('api2/'.$api->permalink) }}">
                    <button type="button" class="btn btn-secondary api-copy-btn" data-target="pub-url-{{ $api->id }}">{{ trans('crudbooster.api_doc_copy') }}</button>
                </div>

                <span class="api-lbl">{{ trans('crudbooster.api_doc_params') }}</span>
                <div class="api-card">
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

                <span class="api-lbl">{{ trans('crudbooster.api_doc_example') }}</span>
                <pre class="api-code mb-3">curl{{ $method === 'get' ? '' : ' -X '.strtoupper($method) }} "{{ url('api2/'.$api->permalink).$qs }}" \
  -H "Authorization: Bearer &lt;token&gt;"</pre>
                <span class="api-lbl">{{ trans('crudbooster.api_doc_response') }}</span>
                <pre class="api-code">{{ json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </section>
        @empty
            <p class="text-center api-help py-5">{{ trans('crudbooster.api_doc_no_endpoints') }}</p>
        @endforelse
    </main>
</div>

<script>
$(function () {
    var T = {copied: {!! json_encode(trans('crudbooster.api_doc_copied')) !!}, copy: {!! json_encode(trans('crudbooster.api_doc_copy')) !!}};
    function show() {
        var slug = decodeURIComponent(location.hash.slice(1)) || $('.pub-section:first').data('slug');
        var found = false;
        $('.pub-section').each(function () { var ok = $(this).data('slug') === slug; this.hidden = !ok; found = found || ok; });
        if (!found) $('.pub-section:first').prop('hidden', false), slug = $('.pub-section:first').data('slug');
        $('.api-pub-nav a').each(function () { $(this).toggleClass('active', $(this).data('slug') === slug); });
    }
    $(window).on('hashchange', show); show();

    $('#pub-search').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        $('.api-pub-nav a').each(function () { this.hidden = q && String($(this).data('search')).indexOf(q) === -1; });
    });

    $(document).on('click', '.api-copy-btn', function () {
        var btn = this, input = document.getElementById($(this).data('target'));
        var done = function () { btn.textContent = T.copied; setTimeout(function () { btn.textContent = T.copy; }, 1500); };
        input.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
        } else { document.execCommand('copy'); done(); }
    });
});
</script>
</body>
</html>
