{{-- Anteprima della pagina di login (impostazioni "Login Register Style"), come nel mockup:
     solo grafica, legge i campi colore / immagine del form e non salva nulla.
     Parametri: $settings (collezione cms_settings del gruppo). --}}
@php
    $lp_val = function ($name, $default = '') use ($settings) {
        $v = optional($settings->firstWhere('name', $name))->content;

        return ($v !== null && $v !== '') ? $v : $default;
    };
    $lp_image = $lp_val('login_background_image');
    $lp_image_url = $lp_image ? asset($lp_image) : '';
@endphp
<div class="ch-lprev" id="ch-lprev">
    <div class="ch-lprev-t">{{ trans('crudbooster.adm_login_preview') }}</div>
    <div class="ch-lprev-box" id="lp-box" style="background-color: {{ $lp_val('login_background_color', '#ffffff') }};{{ $lp_image_url ? ' background-image:url(' . e($lp_image_url) . ');' : '' }}">
        <div class="ch-lprev-card" id="lp-card" style="color: {{ $lp_val('login_font_color', '#16161d') }}">
            <b>{{ CRUDBooster::getSetting('appname') ?: 'CustomerHive' }}</b>
            <small>{{ trans('crudbooster.setting_login_preview_sub') }}</small>
            <span class="ch-lprev-bar"></span>
            <span class="ch-lprev-bar"></span>
            <span class="ch-lprev-btn" id="lp-btn" style="background: {{ $lp_val('button_color', '#4f46e5') }}">{{ trans('crudbooster.setting_login_preview_btn') }}</span>
        </div>
    </div>
    <div class="ch-lprev-cap">{{ trans('crudbooster.setting_login_preview_help') }}</div>
</div>
<script>
(function () {
  function field(n) { return document.querySelector('[name="' + n + '"]'); }
  function ok(v) { return /^#[0-9a-fA-F]{3,8}$/.test(v) ? v : ''; }
  function sync() {
    var bg = field('login_background_color'), fg = field('login_font_color'), bt = field('button_color');
    if (bg && ok(bg.value)) { document.getElementById('lp-box').style.backgroundColor = bg.value; }
    if (fg && ok(fg.value)) { document.getElementById('lp-card').style.color = fg.value; }
    if (bt && ok(bt.value)) { document.getElementById('lp-btn').style.background = bt.value; }
  }
  document.addEventListener('input', function (e) { if (e.target && e.target.name) { sync(); } });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t && t.type === 'file' && t.name === 'login_background_image' && t.files && t.files[0]) {
      var r = new FileReader();
      r.onload = function (ev) { document.getElementById('lp-box').style.backgroundImage = 'url(' + ev.target.result + ')'; };
      r.readAsDataURL(t.files[0]);
    } else { sync(); }
  });
})();
</script>
