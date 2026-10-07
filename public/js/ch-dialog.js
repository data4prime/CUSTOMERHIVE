/*
 * Modali di conferma/avviso nello stile del progetto (ex SweetAlert).
 *
 *   chConfirm({title, text, confirmText, cancelText, danger, icon}, onConfirm)
 *   chAlert({title, text, type})            // type: error|warning|info|success
 *   swal(...)                               // shim di compatibilita' per i moduli
 *                                           // dei clienti che usano ancora la vecchia API
 *
 * Testi gia' tradotti da chi chiama; i default arrivano da window.CH_I18N
 * (confirm_yes, cancel, close). Tutto via textContent, nessun innerHTML.
 * Markup/stile: .ch-sync-overlay/.ch-sync-dialog in public/css/theme.css.
 */
(function () {
    'use strict';

    var overlay = null;
    var keyHandler = null;

    function el(tag, text, className) {
        var e = document.createElement(tag);
        if (text !== undefined && text !== null) { e.textContent = text; }
        if (className) { e.className = className; }
        return e;
    }

    function i18n(key, fallback) {
        return (window.CH_I18N && window.CH_I18N[key]) || fallback;
    }

    function close() {
        if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
        overlay = null;
        if (keyHandler) { document.removeEventListener('keydown', keyHandler); keyHandler = null; }
    }

    var ICONS = {
        warning: 'bi-exclamation-triangle-fill',
        error: 'bi-x-octagon-fill',
        info: 'bi-info-circle-fill',
        success: 'bi-check-circle-fill',
        danger: 'bi-trash-fill'
    };

    function open(opts, onConfirm) {
        close();
        var type = opts.type || 'warning';
        var danger = opts.danger === true || type === 'error';
        var showCancel = opts.showCancel !== false;

        overlay = el('div', null, 'ch-sync-overlay');
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });

        var dlg = el('div', null, 'ch-sync-dialog' + (danger ? ' ch-sync-dialog--danger' : ''));
        dlg.setAttribute('role', 'alertdialog');
        dlg.setAttribute('aria-modal', 'true');

        var head = el('div', null, 'ch-sync-head');
        var title = el('h4', null, 'ch-sync-title');
        var icon = document.createElement('i');
        icon.className = 'bi ' + (opts.icon || ICONS[danger && type === 'warning' ? 'danger' : type] || ICONS.warning);
        title.appendChild(icon);
        title.appendChild(document.createTextNode(opts.title || ''));
        head.appendChild(title);
        var x = el('button', '×', 'ch-sync-x');
        x.type = 'button';
        x.setAttribute('aria-label', i18n('close', 'Close'));
        x.addEventListener('click', close);
        head.appendChild(x);
        dlg.appendChild(head);

        if (opts.text) {
            var body = el('div', null, 'ch-sync-body');
            body.appendChild(el('p', opts.text, 'ch-confirm-text'));
            dlg.appendChild(body);
        }

        var foot = el('div', null, 'modal-footer');
        if (showCancel) {
            var cancel = el('button', opts.cancelText || i18n('cancel', 'Cancel'), 'btn btn-secondary');
            cancel.type = 'button';
            cancel.addEventListener('click', close);
            foot.appendChild(cancel);
        }
        var ok = el('button', opts.confirmText || (showCancel ? i18n('confirm_yes', 'OK') : i18n('close', 'OK')),
            'btn ' + (danger ? 'btn-danger' : 'btn-primary'));
        ok.type = 'button';
        ok.addEventListener('click', function () {
            ok.disabled = true;
            close();
            if (typeof onConfirm === 'function') { onConfirm(); }
        });
        foot.appendChild(ok);
        dlg.appendChild(foot);

        overlay.appendChild(dlg);
        document.body.appendChild(overlay);
        keyHandler = function (e) { if (e.key === 'Escape') { close(); } };
        document.addEventListener('keydown', keyHandler);
        ok.focus();
    }

    window.chConfirm = function (opts, onConfirm) {
        open(opts || {}, onConfirm);
    };

    window.chAlert = function (opts) {
        var o = opts || {};
        open({ title: o.title, text: o.text, type: o.type || 'info', showCancel: false, confirmText: o.confirmText });
    };

    // Shim della vecchia API SweetAlert: swal({title,text,type,showCancelButton,...}, cb)
    // e swal(title, text, type). Il callback parte solo alla conferma.
    window.swal = function (a, b, c) {
        if (a && typeof a === 'object') {
            var cb = typeof b === 'function' ? b : null;
            open({
                title: a.title,
                text: a.text,
                type: a.type || 'warning',
                showCancel: a.showCancelButton === true,
                confirmText: a.confirmButtonText,
                cancelText: a.cancelButtonText,
                danger: a.type === 'warning' || a.type === 'error'
            }, cb);
            return;
        }
        open({ title: a, text: b, type: c || 'info', showCancel: false });
    };
})();
