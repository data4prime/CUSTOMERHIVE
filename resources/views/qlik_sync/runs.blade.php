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
@endphp
@section('content')
<div>
  <p>
    <a href="{{ url('admin/qlik_apps') }}"><i class="bi bi-chevron-left"></i> &nbsp; {{ trans('crudbooster.qlik_sync_back_apps') }}</a>
    &nbsp;|&nbsp;
    <a href="{{ url('admin/qlik_items') }}">{{ trans('crudbooster.qlik_sync_back_items') }}</a>
  </p>

  @if($stuck)
  <div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i> {{ trans('crudbooster.qlik_sync_no_worker') }}
  </div>
  @endif

  <div class="table-responsive rel-table">
      <table class="table table-hover align-middle mb-0">
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
            [$stKey, $stPill, $stVar] = $qsStatus[$run->status] ?? ['qlik_sync_status_failed', 'gray', '--ch-text-muted'];
            $pct = $run->total > 0 ? min(100, (int) round($run->processed * 100 / $run->total)) : ($run->status === 'completed' ? 100 : 0);
          @endphp
          <tr>
            <td>{{ $run->id }}</td>
            <td>{{ trans('crudbooster.qlik_sync_type_' . $run->type) }}</td>
            <td>{{ $run->confname ?? ('#' . $run->qlik_conf_id) }}</td>
            <td>{{ $run->qlik_app_id ? ($run->appname ?? ('#' . $run->qlik_app_id)) : ($run->type === 'items' ? trans('crudbooster.qlik_sync_all_apps') : '—') }}</td>
            <td>{{ $run->user_name ?? ('#' . $run->user_id) }}</td>
            <td>
              <span class="ch-pill ch-pill-dot ch-pill-{{ $stPill }}">{{ trans('crudbooster.' . $stKey) }}</span>
              @if($run->rolled_back_at)
              <span class="ch-pill ch-pill-dot ch-pill-gray">{{ trans('crudbooster.qlik_sync_rolled_back') }}</span>
              @endif
            </td>
            <td style="min-width:120px">
              <div class="progress" style="height:6px;background:var(--ch-border)" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="background:var({{ $stVar }});width:{{ $pct }}%"></div>
              </div>
              <small class="text-secondary">{{ $run->processed }} / {{ $run->total }}</small>
            </td>
            <td>
              <small class="text-secondary">
                {{ trans('crudbooster.qlik_sync_action_created') }}: {{ $run->created }} ·
                {{ trans('crudbooster.qlik_sync_action_linked') }}: {{ $run->linked }} ·
                {{ trans('crudbooster.qlik_sync_action_updated') }}: {{ $run->updated }} ·
                {{ trans('crudbooster.qlik_sync_action_skipped') }}: {{ $run->skipped }}
                @if($run->failed > 0) · <strong style="color:var(--ch-danger)">{{ trans('crudbooster.qlik_sync_action_failed') }}: {{ $run->failed }}</strong> @endif
                @if($run->missing > 0) · {{ trans('crudbooster.qlik_sync_missing') }}: {{ $run->missing }} @endif
              </small>
            </td>
            <td>{{ $run->started_at ?? $run->created_at }}</td>
            <td>
              <a class="btn btn-sm btn-secondary" title="{{ trans('crudbooster.qlik_sync_details') }}" href="{{ url('admin/qlik_apps/sync-runs/' . $run->id) }}"><i class="bi bi-search"></i></a>
            </td>
          </tr>
          @empty
          <tr><td colspan="10" class="text-center text-secondary">{{ trans('crudbooster.qlik_sync_no_runs') }}</td></tr>
          @endforelse
        </tbody>
      </table>
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
