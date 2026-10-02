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
        'badge_block', 'badge_tabs', 'title_ph', 'fit', 'delete', 'confirm_delete', 'drop_here', 'tab_name', 'tab_add', 'tab_delete',
        'tab_default', 'no_blocks', 'field_settings', 'field_where', 'field_nowhere', 'field_width', 'w_full', 'w_full_t', 'w_half_t',
        'w_third_t', 'w_quarter_t', 'field_help', 'field_help_ph', 'field_sys', 'field_remove', 'pool_title', 'pool_help', 'pool_empty',
        'pool_sys', 'sys_note', 'add_title', 'add_block', 'add_block_d', 'add_tabs', 'add_tabs_d', 'mode_edit', 'mode_preview',
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
    .lay-blk { height: 100%; overflow: auto; display: flex; flex-direction: column; background: var(--ch-surface); border: 1px solid var(--ch-border-strong); border-radius: .5rem; }
    .lay-head { display: flex; align-items: center; gap: 6px; padding: 4px 8px; background: var(--ch-bg); border-bottom: 1px solid var(--ch-border-strong); cursor: move; }
    .lay-head .lay-title { flex: 1; min-width: 60px; }
    .lay-body { flex: 1; }
    .lay-title-pm { font-weight: 600; padding: 8px 10px 0; }
    .lay-phone .lay-blk { height: auto; margin-bottom: 10px; }
    .lay-list { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; min-height: 56px; align-content: flex-start; }
    .lay-edit .lay-list { outline: 1px dashed var(--ch-border); outline-offset: -4px; }
    .lay-edit .lay-list:empty::before { content: attr(data-empty); color: var(--ch-text-muted); font-size: .85rem; margin: auto; }
    .lay-fi { position: relative; border-radius: .3rem; }
    .lay-edit .lay-fi:hover { outline: 1px solid var(--ch-blue); background: var(--ch-surface); }
    .lay-tb { position: absolute; top: -4px; right: 0; display: flex; gap: 2px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: .3rem; padding: 0 4px; opacity: 0; z-index: 2; }
    .lay-fi:hover .lay-tb { opacity: 1; }
    .lay-grip { cursor: grab; color: var(--ch-text-muted); }
    .lay-lite { border: 0; background: none; padding: 0 4px; color: var(--ch-text-secondary); cursor: pointer; }
    .lay-lite:hover { color: var(--ch-accent); }
    .lay-pool { display: block; padding: 0; min-height: 36px; }
    .lay-pool:empty::before { content: attr(data-empty); color: var(--ch-text-muted); font-size: .85rem; }
    .lay-pi { display: flex; align-items: center; gap: 6px; background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: .3rem; padding: 3px 8px; font-size: .85rem; margin-bottom: 4px; }
    .lay-phone-frame { max-width: 380px; margin: 0 auto; border: 8px solid var(--ch-border-strong); border-radius: 24px; padding: 8px; background: var(--ch-bg); }
    .lay-ph { border: 1px dashed var(--ch-text-muted); border-radius: .4rem; padding: .4rem; text-align: center; color: var(--ch-text-secondary); font-size: .85rem; }
    #layGrid { min-height: 120px; }
    #layGrid .grid-stack-item-content { overflow: hidden; }
</style>
@endpush

<div class="card card-default">
    <div class="card-header mb-3 with-border">
        <h5 class="card-title">{{ trans('crudbooster.mg_lay_title') }}</h5>
    </div>
    <form id="layoutForm" method="post" action="{{ Route('ModulsControllerPostStep4') }}">
        {{ csrf_field() }}
        <input type="hidden" name="id" value="{{ $id }}">
        <input type="hidden" name="payload" id="payload" value="">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-3" id="laySide"></div>
                <div class="col-lg-9" id="layMain"><div id="layCanvas"></div></div>
            </div>
        </div>
        <div class="card-footer">
            <div class="float-end">
                <a href="{{ CRUDBooster::mainpath('step3') . '/' . $id }}" class="btn btn-secondary mg-nav">&laquo; {{ trans('crudbooster.button_back') }}</a>
                <input type="submit" class="btn btn-primary" value="{{ trans('crudbooster.mg_lay_next') }}">
            </div>
        </div>
    </form>
</div>

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

    var S = {blocks: [], nid: 1, mode: 'edit', pv: 'desktop', grid: null};
    var current = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function field(name) { for (var i = 0; i < FIELDS.length; i++) { if (FIELDS[i].name === name) { return FIELDS[i]; } } return null; }
    function keyOf(it) { return (it.sys ? 's:' : 'f:') + it.name; }
    function blk(id) { for (var i = 0; i < S.blocks.length; i++) { if (S.blocks[i].id === id) { return S.blocks[i]; } } return null; }
    function cleanItems(items) {
        return (items || []).filter(function (it) {
            return it.sys ? SYS.indexOf(it.name) >= 0 : !!field(it.name);
        }).map(function (it) {
            var o = {name: it.name, w: [12, 6, 4, 3].indexOf(parseInt(it.w, 10)) >= 0 ? parseInt(it.w, 10) : 12};
            if (it.sys) { o.sys = 1; }
            return o;
        });
    }
    // layout ricevuto dal server -> stato dell'editor (id propri, campi non piu' validi scartati)
    (LAYOUT.blocks || []).forEach(function (b) {
        var nb = {id: 'b' + (S.nid++), kind: b.kind === 'tabs' ? 'tabs' : 'block', x: parseInt(b.x, 10) || 0, y: parseInt(b.y, 10) || 0, w: parseInt(b.w, 10) || 12, h: parseInt(b.h, 10) || 3, tab: 0};
        if (nb.kind === 'tabs') {
            nb.title = '';
            nb.tabs = (b.tabs || []).map(function (t) { return {id: 't' + (S.nid++), title: t.title || '', fields: cleanItems(t.fields)}; });
            if (!nb.tabs.length) { nb.tabs = [{id: 't' + (S.nid++), title: L.tab_default.replace(':n', 1), fields: []}]; }
        } else {
            nb.title = b.title || '';
            nb.fields = cleanItems(b.fields);
        }
        S.blocks.push(nb);
    });

    function placedKeys() {
        var s = {};
        S.blocks.forEach(function (b) {
            (b.kind === 'tabs' ? b.tabs.reduce(function (a, t) { return a.concat(t.fields); }, []) : b.fields).forEach(function (it) { s[keyOf(it)] = true; });
        });
        return s;
    }
    function locOf(key) {
        for (var i = 0; i < S.blocks.length; i++) {
            var b = S.blocks[i];
            if (b.kind === 'tabs') {
                for (var j = 0; j < b.tabs.length; j++) { if (b.tabs[j].fields.some(function (it) { return keyOf(it) === key; })) { return b.id + ':' + b.tabs[j].id; } }
            } else if (b.fields.some(function (it) { return keyOf(it) === key; })) { return b.id; }
        }
        return '';
    }
    function takeItem(key) {
        var found = null;
        S.blocks.forEach(function (b) {
            var lists = b.kind === 'tabs' ? b.tabs.map(function (t) { return t.fields; }) : [b.fields];
            lists.forEach(function (l) {
                for (var i = l.length - 1; i >= 0; i--) { if (keyOf(l[i]) === key) { found = l.splice(i, 1)[0]; } }
            });
        });
        return found;
    }
    function listFor(loc) {
        var p = loc.split(':'), b = blk(p[0]);
        if (!b) { return null; }
        if (p[1]) { for (var i = 0; i < b.tabs.length; i++) { if (b.tabs[i].id === p[1]) { return b.tabs[i].fields; } } return null; }
        return b.fields;
    }
    function moveItem(key, loc) {
        var it = takeItem(key);
        if (!it && key.indexOf('s:') !== 0 && key.indexOf('f:') !== 0) { return; }
        if (!it) { it = key.indexOf('s:') === 0 ? {name: key.slice(2), w: 12, sys: 1} : {name: key.slice(2), w: 12}; }
        if (!loc) { return; }
        var l = listFor(loc);
        if (l) { l.push(it); }
    }
    function savePos() {
        if (!S.grid) { return; }
        S.grid.engine.nodes.forEach(function (n) {
            var b = blk(n.el.getAttribute('data-bid'));
            if (b) { b.x = n.x; b.y = n.y; b.w = n.w; b.h = n.h; }
        });
    }
    function estH(b) {
        var lists = b.kind === 'tabs' ? b.tabs.map(function (t) { return t.fields; }) : [b.fields];
        var rows = 1;
        lists.forEach(function (l) {
            var r = 0, line = 0;
            l.forEach(function (it) { var u = it.w; if (line + u > 12) { r++; line = u; } else { line += u; } });
            if (line > 0) { r++; }
            rows = Math.max(rows, r);
        });
        return Math.max(2, Math.ceil(((b.kind === 'tabs' ? 110 : 52) + rows * 72 + 16) / 62));
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
        var head = pm ? '' : '<div class="lay-head"><i class="bi bi-grid-3x3-gap-fill"></i><span class="badge text-bg-secondary">' + esc(b.kind === 'tabs' ? L.badge_tabs : L.badge_block) + '</span>'
            + (b.kind === 'tabs' ? '<span class="flex-grow-1"></span>' : '<input class="form-control form-control-sm lay-title" data-bt="' + b.id + '" value="' + esc(b.title) + '" placeholder="' + esc(L.title_ph) + '">')
            + '<button type="button" class="lay-lite" data-act="fit" data-b="' + b.id + '" title="' + esc(L.fit) + '"><i class="bi bi-arrows-vertical"></i></button>'
            + '<button type="button" class="lay-lite text-danger" data-act="delBlk" data-b="' + b.id + '" title="' + esc(L.delete) + '"><i class="bi bi-trash-fill"></i></button></div>';
        var body;
        if (b.kind === 'tabs') {
            if (b.tab >= b.tabs.length) { b.tab = 0; }
            var t = b.tabs[b.tab];
            body = '<ul class="nav nav-tabs px-2 pt-1">' + b.tabs.map(function (x, i) {
                return '<li class="nav-item"><a href="#" class="nav-link py-1 ' + (i === b.tab ? 'active' : '') + '" data-act="tab" data-b="' + b.id + '" data-i="' + i + '" id="lt-' + b.id + '-' + x.id + '">' + esc(x.title || L.tab_default.replace(':n', i + 1)) + '</a></li>';
            }).join('') + (pm ? '' : '<li class="nav-item"><a href="#" class="nav-link py-1" data-act="addTab" data-b="' + b.id + '" title="' + esc(L.tab_add) + '"><i class="bi bi-plus-lg"></i></a></li>') + '</ul>'
                + (pm ? '' : '<div class="d-flex align-items-center gap-2 px-2 pt-2"><span class="small text-muted">' + esc(L.tab_name) + '</span><input class="form-control form-control-sm w-auto" data-tt="' + b.id + ':' + t.id + '" value="' + esc(t.title) + '">'
                    + (b.tabs.length > 1 ? '<button type="button" class="btn btn-sm btn-outline-danger" data-act="delTab" data-b="' + b.id + '" title="' + esc(L.tab_delete) + '"><i class="bi bi-trash-fill"></i></button>' : '') + '</div>')
                + listHtml(t.fields, b.id + ':' + t.id, pm, phone);
        } else {
            body = (pm && b.title ? '<div class="lay-title-pm">' + esc(b.title) + '</div>' : '') + listHtml(b.fields, b.id, pm, phone);
        }
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
            + '<button type="button" class="btn btn-outline-primary btn-sm text-start" data-act="addBlk" data-kind="block"><i class="bi bi-square"></i> <strong>' + esc(L.add_block) + '</strong> <span class="small text-muted">— ' + esc(L.add_block_d) + '</span></button>'
            + '<button type="button" class="btn btn-outline-primary btn-sm text-start" data-act="addBlk" data-kind="tabs"><i class="bi bi-folder"></i> <strong>' + esc(L.add_tabs) + '</strong> <span class="small text-muted">— ' + esc(L.add_tabs_d) + '</span></button></div></div>'
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
    function sortedBlocks() {
        return S.blocks.slice().sort(function (a, b) { return a.y - b.y || a.x - b.x; });
    }
    function draw() {
        if (S.grid) { savePos(); try { S.grid.destroy(false); } catch (e) {} S.grid = null; }
        var pm = S.mode === 'preview', phone = pm && S.pv === 'phone';
        var side = document.getElementById('laySide'), main = document.getElementById('layMain');
        side.className = pm ? 'd-none' : 'col-lg-3';
        main.className = pm ? 'col-12' : 'col-lg-9';
        side.innerHTML = pm ? '' : sideHtml();
        var c = toolbarHtml(pm);
        var sorted = sortedBlocks();
        if (!S.blocks.length) { c += '<div class="lay-ph py-4">' + esc(L.no_blocks) + '</div>'; }
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
    // dopo un trascinamento lo stato si ricostruisce da cio' che c'e' nelle liste del DOM
    // Le liste visibili prendono l'ordine del DOM; le liste non visibili (schede non
    // attive) restano come sono, tranne i campi che nel DOM compaiono altrove.
    function readLists() {
        var all = {};
        S.blocks.forEach(function (b) {
            (b.kind === 'tabs' ? b.tabs.reduce(function (a, t) { return a.concat(t.fields); }, []) : b.fields).forEach(function (it) { all[keyOf(it)] = it; });
        });
        var vis = {}, domKeys = {};
        var keysOf = function (el) { return Array.prototype.map.call(el.querySelectorAll(':scope > [data-key]'), function (n) { return n.getAttribute('data-key'); }); };
        document.querySelectorAll('#layGrid .lay-list').forEach(function (el) {
            var ks = keysOf(el);
            vis[el.getAttribute('data-list')] = ks;
            ks.forEach(function (k) { domKeys[k] = true; });
        });
        document.querySelectorAll('#laySide .lay-list').forEach(function (el) { keysOf(el).forEach(function (k) { domKeys[k] = true; }); });
        S.blocks.forEach(function (b) {
            var lists = b.kind === 'tabs' ? b.tabs.map(function (t) { return {loc: b.id + ':' + t.id, l: t.fields}; }) : [{loc: b.id, l: b.fields}];
            lists.forEach(function (o) {
                var next;
                if (vis[o.loc]) {
                    next = vis[o.loc].map(function (k) { return all[k] || (k.indexOf('s:') === 0 ? {name: k.slice(2), w: 12, sys: 1} : {name: k.slice(2), w: 12}); });
                } else {
                    next = o.l.filter(function (it) { return !domKeys[keyOf(it)]; });
                }
                o.l.length = 0;
                next.forEach(function (it) { o.l.push(it); });
            });
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
        if (act === 'addBlk') {
            savePos();
            var kind = a.getAttribute('data-kind');
            var bottom = S.blocks.reduce(function (m, b) { return Math.max(m, b.y + b.h); }, 0);
            var nb = {id: 'b' + (S.nid++), kind: kind, x: 0, y: bottom, w: kind === 'tabs' ? 12 : 6, h: 3, tab: 0};
            if (kind === 'tabs') {
                nb.title = '';
                nb.tabs = [{id: 't' + (S.nid++), title: L.tab_default.replace(':n', 1), fields: []}, {id: 't' + (S.nid++), title: L.tab_default.replace(':n', 2), fields: []}];
            } else { nb.title = ''; nb.fields = []; }
            nb.h = estH(nb);
            S.blocks.push(nb);
            draw();
        } else if (act === 'delBlk') {
            var b = blk(a.getAttribute('data-b'));
            var n = (b.kind === 'tabs' ? b.tabs.reduce(function (s, t) { return s.concat(t.fields); }, []) : b.fields).length;
            if (n && !confirm(L.confirm_delete.replace(':n', n))) { return; }
            S.blocks = S.blocks.filter(function (x) { return x !== b; });
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
        } else if (act === 'tab') { savePos(); blk(a.getAttribute('data-b')).tab = parseInt(a.getAttribute('data-i'), 10); draw(); }
        else if (act === 'addTab') { savePos(); var tb = blk(a.getAttribute('data-b')); tb.tabs.push({id: 't' + (S.nid++), title: L.tab_default.replace(':n', tb.tabs.length + 1), fields: []}); tb.tab = tb.tabs.length - 1; draw(); }
        else if (act === 'delTab') { savePos(); var db = blk(a.getAttribute('data-b')); db.tabs.splice(db.tab, 1); db.tab = 0; draw(); }
        else if (act === 'fremove') { moveItem(a.getAttribute('data-key'), ''); draw(); }
        else if (act === 'fgear') { openField(a.getAttribute('data-key')); }
    }
    canvas.addEventListener('click', onClick);
    document.getElementById('laySide').addEventListener('click', onClick);
    canvas.addEventListener('input', function (e) {
        var t = e.target;
        if (t.hasAttribute('data-bt')) { blk(t.getAttribute('data-bt')).title = t.value; }
        else if (t.hasAttribute('data-tt')) {
            var p = t.getAttribute('data-tt').split(':'), b = blk(p[0]);
            for (var i = 0; i < b.tabs.length; i++) { if (b.tabs[i].id === p[1]) { b.tabs[i].title = t.value; } }
            var link = document.getElementById('lt-' + p[0] + '-' + p[1]);
            if (link) { link.textContent = t.value || L.tab_default.replace(':n', b.tabs.findIndex(function (x) { return x.id === p[1]; }) + 1); }
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
        S.blocks.forEach(function (b) {
            (b.kind === 'tabs' ? b.tabs.reduce(function (a, t) { return a.concat(t.fields); }, []) : b.fields).forEach(function (it) { if (keyOf(it) === key) { found = it; } });
        });
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
        S.blocks.forEach(function (b) {
            if (b.kind === 'tabs') {
                b.tabs.forEach(function (t, i) { var v = b.id + ':' + t.id; opts += '<option value="' + v + '"' + (cur === v ? ' selected' : '') + '>' + esc(L.badge_tabs) + ' › ' + esc(t.title || L.tab_default.replace(':n', i + 1)) + '</option>'; });
            } else { opts += '<option value="' + b.id + '"' + (cur === b.id ? ' selected' : '') + '>' + esc(L.badge_block) + ': ' + esc(b.title || '—') + '</option>'; }
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
        var blocks = S.blocks.map(function (b) {
            var o = {kind: b.kind, x: b.x, y: b.y, w: b.w, h: b.h};
            if (b.kind === 'tabs') { o.tabs = b.tabs.map(function (t) { return {title: t.title, fields: t.fields}; }); }
            else { o.title = b.title; o.fields = b.fields; }
            return o;
        });
        var help = {};
        FIELDS.forEach(function (f) { help[f.name] = f.help || ''; });
        document.getElementById('payload').value = JSON.stringify({blocks: blocks, help: help});
    });

    draw();
})();
</script>
@endpush

@endsection
