{{-- Anteprima dell'applicazione (impostazioni "Application Setting"), come nel mockup:
     scheda del browser (favicon + nome), finestra con sidebar (logo) e contenuto, formato
     carta e modalita' debug API. Solo grafica: legge i campi del form e non salva nulla.
     Parametri: $settings (collezione cms_settings del gruppo). Sostituisce l'anteprima
     dell'intervento 237 (stessa logica per nome, logo e favicon). --}}
@php
    $ap_val = function ($name) use ($settings) {
        return optional($settings->firstWhere('name', $name))->content;
    };
    $ap_name = $ap_val('appname');
    $ap_logo = $ap_val('logo');
    $ap_fav = $ap_val('favicon');
    $ap_logo_url = $ap_logo ? asset($ap_logo) : asset('/images/customerhive_trasparente.png');
    $ap_fav_url = $ap_fav ? asset($ap_fav) : asset('images/favicon.png');
    $ap_paper = (string) $ap_val('default_paper_size');
    $ap_debug = strtolower((string) $ap_val('api_debug_mode')) === 'true';
@endphp
<div class="ch-aprev" id="ch-aprev">
    <div class="ch-aprev-t">{{ trans('crudbooster.setting_app_preview_title') }}</div>
    <div class="ch-aprev-tab">
        <img id="pv-fav" src="{{ $ap_fav_url }}" alt="">
        <span class="ch-aprev-tt"><span id="pv-name">{{ $ap_name }}</span>: {{ trans('crudbooster.setting_app_preview_tab') }}</span>
    </div>
    <div class="ch-aprev-win">
        <div class="ch-aprev-sb">
            <div class="ch-aprev-logo"><img id="pv-logo" src="{{ $ap_logo_url }}" alt=""></div>
            <span class="ch-aprev-line"></span><span class="ch-aprev-line s"></span><span class="ch-aprev-line"></span><span class="ch-aprev-line s"></span>
        </div>
        <div class="ch-aprev-main"><span class="ch-aprev-bar w"></span><span class="ch-aprev-bar"></span><span class="ch-aprev-bar s"></span></div>
    </div>
    <div class="ch-aprev-meta">
        <div>
            <div class="ch-aprev-k">{{ trans('crudbooster.setting_app_preview_paper') }}</div>
            <div class="ch-aprev-sheet" id="pv-paper">{{ $ap_paper }}</div>
        </div>
        <div>
            <div class="ch-aprev-k">{{ trans('crudbooster.setting_app_preview_debug') }}</div>
            <span class="ch-pill ch-pill-dot {{ $ap_debug ? 'ch-pill-warn' : 'ch-pill-gray' }}" id="pv-debug">{{ $ap_debug ? trans('crudbooster.setting_app_preview_on') : trans('crudbooster.setting_app_preview_off') }}</span>
        </div>
    </div>
    <div class="ch-aprev-cap">{{ trans('crudbooster.setting_app_preview_help') }}</div>
</div>
<script>
(function () {
  var RATIO = { Legal: 1.647, Letter: 1.294, Ledger: 1.545 };
  var T_ON = {!! json_encode(trans('crudbooster.setting_app_preview_on')) !!}, T_OFF = {!! json_encode(trans('crudbooster.setting_app_preview_off')) !!};
  function q(n) { return document.querySelector('[name="' + n + '"]'); }
  function paper() {
    var p = q('default_paper_size'), el = document.getElementById('pv-paper');
    if (!p || !el) { return; }
    el.textContent = p.value;
    el.style.height = Math.round(48 * (RATIO[p.value] || 1.414)) + 'px';
  }
  function debug() {
    var d = q('api_debug_mode'), el = document.getElementById('pv-debug');
    if (!d || !el) { return; }
    var on = d.value === 'true';
    el.textContent = on ? T_ON : T_OFF;
    el.classList.toggle('ch-pill-warn', on);
    el.classList.toggle('ch-pill-gray', !on);
  }
  function bindFile(field, imgId) {
    var f = q(field);
    if (!f || f.type !== 'file') { return; }
    f.addEventListener('change', function () {
      var file = f.files && f.files[0];
      if (!file) { return; }
      var r = new FileReader();
      r.onload = function () { document.getElementById(imgId).src = r.result; };
      r.readAsDataURL(file);
    });
  }
  var name = q('appname');
  if (name) { name.addEventListener('input', function () { document.getElementById('pv-name').textContent = name.value; }); }
  bindFile('logo', 'pv-logo');
  bindFile('favicon', 'pv-fav');
  document.addEventListener('change', function (e) {
    if (e.target && e.target.name === 'default_paper_size') { paper(); }
    if (e.target && e.target.name === 'api_debug_mode') { debug(); }
  });
  paper();
})();
</script>
