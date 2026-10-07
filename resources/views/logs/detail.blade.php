@extends('crudbooster::admin_template')

@section('content')
{{-- Dettaglio di un evento del registro accessi (intervento 232): a sinistra
     i dati dell'evento, a destra cosa e' cambiato. Sola lettura. --}}
<?php
  $userAgent = (string) $row->useragent;
  $parsedUa = \App\Http\Controllers\System\LogsController::parseUserAgent($userAgent);
  $when = $row->created_at ? date('d/m/Y H:i:s', strtotime($row->created_at)) : '';
?>
<style>
  .log-field { min-height: var(--ch-field-h); padding: 7px var(--ch-field-px); border: 1px solid var(--ch-field-border); border-radius: var(--ch-radius-sm); background: var(--ch-field-bg); overflow-wrap: anywhere; }
  .log-lbl { font-size: var(--ch-font-size-sm); font-weight: 600; margin-bottom: 5px; display: block; }
  .log-diff td, .log-diff th { padding: 8px 10px; }
  .log-diff td { font-family: ui-monospace, Consolas, monospace; font-size: var(--ch-font-size-sm); overflow-wrap: anywhere; }
  .log-old { background: var(--ch-danger-soft); color: var(--ch-danger); padding: 1px 5px; border-radius: 4px; }
  .log-new { background: var(--ch-success-soft); color: var(--ch-success); padding: 1px 5px; border-radius: 4px; }
</style>


<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <div class="small text-secondary">
      <a href="{{ g('return_url') ?: CRUDBooster::mainpath() }}" class="text-decoration-none" title="{{ trans('crudbooster.form_back_to_list', ['module' => CRUDBooster::getCurrentModule()->name]) }}">
        <i class="bi bi-chevron-left"></i> {{ CRUDBooster::getCurrentModule()->name }}
      </a> / #{{ $row->id }}
    </div>
    <h4 class="mb-0">
      {{ $row->description }}
      <span class="ch-pill ch-pill-{{ $event[1] }}" style="vertical-align:middle">{{ \App\Http\Controllers\System\LogsController::eventLabel($event[0]) }}</span>
    </h4>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6">
          <span class="log-lbl">{{ trans('crudbooster.adm_log_time') }}</span>
          <div class="log-field">{{ $when }}</div>
        </div>
        <div class="col-sm-6">
          <span class="log-lbl">User</span>
          <div class="log-field d-flex align-items-center gap-2">
            @if($user)
            {!! \App\Http\Controllers\System\LogsController::avatarHtml($user->name, $user->photo, $user->id, 24) !!}
            {{ $user->name }}
            @else
            <span class="text-secondary">-</span>
            @endif
          </div>
        </div>
        <div class="col-sm-6">
          <span class="log-lbl">IP Address</span>
          <div class="log-field">{{ $row->ipaddress }}</div>
        </div>
        <div class="col-sm-6">
          <span class="log-lbl">{{ trans('crudbooster.adm_log_browser') }}</span>
          <div class="log-field">{{ $parsedUa !== '' ? $parsedUa : '-' }}</div>
        </div>
        <div class="col-12">
          <span class="log-lbl">URL</span>
          <div class="log-field">{{ $row->url }}</div>
        </div>
        @if($userAgent !== '')
        <div class="col-12">
          <details>
            <summary class="small text-secondary" style="cursor:pointer">{{ trans('crudbooster.adm_log_full_ua') }}</summary>
            <div class="log-field mt-2 small">{{ $userAgent }}</div>
          </details>
        </div>
        @endif
      </div>
    </div></div>
  </div>

  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <strong>{{ trans('crudbooster.adm_log_changes') }}</strong>
        <span class="ch-pill ch-pill-gray">{{ trans_choice('crudbooster.adm_n_fields', count($diff), ['count' => count($diff)]) }}</span>
      </div>
      @if(count($diff))
      <div class="table-responsive">
        <table class="table log-diff mb-0">
          <thead><tr><th>{{ trans('crudbooster.adm_log_field') }}</th><th>{{ trans('crudbooster.adm_log_before') }}</th><th>{{ trans('crudbooster.adm_log_after') }}</th></tr></thead>
          <tbody>
            @foreach($diff as $d)
            <tr>
              <td>{{ $d[0] }}</td>
              <td>@if($d[1] === '')<span class="text-secondary">-</span>@else<span class="log-old">{{ $d[1] }}</span>@endif</td>
              <td>@if($d[2] === '')<span class="text-secondary">-</span>@else<span class="log-new">{{ $d[2] }}</span>@endif</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="text-secondary">{{ trans('crudbooster.adm_log_no_changes') }}</div>
      @endif
    </div></div>
  </div>
</div>

<div class="mt-3">
  <a href='{{ g("return_url") ?: CRUDBooster::mainpath() }}' class='btn btn-secondary'>
    <i class='bi bi-chevron-left'></i> {{ trans("crudbooster.button_back") }}
  </a>
</div>
@endsection
