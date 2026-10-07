/*
 * ch-icon-picker.js - selettore di icone (intervento 236).
 *
 * Markup: resources/views/crudbooster/partials/ch_icon_picker.blade.php.
 * Ogni .ch-iconpick contiene un input hidden col valore salvato, un pulsante che
 * mostra l'icona scelta e un pannello con ricerca e griglia. Il valore salvato e'
 * "bi bi-<nome>" oppure, con data-bare, solo "<nome>" (es. widget KPI).
 * La griglia viene costruita solo all'apertura, a partire dall'elenco dei nomi
 * (JSON in #ch-ip-data): sono oltre 2000 icone, renderle tutte subito rallenterebbe la pagina.
 * Niente dipendenze (JS puro). Il file puo' essere caricato piu' volte (HTML
 * iniettato via ajax): si registra una volta sola.
 */
(function () {
  'use strict';

  if (window.__chIconPicker) { return; }
  window.__chIconPicker = true;

  var LIMIT = 240;
  var names = null;

  function allNames() {
    if (names) { return names; }
    var el = document.getElementById('ch-ip-data');
    try { names = el ? JSON.parse(el.textContent) : []; } catch (e) { names = []; }
    // alcuni nomi (es. l'icona "123") escono dal JSON come numeri
    names = names.map(String);
    return names;
  }

  /* Sinonimi: nome Bootstrap Icons -> nomi FontAwesome equivalenti (es. "person-fill" -> "user"),
     cosi' chi cerca col vecchio nome (user, home, cog...) trova comunque l'icona. */
  var aliases = null;
  function allAliases() {
    if (aliases) { return aliases; }
    var el = document.getElementById('ch-ip-aliases');
    try { aliases = el ? JSON.parse(el.textContent) : {}; } catch (e) { aliases = {}; }
    return aliases;
  }

  function prefixOf(root) { return root.getAttribute('data-prefix') || 'bi bi-'; }
  function isBare(root) { return root.hasAttribute('data-bare'); }

  /* "bi bi-house" | "house" -> "house" */
  function nameOf(root, value) {
    if (!value) { return ''; }
    var p = prefixOf(root);
    return value.indexOf(p) === 0 ? value.slice(p.length) : value;
  }

  function classOf(root, value) {
    return isBare(root) ? prefixOf(root) + value : value;
  }

  function setValue(root, name) {
    var input = root.querySelector('[data-ip-value]');
    var cur = root.querySelector('.ch-ip-cur');
    var label = root.querySelector('.ch-ip-name');
    input.value = name ? (isBare(root) ? name : prefixOf(root) + name) : '';
    cur.innerHTML = name ? '<i class="' + prefixOf(root) + name + '"></i>' : '';
    cur.classList.toggle('is-empty', !name);
    label.textContent = name || label.getAttribute('data-empty');
    label.classList.toggle('is-empty', !name);
    // Chi ascolta il cambio (anteprime, validazioni) lo riceve come per un normale campo.
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function render(root, query) {
    var grid = root.querySelector('.ch-ip-grid');
    var more = root.querySelector('.ch-ip-more');
    var current = nameOf(root, root.querySelector('[data-ip-value]').value);
    var q = (query || '').trim().toLowerCase().replace(/^(bi bi-|bi-|fa fa-|fa-)/, '').replace(/\s+/g, '-');
    var list = allNames();
    var al = allAliases();
    var out = [];
    var total = 0;
    for (var i = 0; i < list.length; i++) {
      if (q && list[i].indexOf(q) === -1 && !(al[list[i]] && al[list[i]].join(' ').indexOf(q) !== -1)) { continue; }
      total++;
      if (out.length < LIMIT) { out.push(list[i]); }
    }
    var html = '';
    for (var k = 0; k < out.length; k++) {
      html += '<button type="button" class="ch-ip-item' + (out[k] === current ? ' is-on' : '') + '" data-n="' + out[k] + '" title="' + out[k] + '"><i class="' + prefixOf(root) + out[k] + '"></i></button>';
    }
    grid.innerHTML = html || '<span class="ch-ip-empty">' + grid.getAttribute('data-empty') + '</span>';
    if (total > LIMIT) {
      more.hidden = false;
      more.textContent = more.getAttribute('data-tpl').replace(':n', LIMIT).replace(':total', total);
    } else {
      more.hidden = true;
    }
  }

  function open(root) {
    root.querySelector('.ch-ip-pop').hidden = false;
    root.querySelector('[data-ip-open]').setAttribute('aria-expanded', 'true');
    var search = root.querySelector('.ch-ip-search');
    search.value = '';
    render(root, '');
    search.focus();
  }

  function close(root) {
    var pop = root.querySelector('.ch-ip-pop');
    if (pop.hidden) { return; }
    pop.hidden = true;
    root.querySelector('[data-ip-open]').setAttribute('aria-expanded', 'false');
  }

  function closeAll(except) {
    var roots = document.querySelectorAll('[data-ch-iconpick]');
    for (var i = 0; i < roots.length; i++) {
      if (roots[i] !== except) { close(roots[i]); }
    }
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    var root = t.closest ? t.closest('[data-ch-iconpick]') : null;
    if (!root) { closeAll(null); return; }

    if (t.closest('[data-ip-open]')) {
      var isOpen = !root.querySelector('.ch-ip-pop').hidden;
      closeAll(root);
      if (isOpen) { close(root); } else { open(root); }
      return;
    }
    var item = t.closest('.ch-ip-item');
    if (item) {
      setValue(root, item.getAttribute('data-n'));
      close(root);
      return;
    }
    if (t.closest('[data-ip-clear]')) {
      setValue(root, '');
      close(root);
    }
  });

  document.addEventListener('input', function (e) {
    var s = e.target;
    if (s.classList && s.classList.contains('ch-ip-search')) {
      render(s.closest('[data-ch-iconpick]'), s.value);
    }
  });

  document.addEventListener('keydown', function (e) {
    // Invio nella ricerca non deve inviare il form del passo (wizard) che la contiene.
    if (e.key === 'Enter' && e.target.classList && e.target.classList.contains('ch-ip-search')) {
      e.preventDefault();
      return;
    }
    if (e.key === 'Escape') { closeAll(null); }
  });
})();
