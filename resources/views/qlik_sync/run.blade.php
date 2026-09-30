@extends('crudbooster::admin_template')
@php
    $qsStatus = [
        'queued' => ['qlik_sync_status_queued', '#6c757d'],
        'running' => ['qlik_sync_status_running', '#0d6efd'],
        'cancelling' => ['qlik_sync_status_cancelling', '#b58105'],
        'cancelled' => ['qlik_sync_status_cancelled', '#6c757d'],
        'completed' => ['qlik_sync_status_completed', '#198754'],
        'failed' => ['qlik_sync_status_failed', '#dc3545'],
    ];
    $qsActionColor = ['created' => '#198754', 'linked' => '#0d6efd', 'updated' => '#b58105', 'skipped' => '#6c757d', 'failed' => '#dc3545'];
    [$stKey, $stColor] = $qsStatus[$run->status] ?? ['qlik_sync_status_failed', '#6c757d'];
    $pct = $run->total > 0 ? min(100, (int) round($run->processed * 100 / $run->total)) : ($run->status === 'completed' ? 100 : 0);
    $qsBase = url('admin/qlik_apps/sync-runs/' . $run->id);
@endphp
@section('content')
<div>
  <p><a href="{{ url('admin/qlik_apps/sync-runs') }}"><i class="fa fa-chevron-circle-left"></i> &nbsp; {{ trans('crudbooster.qlik_sync_back_runs') }}</a></p>

  @if($stuck && $run->status === 'queued')
  <div class="alert alert-warning" role="alert">
    <i class="fa fa-exclamation-triangle"></i> {{ trans('crudbooster.qlik_sync_no_worker') }}
  </div>
  @endif

  <div class="card card-default">
    <div class="card-header">
      <strong>
        {{ trans('crudbooster.qlik_sync_type_' . $run->type) }} —
        <span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:{{ $stColor }}">{{ trans('crudbooster.' . $stKey) }}</span>
        @if($run->rolled_back_at)
        <span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:#6c757d">{{ trans('crudbooster.qlik_sync_rolled_back') }}</span>
        @endif
      </strong>
    </div>
    <div class="card-body">
      <dl class="row" style="margin-bottom:8px">
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_conf') }}</dt>
        <dd class="col-sm-9">{{ $run->confname ?? ('#' . $run->qlik_conf_id) }}</dd>
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_app') }}</dt>
        <dd class="col-sm-9">{{ $run->qlik_app_id ? ($run->appname ?? ('#' . $run->qlik_app_id)) : ($run->type === 'items' ? trans('crudbooster.qlik_sync_all_apps') : '—') }}</dd>
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_user') }}</dt>
        <dd class="col-sm-9">{{ $run->user_name ?? ('#' . $run->user_id) }}</dd>
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_started') }}</dt>
        <dd class="col-sm-9">{{ $run->started_at ?? $run->created_at }}@if($run->finished_at) → {{ $run->finished_at }}@endif</dd>
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_progress') }}</dt>
        <dd class="col-sm-9">
          <div style="background:#e9ecef;border-radius:3px;height:12px;overflow:hidden;max-width:360px" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
            <div style="background:{{ $stColor }};height:12px;width:{{ $pct }}%"></div>
          </div>
          {{ $run->processed }} / {{ $run->total }}
        </dd>
        <dt class="col-sm-3">{{ trans('crudbooster.qlik_sync_col_results') }}</dt>
        <dd class="col-sm-9">
          {{ trans('crudbooster.qlik_sync_action_created') }}: {{ $run->created }} ·
          {{ trans('crudbooster.qlik_sync_action_linked') }}: {{ $run->linked }} ·
          {{ trans('crudbooster.qlik_sync_action_updated') }}: {{ $run->updated }} ·
          {{ trans('crudbooster.qlik_sync_action_skipped') }}: {{ $run->skipped }} ·
          <span @if($run->failed > 0) style="color:#dc3545;font-weight:600" @endif>{{ trans('crudbooster.qlik_sync_action_failed') }}: {{ $run->failed }}</span> ·
          {{ trans('crudbooster.qlik_sync_missing') }}: {{ $run->missing }}
        </dd>
      </dl>

      @if($run->error)
      <div class="alert alert-danger" role="alert"><i class="fa fa-times-circle"></i> {{ $run->error }}</div>
      @endif

      @if($is_active)
      <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('qs-cancel').showModal()">
        <i class="fa fa-ban"></i> {{ trans('crudbooster.qlik_sync_cancel') }}
      </button>
      @endif
      @if($can_rollback)
      <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('qs-rollback').showModal()">
        <i class="fa fa-undo"></i> {{ trans('crudbooster.qlik_sync_rollback') }}
      </button>
      @endif
    </div>
  </div>

  <div class="box">
    <div class="box-header mb-3">
      <h4>{{ trans('crudbooster.qlik_sync_records_title') }}
        @if($records_total > $records->count())
        <small>({{ trans('crudbooster.qlik_sync_records_truncated', ['shown' => $records->count(), 'total' => $records_total]) }})</small>
        @endif
      </h4>
    </div>
    <div class="box-body table-responsive no-padding">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>{{ trans('crudbooster.qlik_sync_col_type') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_name') }}</th>
            <th>Qlik ID</th>
            <th>{{ trans('crudbooster.qlik_sync_col_action') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_note') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $rec)
          <tr>
            <td>{{ trans('crudbooster.qlik_sync_type_' . $rec->record_type) }}</td>
            <td>{{ $rec->record_name ?? '—' }}</td>
            <td><small>{{ $rec->external_id }}</small></td>
            <td><span style="color:{{ $qsActionColor[$rec->action] ?? '#6c757d' }};font-weight:600">{{ trans('crudbooster.qlik_sync_action_' . $rec->action) }}</span></td>
            <td><small>{{ $rec->message }}</small></td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted">{{ trans('crudbooster.qlik_sync_no_records') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if($is_active)
<dialog id="qs-cancel" style="border:0;border-radius:6px;padding:0;max-width:480px;width:calc(100% - 32px);box-shadow:0 10px 40px rgba(0,0,0,.35)">
  <form method="post" action="{{ $qsBase }}/cancel" style="margin:0">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div style="padding:16px">
      <h4 style="margin-top:0">{{ trans('crudbooster.qlik_sync_cancel_title') }}</h4>
      <p>{{ trans('crudbooster.qlik_sync_cancel_question') }}</p>
      <label style="display:block;margin-bottom:6px"><input type="radio" name="mode" value="keep" checked> {{ trans('crudbooster.qlik_sync_cancel_keep') }}</label>
      <label style="display:block"><input type="radio" name="mode" value="delete"> {{ trans('crudbooster.qlik_sync_cancel_delete') }}</label>
    </div>
    <div style="padding:10px 16px;border-top:1px solid #ddd;background:#f5f5f5;text-align:right">
      <button type="button" class="btn btn-default btn-sm" onclick="this.closest('dialog').close()">{{ trans('crudbooster.qlik_test_close') }}</button>
      <button type="submit" class="btn btn-danger btn-sm">{{ trans('crudbooster.qlik_sync_cancel_confirm') }}</button>
    </div>
  </form>
</dialog>
@endif

@if($can_rollback)
<dialog id="qs-rollback" style="border:0;border-radius:6px;padding:0;max-width:480px;width:calc(100% - 32px);box-shadow:0 10px 40px rgba(0,0,0,.35)">
  <form method="post" action="{{ $qsBase }}/rollback" style="margin:0">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div style="padding:16px">
      <h4 style="margin-top:0">{{ trans('crudbooster.qlik_sync_rollback_title') }}</h4>
      <p>{{ trans('crudbooster.qlik_sync_rollback_question') }}</p>
      <p class="text-muted" style="font-size:13px">{{ trans('crudbooster.qlik_sync_rollback_rules') }}</p>
    </div>
    <div style="padding:10px 16px;border-top:1px solid #ddd;background:#f5f5f5;text-align:right">
      <button type="button" class="btn btn-default btn-sm" onclick="this.closest('dialog').close()">{{ trans('crudbooster.qlik_test_close') }}</button>
      <button type="submit" class="btn btn-warning btn-sm">{{ trans('crudbooster.qlik_sync_rollback_confirm') }}</button>
    </div>
  </form>
</dialog>
@endif
@endsection

@push('bottom')
<script type="text/javascript">
  (function () {
    var active = {!! json_encode((bool) $is_active) !!};
    if (!active) { return; }
    // Aggiorna la pagina finche' il run e' attivo (non mentre e' aperta una finestra di dialogo).
    (function tick() {
      setTimeout(function () {
        if (document.querySelector('dialog[open]')) { tick(); return; }
        window.location.reload();
      }, 4000);
    })();
  })();
</script>
@endpush
