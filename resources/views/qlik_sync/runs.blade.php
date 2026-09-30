@extends('crudbooster::admin_template')
@php
    // stato -> [chiave traduzione, colore]
    $qsStatus = [
        'queued' => ['qlik_sync_status_queued', '#6c757d'],
        'running' => ['qlik_sync_status_running', '#0d6efd'],
        'cancelling' => ['qlik_sync_status_cancelling', '#b58105'],
        'cancelled' => ['qlik_sync_status_cancelled', '#6c757d'],
        'completed' => ['qlik_sync_status_completed', '#198754'],
        'failed' => ['qlik_sync_status_failed', '#dc3545'],
    ];
@endphp
@section('content')
<div>
  <p>
    <a href="{{ url('admin/qlik_apps') }}"><i class="fa fa-chevron-circle-left"></i> &nbsp; {{ trans('crudbooster.qlik_sync_back_apps') }}</a>
    &nbsp;|&nbsp;
    <a href="{{ url('admin/qlik_items') }}">{{ trans('crudbooster.qlik_sync_back_items') }}</a>
  </p>

  @if($stuck)
  <div class="alert alert-warning" role="alert">
    <i class="fa fa-exclamation-triangle"></i> {{ trans('crudbooster.qlik_sync_no_worker') }}
  </div>
  @endif

  <div class="box">
    <div class="box-body table-responsive no-padding">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ trans('crudbooster.qlik_sync_col_type') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_conf') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_app') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_user') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_state') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_progress') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_results') }}</th>
            <th>{{ trans('crudbooster.qlik_sync_col_started') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($runs as $run)
          @php
            [$stKey, $stColor] = $qsStatus[$run->status] ?? ['qlik_sync_status_failed', '#6c757d'];
            $pct = $run->total > 0 ? min(100, (int) round($run->processed * 100 / $run->total)) : ($run->status === 'completed' ? 100 : 0);
          @endphp
          <tr>
            <td>{{ $run->id }}</td>
            <td>{{ trans('crudbooster.qlik_sync_type_' . $run->type) }}</td>
            <td>{{ $run->confname ?? ('#' . $run->qlik_conf_id) }}</td>
            <td>{{ $run->qlik_app_id ? ($run->appname ?? ('#' . $run->qlik_app_id)) : ($run->type === 'items' ? trans('crudbooster.qlik_sync_all_apps') : '—') }}</td>
            <td>{{ $run->user_name ?? ('#' . $run->user_id) }}</td>
            <td>
              <span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:{{ $stColor }}">{{ trans('crudbooster.' . $stKey) }}</span>
              @if($run->rolled_back_at)
              <span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:#6c757d">{{ trans('crudbooster.qlik_sync_rolled_back') }}</span>
              @endif
            </td>
            <td style="min-width:120px">
              <div style="background:#e9ecef;border-radius:3px;height:10px;overflow:hidden" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div style="background:{{ $stColor }};height:10px;width:{{ $pct }}%"></div>
              </div>
              <small>{{ $run->processed }} / {{ $run->total }}</small>
            </td>
            <td>
              <small>
                {{ trans('crudbooster.qlik_sync_action_created') }}: {{ $run->created }} ·
                {{ trans('crudbooster.qlik_sync_action_linked') }}: {{ $run->linked }} ·
                {{ trans('crudbooster.qlik_sync_action_updated') }}: {{ $run->updated }} ·
                {{ trans('crudbooster.qlik_sync_action_skipped') }}: {{ $run->skipped }}
                @if($run->failed > 0) · <strong style="color:#dc3545">{{ trans('crudbooster.qlik_sync_action_failed') }}: {{ $run->failed }}</strong> @endif
                @if($run->missing > 0) · {{ trans('crudbooster.qlik_sync_missing') }}: {{ $run->missing }} @endif
              </small>
            </td>
            <td>{{ $run->started_at ?? $run->created_at }}</td>
            <td>
              <a class="btn btn-info btn-sm" title="{{ trans('crudbooster.qlik_sync_details') }}" href="{{ url('admin/qlik_apps/sync-runs/' . $run->id) }}"><i class="fa fa-search"></i></a>
            </td>
          </tr>
          @empty
          <tr><td colspan="10" class="text-center text-muted">{{ trans('crudbooster.qlik_sync_no_runs') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('bottom')
<script type="text/javascript">
  (function () {
    var active = {!! json_encode((bool) $has_active) !!};
    if (!active) { return; }
    // Aggiorna la pagina finche' c'e' un run attivo (non mentre e' aperta una finestra di dialogo).
    (function tick() {
      setTimeout(function () {
        if (document.querySelector('dialog[open]')) { tick(); return; }
        window.location.reload();
      }, 4000);
    })();
  })();
</script>
@endpush
