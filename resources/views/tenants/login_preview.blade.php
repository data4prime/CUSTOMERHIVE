@php
    $__ini = '';
    foreach (array_slice(preg_split('/\s+/u', trim((string) @$row->name), -1, PREG_SPLIT_NO_EMPTY), 0, 2) as $__w) { $__ini .= mb_strtoupper(mb_substr($__w, 0, 1)); }
    $__ini = $__ini ?: '?';
@endphp
{{-- Anteprima della pagina di login del tenant (intervento 232): solo grafica, legge i
     valori dei campi colore / logo / nome e non salva nulla. Renderizzata da cbInit() come
     campo "custom": niente @push (a fine render verrebbe svuotato), script inline senza jQuery.
     Con logo mancante mostra un quadrato con le iniziali del tenant. --}}
<div id="login-preview" style="border-radius:var(--ch-radius-md);padding:24px;min-height:240px;display:grid;place-items:center;text-align:center;background-color:{{ @$row->login_background_color ?: '#ffffff' }};color:{{ @$row->login_font_color ?: '#16161d' }}">
  <div style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);border-radius:12px;padding:16px 18px;width:100%;max-width:240px">
    <img id="login-preview-logo" alt="" style="max-width:100%;max-height:48px;margin-bottom:8px;{{ @$row->logo ? '' : 'display:none' }}" src="{{ @$row->logo ? asset($row->logo) : '' }}">
    <div id="login-preview-initials" style="width:48px;height:48px;margin:0 auto 8px;border-radius:10px;background:rgba(255,255,255,.25);font-weight:700;display:{{ @$row->logo ? 'none' : 'grid' }};place-items:center">{{ $__ini }}</div>
    <div><b id="login-preview-name">{{ @$row->name }}</b></div>
    <div style="height:28px;border-radius:7px;background:rgba(255,255,255,.3);margin-top:8px"></div>
    <div style="height:28px;border-radius:7px;background:rgba(255,255,255,.3);margin-top:8px"></div>
  </div>
</div>
<p class="help-block">{{ trans('crudbooster.adm_login_preview_help') }}</p>
<script>
(function () {
  function q(s) { return document.querySelector(s); }
  function sync() {
    var box = q('#login-preview');
    if (!box || !q('input[name=name]')) { return; }
    var bg = q('#login_background_color'), fg = q('#login_font_color'), nm = q('input[name=name]');
    box.style.backgroundColor = (bg && bg.value) || '#ffffff';
    box.style.color = (fg && fg.value) || '#16161d';
    var name = ((nm && nm.value) || '').trim();
    q('#login-preview-name').textContent = name;
    var ini = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
    q('#login-preview-initials').textContent = ini || '?';
  }
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t && (t.id === 'login_background_color' || t.id === 'login_font_color' || t.name === 'name' || t.hasAttribute('data-ch-hex'))) { sync(); }
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t && t.type === 'file' && t.name === 'logo' && t.files && t.files[0]) {
      var r = new FileReader();
      r.onload = function (ev) {
        var img = q('#login-preview-logo');
        img.src = ev.target.result;
        img.style.display = '';
        q('#login-preview-initials').style.display = 'none';
      };
      r.readAsDataURL(t.files[0]);
    } else if (t && (t.id === 'login_background_color' || t.id === 'login_font_color')) { sync(); }
  });
  sync();
})();
</script>
