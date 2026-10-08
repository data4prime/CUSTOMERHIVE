{{--
    Scheda Qlik della modifica utente: stesse card del profilo (users/_qlik_row),
    ma il salvataggio resta quello del campo 'child' (pulsante Salva del form +
    AdminCmsUsersController::prepare_qlik_users). Ogni riga posta sempre tutti e
    quattro i campi, nello stesso ordine: CBController li legge per indice.
    Incluso da type_components/child/component.blade.php (chiave 'view').
--}}
@php
    $qlikConfs = DB::table('qlik_confs')->orderBy('confname')->get(['id', 'confname', 'type']);
    $qlikUsers = DB::table('qlik_users')
        ->where('user_id', isset($id) ? $id : 0)
        ->orderBy('id')
        ->get(['qlik_conf_id', 'qlik_login', 'user_directory', 'idp_qlik']);
@endphp
<style>
  .ch-qlik-edit { width: 100%; }
  .ch-qlik-edit .ch-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .ch-qlik-edit label { display: block; font-size: 12px; font-weight: 600; color: var(--ch-text-secondary); margin-bottom: 5px; }
  .ch-qlik-edit .ch-hint { font-size: 12px; color: var(--ch-text-muted); margin-top: 4px; }
  .ch-qlik-edit .ch-qlik-row { border: 1px solid var(--ch-border); border-radius: 10px; padding: 16px; background: var(--ch-bg); margin-bottom: 12px; }
  .ch-qlik-edit .ch-qlik-row .ch-qlik-remove { margin-top: 12px; }
  @media (max-width: 800px) { .ch-qlik-edit .ch-grid { grid-template-columns: 1fr; } }
</style>
<div class="ch-qlik-edit" id="ch-qlik-edit">
  <p class="ch-hint" style="margin:0 0 14px;">{{ trans('crudbooster.profile_section_qlik_sub') }}</p>
  <div id="ch-qlik-rows">
    @foreach($qlikUsers as $qu)
      @include('users._qlik_row', ['qu' => $qu, 'qlikConfs' => $qlikConfs, 'canManage' => true, 'inputPrefix' => 'qlikusers-'])
    @endforeach
  </div>
  <p class="ch-hint" id="ch-qlik-none" @if($qlikUsers->isNotEmpty()) hidden @endif>{{ trans('crudbooster.profile_qlik_none') }}</p>
  <div style="margin-top:12px;">
    <button type="button" class="btn btn-secondary" id="ch-qlik-add">{{ trans('crudbooster.profile_qlik_add') }}</button>
  </div>
  <template id="ch-qlik-template">
    @include('users._qlik_row', ['qu' => null, 'qlikConfs' => $qlikConfs, 'canManage' => true, 'inputPrefix' => 'qlikusers-'])
  </template>
</div>

@push('bottom')
<script type="text/javascript">
(function () {
  var wrap = document.getElementById('ch-qlik-edit');
  if (!wrap) { return; }
  var rows = document.getElementById('ch-qlik-rows');
  var none = document.getElementById('ch-qlik-none');
  var tpl = document.getElementById('ch-qlik-template');
  var toggleNone = function () { none.hidden = rows.children.length > 0; };
  // SaaS -> solo IDP Subject; On-Premise (ogni altro tipo) -> login + user
  // directory; senza configurazione scelta nessuno dei tre (come nel profilo).
  // I campi nascosti restano nel DOM e vengono postati comunque (indici allineati).
  var syncRow = function (row) {
    var sel = row.querySelector('[data-f=qlik_conf_id]');
    var opt = sel && sel.selectedOptions[0];
    var type = (opt && opt.value) ? (opt.dataset.type === 'SAAS' ? 'saas' : 'onprem') : '';
    var show = { qlik_login: type === 'onprem', user_directory: type === 'onprem', idp_qlik: type === 'saas' };
    Object.keys(show).forEach(function (f) {
      var el = row.querySelector('[data-f=' + f + ']');
      if (el) { el.parentElement.hidden = !show[f]; }
    });
  };
  rows.addEventListener('change', function (e) {
    if (e.target.dataset.f === 'qlik_conf_id') { syncRow(e.target.closest('.ch-qlik-row')); }
  });
  rows.querySelectorAll('.ch-qlik-row').forEach(syncRow);
  rows.addEventListener('click', function (e) {
    if (e.target.classList.contains('ch-qlik-remove')) {
      e.target.closest('.ch-qlik-row').remove();
      toggleNone();
    }
  });
  document.getElementById('ch-qlik-add').addEventListener('click', function () {
    rows.appendChild(tpl.content.cloneNode(true));
    syncRow(rows.lastElementChild);
    toggleNone();
  });
  // Una riga senza configurazione non va salvata: si svuotano i suoi campi (non
  // si tolgono dal form, altrimenti si sfalsano gli indici) e CBController la scarta.
  var form = wrap.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      rows.querySelectorAll('.ch-qlik-row').forEach(function (row) {
        var conf = row.querySelector('[data-f=qlik_conf_id]');
        if (conf && !conf.value) {
          row.querySelectorAll('[data-f]').forEach(function (el) { el.value = ''; });
        }
      });
    });
  }
})();
</script>
@endpush
