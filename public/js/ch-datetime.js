/*
 * ch-datetime.js - selettore standard di data, data e ora, ora.
 *
 * Si attiva su ogni <input data-ch-picker="date|datetime|time"> (markup:
 * partials/ch_input con kind date/datetime/time). Sostituisce i tre plugin
 * (bootstrap-datepicker, daterangepicker, bootstrap-timepicker) per i type
 * component date/datetime/time: stesso formato salvato di prima:
 *   date      YYYY-MM-DD
 *   datetime  YYYY-MM-DD HH:mm:ss
 *   time      HH:mm:ss (HH:mm se il valore esistente non ha i secondi)
 *
 * Il campo resta di sola lettura (si sceglie dal selettore); alla scelta si
 * lanciano gli eventi "input" e "change". Un unico popup condiviso, appeso al body
 * (o alla modale/dialog che contiene il campo) con position:fixed, quindi non viene
 * tagliato dai contenitori con overflow. Lingua: window.CH_LOCALE, testi: window.CH_I18N.
 */
(function ($) {
  'use strict';
  if (typeof $ === 'undefined') { return; }

  var LOCALE = window.CH_LOCALE || document.documentElement.lang || 'en';
  var I18N = $.extend({ today: 'Today', clear: 'Clear', time: 'Time' }, window.CH_I18N || {});
  var FIRST_DOW = /^en/i.test(LOCALE) ? 0 : 1; // 0 = domenica, 1 = lunedi'

  var WRAP = '.ch-input, .input-group';

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

  var monthNames = [], dowNames = [];
  try {
    var fm = new Intl.DateTimeFormat(LOCALE, { month: 'long' });
    for (var m = 0; m < 12; m++) { monthNames.push(cap(fm.format(new Date(2021, m, 1)))); }
    var fd = new Intl.DateTimeFormat(LOCALE, { weekday: 'short' });
    for (var d = 0; d < 7; d++) { dowNames.push(cap(fd.format(new Date(2021, 7, 1 + d))).slice(0, 2)); } // 1 ago 2021 = domenica
  } catch (e) {
    monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    dowNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
  }

  var CHEV_L = '<i class="bi bi-chevron-left"></i>', CHEV_R = '<i class="bi bi-chevron-right"></i>';

  // ---- valore <-> oggetto {y,m(0-11),d,H,M,S} -----------------------------
  function parse(kind, str) {
    str = (str || '').trim();
    var r;
    if (kind === 'time') {
      r = /^(\d{1,2}):(\d{2})(?::(\d{2}))?/.exec(str);
      return r ? { H: +r[1], M: +r[2], S: +(r[3] || 0), hasSec: r[3] !== undefined } : null;
    }
    r = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?/.exec(str);
    return r ? { y: +r[1], m: +r[2] - 1, d: +r[3], H: +(r[4] || 0), M: +(r[5] || 0), S: +(r[6] || 0) } : null;
  }

  function format(kind, o, withSec) {
    if (kind === 'time') { return pad(o.H) + ':' + pad(o.M) + (withSec ? ':' + pad(o.S || 0) : ''); }
    var day = o.y + '-' + pad(o.m + 1) + '-' + pad(o.d);
    return kind === 'date' ? day : day + ' ' + pad(o.H) + ':' + pad(o.M) + ':' + pad(o.S || 0);
  }

  // ---- stato del popup condiviso ------------------------------------------
  var $pop = null, cur = null; // cur: {$input, kind, view:{y,m}, value, withSec}

  function now() { var t = new Date(); return { y: t.getFullYear(), m: t.getMonth(), d: t.getDate(), H: t.getHours(), M: t.getMinutes(), S: 0 }; }

  function fire(input) {
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function applyValue($i, kind, o, withSec) {
    $i.val(o ? format(kind, o, withSec) : '');
    $i.closest(WRAP).toggleClass('has-value', !!o);
    fire($i[0]);
  }

  function setValue(o) {
    cur.value = o;
    applyValue(cur.$input, cur.kind, o, cur.withSec);
  }

  function isLocked($i) { return $i.prop('disabled') || $i.is('[data-ch-locked]'); }

  function close() {
    if ($pop) { $pop.remove(); $pop = null; }
    if (cur) { cur.$input.closest(WRAP).removeClass('ch-open'); }
    cur = null;
  }

  function position() {
    if (!$pop || !cur) { return; }
    var r = cur.$input.closest(WRAP)[0].getBoundingClientRect();
    var w = $pop.outerWidth(), h = $pop.outerHeight();
    var left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
    var top = r.bottom + 6;
    if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) { top = r.top - h - 6; }
    $pop.css({ left: left, top: top });
  }

  function dayButtons() {
    var v = cur.view, sel = cur.value, t = now();
    var first = new Date(v.y, v.m, 1), off = (first.getDay() - FIRST_DOW + 7) % 7;
    var html = '';
    for (var i = 0; i < 7; i++) { html += '<div class="ch-dw">' + dowNames[(FIRST_DOW + i) % 7] + '</div>'; }
    for (var c = 0; c < 42; c++) {
      var dt = new Date(v.y, v.m, 1 - off + c), cls = '';
      if (dt.getMonth() !== v.m) { cls += ' oth'; }
      if (dt.getFullYear() === t.y && dt.getMonth() === t.m && dt.getDate() === t.d) { cls += ' today'; }
      if (sel && dt.getFullYear() === sel.y && dt.getMonth() === sel.m && dt.getDate() === sel.d) { cls += ' sel'; }
      html += '<button type="button" class="' + cls.trim() + '" data-day="' + dt.getFullYear() + '-' + dt.getMonth() + '-' + dt.getDate() + '">' + dt.getDate() + '</button>';
    }
    return html;
  }

  function render() {
    if (!cur) { return; }
    var html = '';
    var sel = cur.value || { H: 0, M: 0, S: 0 };
    if (cur.kind === 'time') {
      html += '<div class="ch-pk-cols"><div class="ch-pk-col" data-col="H">';
      for (var h = 0; h < 24; h++) { html += '<button type="button" class="' + (cur.value && sel.H === h ? 'sel' : '') + '" data-h="' + h + '">' + pad(h) + '</button>'; }
      html += '</div><div class="ch-pk-col" data-col="M">';
      for (var mi = 0; mi < 60; mi++) { html += '<button type="button" class="' + (cur.value && sel.M === mi ? 'sel' : '') + '" data-m="' + mi + '">' + pad(mi) + '</button>'; }
      html += '</div></div>';
    } else {
      html += '<div class="ch-pk-hd"><button type="button" class="ch-pk-nav" data-nav="-1" aria-label="-">' + CHEV_L + '</button>'
        + '<b>' + monthNames[cur.view.m] + ' ' + cur.view.y + '</b>'
        + '<button type="button" class="ch-pk-nav" data-nav="1" aria-label="+">' + CHEV_R + '</button></div>'
        + '<div class="ch-cal">' + dayButtons() + '</div>';
      if (cur.kind === 'datetime') {
        var hs = '', ms = '';
        for (var a = 0; a < 24; a++) { hs += '<option' + (a === sel.H ? ' selected' : '') + '>' + pad(a) + '</option>'; }
        for (var b = 0; b < 60; b++) { ms += '<option' + (b === sel.M ? ' selected' : '') + '>' + pad(b) + '</option>'; }
        html += '<div class="ch-pk-time"><label>' + I18N.time + '</label><select data-ch-native data-th>' + hs + '</select>:<select data-ch-native data-tm>' + ms + '</select></div>';
      }
      html += '<div class="ch-pk-ft"><button type="button" data-today>' + I18N.today + '</button><button type="button" data-clear>' + I18N.clear + '</button></div>';
    }
    $pop.html(html);
    if (cur.kind === 'time') {
      $pop.find('.ch-pk-col .sel').each(function () { this.parentNode.scrollTop = this.offsetTop - 60; });
    }
    position();
  }

  function open($input) {
    if (isLocked($input)) { return; }
    close();
    var kind = $input.attr('data-ch-picker');
    var value = parse(kind, $input.val());
    var base = value || now();
    cur = {
      $input: $input, kind: kind, value: value,
      view: { y: base.y, m: base.m },
      withSec: kind === 'time' ? (value ? value.hasSec : true) : true
    };
    var $parent = $input.closest('.modal, dialog');
    $pop = $('<div class="ch-picker' + (kind === 'time' ? ' ch-picker-small' : '') + '" role="dialog"></div>')
      .appendTo($parent.length ? $parent : document.body);
    $input.closest(WRAP).addClass('ch-open');
    render();
  }

  function pickDay(y, m, d) {
    var cv = cur.value || { H: 0, M: 0, S: 0 };
    setValue({ y: y, m: m, d: d, H: cv.H, M: cv.M, S: cv.S });
    if (cur.kind === 'date') { close(); } else { cur.view = { y: y, m: m }; render(); }
  }

  // ---- eventi -------------------------------------------------------------
  $(document).on('click', function (e) {
    var $t = $(e.target);
    var $trigger = $t.closest(WRAP).has('[data-ch-picker]').length && !$t.closest('[data-ch-clear]').length
      ? $t.closest(WRAP).find('[data-ch-picker]').first() : $();
    if ($t.closest('[data-ch-clear]').length) {
      var $c = $t.closest(WRAP).find('[data-ch-picker]');
      if (!isLocked($c)) {
        applyValue($c, $c.attr('data-ch-picker'), null, true);
        if (cur && cur.$input[0] === $c[0]) { cur.value = null; render(); }
      }
      return;
    }
    if ($t.closest('.ch-picker').length) {
      var b = $t.closest('button')[0];
      if (!b || !cur) { return; }
      var ds = b.dataset;
      if (ds.nav) {
        cur.view.m += +ds.nav;
        if (cur.view.m < 0) { cur.view.m = 11; cur.view.y--; }
        if (cur.view.m > 11) { cur.view.m = 0; cur.view.y++; }
        render();
      } else if (ds.day) {
        var p = ds.day.split('-').map(Number); pickDay(p[0], p[1], p[2]);
      } else if (ds.today !== undefined) {
        var n = now(); pickDay(n.y, n.m, n.d);
      } else if (ds.clear !== undefined) {
        setValue(null); close();
      } else if (ds.h !== undefined || ds.m !== undefined) {
        var v = cur.value || { H: 0, M: 0, S: 0 };
        if (ds.h !== undefined) { v.H = +ds.h; } else { v.M = +ds.m; }
        setValue(v); render();
      }
      return;
    }
    if ($trigger.length) {
      if (cur && cur.$input[0] === $trigger[0]) { close(); } else { open($trigger); }
      return;
    }
    close();
  });

  $(document).on('change', '.ch-picker select', function () {
    if (!cur) { return; }
    var v = cur.value || now();
    if ($(this).is('[data-th]')) { v.H = +this.value; } else { v.M = +this.value; }
    setValue(v);
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && cur) { var $i = cur.$input; close(); $i.trigger('focus'); }
  });
  // Enter / Spazio sul campo (readonly) lo aprono
  $(document).on('keydown', '[data-ch-picker]', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (cur && cur.$input[0] === this) { close(); } else { open($(this)); } }
  });

  $(window).on('resize', position);
  window.addEventListener('scroll', position, true);

  // ---- potenziamento dei campi scritti a mano --------------------------------
  // input[data-ch-picker] fuori da un riquadro: lo avvolge con icona e cancella;
  // input[type=date|time].form-control: diventa un campo di sola lettura col selettore
  // (stesso valore: YYYY-MM-DD / HH:mm). Esclusi [data-ch-native].
  var CAL_ICON = '<span class="input-group-text ch-addon-pre"><i class="bi bi-calendar3"></i></span>';
  var CLK_ICON = '<span class="input-group-text ch-addon-pre"><i class="bi bi-clock"></i></span>';
  var CLEAR = '<button type="button" class="ch-clear" data-ch-clear tabindex="-1" aria-label="×"><i class="bi bi-x-lg"></i></button>';

  function enhance(root) {
    var $root = $(root || document);
    $root.find('input[type="date"].form-control, input[type="time"].form-control').not('[data-ch-native]').each(function () {
      var kind = this.type === 'time' ? 'time' : 'date';
      this.type = 'text';
      $(this).attr('data-ch-picker', kind).attr('readonly', 'readonly');
    });
    $root.find('input[data-ch-picker]').each(function () {
      var $i = $(this);
      if ($i.closest(WRAP).length) { return; }
      var kind = $i.attr('data-ch-picker');
      var locked = $i.prop('disabled') || $i.is('[data-ch-locked]');
      $i.wrap('<div class="input-group ch-input"></div>');
      $i.before(kind === 'time' ? CLK_ICON : CAL_ICON);
      if (!locked) { $i.after(CLEAR); }
      $i.closest('.ch-input').toggleClass('has-value', $i.val() !== '');
    });
  }
  $(function () { enhance(document); });
  var pkTimer = null;
  if (window.MutationObserver) {
    new MutationObserver(function (muts) {
      var found = false;
      for (var i = 0; i < muts.length && !found; i++) {
        var added = muts[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          var n = added[j];
          if (n.nodeType === 1 && n.querySelector && (n.matches('input') || n.querySelector('input[data-ch-picker], input[type="date"], input[type="time"]'))) { found = true; break; }
        }
      }
      if (found) { clearTimeout(pkTimer); pkTimer = setTimeout(function () { enhance(document); }, 150); }
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  window.CHDatetime = { open: open, close: close, enhance: enhance };
})(window.jQuery);
