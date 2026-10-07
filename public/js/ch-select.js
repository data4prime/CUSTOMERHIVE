/*
 * ch-select.js - select standard (dropdown con ricerca) per TUTTA l'app.
 * Motore: select2 (caricato da partials/ch_scripts), aspetto: ch-components.css.
 *
 *  - window.chSelect(el, opts): select2 con le impostazioni standard
 *    (ricerca solo con 4+ voci, voci con data-desc / data-icon, "nessun risultato"
 *    tradotto, dropdown dentro la modale/dialog che contiene il campo).
 *  - Auto-potenziamento: ogni <select> dell'app diventa standard, anche quelli
 *    scritti a mano nelle viste e quelli creati dopo da JS, tranne:
 *      · select2 gia' inizializzati o che lo faranno da soli (classe .select2);
 *      · [multiple] e [size];
 *      · [data-ch-native] (opt-out esplicito).
 *    I select creati dinamicamente vengono potenziati dopo una breve attesa, cosi'
 *    il codice che li inizializza con opzioni proprie ha la precedenza.
 *  - $(select).val(x) aggiorna anche la grafica del select2 (select2 4.0 non lo fa).
 */
(function ($) {
  'use strict';
  if (!$ || !$.fn || !$.fn.select2) { return; }

  var NO_RESULTS = window.CH_SELECT_NO_RESULTS || 'No results';
  var MIN_FOR_SEARCH = 4;

  function template(state) {
    if (!state.id || !state.element) { return state.text; }
    var $opt = $(state.element), desc = $opt.data('desc'), icon = $opt.data('icon');
    if (!desc && !icon) { return state.text; }
    var $row = $('<span></span>');
    if (icon && /^[a-z0-9-]+$/i.test(icon)) { $row.append($('<i class="bi"></i>').addClass('bi-' + icon)).append(' '); }
    $row.append(document.createTextNode(state.text));
    if (desc) { $row.append($('<span class="ch-select-desc"></span>').text(desc)); }
    return $row;
  }

  function dropdownParent($el) {
    var $p = $el.closest('.modal, dialog');
    return $p.length ? $p : $(document.body);
  }

  window.chSelect = function (el, opts) {
    var $el = $(el);
    if ($el.data('select2')) { return $el; }
    var full = $el.is('.form-control, .form-select') && !$el.is('.form-control-sm, .form-select-sm, .input-sm');
    // Nascosto (popup, schede chiuse) non si puo' misurare: larghezza piena
    var hidden = !$el[0] || !$el[0].offsetParent;
    $el.select2($.extend({
      width: (full || hidden) ? '100%' : 'resolve',
      minimumResultsForSearch: MIN_FOR_SEARCH,
      templateResult: template,
      dropdownParent: dropdownParent($el),
      language: { noResults: function () { return NO_RESULTS; } }
    }, opts || {}));
    if (!full) { $el.next('.select2-container').addClass('ch-select-compact'); }
    return $el;
  };

  // val(x) su un select potenziato: aggiorna anche la grafica
  var origVal = $.fn.val;
  $.fn.val = function (v) {
    var r = origVal.apply(this, arguments);
    if (arguments.length && v !== undefined) {
      this.filter('select.select2-hidden-accessible').trigger('change.select2');
    }
    return r;
  };

  // select2 (e $(select).trigger('change')) notifica la scelta con un evento jQuery: i
  // listener registrati con addEventListener('change') (molti script dei moduli e dei
  // clienti, es. la visibilita' dei campi di qlik_confs) non lo vedono. Su un select
  // potenziato lo trasformiamo in un evento nativo: arriva sia ai listener nativi sia a
  // quelli jQuery, una volta sola ciascuno.
  var origTrigger = $.fn.trigger;
  $.fn.trigger = function (type) {
    if (type === 'change' && this.length) {
      var rest = Array.prototype.slice.call(arguments, 1);
      var self = this;
      this.each(function () {
        if (this.tagName === 'SELECT' && this.classList.contains('select2-hidden-accessible')) {
          this.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
          origTrigger.apply($(this), ['change'].concat(rest));
        }
      });
      return self;
    }
    return origTrigger.apply(this, arguments);
  };

  function eligible(s) {
    var $s = $(s);
    return !$s.data('select2')
      && !s.multiple
      && !s.hasAttribute('size')
      && !s.hasAttribute('data-ch-native')
      && !s.classList.contains('select2')
      && !s.classList.contains('select2-hidden-accessible')
      && $s.closest('.select2-container, .select2-dropdown').length === 0;
  }

  function scan(root) {
    $(root || document).find('select').each(function () {
      if (eligible(this)) { window.chSelect(this); }
    });
  }

  // Primo passaggio dopo che gli script di pagina hanno fatto i loro init
  $(function () { setTimeout(function () { scan(document); }, 0); });

  // Select aggiunti dopo (modali, builder, righe dinamiche)
  var timer = null;
  if (window.MutationObserver) {
    new MutationObserver(function (muts) {
      var found = false;
      for (var i = 0; i < muts.length && !found; i++) {
        var added = muts[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          var n = added[j];
          if (n.nodeType === 1 && (n.tagName === 'SELECT' || (n.querySelector && n.querySelector('select')))) { found = true; break; }
        }
      }
      if (found) {
        clearTimeout(timer);
        timer = setTimeout(function () { scan(document); }, 200);
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
})(window.jQuery);
