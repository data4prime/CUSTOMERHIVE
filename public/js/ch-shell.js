/*
 * ch-shell.js - comportamento del guscio dell'admin (sidebar, albero del
 * menu, box comprimibili, barra di controllo, tooltip, altezza contenuto).
 *
 * Sostituisce dist/js/app.js di AdminLTE 2.3.8 (MIT). Stesse classi e
 * stessi attributi di prima, cosi' markup di progetto e viste custom dei
 * clienti continuano a funzionare. Richiede jQuery e Bootstrap 5 (bundle).
 */
(function ($) {
  'use strict';

  if (typeof $ === 'undefined') {
    throw new Error('ch-shell.js richiede jQuery');
  }

  var SM = 768;               // sotto questa larghezza la sidebar e' a scomparsa
  var ANIMATION_MS = 100;

  // Selettori. Il toggle del progetto e' data-ch-toggle; i data-bs-toggle
  // storici restano supportati per i markup non ancora migrati.
  var SEL_SIDEBAR_TOGGLE = '[data-ch-toggle="sidebar"], [data-bs-toggle="offcanvas"]';
  var SEL_CONTROL_TOGGLE = '[data-ch-toggle="control-sidebar"], [data-bs-toggle="control-sidebar"]';
  var SEL_CONTROL = '.control-sidebar';

  // Coppie di icone collassa/apri dei box, per i due set di icone.
  var BOX_ICON_PAIRS = [
    ['bi-dash-lg', 'bi-plus-lg'],
    ['bi-dash', 'bi-plus'],
    ['bi-dash-lg', 'bi-plus-lg']
  ];

  var Shell = {};

  /* ---- Altezza minima del contenuto ---------------------------------- */
  Shell.layout = {
    fix: function () {
      $('.layout-boxed > .wrapper').css('overflow', 'hidden');
      var footerH = $('.main-footer').outerHeight() || 0;
      var headerH = $('.main-header').outerHeight() || 0;
      var windowH = $(window).height();
      var sidebarH = $('.main-sidebar').height() || 0;
      var $content = $('.content-wrapper, .right-side');

      if ($('body').hasClass('fixed')) {
        $content.css('min-height', windowH - footerH);
        return;
      }
      var min = windowH >= sidebarH ? windowH - (headerH + footerH) : sidebarH + 20;
      $content.css('min-height', min);

      var $cs = $(SEL_CONTROL);
      if ($cs.length && $cs.height() > min) {
        $content.css('min-height', $cs.height());
      }
    },
    activate: function () {
      var self = this;
      self.fix();
      $('body, html, .wrapper').css('height', 'auto');
      $(window).on('resize', function () { self.fix(); });
    }
  };

  /* ---- Sidebar a scomparsa ------------------------------------------- */
  Shell.pushMenu = {
    activate: function () {
      var $body = $('body');

      $(document).on('click', SEL_SIDEBAR_TOGGLE, function (e) {
        e.preventDefault();
        if ($(window).width() >= SM) {
          if ($body.hasClass('sidebar-collapse')) {
            $body.removeClass('sidebar-collapse').trigger('expanded.pushMenu');
          } else {
            $body.addClass('sidebar-collapse').trigger('collapsed.pushMenu');
          }
        } else if ($body.hasClass('sidebar-open')) {
          $body.removeClass('sidebar-open sidebar-collapse').trigger('collapsed.pushMenu');
        } else {
          $body.addClass('sidebar-open').trigger('expanded.pushMenu');
        }
      });

      // Su schermi piccoli un click sul contenuto richiude la sidebar.
      $(document).on('click', '.content-wrapper', function () {
        if ($(window).width() < SM && $body.hasClass('sidebar-open')) {
          $body.removeClass('sidebar-open');
        }
      });

      // sidebar-mini (ADMIN_LAYOUT): espande al passaggio del mouse.
      $('.main-sidebar').hover(function () {
        if ($body.hasClass('sidebar-mini') && $body.hasClass('sidebar-collapse') && $(window).width() >= SM) {
          $body.removeClass('sidebar-collapse').addClass('sidebar-expanded-on-hover');
        }
      }, function () {
        if ($body.hasClass('sidebar-mini') && $body.hasClass('sidebar-expanded-on-hover') && $(window).width() >= SM) {
          $body.removeClass('sidebar-expanded-on-hover').addClass('sidebar-collapse');
        }
      });
    }
  };

  /* ---- Menu ad albero della sidebar ---------------------------------- */
  Shell.tree = function (menu) {
    $(document).off('click.chTree').on('click.chTree', menu + ' li a', function (e) {
      var $this = $(this);
      var $next = $this.next();
      if (!$next.is('.treeview-menu')) {
        return;
      }
      e.preventDefault();

      if ($next.is(':visible') && !$('body').hasClass('sidebar-collapse')) {
        $next.slideUp(ANIMATION_MS, function () { $next.removeClass('menu-open'); });
        $next.parent('li').removeClass('active');
      } else if (!$next.is(':visible')) {
        var $parent = $this.parents('ul').first();
        $parent.find('ul:visible').slideUp(ANIMATION_MS).removeClass('menu-open');
        var $li = $this.parent('li');
        $next.slideDown(ANIMATION_MS, function () {
          $next.addClass('menu-open');
          $parent.find('li.active').removeClass('active');
          $li.addClass('active');
          Shell.layout.fix();
        });
      }
    });
  };

  /* ---- Barra di controllo (destra) ----------------------------------- */
  Shell.controlSidebar = {
    activate: function () {
      var $sidebar = $(SEL_CONTROL);
      $(document).on('click', SEL_CONTROL_TOGGLE, function (e) {
        e.preventDefault();
        $sidebar.toggleClass('control-sidebar-open');
      });
      if ($('.content-wrapper, .right-side').height() < $sidebar.height()) {
        $('.content-wrapper, .right-side').css('min-height', $sidebar.height());
      }
      $('.control-sidebar-bg').css({ position: 'fixed', height: 'auto' });
    }
  };

  /* ---- Box comprimibili / rimovibili --------------------------------- */
  function swapBoxIcon($btn, toCollapsed) {
    var $icon = $btn.children().first();
    $.each(BOX_ICON_PAIRS, function (_, pair) {
      var from = toCollapsed ? pair[0] : pair[1];
      var to = toCollapsed ? pair[1] : pair[0];
      if ($icon.hasClass(from)) {
        $icon.removeClass(from).addClass(to);
      }
    });
  }

  Shell.boxWidget = {
    activate: function () {
      $(document).on('click', '[data-widget="collapse"]', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $box = $btn.parents('.box').first();
        var $content = $box.find('> .box-body, > .box-footer, > form > .box-body, > form > .box-footer');
        if (!$box.hasClass('collapsed-box')) {
          swapBoxIcon($btn, true);
          $content.slideUp(ANIMATION_MS, function () { $box.addClass('collapsed-box'); });
        } else {
          swapBoxIcon($btn, false);
          $content.slideDown(ANIMATION_MS, function () { $box.removeClass('collapsed-box'); });
        }
      });
      $(document).on('click', '[data-widget="remove"]', function (e) {
        e.preventDefault();
        $(this).parents('.box').first().slideUp(ANIMATION_MS);
      });
    }
  };

  /* ---- Tooltip Bootstrap 5, creati al primo passaggio del mouse ------ */
  function activateTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
      return;
    }
    $(document).on('mouseenter focusin', '[data-bs-toggle="tooltip"]', function () {
      var tip = bootstrap.Tooltip.getOrCreateInstance(this, { container: 'body' });
      tip.show();
    });
  }

  /* ---- Gruppi di bottoni "toggle" ------------------------------------ */
  function activateButtonToggle() {
    $(document).on('click', '.btn-group[data-bs-toggle="btn-toggle"] .btn', function (e) {
      $(this).closest('.btn-group').find('.btn.active').removeClass('active');
      $(this).addClass('active');
      e.preventDefault();
    });
  }

  $(function () {
    $('body').removeClass('hold-transition');
    Shell.layout.activate();
    Shell.tree('.sidebar');
    Shell.controlSidebar.activate();
    Shell.pushMenu.activate();
    Shell.boxWidget.activate();
    activateTooltips();
    activateButtonToggle();
  });

  window.CHShell = Shell;

  // Compatibilita' con codice che richiamava AdminLTE (deprecato, solo layout).
  $.AdminLTE = $.AdminLTE || {
    layout: Shell.layout,
    options: { controlSidebarOptions: { selector: SEL_CONTROL } }
  };
})(window.jQuery);
