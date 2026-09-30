/*
 * Modale "Sincronizza da Qlik" sulle liste app e item.
 * Configurazione in window.QLIK_SYNC (scritta da QlikSyncUi::boot, testi gia'
 * tradotti lato server):
 *   mode:  'apps' | 'items'
 *   confs: [{id, name, usable}]   gia' in ordine alfabetico
 *   urls:  {start, apps, runs}
 *   i18n:  testi
 * Nessun dato viene inserito con innerHTML: tutto via textContent.
 */
(function () {
    'use strict';

    // La configurazione si legge al click, non al caricamento: l'ordine tra
    // questo file e lo <script> che definisce window.QLIK_SYNC non conta.
    var CFG = null;
    var T = {};

    var overlay = null;

    function el(tag, text, style) {
        var e = document.createElement(tag);
        if (text !== undefined && text !== null) { e.textContent = text; }
        if (style) { e.style.cssText = style; }
        return e;
    }

    function close() {
        if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
        overlay = null;
        document.removeEventListener('keydown', onKey);
    }

    function onKey(e) { if (e.key === 'Escape') { close(); } }

    // Select singolo con le opzioni.
    // options: [{value, label, disabled}] -> {node, getValue(), setOptions(), setDisabled()}
    function selectField(labelText, options, onChange) {
        var wrap = el('div');
        wrap.className = 'ch-sync-field';
        wrap.appendChild(el('label', labelText));

        var select = el('select');
        select.className = 'form-control';
        select.setAttribute('aria-label', labelText);
        wrap.appendChild(select);

        function setOptions(opts) {
            var prev = select.value;
            while (select.firstChild) { select.removeChild(select.firstChild); }
            opts.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                if (o.disabled) { opt.disabled = true; }
                if (o.value === prev) { opt.selected = true; }
                select.appendChild(opt);
            });
        }

        select.addEventListener('change', function () { if (onChange) { onChange(select.value); } });
        setOptions(options);

        return {
            node: wrap,
            getValue: function () { return select.value; },
            setOptions: setOptions,
            setDisabled: function (on) { select.disabled = on; }
        };
    }

    function open() {
        CFG = window.QLIK_SYNC;
        if (!CFG) { return; }
        T = CFG.i18n || {};

        close();

        overlay = el('div');
        overlay.className = 'ch-sync-overlay';
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });

        var dlg = el('div');
        dlg.className = 'ch-sync-dialog';
        dlg.setAttribute('role', 'dialog');
        dlg.setAttribute('aria-modal', 'true');

        var head = el('div');
        head.className = 'ch-sync-head';
        var title = el('h4');
        title.className = 'ch-sync-title';
        var titleIcon = document.createElement('i');
        titleIcon.className = 'fa fa-refresh';
        title.appendChild(titleIcon);
        title.appendChild(document.createTextNode(CFG.mode === 'items' ? T.title_items : T.title_apps));
        head.appendChild(title);
        var x = el('button', '×');
        x.className = 'ch-sync-x';
        x.type = 'button';
        x.setAttribute('aria-label', T.close);
        x.addEventListener('click', close);
        head.appendChild(x);
        dlg.appendChild(head);

        var body = el('div');
        body.className = 'ch-sync-body';
        var message = el('div');
        message.className = 'ch-sync-msg';

        var appList = null;

        var confOptions = (CFG.confs || []).map(function (c) {
            return { value: String(c.id), label: c.usable ? c.name : (c.name + ' — ' + T.no_qlik_user), disabled: !c.usable };
        });

        function loadApps(confId) {
            if (!appList) { return; }
            appList.setOptions([{ value: '', label: T.all_apps }]);
            if (!confId) { return; }
            appList.setDisabled(true);
            fetch(CFG.urls.apps + '?conf_id=' + encodeURIComponent(confId), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                var opts = [{ value: '', label: T.all_apps }];
                (data.apps || []).forEach(function (a) { opts.push({ value: String(a.id), label: a.name }); });
                appList.setOptions(opts);
            }).catch(function () {
                showMessage(T.network_error, true);
            }).then(function () {
                appList.setDisabled(false);
            });
        }

        var confList = selectField(T.label_conf, [{ value: '', label: T.choose_conf }].concat(confOptions), loadApps);
        body.appendChild(confList.node);

        if (CFG.mode === 'items') {
            appList = selectField(T.label_app, [{ value: '', label: T.all_apps }]);
            body.appendChild(appList.node);
            var itemsHint = el('p', T.items_hint);
            itemsHint.className = 'ch-sync-hint';
            body.appendChild(itemsHint);
        } else {
            var appsHint = el('p', T.apps_hint);
            appsHint.className = 'ch-sync-hint';
            body.appendChild(appsHint);
        }
        body.appendChild(message);
        dlg.appendChild(body);

        var foot = el('div');
        foot.className = 'modal-footer';
        var cancel = el('button', T.close);
        cancel.type = 'button';
        cancel.className = 'btn btn-default';
        cancel.addEventListener('click', close);
        var start = el('button', T.start);
        start.type = 'button';
        start.className = 'btn btn-primary';
        foot.appendChild(cancel);
        foot.appendChild(start);
        dlg.appendChild(foot);

        function showMessage(text, isError, link) {
            message.className = 'ch-sync-msg ' + (isError ? 'is-error' : 'is-ok');
            message.textContent = text;
            if (link) {
                message.appendChild(document.createTextNode(' '));
                var a = document.createElement('a');
                a.href = link;
                a.textContent = T.go_to_runs;
                message.appendChild(a);
            }
        }

        start.addEventListener('click', function () {
            var confId = confList.getValue();
            if (!confId) { showMessage(T.choose_conf, true); return; }

            var fd = new FormData();
            fd.set('conf_id', confId);
            if (CFG.mode === 'items') { fd.set('app_id', appList ? appList.getValue() : ''); }

            start.disabled = true;
            fetch(CFG.urls.start, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) {
                return r.text().then(function (t) { return { status: r.status, text: t }; });
            }).then(function (res) {
                var data = null;
                try { data = JSON.parse(res.text); } catch (e) { data = null; }
                if (data && data.ok) {
                    showMessage(T.started, false, data.run_url);
                    cancel.textContent = T.close;
                } else {
                    showMessage((data && data.message) || (T.bad_response + ' (HTTP ' + res.status + ')'), true);
                    start.disabled = false;
                }
            }).catch(function () {
                showMessage(T.network_error, true);
                start.disabled = false;
            });
        });

        overlay.appendChild(dlg);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', onKey);
    }

    window.qlikSyncOpen = open;
})();
