/*
 * Modale di conferma eliminazione per la lista app Qlik, con la casella
 * "elimina anche gli item Qlik collegati" (solo superadmin).
 * Configurazione in window.QLIK_APP_DELETE (scritta da QlikAppController::cbInit,
 * testi gia' tradotti lato server):
 *   itemsOption: bool         mostra la casella
 *   urls:        {linked}     conteggio item collegati
 *   i18n:        testi
 *
 * Intercetta in fase di cattura i click sul cestino di riga (.btn-delete) e
 * sull'azione di gruppo "Elimina selezionati", prima dello swal di default.
 * Se qualcosa non si riesce a leggere dalla pagina non intercetta nulla: resta
 * la conferma standard. Tutto via textContent, nessun innerHTML.
 */
(function () {
    'use strict';

    var overlay = null;

    function el(tag, text, className) {
        var e = document.createElement(tag);
        if (text !== undefined && text !== null) { e.textContent = text; }
        if (className) { e.className = className; }
        return e;
    }

    function close() {
        if (overlay && overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
        overlay = null;
        document.removeEventListener('keydown', onKey);
    }

    function onKey(e) { if (e.key === 'Escape') { close(); } }

    // ids: id delle app da eliminare; onConfirm(deleteItems: bool)
    function openConfirm(ids, onConfirm) {
        var CFG = window.QLIK_APP_DELETE;
        var T = CFG.i18n || {};
        close();

        overlay = el('div', null, 'ch-sync-overlay');
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });

        var dlg = el('div', null, 'ch-sync-dialog ch-sync-dialog--danger');
        dlg.setAttribute('role', 'alertdialog');
        dlg.setAttribute('aria-modal', 'true');

        var head = el('div', null, 'ch-sync-head');
        var title = el('h4', null, 'ch-sync-title');
        var icon = document.createElement('i');
        icon.className = 'bi bi-trash-fill';
        title.appendChild(icon);
        title.appendChild(document.createTextNode(T.title));
        head.appendChild(title);
        var x = el('button', '×', 'ch-sync-x');
        x.type = 'button';
        x.setAttribute('aria-label', T.close);
        x.addEventListener('click', close);
        head.appendChild(x);
        dlg.appendChild(head);

        var body = el('div', null, 'ch-sync-body');
        body.appendChild(el('p', ids.length > 1 ? T.text_many.replace(':n', String(ids.length)) : T.text_one, 'ch-confirm-text'));

        var box = null;
        var count = null;
        if (CFG.itemsOption) {
            var option = el('label', null, 'ch-confirm-option');
            box = document.createElement('input');
            box.type = 'checkbox';
            box.disabled = true;
            option.appendChild(box);
            var optText = el('span', null, 'ch-confirm-option-text');
            optText.appendChild(el('strong', T.items_label));
            count = el('span', '', 'ch-confirm-option-count');
            optText.appendChild(count);
            option.appendChild(optText);
            body.appendChild(option);
            body.appendChild(el('p', T.items_hint, 'ch-sync-hint'));
        }
        dlg.appendChild(body);

        var foot = el('div', null, 'modal-footer');
        var cancel = el('button', T.cancel, 'btn btn-secondary');
        cancel.type = 'button';
        cancel.addEventListener('click', close);
        var ok = el('button', T.confirm, 'btn btn-danger');
        ok.type = 'button';
        ok.addEventListener('click', function () {
            ok.disabled = true;
            var withItems = !!(box && box.checked);
            close();
            onConfirm(withItems);
        });
        foot.appendChild(cancel);
        foot.appendChild(ok);
        dlg.appendChild(foot);

        overlay.appendChild(dlg);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', onKey);
        ok.focus();

        // Conteggio item collegati: la casella si abilita solo se ce ne sono.
        if (box) {
            fetch(CFG.urls.linked + '?ids=' + encodeURIComponent(ids.join(',')), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                var total = (data && data.total) || 0;
                if (total > 0) {
                    count.textContent = T.items_count.replace(':n', String(total));
                    box.disabled = false;
                } else {
                    count.textContent = T.items_none;
                }
            }).catch(function () {
                // Senza conteggio si lascia la casella usabile: il server decide.
                box.disabled = false;
            });
        }
    }

    // Riga: l'id e l'URL di eliminazione sono nell'onclick generato da CRUDBooster::deleteConfirm().
    function rowDeleteInfo(link) {
        var js = link.getAttribute('onclick') || '';
        var m = js.match(/location\.href\s*=\s*"([^"]*\/delete\/(\d+))"/);
        return m ? { url: m[1], id: m[2] } : null;
    }

    document.addEventListener('click', function (e) {
        if (!window.QLIK_APP_DELETE || !e.target || !e.target.closest) { return; }

        var link = e.target.closest('#table_dashboard a.btn-delete');
        if (link) {
            var info = rowDeleteInfo(link);
            if (!info) { return; }
            e.preventDefault();
            e.stopPropagation();
            openConfirm([info.id], function (withItems) {
                location.href = info.url + (withItems ? (info.url.indexOf('?') === -1 ? '?' : '&') + 'delete_items=1' : '');
            });
            return;
        }

        var bulk = e.target.closest('.selected-action ul li a');
        if (bulk && bulk.getAttribute('data-name') === 'delete') {
            var form = document.getElementById('form-table');
            if (!form) { return; }
            var checked = form.querySelectorAll('input[name="checkbox[]"]:checked');
            if (!checked.length) { return; } // lascia la gestione standard (nessuna selezione)
            var ids = Array.prototype.map.call(checked, function (c) { return c.value; });
            e.preventDefault();
            e.stopPropagation();
            openConfirm(ids, function (withItems) {
                var name = form.querySelector('input[name="button_name"]');
                if (name) { name.value = 'delete'; }
                if (withItems) {
                    var flag = document.createElement('input');
                    flag.type = 'hidden';
                    flag.name = 'delete_items';
                    flag.value = '1';
                    form.appendChild(flag);
                }
                form.submit();
            });
        }
    }, true);
})();
