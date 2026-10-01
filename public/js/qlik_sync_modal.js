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

    // Elenco di app con checkbox, ricerca e selezione dei soli elementi visibili.
    // apps: [{id, name, imported}]; le gia' importate partono spuntate.
    // -> {node, reset(), setStatus(text), setApps(apps), selectedIds()}
    function appPicker(onChange, tagText) {
        var apps = [];
        var checked = {};
        var rows = [];

        var wrap = el('div');
        wrap.style.cssText = 'margin-top:10px';

        var status = el('div', '', 'color:#777;margin:6px 0');
        var tools = el('div', '', 'display:none;margin-bottom:6px');

        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'form-control';
        search.placeholder = T.search;
        search.setAttribute('aria-label', T.search);
        search.style.cssText = 'margin-bottom:6px';
        tools.appendChild(search);

        var bar = el('div', '', 'display:flex;gap:6px;align-items:center;flex-wrap:wrap');
        var btnSel = el('button', T.select_visible);
        btnSel.type = 'button';
        btnSel.className = 'btn btn-default btn-xs';
        var btnDesel = el('button', T.deselect_visible);
        btnDesel.type = 'button';
        btnDesel.className = 'btn btn-default btn-xs';
        var counter = el('span', '', 'margin-left:auto;color:#555');
        bar.appendChild(btnSel);
        bar.appendChild(btnDesel);
        bar.appendChild(counter);
        tools.appendChild(bar);

        var list = el('div', '', 'max-height:50vh;overflow-y:auto;border:1px solid #ddd;border-radius:3px;display:none');

        wrap.appendChild(status);
        wrap.appendChild(tools);
        wrap.appendChild(list);

        function selectedIds() {
            return apps.filter(function (a) { return checked[a.id]; }).map(function (a) { return a.id; });
        }

        function refreshCounter() {
            counter.textContent = T.selected_count.replace(':n', String(selectedIds().length)).replace(':total', String(apps.length));
            if (onChange) { onChange(); }
        }

        function applyFilter() {
            var q = search.value.trim().toLowerCase();
            rows.forEach(function (r) {
                r.node.style.display = (q === '' || r.name.indexOf(q) !== -1) ? '' : 'none';
            });
        }

        function setVisible(on) {
            rows.forEach(function (r) {
                if (r.node.style.display === 'none') { return; }
                checked[r.app.id] = on;
                r.box.checked = on;
            });
            refreshCounter();
        }

        search.addEventListener('input', applyFilter);
        btnSel.addEventListener('click', function () { setVisible(true); });
        btnDesel.addEventListener('click', function () { setVisible(false); });

        return {
            node: wrap,
            reset: function () {
                apps = []; checked = {}; rows = [];
                while (list.firstChild) { list.removeChild(list.firstChild); }
                list.style.display = 'none';
                tools.style.display = 'none';
                status.textContent = '';
                search.value = '';
                refreshCounter();
            },
            setStatus: function (text) { status.textContent = text; },
            // Spinner al posto del testo "caricamento": il testo resta solo per screen reader.
            setLoading: function (on) {
                status.textContent = '';
                if (!on) { return; }
                var wrapSpin = el('div', '', 'text-align:center;padding:28px 0;color:#8b8b96');
                wrapSpin.setAttribute('role', 'status');
                wrapSpin.setAttribute('aria-label', T.loading_apps);
                var icon = document.createElement('i');
                icon.className = 'fa fa-spinner fa-spin fa-2x';
                icon.setAttribute('aria-hidden', 'true');
                wrapSpin.appendChild(icon);
                status.appendChild(wrapSpin);
            },
            setApps: function (items) {
                this.reset();
                // Ordine alfabetico (senza distinguere maiuscole/accenti, numeri in ordine naturale).
                apps = items.slice().sort(function (a, b) {
                    return String(a.name).localeCompare(String(b.name), undefined, { sensitivity: 'base', numeric: true });
                });
                if (!apps.length) { status.textContent = T.no_apps_found; return; }
                apps.forEach(function (a) {
                    checked[a.id] = !!a.imported;

                    var label = document.createElement('label');
                    label.style.cssText = 'display:flex;align-items:center;gap:8px;margin:0;padding:6px 10px;border-bottom:1px solid #f0f0f0;font-weight:normal;cursor:pointer';
                    var box = document.createElement('input');
                    box.type = 'checkbox';
                    box.checked = !!a.imported;
                    box.addEventListener('change', function () { checked[a.id] = box.checked; refreshCounter(); });
                    label.appendChild(box);
                    // Titolo e, se c'e', descrizione (item) in grigio sotto.
                    var text = el('span', null);
                    text.style.cssText = 'display:flex;flex-direction:column;min-width:0';
                    text.appendChild(el('span', a.name));
                    if (a.description) {
                        text.appendChild(el('span', a.description, 'font-size:12px;color:#8b8b96;white-space:normal'));
                    }
                    label.appendChild(text);
                    if (a.imported) {
                        var tag = el('span', tagText || T.already_imported,'margin-left:auto;font-size:11px;color:#fff;background:#5cb85c;padding:1px 6px;border-radius:3px');
                        label.appendChild(tag);
                    }
                    list.appendChild(label);
                    rows.push({ app: a, box: box, node: label, name: (String(a.name) + ' ' + String(a.description || '')).toLowerCase() });
                });
                list.style.display = '';
                tools.style.display = '';
                refreshCounter();
            },
            selectedIds: selectedIds
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
        dlg.className = 'ch-sync-dialog ch-sync-dialog--wide';
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

        // Modalita' app: elenco con checkbox per l'import selettivo.
        var picker = null;
        var previewSeq = 0;

        // Modalita' item: stesso elenco, con i fogli della app scelta (appId = id locale).
        function loadPreview(confId, appId) {
            if (!picker) { return; }
            picker.reset();
            if (!confId) { return; }
            if (CFG.mode === 'items' && !appId) { return; }
            var seq = ++previewSeq;
            picker.setLoading(true);
            var qs = '?conf_id=' + encodeURIComponent(confId) + (CFG.mode === 'items' ? '&app_id=' + encodeURIComponent(appId) : '');
            fetch(CFG.urls.preview + qs, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) {
                return r.text().then(function (t) { return { status: r.status, text: t }; });
            }).then(function (res) {
                if (seq !== previewSeq) { return; }
                var data = null;
                try { data = JSON.parse(res.text); } catch (e) { data = null; }
                if (data && data.ok) {
                    picker.setApps(data.apps || []);
                } else {
                    picker.setLoading(false);
                    showMessage((data && data.message) || (T.bad_response + ' (HTTP ' + res.status + ')'), true);
                }
            }).catch(function () {
                if (seq !== previewSeq) { return; }
                picker.setLoading(false);
                showMessage(T.network_error, true);
            });
        }

        function onConfChange(confId) {
            message.textContent = '';
            message.className = 'ch-sync-msg';
            if (CFG.mode === 'items') {
                previewSeq++; // scarta eventuali anteprime in volo della conf precedente
                if (picker) { picker.reset(); }
                loadApps(confId);
            } else {
                loadPreview(confId);
            }
        }

        var confList = selectField(T.label_conf, [{ value: '', label: T.choose_conf }].concat(confOptions), onConfChange);
        body.appendChild(confList.node);

        if (CFG.mode === 'items') {
            appList = selectField(T.label_app, [{ value: '', label: T.all_apps }], function (appId) {
                message.textContent = '';
                message.className = 'ch-sync-msg';
                loadPreview(confList.getValue(), appId);
            });
            body.appendChild(appList.node);
            var itemsHint = el('p', T.items_hint + ' ' + T.pick_sheets_hint);
            itemsHint.className = 'ch-sync-hint';
            body.appendChild(itemsHint);
            picker = appPicker(function () { updateButtons(); }, T.already_imported_item);
            body.appendChild(picker.node);
        } else {
            var appsHint = el('p', T.apps_hint + ' ' + T.pick_apps_hint);
            appsHint.className = 'ch-sync-hint';
            body.appendChild(appsHint);
            picker = appPicker(function () { updateButtons(); });
            body.appendChild(picker.node);
        }
        body.appendChild(message);
        dlg.appendChild(body);

        var foot = el('div');
        foot.className = 'modal-footer';
        var cancel = el('button', T.close);
        cancel.type = 'button';
        cancel.className = 'btn btn-default';
        cancel.addEventListener('click', close);
        var start = el('button', T.import_selected.replace(':n', '0'));
        start.type = 'button';
        start.className = 'btn btn-primary';
        foot.appendChild(cancel);
        var importAll = el('button', T.import_all);
        importAll.type = 'button';
        importAll.className = 'btn btn-default';
        foot.appendChild(importAll);
        foot.appendChild(start);
        dlg.appendChild(foot);

        // Il bottone "importa selezionate" resta spento finche' non c'e' almeno un elemento spuntato.
        function updateButtons() {
            if (!picker) { return; }
            var n = picker.selectedIds().length;
            start.textContent = T.import_selected.replace(':n', String(n));
            start.disabled = (n === 0) || busy;
        }
        var busy = false;
        updateButtons();

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

        function setBusy(on) {
            busy = on;
            start.disabled = on;
            if (importAll) { importAll.disabled = on; }
            if (!on) { updateButtons(); }
        }

        // ids: id Qlik scelti (app in modalita' app, fogli in modalita' item); null = tutto.
        function runSync(ids) {
            var confId = confList.getValue();
            if (!confId) { showMessage(T.choose_conf, true); return; }

            var fd = new FormData();
            fd.set('conf_id', confId);
            if (CFG.mode === 'items') { fd.set('app_id', appList ? appList.getValue() : ''); }
            if (ids) { ids.forEach(function (id) { fd.append(CFG.mode === 'items' ? 'sheet_ids[]' : 'app_ids[]', id); }); }

            setBusy(true);
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
                    setBusy(false);
                }
            }).catch(function () {
                showMessage(T.network_error, true);
                setBusy(false);
            });
        }

        start.addEventListener('click', function () {
            var ids = picker ? picker.selectedIds() : [];
            if (!ids.length) { return; }
            runSync(ids);
        });
        if (importAll) {
            importAll.addEventListener('click', function () { runSync(null); });
        }

        overlay.appendChild(dlg);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', onKey);
    }

    window.qlikSyncOpen = open;
})();
