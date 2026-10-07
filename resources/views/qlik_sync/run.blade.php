@extends('crudbooster::admin_template')
@php
    // stato -> [chiave traduzione, variante .ch-pill-*, token colore della barra]
    $qsStatus = [
        'queued' => ['qlik_sync_status_queued', 'gray', '--ch-text-muted'],
        'running' => ['qlik_sync_status_running', 'blue', '--ch-blue'],
        'cancelling' => ['qlik_sync_status_cancelling', 'warn', '--ch-warning'],
        'cancelled' => ['qlik_sync_status_cancelled', 'gray', '--ch-text-muted'],
        'completed' => ['qlik_sync_status_completed', 'ok', '--ch-success'],
        'failed' => ['qlik_sync_status_failed', 'bad', '--ch-danger'],
    ];
    $qsActionColor = ['created' => 'var(--ch-success)', 'linked' => 'var(--ch-blue)', 'updated' => 'var(--ch-warning)', 'skipped' => 'var(--ch-text-muted)', 'failed' => 'var(--ch-danger)'];
    [$stKey, $stPill, $stVar] = $qsStatus[$run->status] ?? ['qlik_sync_status_failed', 'gray', '--ch-text-muted'];
    $pct = $run->total > 0 ? min(100, (int) round($run->processed * 100 / $run->total)) : ($run->status === 'completed' ? 100 : 0);
    $qsBase = url('admin/qlik_apps/sync-runs/' . $run->id);
@endphp
@section('content')
<div>
  <p><a href="{{ url('admin/qlik_apps/sync-runs') }}"><i class="bi bi-chevron-left"></i> &nbsp; {{ trans('crudbooster.qlik_sync_back_runs') }}</a></p>

  @if($stuck && $run->status === 'queued')
  <div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i> {{ trans('crudbooster.qlik_sync_no_worker') }}
  </div>
  @endif

  <div class="card card-default">
    <div class="card-header d-flex align-items-center gap-2">
      <strong class="fs-6">{{ trans('crudbooster.qlik_sync_type_' . $run->type) }}</strong>
      <span class="ch-pill ch-pill-dot ch-pill-{{ $stPill }}">{{ trans('crudbooster.' . $stKey) }}</span>
      @if($run->rolled_back_at)
      <span class="ch-pill ch-pill-dot ch-pill-gray">{{ trans('crudbooster.qlik_sync_rolled_back') }}</span>
      @endif
    </div>
    <div class="card-body">
      @php $qsLbl = 'col-sm-3 text-secondary text-uppercase fw-semibold small'; $qsRow = 'row py-2 border-bottom align-items-center mx-0'; @endphp
      <div class="{{ $qsRow }}">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_conf') }}</div>
        <div class="col-sm-9">{{ $run->confname ?? ('#' . $run->qlik_conf_id) }}</div>
      </div>
      <div class="{{ $qsRow }}">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_app') }}</div>
        <div class="col-sm-9">{{ $run->qlik_app_id ? ($run->appname ?? ('#' . $run->qlik_app_id)) : ($run->type === 'items' ? trans('crudbooster.qlik_sync_all_apps') : '—') }}</div>
      </div>
      <div class="{{ $qsRow }}">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_user') }}</div>
        <div class="col-sm-9">{{ $run->user_name ?? ('#' . $run->user_id) }}</div>
      </div>
      <div class="{{ $qsRow }}">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_started') }}</div>
        <div class="col-sm-9">{{ $run->started_at ?? $run->created_at }}@if($run->finished_at) → {{ $run->finished_at }}@endif</div>
      </div>
      <div class="{{ $qsRow }}">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_progress') }}</div>
        <div class="col-sm-9">
          <div class="progress" style="height:8px;max-width:360px;background:var(--ch-border)" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="background:var({{ $stVar }});width:{{ $pct }}%"></div>
          </div>
          <small class="text-secondary">{{ $run->processed }} / {{ $run->total }}</small>
        </div>
      </div>
      <div class="{{ $qsRow }} border-bottom-0">
        <div class="{{ $qsLbl }}">{{ trans('crudbooster.qlik_sync_col_results') }}</div>
        <div class="col-sm-9">
          {{ trans('crudbooster.qlik_sync_action_created') }}: {{ $run->created }} ·
          {{ trans('crudbooster.qlik_sync_action_linked') }}: {{ $run->linked }} ·
          {{ trans('crudbooster.qlik_sync_action_updated') }}: {{ $run->updated }} ·
          {{ trans('crudbooster.qlik_sync_action_skipped') }}: {{ $run->skipped }} ·
          <span @if($run->failed > 0) style="color:var(--ch-danger);font-weight:600" @endif>{{ trans('crudbooster.qlik_sync_action_failed') }}: {{ $run->failed }}</span> ·
          {{ trans('crudbooster.qlik_sync_missing') }}: {{ $run->missing }}
        </div>
      </div>

      <div class="mt-3">

      @if($run->error)
      <div class="alert alert-danger" role="alert"><i class="bi bi-x-circle-fill"></i> {{ $run->error }}</div>
      @endif

      @if($is_active)
      <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('qs-cancel').showModal()">
        <i class="bi bi-slash-circle"></i> {{ trans('crudbooster.qlik_sync_cancel') }}
      </button>
      @endif
      @if($can_rollback)
      <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('qs-rollback').showModal()">
        <i class="bi bi-arrow-counterclockwise"></i> {{ trans('crudbooster.qlik_sync_rollback') }}
      </button>
      @endif
      </div>
    </div>
  </div>

  <h4 class="mt-4 mb-3">{{ trans('crudbooster.qlik_sync_records_title') }}
    @if($records_total > $records->count())
    <small class="text-secondary fs-6">({{ trans('crudbooster.qlik_sync_records_truncated', ['shown' => $records->count(), 'total' => $records_total]) }})</small>
    @endif
  </h4>
  <div class="table-responsive rel-table">
      <table class="table table-hover align-middle mb-0">
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
            <td><small class="font-monospace text-secondary">{{ $rec->external_id }}</small></td>
            <td><span style="color:{{ $qsActionColor[$rec->action] ?? 'var(--ch-text-secondary)' }};font-weight:700">{{ trans('crudbooster.qlik_sync_action_' . $rec->action) }}</span></td>
            <td><small class="text-secondary">{{ $rec->message }}</small></td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-secondary">{{ trans('crudbooster.qlik_sync_no_records') }}</td></tr>
          @endforelse
        </tbody>
      </table>
  </div>
</div>

@if($is_active)
<dialog id="qs-cancel" style="border:0;border-radius:6px;padding:0;max-width:480px;width:calc(100% - 32px);box-shadow:0 10px 40px rgba(0,0,0,.35)">
  <form method="post" action="{{ $qsBase }}/cancel" style="margin:0">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div style="padding:16px">
      <h4 style="margin-top:0">{{ trans('crudbooster.qlik_sync_cancel_title') }}</h4>
      <p>{{ trans('crudbooster.qlik_sync_cancel_question') }}</p>
      @include('crudbooster::partials.ch_radio', [
          'name' => 'mode',
          'disabled' => false,
          'rd_mode' => 'list',
          'rd_options' => [
              ['value' => 'keep', 'label' => trans('crudbooster.qlik_sync_cancel_keep'), 'checked' => true],
              ['value' => 'delete', 'label' => trans('crudbooster.qlik_sync_cancel_delete'), 'checked' => false],
          ],
      ])
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--ch-border-strong);background:var(--ch-bg);text-align:right">
      <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('dialog').close()">{{ trans('crudbooster.qlik_test_close') }}</button>
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
    <div style="padding:10px 16px;border-top:1px solid var(--ch-border-strong);background:var(--ch-bg);text-align:right">
      <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('dialog').close()">{{ trans('crudbooster.qlik_test_close') }}</button>
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
