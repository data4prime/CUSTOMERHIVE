{{--
    Scheda Qlik della pagina di dettaglio utente: stesse card del profilo e della
    modifica (users/_qlik_row), in sola lettura. Incluso da
    type_components/child/component_detail.blade.php (chiave 'view_detail').
--}}
@php
    $qlikConfs = DB::table('qlik_confs')->orderBy('confname')->get(['id', 'confname', 'type']);
    $qlikUsers = DB::table('qlik_users')
        ->where('user_id', isset($id) ? $id : 0)
        ->orderBy('id')
        ->get(['qlik_conf_id', 'qlik_login', 'user_directory', 'idp_qlik']);
@endphp
<style>
  .ch-qlik-detail .ch-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .ch-qlik-detail label { display: block; font-size: 12px; font-weight: 600; color: var(--ch-text-secondary); margin-bottom: 5px; }
  .ch-qlik-detail .ch-hint { font-size: 12px; color: var(--ch-text-muted); margin-top: 4px; }
  .ch-qlik-detail .ch-qlik-row { border: 1px solid var(--ch-border); border-radius: 10px; padding: 16px; background: var(--ch-bg); margin-bottom: 12px; }
  @media (max-width: 800px) { .ch-qlik-detail .ch-grid { grid-template-columns: 1fr; } }
</style>
<div class="ch-qlik-detail">
  @forelse($qlikUsers as $qu)
    @include('users._qlik_row', ['qu' => $qu, 'qlikConfs' => $qlikConfs, 'canManage' => false])
  @empty
    <p class="ch-hint">{{ trans('crudbooster.profile_qlik_none') }}</p>
  @endforelse
</div>
<script type="text/javascript">
(function () {
  // Come nel profilo: SaaS -> solo IDP; On-Premise -> login + user directory.
  document.querySelectorAll('.ch-qlik-detail .ch-qlik-row').forEach(function (row) {
    var sel = row.querySelector('[data-f=qlik_conf_id]');
    var opt = sel && sel.selectedOptions[0];
    var type = (opt && opt.value) ? (opt.dataset.type === 'SAAS' ? 'saas' : 'onprem') : '';
    var show = { qlik_login: type === 'onprem', user_directory: type === 'onprem', idp_qlik: type === 'saas' };
    Object.keys(show).forEach(function (f) {
      var el = row.querySelector('[data-f=' + f + ']');
      if (el) { el.parentElement.hidden = !show[f]; }
    });
  });
})();
</script>
