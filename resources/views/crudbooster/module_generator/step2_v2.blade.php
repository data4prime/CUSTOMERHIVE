@extends("crudbooster::module_generator.template")
@section("inner_content")

@php
    // Tipi di campo: nomi/descrizioni da trans() (array mg_field_types), solo quelli presenti su disco.
    $typeTexts = trans('crudbooster.mg_field_types');
    $typeTexts = is_array($typeTexts) ? $typeTexts : [];
    $groupDefs = [
        'text' => ['text', 'textarea', 'email', 'password', 'multitext', 'hidden'],
        'formatted' => ['tinymce', 'wysiwyg', 'json'],
        'numbers' => ['number', 'money', 'percent'],
        'dates' => ['date', 'datetime', 'time'],
        'choices' => ['select', 'select2', 'radio', 'checkbox', 'datamodal'],
        'files' => ['upload', 'image', 'filemanager', 'color', 'googlemaps'],
        'special' => ['child', 'custom'],
        'ch' => ['group_items_datamodal', 'group_members_datamodal', 'group_tenant_datamodal', 'item_access_datamodal', 'item_tenant_datamodal', 'tenant_group_datamodal', 'user_groups_datamodal'],
    ];
    $groups = [];
    $listed = [];
    foreach ($groupDefs as $g => $items) {
        $items = array_values(array_intersect($items, $type_names));
        if ($items) {
            $groups[] = ['label' => trans('crudbooster.mg_fld_group_' . $g), 'items' => $items];
            $listed = array_merge($listed, $items);
        }
    }
    $others = array_values(array_diff($type_names, $listed, ['header']));
    if ($others) {
        $groups[] = ['label' => trans('crudbooster.mg_fld_group_other'), 'items' => $others];
    }
    $typeInfo = [];
    foreach (array_unique(array_merge($type_names, ['header'])) as $t) {
        $x = $typeTexts[$t] ?? [$t, '', ''];
        $typeInfo[$t] = ['l' => $x[0], 'd' => $x[1] ?? '', 'e' => $x[2] ?? ''];
    }
    $labelKeys = [
        'label', 'type', 'required', 'advanced', 'remove_existing', 'remove_new', 'in_module', 'badge_existing', 'badge_new',
        'badge_nocolumn', 'badge_system', 'column', 'not_in_module', 'db_keeps', 'db_info', 'size', 'size_locked',
        'column_info', 'choices_title', 'src_enum', 'src_table', 'src_query', 'enum_add', 'enum_hint', 'table_which', 'table_col',
        'table_filter', 'table_note', 'query_help', 'modal_title', 'modal_cols', 'modal_size', 'size_large', 'size_small',
        'file_which', 'file_file', 'file_image', 'img_shape', 'img_circle', 'img_square', 'img_size', 'maps_lat', 'maps_lng', 'maps_none', 'html', 'child_note', 'rules_title',
        'rules_none', 'rule_min_len', 'rule_max_len', 'rule_min_val', 'rule_max_val', 'rule_alpha', 'rule_alpha_any',
        'rule_alpha_letters', 'rule_alpha_alnum', 'rule_unique', 'rule_unique_desc', 'rule_date', 'rule_date_any',
        'rule_date_nopast', 'rule_date_nofuture', 'rule_file', 'rule_file_any', 'rule_file_image', 'rule_file_doc',
        'rule_max_mb', 'rule_extra', 'money_title', 'currency', 'currency_none', 'decimals', 'decimals_hint', 'decimals_hint_percent',
        'drop', 'drop_title', 'drop_body', 'drop_type', 'drop_confirm', 'dropped', 'drop_undo',
    ];
    $L = [];
    foreach ($labelKeys as $k) {
        $L[$k] = trans('crudbooster.mg_fld_' . $k);
    }
    $L['choose'] = trans('crudbooster.mg_list_choose');
    $jf = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
@endphp

@push('head')
<style>
    .fld-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); padding: .7rem .75rem; margin-bottom: .5rem; }
    .fld-card.nocol { background: var(--ch-bg); border-style: dashed; }
    .fld-card.off { background: var(--ch-bg); }
    .fld-card.off .handle, .fld-card.off .fld-dim { opacity: .55; }
    .fld-card .handle { cursor: grab; color: var(--ch-text-muted); }
    .fld-wl { font-size: .7rem; color: var(--ch-text-secondary); display: block; margin-bottom: 2px; }
    .fld-panel { background: var(--ch-bg); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); padding: .6rem .75rem; margin-bottom: .75rem; }
    .fld-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--ch-accent); margin-left: 4px; }
</style>
@endpush

<form id="fieldsForm" method="post" action="{{ Route('ModulsControllerPostStep2') }}">
    <div class="card card-default">
    <div class="card-header mb-3 with-border">
        <h5 class="card-title">{{ trans('crudbooster.mg_fld_title') }}</h5>
        <div class="small text-muted">{{ trans('crudbooster.mg_fld_subtitle') }}</div>
    </div>
        {{ csrf_field() }}
        <input type="hidden" name="id" value="{{ $id }}">
        <input type="hidden" name="payload" id="payload" value="">
        <div class="card-body">
            @if($table_exists)
            <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle-fill"></i> {{ trans('crudbooster.mg_fld_alert_existing', ['table' => $table_name]) }}</div>
            @else
            <div class="alert alert-info py-2 small"><i class="bi bi-info-circle-fill"></i> {{ trans('crudbooster.mg_fld_alert_new') }}</div>
            @endif
            <div id="fldList"></div>
            <button type="button" class="btn btn-outline-primary" id="fldAdd"><i class="bi bi-plus-lg"></i> {{ trans('crudbooster.mg_fld_add') }}</button>
        </div>
    </div>

    @include('crudbooster::module_generator._nav', [
        'nav_back' => CRUDBooster::mainpath('step1') . '/' . $id,
        'nav_back_ajax' => true,
        'nav_next' => trans('crudbooster.mg_nav_next'),
    ])
</form>

<div class="modal fade" id="fldAdvModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="fldAdvTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="fldAdvBody"></div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('crudbooster.mg_list_done') }}</button></div>
    </div></div>
</div>

<div class="modal fade" id="fldDropModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill"></i> {{ trans('crudbooster.mg_fld_drop_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p>{!! trans('crudbooster.mg_fld_drop_body', ['name' => '<code id="fldDropName"></code>']) !!}</p>
            <label class="small" for="fldDropInput">{{ trans('crudbooster.mg_fld_drop_type') }}</label>
            <input class="form-control" id="fldDropInput" autocomplete="off">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('crudbooster.confirmation_no') }}</button>
            <button type="button" class="btn btn-danger" id="fldDropOk" disabled>{{ trans('crudbooster.mg_fld_drop_confirm') }}</button>
        </div>
    </div></div>
</div>

@push('bottom')
<script>
(function () {
    var ROWS = {!! json_encode($rows, $jf) !!};
    var TYPES = {!! json_encode($typeInfo, $jf) !!};
    var GROUPS = {!! json_encode($groups, $jf) !!};
    var TABLES = {!! json_encode(array_values($table_list), $jf) !!};
    var RESERVED = {!! json_encode(array_values((array) config('app.reserved_column_names')), $jf) !!};
    var COLUMNS_URL = {!! json_encode(CRUDBooster::mainpath('table-columns'), $jf) !!};
    var L = {!! json_encode($L, $jf) !!};
    var CURRENCIES = {!! json_encode(\App\Helpers\NumberFormat::currencyOptions(), $jf) !!};

    var NOCOL = ['header', 'child', 'custom', 'googlemaps', 'group_items_datamodal', 'group_members_datamodal', 'group_tenant_datamodal', 'item_access_datamodal', 'item_tenant_datamodal', 'tenant_group_datamodal', 'user_groups_datamodal'];
    var CHOICE = ['select', 'select2', 'radio', 'checkbox'];
    var MODAL = ['datamodal', 'group_items_datamodal', 'group_members_datamodal', 'group_tenant_datamodal', 'item_access_datamodal', 'item_tenant_datamodal', 'tenant_group_datamodal', 'user_groups_datamodal'];
    var SQL_BY_TYPE = {!! json_encode(\App\Helpers\ModuleGeneratorFields::SQL_BY_TYPE, $jf) !!};
    var TABLE_CHOICE = {!! json_encode(\App\Helpers\ModuleGeneratorFields::TABLE_CHOICE, $jf) !!};
    var TEXTISH = ['text', 'textarea', 'password', 'email', 'multitext'];
    var NUMBERISH = ['number', 'money', 'percent'];
    var seq = 0;
    var current = null;
    var colsCache = {};

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function slug(s) {
        return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }
    function byKey(k) {
        for (var i = 0; i < ROWS.length; i++) { if (String(ROWS[i]._k) === String(k)) { return ROWS[i]; } }
        return null;
    }
    function hasColumn(t) { return NOCOL.indexOf(t) < 0; }
    // famiglia (testo/numero) con cui getTableStructure() descrive una colonna esistente
    function dbFamily(t) { return (t === 'number' || t === 'money') ? 'number' : 'text'; }
    // colonna creata per un campo nuovo: [tipo, dimensione di default] (stessa logica di ModuleGeneratorFields)
    function sqlSpec(r) {
        if (TABLE_CHOICE.indexOf(r.type) >= 0 && r.opts && r.opts.source === 'table') { return ['number', '11']; }
        if ((r.type === 'money' || r.type === 'percent') && r.opts && r.opts.decimals !== '' && /^\d+$/.test(String(r.opts.decimals))) {
            var d = Math.min(6, parseInt(r.opts.decimals, 10));
            return ['decimal', (r.type === 'money' ? Math.max(12, d + 8) : Math.max(5, d + 3)) + ',' + d];
        }
        return SQL_BY_TYPE[r.type] || ['text', '255'];
    }
    function hasSize(r) { var k = sqlSpec(r)[0]; return k === 'text' || k === 'number'; }
    function sqlLabel(r) {
        var s = sqlSpec(r), n = (hasSize(r) && r.size) ? r.size : s[1];
        return {text: 'VARCHAR(' + n + ')', number: 'INT(' + n + ')', plaintext: 'TEXT', longtext: 'LONGTEXT', decimal: 'DECIMAL(' + s[1] + ')',
            date: 'DATE', datetime: 'DATETIME', time: 'TIME'}[s[0]];
    }
    function emptyOpts() {
        return {source: 'enum', enum: [''], table: '', column: '', where: '', query: '', mtable: '', mcols: [], msize: 'large', mwhere: '', ftype: 'file', lat: '', lng: '', html: '', shape: 'circle', isize: '96', currency: 'EUR', decimals: '2'};
    }
    function emptyRules() {
        return {min: '', max: '', unique: false, unique_raw: '', alpha: '', datePolicy: '', fileKind: 'any', maxMb: '', extra: []};
    }
    function typeLabel(t) { return TYPES[t] ? TYPES[t].l : t; }

    ROWS.forEach(function (r) {
        r._k = ++seq;
        r.opts = Object.assign(emptyOpts(), r.opts || {});
        r.rules = Object.assign(emptyRules(), r.rules || {});
        if (!Array.isArray(r.opts.enum) || !r.opts.enum.length) { r.opts.enum = ['']; }
        if (!Array.isArray(r.opts.mcols)) { r.opts.mcols = []; }
        r.size = r.size || '';
        r.no_column = !hasColumn(r.type);
    });

    function usedNames() {
        var u = {};
        ROWS.forEach(function (r) { if (r.name) { u[r.name] = true; } });
        return u;
    }
    // nome colonna di un campo nuovo: generato dall'etichetta, mai modificabile
    function autoName(r) {
        var base = slug(r.label);
        if (!base) { return ''; }
        if (/^\d/.test(base)) { base = 'f_' + base; }
        base = base.slice(0, 60);
        var used = usedNames();
        delete used[r.name];
        var n = base, i = 2;
        while (used[n] || RESERVED.indexOf(n) >= 0) { n = base + '_' + (i++); }
        return n;
    }

    /* ---------- elenco campi ---------- */
    function typeSelect(r) {
        var known = false;
        var h = GROUPS.map(function (g) {
            return '<optgroup label="' + esc(g.label) + '">' + g.items.map(function (t) {
                if (t === r.type) { known = true; }
                return '<option value="' + t + '"' + (t === r.type ? ' selected' : '') + '>' + esc(typeLabel(t)) + '</option>';
            }).join('') + '</optgroup>';
        }).join('');
        if (!known) { h = '<option value="' + esc(r.type) + '" selected>' + esc(typeLabel(r.type)) + '</option>' + h; }
        return '<select class="form-select" data-k="' + r._k + '" data-f="type">' + h + '</select>';
    }
    function hasAdv(r) {
        var d = emptyRules(), x = r.rules;
        return !!r.size || x.min !== '' || x.max !== '' || x.unique || x.alpha !== '' || x.datePolicy !== '' || x.fileKind !== 'any' || x.maxMb !== '' || x.extra.length > 0;
    }
    function dbText(r) {
        if (!r.db) { return ''; }
        return r.db.type + (r.db.size ? '(' + r.db.size + ')' : '');
    }
    // colonna reale gia' presente nel database e non di sistema: si puo' eliminare del tutto
    function canDrop(r) { return r.exists && !r.is_new && !r.system && hasColumn(r.type); }
    function dropBtn(r) {
        return '<button type="button" class="btn btn-sm btn-outline-danger" data-act="drop" data-k="' + r._k + '" title="' + esc(L.drop) + '"><i class="bi bi-database-x"></i></button>';
    }
    function rowHtml(r) {
        var k = r._k, nc = !hasColumn(r.type);
        if (r.drop) {
            return '<div class="fld-card off" data-k="' + k + '"><div class="d-flex align-items-center gap-2">'
                + '<i class="bi bi-database-x text-danger"></i>'
                + '<div class="flex-grow-1"><span class="fw-semibold text-decoration-line-through">' + esc(r.label) + '</span> <code>' + esc(r.name) + '</code> <span class="badge text-bg-danger">' + esc(L.dropped) + '</span></div>'
                + '<button type="button" class="btn btn-sm btn-outline-secondary" data-act="undrop" data-k="' + k + '"><i class="bi bi-arrow-counterclockwise"></i> ' + esc(L.drop_undo) + '</button></div></div>';
        }
        if (!r.in_module) {
            return '<div class="fld-card off" data-k="' + k + '"><div class="d-flex align-items-center gap-2">'
                + '<i class="bi bi-grip-vertical handle"></i>'
                + '<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-k="' + k + '" data-f="in_module" title="' + esc(L.in_module) + '"></div>'
                + '<div class="flex-grow-1 fld-dim"><span class="fw-semibold">' + esc(r.label) + '</span> <code>' + esc(r.name) + '</code> <span class="badge text-bg-light border">' + esc(L.not_in_module) + '</span>'
                + (r.db ? ' <span class="small text-muted">' + esc(L.db_info.replace(':type', dbText(r))) + '</span>' : '') + '</div>'
                + (canDrop(r) ? dropBtn(r) : '') + '</div></div>';
        }
        var badge = nc ? '<span class="badge text-bg-light border">' + esc(L.badge_nocolumn) + '</span>'
            : (r.exists ? '<span class="badge text-bg-secondary">' + esc(r.system ? L.badge_system : L.badge_existing) + '</span>' : '<span class="badge text-bg-success">' + esc(L.badge_new) + '</span>');
        var h = '<div class="fld-card ' + (nc ? 'nocol' : '') + '" data-k="' + k + '"><div class="row g-2 align-items-start">'
            + '<div class="col-auto pt-4"><i class="bi bi-grip-vertical handle"></i></div>';
        if (!r.is_new) {
            h += '<div class="col-auto pt-4"><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" checked data-k="' + k + '" data-f="in_module" title="' + esc(L.in_module) + '"></div></div>';
        }
        h += '<div class="col-md-3"><span class="fld-wl">' + esc(L.label) + '</span>'
            + '<input class="form-control" data-k="' + k + '" data-f="label" value="' + esc(r.label) + '">'
            + '<div class="small text-muted mt-1">' + (nc ? '' : '<code id="fnm-' + k + '">' + esc(r.name || '…') + '</code> ') + badge + '</div></div>'
            + '<div class="col-md-4"><span class="fld-wl">' + esc(L.type) + '</span>' + typeSelect(r)
            + '<div class="small text-muted mt-1">' + esc(TYPES[r.type] ? TYPES[r.type].d : '') + (TYPES[r.type] && TYPES[r.type].e ? ' — <em>' + esc(TYPES[r.type].e) + '</em>' : '') + '</div>';
        if (!nc && r.exists && r.db && dbFamily(r.type) !== r.db.type) {
            h += '<div class="small text-warning-emphasis mt-1"><i class="bi bi-info-circle-fill"></i> ' + esc(L.db_keeps.replace(':type', dbText(r))) + '</div>';
        }
        if (!nc && !r.exists && !hasSize(r)) {
            h += '<div class="small text-muted mt-1"><i class="bi bi-info-circle-fill"></i> ' + esc(L.db_info.replace(':type', sqlLabel(r))) + '</div>';
        }
        h += '</div>';
        h += '<div class="col-auto pt-4">' + (r.type === 'hidden' || r.type === 'header' || nc ? '' : '<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" id="rq' + k + '" data-k="' + k + '" data-f="required"' + (r.required ? ' checked' : '') + '><label class="form-check-label" for="rq' + k + '">' + esc(L.required) + '</label></div>') + '</div>';
        h += '<div class="col-auto ms-md-auto pt-3 text-nowrap">'
            + '<button type="button" class="btn btn-sm btn-outline-secondary" data-act="adv" data-k="' + k + '"><i class="bi bi-sliders"></i> ' + esc(L.advanced) + (hasAdv(r) ? '<span class="fld-dot"></span>' : '') + '</button> '
            + (canDrop(r) ? dropBtn(r) + ' ' : '')
            + '<button type="button" class="btn btn-sm btn-outline-danger" data-act="del" data-k="' + k + '" title="' + esc(r.is_new ? L.remove_new : L.remove_existing) + '"><i class="bi bi-trash-fill"></i></button></div>';
        return h + '</div></div>';
    }
    function renderList() {
        document.getElementById('fldList').innerHTML = ROWS.map(rowHtml).join('');
    }

    var listEl = document.getElementById('fldList');
    listEl.addEventListener('input', function (e) {
        var t = e.target, f = t.getAttribute('data-f');
        if (f !== 'label') { return; }
        var r = byKey(t.getAttribute('data-k'));
        r.label = t.value;
        if (r.is_new && hasColumn(r.type)) {
            r.name = autoName(r);
            var el = document.getElementById('fnm-' + r._k);
            if (el) { el.textContent = r.name || '…'; }
        }
    });
    listEl.addEventListener('change', function (e) {
        var t = e.target, f = t.getAttribute('data-f');
        if (!f) { return; }
        var r = byKey(t.getAttribute('data-k'));
        if (f === 'in_module') { r.in_module = t.checked; renderList(); }
        else if (f === 'required') { r.required = t.checked; }
        else if (f === 'type') {
            r.type = t.value;
            r.no_column = !hasColumn(r.type);
            r.rules = Object.assign(emptyRules(), {extra: r.rules.extra});
            if (r.is_new) { r.name = autoName(r); }
            renderList();
        }
    });
    listEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) { return; }
        var r = byKey(b.getAttribute('data-k')), act = b.getAttribute('data-act');
        if (act === 'del') {
            if (r.is_new) { ROWS = ROWS.filter(function (x) { return x !== r; }); } else { r.in_module = false; }
            renderList();
        } else if (act === 'adv') { openAdv(r); }
        else if (act === 'drop') { openDrop(r); }
        else if (act === 'undrop') { r.drop = false; renderList(); }
    });

    /* ---------- eliminazione definitiva di una colonna ---------- */
    var dropModal = new bootstrap.Modal(document.getElementById('fldDropModal'));
    var dropRow = null;
    var dropInput = document.getElementById('fldDropInput');
    var dropOk = document.getElementById('fldDropOk');
    function openDrop(r) {
        dropRow = r;
        document.getElementById('fldDropName').textContent = r.name;
        dropInput.value = '';
        dropOk.disabled = true;
        dropModal.show();
    }
    // si conferma scrivendo il nome della colonna: l'eliminazione cancella anche i dati
    dropInput.addEventListener('input', function () { dropOk.disabled = !dropRow || dropInput.value.trim() !== dropRow.name; });
    dropOk.addEventListener('click', function () {
        if (!dropRow || dropInput.value.trim() !== dropRow.name) { return; }
        dropRow.drop = true;
        dropRow.in_module = false;
        dropModal.hide();
        renderList();
    });
    document.getElementById('fldAdd').addEventListener('click', function () {
        var r = {_k: ++seq, name: '', label: '', type: 'text', required: false, in_module: true, exists: false, no_column: false, db: null, size: '',
                 opts: emptyOpts(), rules: emptyRules(), system: false, is_new: true};
        ROWS.push(r);
        renderList();
        var inp = document.querySelector('.fld-card[data-k="' + r._k + '"] input[data-f="label"]');
        if (inp) { inp.focus(); inp.scrollIntoView({block: 'center'}); }
    });
    if (window.jQuery && jQuery.fn.sortable) {
        jQuery('#fldList').sortable({
            handle: '.handle', items: '> .fld-card', tolerance: 'pointer',
            stop: function () {
                var keys = Array.prototype.map.call(listEl.querySelectorAll(':scope > .fld-card'), function (n) { return n.getAttribute('data-k'); });
                ROWS = keys.map(byKey);
            }
        });
    }

    /* ---------- modale Avanzate ---------- */
    var advModal = new bootstrap.Modal(document.getElementById('fldAdvModal'));
    function loadCols(table, cb) {
        if (!table) { cb([]); return; }
        if (colsCache[table]) { cb(colsCache[table]); return; }
        fetch(COLUMNS_URL + '/' + encodeURIComponent(table), {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (resp) { return resp.json(); })
            .then(function (cols) { colsCache[table] = cols; cb(cols); });
    }
    function tableOptions(sel) {
        return '<option value="">' + esc(L.choose) + '</option>' + TABLES.map(function (t) {
            return '<option value="' + esc(t) + '"' + (t === sel ? ' selected' : '') + '>' + esc(t) + '</option>';
        }).join('');
    }
    function optionsPanel(r) {
        var o = r.opts, t = r.type, k = r._k;
        if (CHOICE.indexOf(t) >= 0) {
            var seg = [['enum', L.src_enum], ['table', L.src_table], ['query', L.src_query]].map(function (s) {
                return '<input type="radio" class="btn-check" name="src" id="src-' + s[0] + '" data-o="source" value="' + s[0] + '"' + (o.source === s[0] ? ' checked' : '') + '>'
                    + '<label class="btn btn-outline-primary btn-sm" for="src-' + s[0] + '">' + esc(s[1]) + '</label>';
            }).join('');
            var body = '';
            if (o.source === 'enum') {
                body = o.enum.map(function (v, i) {
                    return '<div class="input-group input-group-sm mb-1"><span class="input-group-text">' + (i + 1) + '</span><input class="form-control" data-enum="' + i + '" value="' + esc(v) + '">'
                        + '<button type="button" class="btn btn-outline-danger" data-act="enum-del" data-i="' + i + '"><i class="bi bi-trash-fill"></i></button></div>';
                }).join('') + '<button type="button" class="btn btn-sm btn-link px-0" data-act="enum-add"><i class="bi bi-plus-lg"></i> ' + esc(L.enum_add) + '</button>'
                    + '<div class="small text-muted">' + esc(L.enum_hint) + '</div>';
            } else if (o.source === 'table') {
                body = '<div class="row g-2"><div class="col-md-5"><label class="small">' + esc(L.table_which) + '</label><select class="form-select form-select-sm" data-o="table">' + tableOptions(o.table) + '</select></div>'
                    + '<div class="col-md-4"><label class="small">' + esc(L.table_col) + '</label><select class="form-select form-select-sm" id="fldColSel" data-o="column"><option value="">…</option></select></div>'
                    + '<div class="col-md-3"><label class="small">' + esc(L.table_filter) + '</label><input class="form-control form-control-sm" data-o="where" value="' + esc(o.where) + '"></div></div>'
                    + '<div class="small text-muted mt-1">' + esc(L.table_note) + '</div>';
            } else {
                body = '<textarea class="form-control form-control-sm" rows="2" data-o="query">' + esc(o.query) + '</textarea><div class="small text-muted mt-1"><i class="bi bi-lock-fill"></i> ' + esc(L.query_help) + '</div>';
            }
            return '<div class="fld-panel"><div class="small fw-semibold mb-1">' + esc(L.choices_title) + '</div><div class="btn-group mb-2">' + seg + '</div>' + body + '</div>';
        }
        if (MODAL.indexOf(t) >= 0) {
            return '<div class="fld-panel"><div class="small fw-semibold mb-1">' + esc(L.modal_title) + '</div><div class="row g-2">'
                + '<div class="col-md-4"><select class="form-select form-select-sm" data-o="mtable">' + tableOptions(o.mtable) + '</select></div>'
                + '<div class="col-md-5"><label class="small d-block">' + esc(L.modal_cols) + '</label><div id="fldModalCols"></div></div>'
                + '<div class="col-md-3"><label class="small d-block">' + esc(L.modal_size) + '</label><div class="btn-group btn-group-sm">'
                + '<input type="radio" class="btn-check" name="msize" id="ms-l" data-o="msize" value="large"' + (o.msize === 'large' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="ms-l">' + esc(L.size_large) + '</label>'
                + '<input type="radio" class="btn-check" name="msize" id="ms-s" data-o="msize" value="small"' + (o.msize === 'small' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="ms-s">' + esc(L.size_small) + '</label></div></div>'
                + '<div class="col-12"><label class="small">' + esc(L.table_filter) + '</label><input class="form-control form-control-sm" data-o="mwhere" value="' + esc(o.mwhere) + '"></div></div></div>';
        }
        if (t === 'filemanager') {
            return '<div class="fld-panel"><span class="small fw-semibold me-2">' + esc(L.file_which) + '</span><div class="btn-group btn-group-sm">'
                + '<input type="radio" class="btn-check" name="ftype" id="ft-f" data-o="ftype" value="file"' + (o.ftype === 'file' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="ft-f">' + esc(L.file_file) + '</label>'
                + '<input type="radio" class="btn-check" name="ftype" id="ft-i" data-o="ftype" value="image"' + (o.ftype === 'image' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="ft-i">' + esc(L.file_image) + '</label></div></div>';
        }
        if (t === 'image') {
            return '<div class="fld-panel"><div class="row g-2 align-items-end"><div class="col-md-6"><span class="small fw-semibold me-2">' + esc(L.img_shape) + '</span><div class="btn-group btn-group-sm">'
                + '<input type="radio" class="btn-check" name="ishape" id="is-c" data-o="shape" value="circle"' + (o.shape !== 'square' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="is-c">' + esc(L.img_circle) + '</label>'
                + '<input type="radio" class="btn-check" name="ishape" id="is-s" data-o="shape" value="square"' + (o.shape === 'square' ? ' checked' : '') + '><label class="btn btn-outline-secondary" for="is-s">' + esc(L.img_square) + '</label></div></div>'
                + '<div class="col-md-6"><label class="small">' + esc(L.img_size) + '</label><input type="number" min="48" max="240" class="form-control form-control-sm" data-o="isize" value="' + esc(o.isize) + '"></div></div></div>';
        }
        if (t === 'money' || t === 'percent') {
            var cur = '';
            if (t === 'money') {
                cur = '<div class="col-md-8"><label class="small">' + esc(L.currency) + '</label><select class="form-select form-select-sm" data-o="currency">'
                    + '<option value=""' + (o.currency === '' ? ' selected' : '') + '>' + esc(L.currency_none) + '</option>'
                    + Object.keys(CURRENCIES).map(function (c) { return '<option value="' + c + '"' + (o.currency === c ? ' selected' : '') + '>' + esc(CURRENCIES[c]) + '</option>'; }).join('')
                    + '</select></div>';
            }
            return '<div class="fld-panel"><div class="small fw-semibold mb-1">' + esc(L.money_title) + '</div><div class="row g-2">' + cur
                + '<div class="col-md-4"><label class="small">' + esc(L.decimals) + '</label><input type="number" min="0" max="6" class="form-control form-control-sm" data-o="decimals" value="' + esc(o.decimals) + '"></div></div>'
                + '<div class="small text-muted mt-1">' + esc(t === 'money' ? L.decimals_hint : L.decimals_hint_percent) + '</div></div>';
        }
        if (t === 'googlemaps') {
            var sel = function (key) {
                return '<select class="form-select form-select-sm" data-o="' + key + '"><option value="">' + esc(L.maps_none) + '</option>' + ROWS.filter(function (x) { return x !== r && hasColumn(x.type) && x.in_module; }).map(function (x) {
                    return '<option value="' + esc(x.name) + '"' + (o[key] === x.name ? ' selected' : '') + '>' + esc(x.label || x.name) + '</option>';
                }).join('') + '</select>';
            };
            return '<div class="fld-panel"><div class="row g-2"><div class="col-md-6"><label class="small">' + esc(L.maps_lat) + '</label>' + sel('lat') + '</div><div class="col-md-6"><label class="small">' + esc(L.maps_lng) + '</label>' + sel('lng') + '</div></div></div>';
        }
        if (t === 'custom') {
            return '<div class="fld-panel"><label class="small">' + esc(L.html) + '</label><textarea class="form-control form-control-sm" rows="3" data-o="html">' + esc(o.html) + '</textarea></div>';
        }
        if (t === 'child') {
            return '<div class="small text-muted mb-3"><i class="bi bi-info-circle-fill"></i> ' + esc(L.child_note) + '</div>';
        }
        return '';
    }
    function rulesPanel(r) {
        var t = r.type, x = r.rules;
        var inp = function (key, lab) { return '<div class="col-6"><label class="small">' + esc(lab) + '</label><input class="form-control form-control-sm" data-r="' + key + '" value="' + esc(x[key]) + '"></div>'; };
        var sel = function (key, lab, items) {
            return '<div class="col-12"><label class="small">' + esc(lab) + '</label><select class="form-select form-select-sm" data-r="' + key + '">' + items.map(function (i) {
                return '<option value="' + i[0] + '"' + (x[key] === i[0] ? ' selected' : '') + '>' + esc(i[1]) + '</option>';
            }).join('') + '</select></div>';
        };
        var h = '', none = true;
        if (TEXTISH.indexOf(t) >= 0) {
            none = false;
            h += inp('min', L.rule_min_len) + inp('max', L.rule_max_len);
            if (t !== 'email' && t !== 'password') { h += sel('alpha', L.rule_alpha, [['', L.rule_alpha_any], ['letters', L.rule_alpha_letters], ['alnum', L.rule_alpha_alnum]]); }
            if (t === 'text' || t === 'email') {
                h += '<div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="rUnique" data-r="unique"' + (x.unique ? ' checked' : '') + '><label class="form-check-label" for="rUnique">' + esc(L.rule_unique) + '<div class="small text-muted">' + esc(L.rule_unique_desc) + '</div></label></div></div>';
            }
        } else if (NUMBERISH.indexOf(t) >= 0) {
            none = false;
            h += inp('min', L.rule_min_val) + inp('max', L.rule_max_val);
        } else if (t === 'date' || t === 'datetime') {
            none = false;
            h += sel('datePolicy', L.rule_date, [['', L.rule_date_any], ['nopast', L.rule_date_nopast], ['nofuture', L.rule_date_nofuture]]);
        } else if (t === 'upload') {
            none = false;
            h += '<div class="col-6"><label class="small">' + esc(L.rule_file) + '</label><select class="form-select form-select-sm" data-r="fileKind">'
                + [['any', L.rule_file_any], ['image', L.rule_file_image], ['doc', L.rule_file_doc]].map(function (i) { return '<option value="' + i[0] + '"' + (x.fileKind === i[0] ? ' selected' : '') + '>' + esc(i[1]) + '</option>'; }).join('') + '</select></div>'
                + inp('maxMb', L.rule_max_mb);
        } else if (t === 'image') {
            // sempre e solo immagini: resta la dimensione massima
            none = false;
            h += inp('maxMb', L.rule_max_mb);
        }
        if (none) { h = '<div class="col-12 small text-muted">' + esc(L.rules_none) + '</div>'; }
        var extra = x.extra.length ? '<div class="small text-muted mt-1"><i class="bi bi-info-circle-fill"></i> ' + esc(L.rule_extra.replace(':list', x.extra.join(', '))) + '</div>' : '';
        return '<div class="fw-semibold mb-2">' + esc(L.rules_title) + '</div><div class="row g-2 mb-1">' + h + '</div>' + extra;
    }
    function advBody(r) {
        var h = optionsPanel(r);
        if (hasColumn(r.type)) {
            h += '<div class="small text-muted mb-2">' + esc(L.column_info.replace(':name', r.name || '…')) + '</div>';
            if (r.exists) {
                h += '<div class="small text-muted mb-3">' + esc(L.db_info.replace(':type', dbText(r))) + ' · ' + esc(L.size_locked) + '</div>';
            } else if (hasSize(r)) {
                h += '<div class="mb-3"><label class="small">' + esc(L.size) + '</label><input class="form-control form-control-sm" style="max-width:160px" data-s="size" value="' + esc(r.size) + '" placeholder="' + sqlSpec(r)[1] + '"></div>';
            } else {
                h += '<div class="small text-muted mb-3">' + esc(L.db_info.replace(':type', sqlLabel(r))) + '</div>';
            }
        }
        return h + rulesPanel(r);
    }
    function fillAsync(r) {
        var o = r.opts;
        if (CHOICE.indexOf(r.type) >= 0 && o.source === 'table') {
            loadCols(o.table, function (cols) {
                var sel = document.getElementById('fldColSel');
                if (!sel) { return; }
                sel.innerHTML = '<option value="">' + esc(L.choose) + '</option>' + cols.map(function (c) {
                    return '<option value="' + esc(c) + '"' + (c === o.column ? ' selected' : '') + '>' + esc(c) + '</option>';
                }).join('');
            });
        }
        if (MODAL.indexOf(r.type) >= 0) {
            loadCols(o.mtable, function (cols) {
                var box = document.getElementById('fldModalCols');
                if (!box) { return; }
                box.innerHTML = cols.map(function (c, i) {
                    return '<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="mc' + i + '" data-mcol="' + esc(c) + '"' + (o.mcols.indexOf(c) >= 0 ? ' checked' : '') + '><label class="form-check-label small" for="mc' + i + '">' + esc(c) + '</label></div>';
                }).join('');
            });
        }
    }
    function refreshAdv() {
        document.getElementById('fldAdvBody').innerHTML = advBody(current);
        fillAsync(current);
    }
    function openAdv(r) {
        current = r;
        document.getElementById('fldAdvTitle').textContent = (r.label || r.name) + ' — ' + typeLabel(r.type);
        refreshAdv();
        advModal.show();
    }
    var advBodyEl = document.getElementById('fldAdvBody');
    advBodyEl.addEventListener('input', function (e) {
        var t = e.target;
        if (!current) { return; }
        if (t.hasAttribute('data-enum')) { current.opts.enum[parseInt(t.getAttribute('data-enum'), 10)] = t.value; }
        else if (t.hasAttribute('data-s')) { current.size = t.value.replace(/\D/g, ''); }
        else if (t.hasAttribute('data-r') && t.type !== 'checkbox' && t.tagName !== 'SELECT') { current.rules[t.getAttribute('data-r')] = t.value; }
        else if (t.hasAttribute('data-o') && t.type !== 'radio' && t.tagName !== 'SELECT') { current.opts[t.getAttribute('data-o')] = t.value; }
    });
    advBodyEl.addEventListener('change', function (e) {
        var t = e.target;
        if (!current) { return; }
        if (t.hasAttribute('data-mcol')) {
            var c = t.getAttribute('data-mcol'), a = current.opts.mcols, i = a.indexOf(c);
            if (t.checked && i < 0) { a.push(c); }
            if (!t.checked && i >= 0) { a.splice(i, 1); }
        } else if (t.hasAttribute('data-r')) {
            current.rules[t.getAttribute('data-r')] = t.type === 'checkbox' ? t.checked : t.value;
        } else if (t.hasAttribute('data-o')) {
            var key = t.getAttribute('data-o');
            current.opts[key] = t.value;
            if (key === 'source') { refreshAdv(); }
            if (key === 'table') { current.opts.column = ''; refreshAdv(); }
            if (key === 'mtable') { current.opts.mcols = []; refreshAdv(); }
        }
    });
    advBodyEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b || !current) { return; }
        var act = b.getAttribute('data-act');
        if (act === 'enum-add') { current.opts.enum.push(''); refreshAdv(); var ins = advBodyEl.querySelectorAll('[data-enum]'); if (ins.length) { ins[ins.length - 1].focus(); } }
        else if (act === 'enum-del') { current.opts.enum.splice(parseInt(b.getAttribute('data-i'), 10), 1); if (!current.opts.enum.length) { current.opts.enum = ['']; } refreshAdv(); }
    });
    // current non si azzera alla chiusura: se si apre subito un altro campo, l'evento
    // di chiusura della modale precedente (arriva dopo l'animazione) non deve cancellarlo.
    document.getElementById('fldAdvModal').addEventListener('hidden.bs.modal', function () { renderList(); });

    /* ---------- invio ---------- */
    document.getElementById('fieldsForm').addEventListener('submit', function () {
        var rows = ROWS.map(function (r) {
            var c = JSON.parse(JSON.stringify(r));
            delete c._k;
            return c;
        });
        document.getElementById('payload').value = JSON.stringify({rows: rows});
    });

    renderList();
})();
</script>
@endpush

@endsection
