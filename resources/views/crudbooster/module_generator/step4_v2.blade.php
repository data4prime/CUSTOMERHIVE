@extends("crudbooster::module_generator.template")
@section("inner_content")

@php
    $texts = is_array($type_texts) ? $type_texts : [];
    $typeLabels = [];
    foreach ($fields as $f) {
        $typeLabels[$f['type']] = $texts[$f['type']][0] ?? $f['type'];
    }
    $sysLabels = [];
    foreach ($system_fields as $s) {
        $sysLabels[$s] = trans('crudbooster.mg_lay_sys_' . $s);
    }
    $labelKeys = [
        'badge_block', 'title_ph', 'fit', 'delete', 'confirm_delete', 'drop_here', 'tab_name', 'tab_add', 'tab_delete',
        'tab_default', 'confirm_delete_tab', 'no_blocks', 'field_settings', 'field_where', 'field_nowhere', 'field_width', 'w_full', 'w_full_t', 'w_half_t',
        'w_third_t', 'w_quarter_t', 'field_help', 'field_help_ph', 'field_sys', 'field_remove', 'pool_title', 'pool_help', 'pool_empty',
        'pool_sys', 'sys_note', 'add_title', 'add_block', 'add_block_d', 'mode_edit', 'mode_preview',
        'pv_desktop', 'pv_phone', 'hint',
    ];
    $L = [];
    foreach ($labelKeys as $k) {
        $L[$k] = trans('crudbooster.mg_lay_' . $k);
    }
    $L['done'] = trans('crudbooster.mg_list_done');
    $L['required'] = trans('crudbooster.mg_fld_required');
    $jf = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
@endphp

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/libs/gridstack/gridstack.min.css') }}">
<style>
    .lay-blk { height: 100%; overflow: auto; display: flex; flex-direction: column; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); }
    .lay-head { display: flex; align-items: center; gap: 6px; padding: 4px 8px; background: var(--ch-bg); border-bottom: 1px solid var(--ch-border-strong); cursor: move; }
    .lay-head .lay-title { flex: 1; min-width: 60px; }
    .lay-body { flex: 1; }
    .lay-title-pm { font-weight: 600; padding: 8px 10px 0; }
    .lay-phone .lay-blk { height: auto; margin-bottom: 10px; }
    .lay-list { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; min-height: 56px; align-content: flex-start; }
    .lay-edit .lay-list { outline: 1px dashed var(--ch-border); outline-offset: -4px; min-height: 140px; }
    /* in modifica l'area di rilascio occupa tutto il blocco, non solo lo spazio sotto l'ultimo campo */
    .lay-edit .lay-body { display: flex; flex-direction: column; min-height: 0; }
    .lay-edit .lay-body > .lay-list { flex: 1 1 auto; }
    .lay-edit .lay-list:empty::before { content: attr(data-empty); color: var(--ch-text-muted); font-size: .85rem; margin: auto; }
    .lay-fi { position: relative; border-radius: var(--ch-radius-sm); }
    .lay-edit .lay-fi:hover { outline: 1px solid var(--ch-blue); background: var(--ch-surface); }
    .lay-tb { position: absolute; top: -4px; right: 0; display: flex; gap: 2px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-sm); padding: 0 4px; opacity: 0; z-index: 2; }
    .lay-fi:hover .lay-tb { opacity: 1; }
    .lay-grip { cursor: grab; color: var(--ch-text-muted); }
    .lay-lite { border: 0; background: none; padding: 0 4px; color: var(--ch-text-secondary); cursor: pointer; }
    .lay-lite:hover { color: var(--ch-accent); }
    .lay-pool { display: block; padding: 0; min-height: 36px; }
    .lay-pool:empty::before { content: attr(data-empty); color: var(--ch-text-muted); font-size: .85rem; }
    .lay-pi { display: flex; align-items: center; gap: 6px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-sm); padding: 3px 8px; font-size: .85rem; margin-bottom: 4px; }
    .lay-phone-frame { max-width: 380px; margin: 0 auto; border: 8px solid var(--ch-border-strong); border-radius: 24px; padding: 8px; background: var(--ch-bg); }
    .lay-ph { border: 1px dashed var(--ch-text-muted); border-radius: var(--ch-radius-md); padding: .4rem; text-align: center; color: var(--ch-text-secondary); font-size: .85rem; }
    .lay-tabs .nav-link { cursor: pointer; }
    #layGrid { min-height: 120px; }
    #layGrid .grid-stack-item-content { overflow: hidden; }
</style>
@endpush

<form id="layoutForm" method="post" action="{{ Route('ModulsControllerPostStep4') }}">
    <div class="card card-default">
    <div class="card-header mb-3 with-border">
        <h5 class="card-title">{{ trans('crudbooster.mg_lay_title') }}</h5>
    </div>
        {{ csrf_field() }}
        <input type="hidden" name="id" value="{{ $id }}">
        <input type="hidden" name="payload" id="payload" value="">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-3" id="laySide"></div>
                <div class="col-lg-9" id="layMain"><div id="layCanvas"></div></div>
            </div>
        </div>
    </div>

    @include('crudbooster::module_generator._nav', [
        'nav_back' => CRUDBooster::mainpath('step3') . '/' . $id,
        'nav_back_ajax' => true,
        'nav_next' => trans('crudbooster.mg_nav_next'),
    ])
</form>

<div class="modal fade" id="layFieldModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="layFieldTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="layFieldBody"></div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('crudbooster.mg_list_done') }}</button></div>
    </div></div>
</div>

@push('bottom')
<script src="{{ asset('vendor/libs/gridstack/gridstack-all.js') }}"></script>
<script>
(function () {
    var FIELDS = {!! json_encode(array_values($fields), $jf) !!};
    var SYS = {!! json_encode(array_values($system_fields), $jf) !!};
    var SYS_LABELS = {!! json_encode($sysLabels, $jf) !!};
    var TYPE_LABELS = {!! json_encode($typeLabels, $jf) !!};
    var LAYOUT = {!! json_encode($layout, $jf) !!};
    var L = {!! json_encode($L, $jf) !!};
    var WIDTHS = [[12, L.w_full, L.w_full_t], [6, '½', L.w_half_t], [4, '⅓', L.w_third_t], [3, '¼', L.w_quarter_t]];

    // Struttura: schede (S.tabs) -> blocchi (tab.blocks) -> campi (block.fields). S.cur = scheda aperta.
    var S = {tabs: [], cur: 0, nid: 1, mode: 'edit', pv: 'desktop', grid: null};
    var current = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function field(name) { for (var i = 0; i < FIELDS.length; i++) { if (FIELDS[i].name === name) { return FIELDS[i]; } } return null; }
    function keyOf(it) { return (it.sys ? 's:' : 'f:') + it.name; }
    function allBlocks() { return S.tabs.reduce(function (a, t) { return a.concat(t.blocks); }, []); }
    function blk(id) { var all = allBlocks(); for (var i = 0; i < all.length; i++) { if (all[i].id === id) { return all[i]; } } return null; }
    function tabLabel(t, i) { return t.title || L.tab_default.replace(':n', i + 1); }
    function curTab() { if (S.cur >= S.tabs.length) { S.cur = 0; } return S.tabs[S.cur]; }
    function cleanItems(items) {
        return (items || []).filter(function (it) {
            return it.sys ? SYS.indexOf(it.name) >= 0 : !!field(it.name);
        }).map(function (it) {
            var o = {name: it.name, w: [12, 6, 4, 3].indexOf(parseInt(it.w, 10)) >= 0 ? parseInt(it.w, 10) : 12};
            if (it.sys) { o.sys = 1; }
            return o;
        });
    }
    function newTab(title) { return {id: 't' + (S.nid++), title: title, blocks: []}; }
    // layout ricevuto dal server -> stato dell'editor (id propri, campi non piu' validi scartati)
    (LAYOUT.tabs || []).forEach(function (t) {
        var nt = newTab(t.title || '');
        (t.blocks || []).forEach(function (b) {
            nt.blocks.push({id: 'b' + (S.nid++), title: b.title || '', x: parseInt(b.x, 10) || 0, y: parseInt(b.y, 10) || 0, w: parseInt(b.w, 10) || 12, h: parseInt(b.h, 10) || 3, fields: cleanItems(b.fields)});
        });
        S.tabs.push(nt);
    });
    if (!S.tabs.length) { S.tabs.push(newTab('')); }

    function placedKeys() {
        var s = {};
        allBlocks().forEach(function (b) { b.fields.forEach(function (it) { s[keyOf(it)] = true; }); });
        return s;
    }
    function locOf(key) {
        var all = allBlocks();
        for (var i = 0; i < all.length; i++) { if (all[i].fields.some(function (it) { return keyOf(it) === key; })) { return all[i].id; } }
        return '';
    }
    function takeItem(key) {
        var found = null;
        allBlocks().forEach(function (b) {
            for (var i = b.fields.length - 1; i >= 0; i--) { if (keyOf(b.fields[i]) === key) { found = b.fields.splice(i, 1)[0]; } }
        });
        return found;
    }
    function moveItem(key, loc) {
        var it = takeItem(key);
        if (!it && key.indexOf('s:') !== 0 && key.indexOf('f:') !== 0) { return; }
        if (!it) { it = key.indexOf('s:') === 0 ? {name: key.slice(2), w: 12, sys: 1} : {name: key.slice(2), w: 12}; }
        if (!loc) { return; }
        var b = blk(loc);
        if (b) { b.fields.push(it); }
    }
    function savePos() {
        if (!S.grid) { return; }
        S.grid.engine.nodes.forEach(function (n) {
            var b = blk(n.el.getAttribute('data-bid'));
            if (b) { b.x = n.x; b.y = n.y; b.w = n.w; b.h = n.h; }
        });
    }
    function estH(b) {
        var r = 0, line = 0;
        b.fields.forEach(function (it) { var u = it.w; if (line + u > 12) { r++; line = u; } else { line += u; } });
        if (line > 0) { r++; }
        return Math.max(2, Math.ceil((52 + Math.max(1, r) * 72 + 16) / 62));
    }

    /* ---------- disegno ---------- */
    function ctl(f) {
        var t = f.type, d = ' disabled';
        if (['text', 'email', 'password', 'multitext'].indexOf(t) >= 0) { return '<input class="form-control form-control-sm"' + d + '>'; }
        if (['textarea', 'ckeditor', 'tinymce', 'wysiwyg', 'json'].indexOf(t) >= 0) { return '<textarea class="form-control form-control-sm" rows="2"' + d + '></textarea>'; }
        if (['number', 'money', 'percent'].indexOf(t) >= 0) { return '<input type="number" class="form-control form-control-sm"' + d + '>'; }
        if (t === 'date') { return '<input type="date" class="form-control form-control-sm"' + d + '>'; }
        if (t === 'datetime') { return '<input type="datetime-local" class="form-control form-control-sm"' + d + '>'; }
        if (t === 'time') { return '<input type="time" class="form-control form-control-sm"' + d + '>'; }
        if (['select', 'select2', 'datamodal'].indexOf(t) >= 0) { return '<select class="form-select form-select-sm"' + d + '><option></option></select>'; }
        if (t === 'radio' || t === 'checkbox') { return '<div class="small text-muted">○ ○ ○</div>'; }
        if (t === 'upload' || t === 'filemanager') { return '<input type="file" class="form-control form-control-sm"' + d + '>'; }
        if (t === 'color') { return '<input type="color" class="form-control form-control-sm form-control-color" value="#0d6efd"' + d + '>'; }
        return '<div class="lay-ph">' + esc(TYPE_LABELS[t] || t) + '</div>';
    }
    function fiHtml(it, pm, phone) {
        var w = phone ? 100 : it.w * 100 / 12;
        var st = w >= 100 ? 'flex:0 0 100%' : 'flex:0 0 calc(' + w + '% - 8px);max-width:calc(' + w + '% - 8px)';
        var key = keyOf(it);
        var tb = pm ? '' : '<div class="lay-tb"><i class="bi bi-grip-vertical lay-grip" title=""></i>'
            + '<button type="button" class="lay-lite" data-act="fgear" data-key="' + esc(key) + '" title="' + esc(L.field_settings) + '"><i class="bi bi-gear-fill"></i></button>'
            + '<button type="button" class="lay-lite" data-act="fremove" data-key="' + esc(key) + '" title="' + esc(L.field_remove) + '"><i class="bi bi-x-lg"></i></button></div>';
        var body;
        if (it.sys) {
            body = '<label class="form-label small mb-0">' + esc(SYS_LABELS[it.name] || it.name) + ' <i class="bi bi-lock-fill text-muted"></i></label><input class="form-control form-control-sm" disabled>';
        } else {
            var f = field(it.name);
            body = '<label class="form-label small mb-0">' + esc(f.label) + (f.required ? ' <span class="text-danger">*</span>' : '') + '</label>' + ctl(f) + (f.help ? '<div class="form-text">' + esc(f.help) + '</div>' : '');
        }
        return '<div class="lay-fi" data-key="' + esc(key) + '" style="' + st + '">' + tb + body + '</div>';
    }
    function listHtml(items, loc, pm, phone) {
        return '<div class="lay-list" ' + (pm ? '' : 'data-list="' + loc + '" data-empty="' + esc(L.drop_here) + '"') + '>' + items.map(function (it) { return fiHtml(it, pm, phone); }).join('') + '</div>';
    }
    function blockHtml(b, pm, phone) {
        var head = pm ? '' : '<div class="lay-head"><i class="bi bi-grid-3x3-gap-fill"></i><span class="badge text-bg-secondary">' + esc(L.badge_block) + '</span>'
            + '<input class="form-control form-control-sm lay-title" data-bt="' + b.id + '" value="' + esc(b.title) + '" placeholder="' + esc(L.title_ph) + '">'
            + '<button type="button" class="lay-lite" data-act="fit" data-b="' + b.id + '" title="' + esc(L.fit) + '"><i class="bi bi-arrows-vertical"></i></button>'
            + '<button type="button" class="lay-lite text-danger" data-act="delBlk" data-b="' + b.id + '" title="' + esc(L.delete) + '"><i class="bi bi-trash-fill"></i></button></div>';
        var body = (pm && b.title ? '<div class="lay-title-pm">' + esc(b.title) + '</div>' : '') + listHtml(b.fields, b.id, pm, phone);
        return '<div class="lay-blk">' + head + '<div class="lay-body">' + body + '</div></div>';
    }
    function poolItem(label, sub, key, icon) {
        return '<div class="lay-pi" data-key="' + esc(key) + '"><i class="bi bi-grip-vertical lay-grip"></i><span class="flex-grow-1">' + esc(label) + '</span>' + icon + '<small class="text-muted">' + esc(sub) + '</small></div>';
    }
    function sideHtml() {
        var placed = placedKeys();
        var norm = FIELDS.filter(function (f) { return !placed['f:' + f.name]; }).map(function (f) {
            return poolItem(f.label + (f.required ? ' *' : ''), TYPE_LABELS[f.type] || f.type, 'f:' + f.name, '');
        }).join('');
        var sys = SYS.filter(function (s) { return !placed['s:' + s]; }).map(function (s) { return poolItem(SYS_LABELS[s] || s, '', 's:' + s, '<i class="bi bi-lock-fill text-muted"></i>'); }).join('');
        return '<div class="card mb-3"><div class="card-header fw-semibold">' + esc(L.add_title) + '</div><div class="card-body d-grid gap-2">'
            + '<button type="button" class="btn btn-outline-primary btn-sm text-start" data-act="addBlk"><i class="bi bi-square"></i> <strong>' + esc(L.add_block) + '</strong> <span class="small text-muted">— ' + esc(L.add_block_d) + '</span></button></div></div>'
            + '<div class="card"><div class="card-header fw-semibold">' + esc(L.pool_title) + '</div><div class="card-body">'
            + '<div class="small text-muted mb-2">' + esc(L.pool_help) + '</div>'
            + '<div class="lay-list lay-pool" data-list="pool" data-empty="' + esc(L.pool_empty) + '">' + norm + '</div>'
            + '<div class="small fw-semibold mt-3 mb-1"><i class="bi bi-lock-fill"></i> ' + esc(L.pool_sys) + '</div>'
            + '<div class="lay-list lay-pool" data-list="pool-sys" data-empty="—">' + sys + '</div>'
            + '<div class="small text-muted mt-3"><i class="bi bi-info-circle-fill"></i> ' + esc(L.sys_note) + '</div></div></div>';
    }
    function toolbarHtml(pm) {
        var rad = function (name, attr, cur, items) {
            return '<div class="btn-group btn-group-sm">' + items.map(function (i) {
                return '<input type="radio" class="btn-check" name="' + name + '" id="' + name + '-' + i[0] + '" ' + attr + ' value="' + i[0] + '"' + (cur === i[0] ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="' + name + '-' + i[0] + '"><i class="bi ' + i[2] + '"></i> ' + esc(i[1]) + '</label>';
            }).join('') + '</div>';
        };
        return '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">'
            + rad('laymode', 'data-lmode="1"', S.mode, [['edit', L.mode_edit, 'bi-pencil-fill'], ['preview', L.mode_preview, 'bi-eye-fill']])
            + (pm ? rad('laypv', 'data-lpv="1"', S.pv, [['desktop', L.pv_desktop, 'bi-display'], ['phone', L.pv_phone, 'bi-phone']]) : '<div class="small text-muted"><i class="bi bi-arrows-move"></i> ' + esc(L.hint) + '</div>') + '</div>';
    }
    // barra delle schede in alto (navigazione orizzontale); in modifica ha anche "+" e il nome della scheda aperta
    function tabsHtml(pm) {
        var t = curTab();
        var nav = '<ul class="nav nav-tabs lay-tabs mb-2">' + S.tabs.map(function (x, i) {
            return '<li class="nav-item"><a href="#" class="nav-link ' + (i === S.cur ? 'active' : '') + '" data-act="tab" data-i="' + i + '" id="lt-' + x.id + '">' + esc(tabLabel(x, i)) + '</a></li>';
        }).join('') + (pm ? '' : '<li class="nav-item"><a href="#" class="nav-link" data-act="addTab" title="' + esc(L.tab_add) + '"><i class="bi bi-plus-lg"></i></a></li>') + '</ul>';
        if (pm) { return nav; }
        return nav + '<div class="d-flex align-items-center gap-2 mb-2"><span class="small text-muted">' + esc(L.tab_name) + '</span><input class="form-control form-control-sm w-auto" data-tt="' + t.id + '" value="' + esc(t.title) + '">'
            + (S.tabs.length > 1 ? '<button type="button" class="btn btn-sm btn-outline-danger" data-act="delTab" title="' + esc(L.tab_delete) + '"><i class="bi bi-trash-fill"></i></button>' : '') + '</div>';
    }
    function sortedBlocks(t) {
        return t.blocks.slice().sort(function (a, b) { return a.y - b.y || a.x - b.x; });
    }
    function draw() {
        if (S.grid) { savePos(); try { S.grid.destroy(false); } catch (e) {} S.grid = null; }
        var pm = S.mode === 'preview', phone = pm && S.pv === 'phone';
        var side = document.getElementById('laySide'), main = document.getElementById('layMain');
        side.className = pm ? 'd-none' : 'col-lg-3';
        main.className = pm ? 'col-12' : 'col-lg-9';
        side.innerHTML = pm ? '' : sideHtml();
        var t = curTab();
        var c = toolbarHtml(pm) + tabsHtml(pm);
        var sorted = sortedBlocks(t);
        if (!t.blocks.length) { c += '<div class="lay-ph py-4">' + esc(L.no_blocks) + '</div>'; }
        else if (phone) { c += '<div class="lay-phone lay-phone-frame">' + sorted.map(function (b) { return blockHtml(b, true, true); }).join('') + '</div>'; }
        else {
            c += '<div class="grid-stack ' + (pm ? '' : 'lay-edit') + '" id="layGrid">' + sorted.map(function (b) {
                return '<div class="grid-stack-item" gs-x="' + b.x + '" gs-y="' + b.y + '" gs-w="' + b.w + '" gs-h="' + b.h + '" gs-min-w="2" gs-min-h="2" data-bid="' + b.id + '"><div class="grid-stack-item-content">' + blockHtml(b, pm, false) + '</div></div>';
            }).join('') + '</div>';
        }
        document.getElementById('layCanvas').innerHTML = c;
        var el = document.getElementById('layGrid');
        if (el) {
            S.grid = GridStack.init({column: 12, cellHeight: 56, margin: 6, float: false, disableOneColumnMode: true, handle: '.lay-head', draggable: {handle: '.lay-head'}, resizable: {handles: 'e,se,s,sw,w'}, animate: true, staticGrid: pm}, el);
            S.grid.on('change', savePos);
        }
        if (!pm && window.jQuery && jQuery.fn.sortable) {
            jQuery('#laySide .lay-list, #layGrid .lay-list').sortable({
                connectWith: '#laySide .lay-list, #layGrid .lay-list', handle: '.lay-grip', items: '> [data-key]', tolerance: 'pointer',
                stop: function () { readLists(); }
            });
        }
    }
    // dopo un trascinamento lo stato si ricostruisce da cio' che c'e' nelle liste del DOM.
    // Si vedono solo i blocchi della scheda aperta: le liste delle altre schede restano
    // come sono, tranne i campi che nel DOM compaiono altrove.
    function readLists() {
        var all = {};
        allBlocks().forEach(function (b) { b.fields.forEach(function (it) { all[keyOf(it)] = it; }); });
        var vis = {}, domKeys = {};
        var keysOf = function (el) { return Array.prototype.map.call(el.querySelectorAll(':scope > [data-key]'), function (n) { return n.getAttribute('data-key'); }); };
        document.querySelectorAll('#layGrid .lay-list').forEach(function (el) {
            var ks = keysOf(el);
            vis[el.getAttribute('data-list')] = ks;
            ks.forEach(function (k) { domKeys[k] = true; });
        });
        document.querySelectorAll('#laySide .lay-list').forEach(function (el) { keysOf(el).forEach(function (k) { domKeys[k] = true; }); });
        allBlocks().forEach(function (b) {
            var next;
            if (vis[b.id]) {
                next = vis[b.id].map(function (k) { return all[k] || (k.indexOf('s:') === 0 ? {name: k.slice(2), w: 12, sys: 1} : {name: k.slice(2), w: 12}); });
            } else {
                next = b.fields.filter(function (it) { return !domKeys[keyOf(it)]; });
            }
            b.fields.length = 0;
            next.forEach(function (it) { b.fields.push(it); });
        });
        setTimeout(draw, 0);
    }

    /* ---------- eventi ---------- */
    var canvas = document.getElementById('layCanvas');
    function onClick(e) {
        var a = e.target.closest('[data-act]');
        if (!a) { return; }
        e.preventDefault();
        var act = a.getAttribute('data-act');
        var t = curTab();
        if (act === 'addBlk') {
            savePos();
            var bottom = t.blocks.reduce(function (m, b) { return Math.max(m, b.y + b.h); }, 0);
            var nb = {id: 'b' + (S.nid++), title: '', x: 0, y: bottom, w: 12, h: 5, fields: []};
            nb.h = estH(nb);
            t.blocks.push(nb);
            draw();
        } else if (act === 'delBlk') {
            var b = blk(a.getAttribute('data-b'));
            var n = b.fields.length;
            if (n && !confirm(L.confirm_delete.replace(':n', n))) { return; }
            t.blocks = t.blocks.filter(function (x) { return x !== b; });
            draw();
        } else if (act === 'fit') {
            var item = document.querySelector('.grid-stack-item[data-bid="' + a.getAttribute('data-b') + '"]');
            if (item && S.grid) {
                var inner = item.querySelector('.lay-blk');
                inner.style.height = 'auto';
                var sh = inner.scrollHeight;
                inner.style.height = '';
                S.grid.update(item, {h: Math.max(2, Math.ceil((sh + 6) / 62))});
                savePos();
            }
        } else if (act === 'tab') { savePos(); S.cur = parseInt(a.getAttribute('data-i'), 10); draw(); }
        else if (act === 'addTab') { savePos(); S.tabs.push(newTab('')); S.cur = S.tabs.length - 1; draw(); }
        else if (act === 'delTab') {
            var n2 = t.blocks.reduce(function (s, x) { return s + x.fields.length; }, 0);
            if (n2 && !confirm(L.confirm_delete_tab.replace(':n', n2))) { return; }
            savePos();
            S.tabs.splice(S.cur, 1);
            S.cur = Math.max(0, S.cur - 1);
            draw();
        }
        else if (act === 'fremove') { moveItem(a.getAttribute('data-key'), ''); draw(); }
        else if (act === 'fgear') { openField(a.getAttribute('data-key')); }
    }
    canvas.addEventListener('click', onClick);
    document.getElementById('laySide').addEventListener('click', onClick);
    canvas.addEventListener('input', function (e) {
        var t = e.target;
        if (t.hasAttribute('data-bt')) { blk(t.getAttribute('data-bt')).title = t.value; }
        else if (t.hasAttribute('data-tt')) {
            var id = t.getAttribute('data-tt');
            S.tabs.forEach(function (x, i) {
                if (x.id === id) {
                    x.title = t.value;
                    var link = document.getElementById('lt-' + id);
                    if (link) { link.textContent = tabLabel(x, i); }
                }
            });
        }
    });
    canvas.addEventListener('change', function (e) {
        var t = e.target;
        if (t.hasAttribute('data-lmode')) { savePos(); S.mode = t.value; draw(); }
        else if (t.hasAttribute('data-lpv')) { S.pv = t.value; draw(); }
    });

    /* ---------- modale del campo ---------- */
    var fieldModal = new bootstrap.Modal(document.getElementById('layFieldModal'));
    function findItem(key) {
        var found = null;
        allBlocks().forEach(function (b) { b.fields.forEach(function (it) { if (keyOf(it) === key) { found = it; } }); });
        return found;
    }
    function openField(key) {
        var it = findItem(key);
        if (!it) { return; }
        current = key;
        var f = it.sys ? null : field(it.name);
        document.getElementById('layFieldTitle').textContent = it.sys ? (SYS_LABELS[it.name] || it.name) : f.label;
        var cur = locOf(key);
        var opts = '<option value=""' + (cur === '' ? ' selected' : '') + '>' + esc(L.field_nowhere) + '</option>';
        S.tabs.forEach(function (t, ti) {
            var inner = t.blocks.map(function (b) { return '<option value="' + b.id + '"' + (cur === b.id ? ' selected' : '') + '>' + esc(b.title || '—') + '</option>'; }).join('');
            if (inner) { opts += '<optgroup label="' + esc(tabLabel(t, ti)) + '">' + inner + '</optgroup>'; }
        });
        document.getElementById('layFieldBody').innerHTML =
            '<div class="mb-3"><label class="small fw-semibold">' + esc(L.field_where) + '</label><select class="form-select form-select-sm" data-fw="loc">' + opts + '</select></div>'
            + '<div class="mb-3"><label class="small fw-semibold d-block">' + esc(L.field_width) + '</label><div class="btn-group btn-group-sm">' + WIDTHS.map(function (w) {
                return '<input type="radio" class="btn-check" name="fwid" id="fwid-' + w[0] + '" data-fw="w" value="' + w[0] + '"' + (it.w === w[0] ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="fwid-' + w[0] + '" title="' + esc(w[2]) + '">' + esc(w[1]) + '</label>';
            }).join('') + '</div></div>'
            + (it.sys ? '<div class="small text-muted"><i class="bi bi-lock-fill"></i> ' + esc(L.field_sys) + '</div>'
                : '<div><label class="small fw-semibold">' + esc(L.field_help) + '</label><input class="form-control form-control-sm" data-fw="help" value="' + esc(f.help) + '" placeholder="' + esc(L.field_help_ph) + '"></div>');
        fieldModal.show();
    }
    var fbody = document.getElementById('layFieldBody');
    fbody.addEventListener('input', function (e) {
        if (e.target.getAttribute('data-fw') === 'help' && current) { var f = field(current.slice(2)); if (f) { f.help = e.target.value; } }
    });
    fbody.addEventListener('change', function (e) {
        var k = e.target.getAttribute('data-fw');
        if (!current) { return; }
        // il layout si ridisegna subito: il DOM e lo stato restano sempre allineati
        if (k === 'w') { var it = findItem(current); if (it) { it.w = parseInt(e.target.value, 10); savePos(); draw(); } }
        else if (k === 'loc') { savePos(); moveItem(current, e.target.value); draw(); }
    });
    // current non si azzera alla chiusura: se si apre subito un altro campo, l'evento
    // di chiusura della modale precedente (arriva dopo l'animazione) non deve cancellarlo.
    document.getElementById('layFieldModal').addEventListener('hidden.bs.modal', function () { draw(); });

    /* ---------- invio ---------- */
    document.getElementById('layoutForm').addEventListener('submit', function () {
        savePos();
        var tabs = S.tabs.map(function (t) {
            return {title: t.title, blocks: t.blocks.map(function (b) { return {title: b.title, x: b.x, y: b.y, w: b.w, h: b.h, fields: b.fields}; })};
        });
        var help = {};
        FIELDS.forEach(function (f) { help[f.name] = f.help || ''; });
        document.getElementById('payload').value = JSON.stringify({tabs: tabs, help: help});
    });

    draw();
})();
</script>
@endpush

@endsection
