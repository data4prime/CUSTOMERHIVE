{{-- Selettore di colore (intervento 237): pallini della palette + colore libero + codice esadecimale.
     Parametri: $name (input inviato, valore "#rrggbb" o vuoto), $value.
     Un solo input di testo porta il valore; i pallini e il selettore nativo lo aggiornano
     e viceversa. Stile: ch-components.css (.ch-colorpick); logica inline qui sotto, una volta sola. --}}
@php
    $cp_value = (string) ($value ?? '');
    $cp_palette = ['#5b4cf0', '#0d6efd', '#0f9d6a', '#f59e0b', '#d9344a', '#12142a'];
    $cp_valid = (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $cp_value);
@endphp
<div class="ch-colorpick" data-ch-colorpick>
    <div class="ch-cp-dots">
        @foreach($cp_palette as $c)
        <button type="button" class="ch-cp-dot {{ strtolower($cp_value) === $c ? 'is-on' : '' }}" style="background: {{ $c }}" data-c="{{ $c }}" title="{{ $c }}"></button>
        @endforeach
        <label class="ch-cp-free" title="{{ trans('crudbooster.color_custom') }}">
            <input type="color" value="{{ $cp_valid ? $cp_value : '#5b4cf0' }}" tabindex="-1">
        </label>
    </div>
    <input type="text" class="form-control ch-cp-hex" name="{{ $name }}" value="{{ $cp_value }}" maxlength="7" placeholder="#rrggbb" autocomplete="off">
    <span class="ch-cp-sw" style="background: {{ $cp_valid ? $cp_value : 'transparent' }}"></span>
</div>

@once
<script>
(function () {
  function valid(v) { return /^#[0-9a-fA-F]{6}$/.test(v); }
  function sync(root, v, from) {
    var hex = root.querySelector('.ch-cp-hex');
    var native = root.querySelector('input[type=color]');
    var sw = root.querySelector('.ch-cp-sw');
    if (from !== 'hex') { hex.value = v; }
    if (valid(v)) { native.value = v; sw.style.background = v; } else { sw.style.background = 'transparent'; }
    var dots = root.querySelectorAll('.ch-cp-dot');
    for (var i = 0; i < dots.length; i++) {
      dots[i].classList.toggle('is-on', v.toLowerCase() === dots[i].getAttribute('data-c'));
    }
    hex.dispatchEvent(new Event('change', { bubbles: true }));
  }
  document.addEventListener('click', function (e) {
    var d = e.target.closest ? e.target.closest('.ch-cp-dot') : null;
    if (d) { sync(d.closest('[data-ch-colorpick]'), d.getAttribute('data-c')); }
  });
  document.addEventListener('input', function (e) {
    var t = e.target, root = t.closest ? t.closest('[data-ch-colorpick]') : null;
    if (!root) { return; }
    if (t.type === 'color') { sync(root, t.value); }
    else if (t.classList.contains('ch-cp-hex')) { sync(root, t.value.trim(), 'hex'); }
  });
})();
</script>
@endonce
