@extends('crudbooster::admin_template')

@section('content')

@include('crudbooster::partials.api_nav', ['active' => 'keys'])

@php
    // Come nel mockup: le chiavi segrete saranno dismesse, il pulsante di creazione resta visibile ma spento
    // (le chiavi esistenti continuano a funzionare). Per riattivarlo basta mettere false.
    $apiKeyGenerateDisabled = true;
    $apiTokensLink = '<a class="fw-semibold" href="'.url(config('crudbooster.ADMIN_PATH').'/api_tokens').'"><i class="bi bi-lock-fill"></i> '.e(trans('crudbooster.api_doc_api2_howto_hint_link_label')).' <i class="bi bi-arrow-right"></i></a>';
@endphp
<div class="api-note-danger" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <b>{{ trans('crudbooster.api_keys_note_title') }}</b> {{ trans('crudbooster.api_keys_note_body') }}
        <p>{!! trans('crudbooster.api_keys_note_sunset', ['link' => $apiTokensLink]) !!}</p>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
    <span class="ch-pill ch-pill-vio">{{ trans('crudbooster.api_keys_count', ['n' => count($apikeys)]) }}</span>
    <button type="button" class="btn btn-primary" id="api-key-generate" @if($apiKeyGenerateDisabled) disabled title="{{ trans('crudbooster.api_keys_generate_disabled') }}" @endif><i class="bi bi-plus-lg"></i> {{ trans('crudbooster.Generate_Screet_Key') }}</button>
</div>

<div class="api-card">
    @if(count($apikeys) == 0)
        <div class="api-card-b text-center py-5">
            <i class="bi bi-key" style="font-size:2rem;color:var(--ch-text-muted)"></i>
            <p class="mt-2 mb-3">{{ trans('crudbooster.no_secret_key_found') }}. {{ trans('crudbooster.api_keys_empty_hint') }}</p>
            <button type="button" class="btn btn-primary" id="api-key-generate-empty" @if($apiKeyGenerateDisabled) disabled title="{{ trans('crudbooster.api_keys_generate_disabled') }}" @endif><i class="bi bi-plus-lg"></i> {{ trans('crudbooster.Generate_Screet_Key') }}</button>
        </div>
    @else
        <div class="table-responsive">
            <table id="table-apikey" class="table api-table align-middle mb-0">
                <thead>
                <tr>
                    <th class="ps-3">{{ trans('crudbooster.api_keys_col_key') }}</th>
                    <th>{{ trans('crudbooster.api_keys_col_created') }}</th>
                    <th>{{ trans('crudbooster.api_keys_col_hits') }}</th>
                    <th>{{ trans('crudbooster.api_keys_col_status') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($apikeys as $row)
                    @php
                        $isActive = $row->status == 'active';
                        $masked = strlen($row->screetkey) > 8 ? substr($row->screetkey, 0, 4).str_repeat('•', 16).substr($row->screetkey, -4) : $row->screetkey;
                    @endphp
                    <tr data-id="{{ $row->id }}">
                        <td class="ps-3 api-secret"><span class="api-key-text" data-full="{{ $row->screetkey }}" data-masked="{{ $masked }}">{{ $masked }}</span></td>
                        <td>{{ !empty($row->created_at) ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') : '-' }}</td>
                        <td>{{ number_format($row->hit, 0, ',', '.') }}</td>
                        <td>
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input api-key-switch" type="checkbox" role="switch" id="api-key-sw-{{ $row->id }}"
                                       {{ $isActive ? 'checked' : '' }}
                                       data-url="{{ CRUDBooster::mainpath('status-apikey') }}?id={{ $row->id }}&status={{ $isActive ? 0 : 1 }}">
                                <label class="form-check-label" for="api-key-sw-{{ $row->id }}">
                                    <span class="badge {{ $isActive ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $isActive ? trans('crudbooster.api_keys_active') : trans('crudbooster.api_keys_inactive') }}</span>
                                </label>
                            </div>
                        </td>
                        <td class="text-end pe-3 text-nowrap">
                            <button type="button" class="btn btn-sm btn-secondary api-key-show">{{ trans('crudbooster.api_keys_show') }}</button>
                            <button type="button" class="btn btn-sm btn-secondary api-key-copy">{{ trans('crudbooster.api_doc_copy') }}</button>
                            <button type="button" class="btn btn-sm btn-secondary text-danger api-key-ask">{{ trans('crudbooster.delete_button') }}</button>
                        </td>
                    </tr>
                    <tr class="api-confirm api-key-confirm" hidden>
                        <td colspan="5" class="px-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <span><strong>{{ trans('crudbooster.api_keys_delete_confirm', ['key' => substr($row->screetkey, 0, 4).'…'.substr($row->screetkey, -4)]) }}</strong> {{ trans('crudbooster.api_keys_delete_warning') }}</span>
                                <span class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-secondary api-key-cancel">{{ trans('crudbooster.api_cancel') }}</button>
                                    <button type="button" class="btn btn-sm btn-danger api-key-del" data-id="{{ $row->id }}">{{ trans('crudbooster.api_keys_delete_do') }}</button>
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('bottom')
<script>
$(function () {
    var T = {
        show: {!! json_encode(trans('crudbooster.api_keys_show')) !!},
        hide: {!! json_encode(trans('crudbooster.api_keys_hide')) !!},
        copied: {!! json_encode(trans('crudbooster.api_doc_copied')) !!},
        copy: {!! json_encode(trans('crudbooster.api_doc_copy')) !!}
    };

    $('#api-key-generate, #api-key-generate-empty').on('click', function () {
        $.get("{{ route('ApiCustomControllerGetGenerateScreetKey') }}", function () { location.reload(); });
    });

    $('.api-key-switch').on('change', function () { location.href = $(this).data('url'); });

    $('.api-key-show').on('click', function () {
        var span = $(this).closest('tr').find('.api-key-text'), shown = $(this).data('shown');
        span.text(shown ? span.data('masked') : span.data('full'));
        $(this).data('shown', !shown).text(shown ? T.show : T.hide);
    });

    $('.api-key-copy').on('click', function () {
        var btn = this, full = $(this).closest('tr').find('.api-key-text').data('full');
        var done = function () { btn.textContent = T.copied; setTimeout(function () { btn.textContent = T.copy; }, 1500); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(full).then(done, function () {});
        }
    });

    $('.api-key-ask').on('click', function () { $(this).closest('tr').next('.api-key-confirm').prop('hidden', false); });
    $('.api-key-cancel').on('click', function () { $(this).closest('tr').prop('hidden', true); });
    $('.api-key-del').on('click', function () {
        $.get("{{ CRUDBooster::mainpath('delete-api-key') }}?id=" + $(this).data('id'), function () { location.reload(); });
    });
});
</script>
@endpush

@endsection
