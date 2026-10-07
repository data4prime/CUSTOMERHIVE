/*
 * ch-theme.js - tema chiaro/scuro del nuovo linguaggio visivo (intervento 235).
 *
 * Caricato subito dopo l'apertura del <body> (solo con UI_V2 attivo) cosi' la
 * scelta salvata viene applicata prima del primo disegno, senza lampeggio.
 * La scelta e' per-browser (localStorage "ch-theme": "dark" | "light"); senza
 * scelta si segue il sistema operativo (prefers-color-scheme).
 * Il pulsante e' qualunque elemento con data-ch-toggle="theme".
 */
(function () {
  'use strict';

  var KEY = 'ch-theme';

  function stored() {
    try { return window.localStorage.getItem(KEY); } catch (e) { return null; }
  }

  function systemDark() {
    return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  }

  function current() {
    var s = stored();
    if (s === 'dark' || s === 'light') { return s; }
    return systemDark() ? 'dark' : 'light';
  }

  function setBody(doc, theme) {
    var body = doc && doc.body;
    if (!body || !body.classList.contains('ch-ui2')) { return; }
    if (theme === 'dark') { body.setAttribute('data-ch-theme', 'dark'); }
    else { body.removeAttribute('data-ch-theme'); }
  }

  function apply(theme) {
    setBody(document, theme);
    var icons = document.querySelectorAll('[data-ch-toggle="theme"] i');
    for (var i = 0; i < icons.length; i++) {
      icons[i].className = 'bi ' + (theme === 'dark' ? 'bi-sun' : 'bi-moon-stars');
    }
    // Moduli incorporati (iframe dello stesso sito): seguono la pagina anche dopo il caricamento.
    var frames = document.querySelectorAll('iframe');
    for (var f = 0; f < frames.length; f++) {
      try { setBody(frames[f].contentDocument, theme); } catch (e) { /* iframe di un altro sito */ }
    }
  }

  apply(current());

  document.addEventListener('DOMContentLoaded', function () {
    apply(current());
  });

  document.addEventListener('click', function (e) {
    var t = e.target && e.target.closest ? e.target.closest('[data-ch-toggle="theme"]') : null;
    if (!t) { return; }
    e.preventDefault();
    var next = current() === 'dark' ? 'light' : 'dark';
    try { window.localStorage.setItem(KEY, next); } catch (err) { /* modalita' privata: vale solo per questa pagina */ }
    apply(next);
  });
})();
