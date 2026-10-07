/*
 * ch-inputs.js - comportamento dei campi testuali standard (partials/ch_input):
 * stepper dei numeri, mostra/nascondi password, contatore caratteri.
 * Delegazione di eventi sul document: funziona anche per campi aggiunti dopo
 * (righe dei child, modali). Richiede jQuery.
 */
(function ($) {
  'use strict';
  if (typeof $ === 'undefined') { return; }

  function fire(el) {
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  // Stepper: ▲ / ▼ sul numero (rispetta step, min e max dell'input)
  $(document).on('click', '[data-ch-step]', function (e) {
    e.preventDefault();
    var input = $(this).closest('.ch-input').find('input[type="number"]')[0];
    if (!input || input.disabled || input.readOnly) { return; }
    if (+this.getAttribute('data-ch-step') > 0) { input.stepUp(); } else { input.stepDown(); }
    fire(input);
  });

  // Mostra/nascondi password
  $(document).on('click', '[data-ch-eye]', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var input = $btn.closest('.ch-input').find('input')[0];
    if (!input) { return; }
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    $btn.find('i').toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
    $btn.attr('aria-pressed', show ? 'true' : 'false');
  });

  // Contatore caratteri delle textarea con maxlength
  function updateCount(el) {
    var $c = $(el).closest('.ch-textarea').find('[data-ch-count]');
    if ($c.length) { $c.text(el.value.length); }
  }
  $(document).on('input', '.ch-textarea textarea', function () { updateCount(this); });
  $(function () { $('.ch-textarea textarea').each(function () { updateCount(this); }); });

  // ---------------------------------------------------------------------------
  // Potenziamento dei campi scritti a mano nelle viste (profilo, impostazioni,
  // popup, builder...): password con occhio, numeri con stepper, textarea con
  // contatore. I campi gia' resi da partials/ch_input hanno gia' tutto.
  // Esclusi: [data-ch-native], campi piccoli (.form-control-sm) e numeri dentro
  // tabelle.
  // ---------------------------------------------------------------------------
  var EYE = '<button type="button" class="ch-eye" data-ch-eye aria-pressed="false" tabindex="-1"><i class="bi bi-eye"></i></button>';
  var STEP = '<span class="ch-step"><button type="button" tabindex="-1" data-ch-step="1" aria-label="+"><i class="bi bi-chevron-up"></i></button>'
    + '<button type="button" tabindex="-1" data-ch-step="-1" aria-label="-"><i class="bi bi-chevron-down"></i></button></span>';

  function wrapInput($input, extraHtml) {
    var $parent = $input.parent();
    if ($parent.is('.input-group')) {
      $parent.addClass('ch-input');
    } else {
      $input.wrap('<div class="input-group ch-input"></div>');
    }
    $input.after(extraHtml);
  }

  function enhanceInputs(root) {
    var $root = $(root || document);
    $root.find('input[type="password"].form-control').not('[data-ch-native], .form-control-sm').each(function () {
      var $i = $(this);
      if ($i.closest('.ch-input').length) { return; }
      wrapInput($i, EYE);
    });
    $root.find('input[type="number"].form-control').not('[data-ch-native], .form-control-sm').each(function () {
      var $i = $(this);
      if ($i.closest('.ch-input, td, th, .table').length || this.readOnly || this.disabled) { return; }
      wrapInput($i, STEP);
    });
    $root.find('textarea.form-control[maxlength]').not('[data-ch-native]').each(function () {
      var $t = $(this);
      if ($t.closest('.ch-textarea').length) { return; }
      $t.wrap('<div class="ch-textarea"></div>')
        .after('<div class="ch-counter"><span data-ch-count>0</span>/' + parseInt(this.getAttribute('maxlength'), 10) + '</div>');
      updateCount(this);
    });
  }

  // ---------------------------------------------------------------------------
  // Campo file: nome del file scelto (partials/ch_file e campi file scritti a mano)
  // ---------------------------------------------------------------------------
  var I18N = window.CH_I18N || {};
  $(document).on('change', 'input[data-ch-file]', function () {
    var n = this.files ? this.files.length : 0;
    var txt = n === 0 ? (I18N.file_none || '') : (n === 1 ? this.files[0].name : n + ' files');
    var $f = $(this).nextAll('.ch-file').first().find('[data-ch-fname]');
    $f.text(txt).toggleClass('has-file', n > 0);
  });

  // Campi file scritti a mano: l'input resta dov'e' (stesso parent di prima, per il codice che
  // lo cerca per name e lo nasconde), il riquadro standard gli viene messo dopo.
  var fileSeq = 0;
  function enhanceFiles($root) {
    $root.find('input[type="file"].form-control').not('[data-ch-native], [data-ch-file]').each(function () {
      var $i = $(this);
      if ($i.is('[hidden]') || $i.css('display') === 'none') { return; }
      if (!this.id) { this.id = 'ch-file-' + (++fileSeq); }
      var isImage = /image/i.test($i.attr('accept') || '');
      $i.removeClass('form-control').addClass('ch-file-input').attr('data-ch-file', '');
      var $g = $('<div class="input-group ch-input ch-file"></div>');
      var $l = $('<label class="input-group-text ch-addon-pre ch-file-btn"></label>').attr('for', this.id)
        .append('<i class="bi bi-' + (isImage ? 'image' : 'paperclip') + '"></i> ')
        .append(document.createTextNode(isImage ? (I18N.choose_image || '') : (I18N.choose_file || '')));
      $g.append($l).append('<div class="ch-fname" data-ch-fname>' + (I18N.file_none || '') + '</div>');
      $i.after($g);
    });
  }
  // ---------------------------------------------------------------------------
  // Colore: il campo di testo esadecimale e il selettore restano sincronizzati
  // ---------------------------------------------------------------------------
  $(document).on('input change', '.ch-color .ch-color-input', function () {
    var $g = $(this).closest('.ch-color');
    $g.find('.ch-swatch').css('background', this.value);
    $g.find('[data-ch-hex]').val(this.value);
  });
  $(document).on('input', '.ch-color [data-ch-hex]', function () {
    var v = this.value.trim();
    if (v.charAt(0) !== '#') { v = '#' + v; }
    if (/^#[0-9a-fA-F]{6}$/.test(v)) {
      var $g = $(this).closest('.ch-color');
      $g.find('.ch-color-input').val(v).trigger('change');
    }
  });

  // ---------------------------------------------------------------------------
  // Datamodal: svuota la scelta (pulsante ✕ del campo)
  // ---------------------------------------------------------------------------
  window.chDatamodalClear = function (name) {
    var $g = $('#' + name);
    $g.find('.input-id').val('').trigger('change');
    $g.find('.input-label').val('');
    $g.removeClass('has-value');
  };

  $(function () { enhanceInputs(document); enhanceFiles($(document)); });

  var inputTimer = null;
  if (window.MutationObserver) {
    new MutationObserver(function (muts) {
      var found = false;
      for (var i = 0; i < muts.length && !found; i++) {
        var added = muts[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          var n = added[j];
          if (n.nodeType === 1 && n.querySelector && (n.matches('input,textarea') || n.querySelector('input[type="password"],input[type="number"],input[type="file"],textarea[maxlength]'))) { found = true; break; }
        }
      }
      if (found) {
        clearTimeout(inputTimer);
        inputTimer = setTimeout(function () { enhanceInputs(document); enhanceFiles($(document)); }, 150);
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
})(window.jQuery);
