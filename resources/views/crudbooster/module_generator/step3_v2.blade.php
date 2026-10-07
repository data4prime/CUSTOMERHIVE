@extends("crudbooster::module_generator.template")
@section("inner_content")

@php
    // Testi per il JavaScript: tutti da trans(), mai scritti in chiaro nel JS.
    $labelKeys = [
        'title_ph', 'w_auto', 'w_narrow', 'w_medium', 'w_wide', 'w_custom', 'label_format', 'label_width',
        'fmt_raw', 'fmt_date_short', 'fmt_date_long', 'fmt_datetime_short', 'fmt_money', 'fmt_money_eur', 'fmt_money_plain',
        'label_currency', 'label_decimals', 'currency_none',
        'fmt_badge', 'fmt_trunc', 'fmt_image', 'fmt_download', 'trunc_first', 'trunc_chars',
        'btn_colors', 'btn_join', 'btn_expr', 'badge_calc', 'badge_system', 'badge_advanced', 'system_note',
        'legacy_note', 'join_set', 'expr_missing', 'join_none', 'join_suggested', 'choose',
        'colors_help_enum', 'colors_help_single', 'colors_custom', 'colors_example',
        'expr_how', 'expr_sql_help', 'expr_php_help', 'expr_examples', 'expr_label',
        'order_default', 'order_custom', 'order_asc', 'order_desc',
        'preview_cols', 'preview_wide', 'preview_calc', 'preview_per_page', 'preview_actions',
    ];
    $L = [];
    foreach ($labelKeys as $k) {
        $L[$k] = trans('crudbooster.mg_list_' . $k);
    }
    $L['expr_title'] = trans('crudbooster.mg_list_expr_title');
    $L['colors_title'] = trans('crudbooster.mg_list_colors_title');
    $L['detail'] = trans('crudbooster.action_detail_data');
    $L['edit'] = trans('crudbooster.action_edit_data');
    $L['delete'] = trans('crudbooster.action_delete_data');
    $jf = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $currencyOptions = \App\Helpers\NumberFormat::currencyOptions();
    $currencySymbols = \App\Helpers\NumberFormat::CURRENCIES;
@endphp

@push('head')
<style>
    .cfg-row { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); padding: .5rem .6rem; margin-bottom: .4rem; }
    .cfg-row.calc { background: var(--ch-bg); border-color: var(--ch-warning); }
    .cfg-row.sys { background: var(--ch-bg); border-style: dashed; }
    .cfg-row.off .handle, .cfg-row.off .cfg-name { opacity: .5; }
    .cfg-row .handle { cursor: grab; color: var(--ch-text-muted); }
    .cfg-wl { font-size: .7rem; color: var(--ch-text-secondary); display: block; margin-bottom: 2px; }
    .cfg-sw { display: inline-block; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; border: 2px solid var(--ch-border); box-shadow: 0 0 0 1px #adb5bd; margin: 0 2px; }
    .btn-check:checked + .cfg-sw { box-shadow: 0 0 0 3px #0d6efd; }
    .cfg-swc { width: 26px; height: 26px; padding: 0; border: 0; background: none; }
    .cfg-pv-frame { border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); overflow-x: auto; margin: 0 auto; background: var(--ch-surface); }
    .cfg-pv-frame th, .cfg-pv-frame td { white-space: nowrap; }
</style>
@endpush

<form id="listForm" method="post" action="{{ Route('ModulsControllerPostStep3') }}">
    <div class="card card-default">
    <div class="card-header mb-3 with-border">
        <h5 class="card-title">{{ trans('crudbooster.mg_list_title') }}</h5>
    </div>
        {{ csrf_field() }}
        <input type="hidden" name="id" value="{{ $id }}">
        <input type="hidden" name="payload" id="payload" value="">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <p class="text-muted small">{{ trans('crudbooster.mg_list_subtitle') }}</p>
                    <div class="alert alert-light border py-2 small">{{ trans('crudbooster.mg_list_width_hint') }}</div>
                    <div id="cfgList"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="cfgAddCalc"><i class="bi bi-calculator-fill"></i> {{ trans('crudbooster.mg_list_add_calc') }}</button>
                    <div class="small text-muted mt-1">{{ trans('crudbooster.mg_list_add_calc_help') }}</div>

                </div>

                <div class="col-lg-6">
                    {{-- Ordinamento e pagine sopra l'anteprima (come il mockup), tre campi su una riga.
                         Gli id (cfgLimit, cfgOrderField, cfgOrderDir) li usa il JS del passo. --}}
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">{{ trans('crudbooster.mg_list_settings_title') }}</div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <label class="small fw-semibold" for="cfgLimit">{{ trans('crudbooster.mg_cfg_limit') }}</label>
                                    <input type="number" min="1" max="1000" class="form-control" id="cfgLimit">
                                </div>
                                <div class="col-sm-4">
                                    <label class="small fw-semibold" for="cfgOrderField">{{ trans('crudbooster.mg_cfg_orderby') }}</label>
                                    <select class="form-select" id="cfgOrderField"></select>
                                </div>
                                <div class="col-sm-4">
                                    <label class="small fw-semibold" for="cfgOrderDir">{{ trans('crudbooster.mg_list_order_dir') }}</label>
                                    <select class="form-select" id="cfgOrderDir">
                                        <option value="asc">{{ trans('crudbooster.mg_list_order_asc') }}</option>
                                        <option value="desc">{{ trans('crudbooster.mg_list_order_desc') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card position-sticky" style="top: 1rem">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="small text-muted text-uppercase"><i class="bi bi-eye-fill"></i> {{ trans('crudbooster.mg_list_preview_title') }} <span id="cfgPvCount"></span></div>
                                <div class="btn-group btn-group-sm" id="cfgPvSwitch">
                                    <input type="radio" class="btn-check" name="cfgpv" id="cfgpv-desktop" value="desktop" checked><label class="btn btn-outline-secondary" for="cfgpv-desktop" title="{{ trans('crudbooster.mg_list_pv_desktop') }}"><i class="bi bi-display"></i></label>
                                    <input type="radio" class="btn-check" name="cfgpv" id="cfgpv-tablet" value="tablet"><label class="btn btn-outline-secondary" for="cfgpv-tablet" title="{{ trans('crudbooster.mg_list_pv_tablet') }}"><i class="bi bi-tablet"></i></label>
                                    <input type="radio" class="btn-check" name="cfgpv" id="cfgpv-phone" value="phone"><label class="btn btn-outline-secondary" for="cfgpv-phone" title="{{ trans('crudbooster.mg_list_pv_phone') }}"><i class="bi bi-phone"></i></label>
                                </div>
                            </div>
                            <div id="cfgPvWarn"></div>
                            <div id="cfgPreview"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('crudbooster::module_generator._nav', [
        'nav_back' => CRUDBooster::mainpath('step2') . '/' . $id,
        'nav_back_ajax' => true,
        'nav_next' => trans('crudbooster.mg_nav_next'),
    ])
</form>

<div class="modal fade" id="cfgJoinModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">{{ trans('crudbooster.mg_list_join_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="small text-muted">{{ trans('crudbooster.mg_list_join_help') }}</p>
            <div class="mb-3"><label class="small fw-semibold">{{ trans('crudbooster.mg_list_join_table') }}</label><select class="form-select form-select-sm" id="cfgJoinTable"></select></div>
            <div class="mb-1"><label class="small fw-semibold">{{ trans('crudbooster.mg_list_join_column') }}</label><select class="form-select form-select-sm" id="cfgJoinColumn"></select></div>
            <div class="small text-muted mt-2" id="cfgJoinNote"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('crudbooster.mg_list_done') }}</button></div>
    </div></div>
</div>

<div class="modal fade" id="cfgColorsModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">{{ trans('crudbooster.mg_list_colors_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="cfgColorsBody"></div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('crudbooster.mg_list_done') }}</button></div>
    </div></div>
</div>

<div class="modal fade" id="cfgExprModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">{{ trans('crudbooster.mg_list_expr_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="cfgExprBody"></div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ trans('crudbooster.mg_list_done') }}</button></div>
    </div></div>
</div>

@push('bottom')
<script>
(function () {
    var ROWS = {!! json_encode($rows, $jf) !!};
    var TABLES = {!! json_encode(array_values($table_list), $jf) !!};
    var TABLE_COLUMNS = {!! json_encode(array_values($table_columns), $jf) !!};
    var COLUMNS_URL = {!! json_encode(CRUDBooster::mainpath('table-columns'), $jf) !!};
    var ORDERBY_RAW = {!! json_encode((string) $orderby, $jf) !!};
    var LIMIT = {!! json_encode($limit, $jf) !!};
    var L = {!! json_encode($L, $jf) !!};
    var CURRENCIES = {!! json_encode($currencyOptions, $jf) !!};
    var CURRENCY_SYMBOLS = {!! json_encode($currencySymbols, $jf) !!};

    var PALETTE = ['#198754', '#ffc107', '#dc3545', '#0d6efd', '#0dcaf0', '#6c757d', '#212529'];
    var AUTO = ['#198754', '#ffc107', '#6c757d', '#dc3545', '#0dcaf0', '#0d6efd'];
    var DEFAULT_BADGE = '#6c757d';
    var WIDTHS = {narrow: 80, medium: 140, wide: 240};
    var pv = 'desktop';
    var seq = 0;
    var calcSeq = 0;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }
    function byKey(k) {
        for (var i = 0; i < ROWS.length; i++) { if (String(ROWS[i]._k) === String(k)) { return ROWS[i]; } }
        return null;
    }
    function isEnum(r) { return r.enum && r.enum.length; }
    function colorsOf(r) {
        if (!r.badge || Array.isArray(r.badge.colors) || !r.badge.colors) { r.badge = r.badge || {}; r.badge.colors = {}; }
        if (!r.badge.default) { r.badge.default = DEFAULT_BADGE; }
        return r.badge.colors;
    }
    function badgeColor(r, v, i) {
        if (isEnum(r)) { return colorsOf(r)[v] || AUTO[i % AUTO.length]; }
        colorsOf(r);
        return r.badge.default;
    }
    function textOn(hex) {
        var n = parseInt(hex.slice(1), 16);
        var l = 0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255);
        return l > 150 ? '#000' : '#fff';
    }
    function badgeHtml(txt, c) {
        return '<span class="badge" style="background:' + c + ';color:' + textOn(c) + '">' + esc(txt) + '</span>';
    }

    // normalizzazione delle righe ricevute dal server
    ROWS.forEach(function (r) {
        r._k = ++seq;
        if (r.trunc == null) { r.trunc = 60; }
        if (r.format == null) { r.format = ''; }
        if (r.currency == null) { r.currency = 'EUR'; }
        if (r.decimals == null) { r.decimals = 2; }
        if (!r.width) { r.width = 'auto'; }
        colorsOf(r);
        if (r.kind === 'calc') { r.show = true; r.calc = r.calc || {lang: 'sql', expr: '', alias: ''}; calcSeq++; }
    });

    function formatsFor(r) {
        var t = String(r.type || '').toLowerCase();
        if (t === 'date') { return ['', 'date_short', 'date_long']; }
        if (t === 'datetime' || t === 'timestamp') { return ['', 'datetime_short', 'date_short', 'date_long']; }
        if (['decimal', 'float', 'double', 'numeric'].indexOf(t) >= 0) { return ['', 'money', 'money_plain']; }
        if (['tinyint', 'int', 'bigint', 'smallint', 'mediumint'].indexOf(t) >= 0) { return ['', 'badge']; }
        return ['', 'trunc', 'badge', 'image', 'download'];
    }
    function joinable(r) {
        return ['int', 'integer', 'bigint', 'smallint', 'mediumint'].indexOf(String(r.type || '').toLowerCase()) >= 0;
    }
    function fmtLabel(f) { return f === '' ? L.fmt_raw : L['fmt_' + f]; }

    function widthPills(r) {
        var opts = [['auto', L.w_auto], ['narrow', L.w_narrow], ['medium', L.w_medium], ['wide', L.w_wide]];
        if (r.width !== 'auto' && !WIDTHS[r.width]) { opts.push([r.width, L.w_custom.replace(':px', r.width)]); }
        return '<div class="btn-group btn-group-sm" role="group">' + opts.map(function (o) {
            var id = 'w-' + r._k + '-' + o[0];
            return '<input type="radio" class="btn-check" name="w-' + r._k + '" id="' + id + '" data-k="' + r._k + '" data-f="width" value="' + esc(o[0]) + '"' + (r.width === o[0] ? ' checked' : '') + '>'
                + '<label class="btn btn-outline-secondary" for="' + id + '">' + esc(o[1]) + '</label>';
        }).join('') + '</div>';
    }

    function rowHtml(r) {
        var k = r._k;
        if (r.kind === 'calc') {
            var has = r.calc && r.calc.expr && r.calc.expr.trim() !== '';
            return '<div class="cfg-row calc" data-k="' + k + '"><div class="d-flex align-items-center gap-2">'
                + '<i class="bi bi-grip-vertical handle"></i><span class="badge text-bg-warning">' + esc(L.badge_calc) + '</span>'
                + '<input class="form-control form-control-sm" data-k="' + k + '" data-f="label" value="' + esc(r.label) + '" placeholder="' + esc(L.title_ph) + '">'
                + '<button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-act="expr" data-k="' + k + '"><i class="bi bi-code-slash"></i> ' + esc(L.btn_expr) + '</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger" data-act="del" data-k="' + k + '"><i class="bi bi-trash-fill"></i></button></div>'
                + '<div class="small mt-1 ' + (has ? 'text-muted' : 'text-danger') + '">' + (has
                    ? '<span class="badge text-bg-dark">' + esc(r.calc.lang.toUpperCase()) + '</span> <code>' + esc(r.calc.expr.length > 60 ? r.calc.expr.slice(0, 60) + '…' : r.calc.expr) + '</code>'
                    : '<i class="bi bi-exclamation-triangle-fill"></i> ' + esc(L.expr_missing)) + '</div>'
                + '<div class="mt-2"><span class="cfg-wl">' + esc(L.label_width) + '</span>' + widthPills(r) + '</div></div>';
        }
        var sw = '<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-k="' + k + '" data-f="show"' + (r.show ? ' checked' : '') + '></div>';
        if (r.system) {
            return '<div class="cfg-row sys ' + (r.show ? '' : 'off') + '" data-k="' + k + '"><div class="d-flex align-items-center gap-2">'
                + '<i class="bi bi-grip-vertical handle"></i>' + sw
                + '<div class="flex-grow-1 cfg-name"><span class="fw-semibold">' + esc(r.label) + '</span> <span class="badge text-bg-light border"><i class="bi bi-lock-fill"></i> ' + esc(L.badge_system) + '</span>'
                + '<div class="small text-muted">' + esc(L.system_note.replace(':name', r.name)) + '</div></div></div></div>';
        }
        var h = '<div class="cfg-row ' + (r.show ? '' : 'off') + '" data-k="' + k + '"><div class="d-flex align-items-center gap-2">'
            + '<i class="bi bi-grip-vertical handle"></i>' + sw;
        if (r.show) {
            h += '<div class="flex-grow-1"><input class="form-control form-control-sm" data-k="' + k + '" data-f="label" value="' + esc(r.label) + '" placeholder="' + esc(L.title_ph) + '"></div>';
        } else {
            h += '<div class="flex-grow-1 cfg-name"><span class="fw-semibold">' + esc(r.label) + '</span> <span class="small text-muted">' + esc(r.name) + '</span></div>';
        }
        h += '</div>';
        if (r.show) {
            h += '<div class="row g-2 mt-1">';
            if (r.kind === 'legacy') {
                h += '<div class="col-12 small text-muted">' + esc(L.legacy_note.replace(':name', r.name)) + '</div>';
            } else {
                var fl = formatsFor(r);
                if (fl.indexOf(r.format) < 0) { fl.push(r.format); }
                h += '<div class="col-md-5"><span class="cfg-wl">' + esc(L.label_format) + '</span><select class="form-select form-select-sm" data-k="' + k + '" data-f="format">'
                    + fl.map(function (f) { return '<option value="' + f + '"' + (r.format === f ? ' selected' : '') + '>' + esc(fmtLabel(f)) + '</option>'; }).join('') + '</select>';
                if (r.format === 'trunc') {
                    h += '<div class="input-group input-group-sm mt-1"><span class="input-group-text">' + esc(L.trunc_first) + '</span><input type="number" min="1" max="1000" class="form-control" data-k="' + k + '" data-f="trunc" value="' + esc(r.trunc) + '"><span class="input-group-text">' + esc(L.trunc_chars) + '</span></div>';
                }
                if (r.format === 'money') {
                    h += '<div class="row g-1 mt-1"><div class="col-8"><span class="cfg-wl">' + esc(L.label_currency) + '</span><select class="form-select form-select-sm" data-k="' + k + '" data-f="currency">'
                        + '<option value=""' + (r.currency === '' ? ' selected' : '') + '>' + esc(L.currency_none) + '</option>'
                        + Object.keys(CURRENCIES).map(function (c) { return '<option value="' + c + '"' + (r.currency === c ? ' selected' : '') + '>' + esc(CURRENCIES[c]) + '</option>'; }).join('')
                        + '</select></div><div class="col-4"><span class="cfg-wl">' + esc(L.label_decimals) + '</span><input type="number" min="0" max="6" class="form-control form-control-sm" data-k="' + k + '" data-f="decimals" value="' + esc(r.decimals) + '"></div></div>';
                }
                if (r.format === 'badge') {
                    h += '<button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-act="colors" data-k="' + k + '"><i class="bi bi-brush-fill"></i> ' + esc(L.btn_colors) + ' ' + dots(r) + '</button>';
                }
                h += '</div>';
            }
            h += '<div class="col-md-7"><span class="cfg-wl">' + esc(L.label_width) + '</span>' + widthPills(r) + '</div>';
            // "Tabella collegata" ha senso solo per colonne che possono contenere l'id di un altro record
            // (numeri interi), o se il collegamento c'e' gia': non per testo, email, date, importi...
            if (r.kind === 'col' && (joinable(r) || (r.join && r.join.table))) {
                h += '<div class="col-12"><button type="button" class="btn btn-sm btn-outline-secondary" data-act="join" data-k="' + k + '"><i class="bi bi-link-45deg"></i> ' + esc(L.btn_join) + '</button> '
                    + (r.join && r.join.table ? '<span class="small text-muted">' + esc(L.join_set.replace(':column', r.join.column).replace(':table', r.join.table)) + '</span>' : '') + '</div>';
            }
            if (r.advanced) {
                h += '<div class="col-12 small text-muted"><i class="bi bi-info-circle-fill"></i> ' + esc(L.badge_advanced) + '</div>';
            }
            h += '</div>';
        }
        return h + '</div>';
    }

    function dots(r) {
        var cols = isEnum(r) ? r.enum.map(function (v, i) { return badgeColor(r, v, i); }) : [badgeColor(r, '', 0)];
        return cols.slice(0, 5).map(function (c) {
            return '<span class="d-inline-block rounded-circle border" style="width:10px;height:10px;background:' + c + '"></span>';
        }).join(' ');
    }

    function renderList() {
        document.getElementById('cfgList').innerHTML = ROWS.map(rowHtml).join('');
        renderOrderField();
        renderPreview();
    }

    /* ---------- anteprima ---------- */
    var LONG = 'Testo lungo di esempio che continua per diverse righe, con abbastanza parole da mostrare dove viene tagliato nella lista.';
    function sample(r, i) {
        if (r.kind === 'calc') { return '<span class="text-muted">' + esc(L.preview_calc) + '</span>'; }
        if (r.system) { return r.name === 'id' ? String(i + 1) : (/_at$/.test(r.name) ? '12/03/2026 09:' + (10 + i * 5) : String(1 + (i % 3))); }
        var f = r.format, t = String(r.type || '').toLowerCase();
        if (f === 'image') { return '<i class="bi bi-image text-secondary"></i>'; }
        if (f === 'download') { return '<span class="badge text-bg-primary"><i class="bi bi-download"></i></span>'; }
        if (f === 'badge') {
            if (isEnum(r)) { var v = r.enum[i % r.enum.length]; return badgeHtml(v, badgeColor(r, v, i % r.enum.length)); }
            return badgeHtml(r.label || 'Valore', badgeColor(r, '', 0));
        }
        if (f === 'date_short') { return ['12/03/2026', '03/04/2026', '21/05/2026', '08/06/2026'][i % 4]; }
        if (f === 'date_long') { return ['12 marzo 2026', '3 aprile 2026', '21 maggio 2026', '8 giugno 2026'][i % 4]; }
        if (f === 'datetime_short') { return '12/03/2026 09:' + (10 + i * 5); }
        if (f === 'money' || f === 'money_eur') {
            var dec = f === 'money_eur' ? 2 : Math.max(0, Math.min(6, parseInt(r.decimals, 10) || 0));
            var sym = f === 'money_eur' ? '€' : (CURRENCY_SYMBOLS[r.currency] || '');
            return (sym ? sym + ' ' : '') + (125400 + i * 8300).toLocaleString('it-IT', {minimumFractionDigits: dec, maximumFractionDigits: dec});
        }
        if (f === 'money_plain') { return (125400 + i * 8300) + '.00'; }
        if (r.join && r.join.table) { return ['Mario Rossi', 'Lucia Bianchi', 'Paolo Verdi', 'Anna Neri'][i % 4]; }
        if (t === 'date') { return ['2026-03-12', '2026-04-03', '2026-05-21', '2026-06-08'][i % 4]; }
        if (t === 'datetime' || t === 'timestamp') { return '2026-03-12 09:' + (10 + i * 5) + ':00'; }
        if (['decimal', 'float', 'double'].indexOf(t) >= 0) { return (125400 + i * 8300) + '.00'; }
        if (['int', 'bigint', 'smallint', 'mediumint', 'tinyint'].indexOf(t) >= 0) { return String(10 + i * 7); }
        if (f === 'trunc') { var n = parseInt(r.trunc, 10) || 60; return esc(LONG.slice(0, n)) + (LONG.length > n ? '…' : ''); }
        return esc((r.label || r.name) + ' ' + (i + 1));
    }

    function renderPreview() {
        var shown = ROWS.filter(function (r) { return r.show; });
        var max = {desktop: '100%', tablet: '420px', phone: '260px'}[pv];
        var el = document.getElementById('cfgPreview');
        if (!shown.length) { el.innerHTML = ''; }
        var h = '<div class="cfg-pv-frame" style="max-width:' + max + '"><table class="table table-sm table-striped align-middle mb-0"><thead><tr><th style="width:26px"><input type="checkbox" disabled></th>';
        shown.forEach(function (r) {
            var w = WIDTHS[r.width] || (/^\d+$/.test(r.width) ? parseInt(r.width, 10) : 0);
            h += '<th style="' + (w ? 'min-width:' + w + 'px' : '') + '">' + esc(r.label || r.name) + '</th>';
        });
        h += '<th>' + esc(L.preview_actions) + '</th></tr></thead><tbody>';
        for (var i = 0; i < 4; i++) {
            h += '<tr><td><input type="checkbox" disabled></td>';
            shown.forEach(function (r) { h += '<td>' + sample(r, i) + '</td>'; });
            h += '<td><span class="btn btn-sm btn-info"><i class="bi bi-eye-fill"></i></span> <span class="btn btn-sm btn-success"><i class="bi bi-pencil-fill"></i></span></td></tr>';
        }
        h += '</tbody></table></div>';
        var lim = document.getElementById('cfgLimit').value;
        el.innerHTML = h + '<div class="small text-muted mt-1">' + esc(L.preview_per_page.replace(':n', lim || '…')) + '</div>';
        document.getElementById('cfgPvCount').textContent = '· ' + L.preview_cols.replace(':n', shown.length);
        document.getElementById('cfgPvWarn').innerHTML = shown.length > 7
            ? '<div class="alert alert-warning py-1 small mb-2"><i class="bi bi-exclamation-triangle-fill"></i> ' + esc(L.preview_wide.replace(':n', shown.length)) + '</div>' : '';
    }

    /* ---------- ordinamento ---------- */
    var order = {mode: 'default', field: '', dir: 'asc'};
    (function parseOrder() {
        var m = /^([A-Za-z0-9_.]+),(asc|desc)$/.exec(ORDERBY_RAW || '');
        if (!ORDERBY_RAW) { order.mode = 'default'; }
        else if (m && TABLE_COLUMNS.indexOf(m[1]) >= 0) { order.mode = 'field'; order.field = m[1]; order.dir = m[2]; }
        else { order.mode = 'custom'; }
    })();
    function renderOrderField() {
        var labels = {};
        ROWS.forEach(function (r) { if (r.kind === 'col' && !labels[r.name]) { labels[r.name] = r.label || r.name; } });
        var sel = document.getElementById('cfgOrderField');
        var h = '<option value="">' + esc(L.order_default) + '</option>';
        TABLE_COLUMNS.forEach(function (c) { h += '<option value="' + esc(c) + '"' + (order.mode === 'field' && order.field === c ? ' selected' : '') + '>' + esc(labels[c] || c) + '</option>'; });
        if (order.mode === 'custom') { h += '<option value="__raw" selected>' + esc(L.order_custom.replace(':raw', ORDERBY_RAW)) + '</option>'; }
        sel.innerHTML = h;
        if (order.mode === 'default') { sel.value = ''; }
        document.getElementById('cfgOrderDir').value = order.dir;
    }
    document.getElementById('cfgOrderField').addEventListener('change', function () {
        if (this.value === '') { order.mode = 'default'; order.field = ''; }
        else if (this.value === '__raw') { order.mode = 'custom'; }
        else { order.mode = 'field'; order.field = this.value; }
    });
    document.getElementById('cfgOrderDir').addEventListener('change', function () { order.dir = this.value; });
    document.getElementById('cfgLimit').value = LIMIT == null ? '' : LIMIT;
    document.getElementById('cfgLimit').addEventListener('input', renderPreview);

    /* ---------- eventi della lista ---------- */
    var listEl = document.getElementById('cfgList');
    function onChange(e) {
        var t = e.target, k = t.getAttribute('data-k'), f = t.getAttribute('data-f');
        if (!k || !f) { return; }
        var r = byKey(k);
        if (!r) { return; }
        if (f === 'show') { r.show = t.checked; renderList(); return; }
        if (f === 'format') { r.format = t.value; renderList(); return; }
        if (f === 'width') { r.width = t.value; renderList(); return; }
        if (f === 'label') { r.label = t.value; renderPreview(); return; }
        if (f === 'currency') { r.currency = t.value; renderPreview(); return; }
        if (f === 'decimals') { r.decimals = Math.max(0, Math.min(6, parseInt(t.value, 10) || 0)); renderPreview(); return; }
        if (f === 'trunc') { r.trunc = parseInt(t.value, 10) || 60; renderPreview(); }
    }
    listEl.addEventListener('change', onChange);
    listEl.addEventListener('input', function (e) {
        var f = e.target.getAttribute('data-f');
        if (f === 'label' || f === 'trunc' || f === 'decimals') { onChange(e); }
    });

    var current = null;
    listEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) { return; }
        var r = byKey(b.getAttribute('data-k'));
        var act = b.getAttribute('data-act');
        if (act === 'del') { ROWS = ROWS.filter(function (x) { return x !== r; }); renderList(); }
        else if (act === 'expr') { openExpr(r); }
        else if (act === 'colors') { openColors(r); }
        else if (act === 'join') { openJoin(r); }
    });

    document.getElementById('cfgAddCalc').addEventListener('click', function () {
        calcSeq++;
        var r = {_k: ++seq, src: null, kind: 'calc', name: '', label: '', show: true, type: 'varchar', system: false, format: '', trunc: 60,
                 width: 'auto', join: null, advanced: false, enum: null, badge: {colors: {}, default: DEFAULT_BADGE}, calc: {lang: 'sql', expr: '', alias: ''}};
        ROWS.push(r);
        renderList();
        openExpr(r);
    });

    // trascinamento (jQuery UI sortable, gia' caricato dal template admin)
    if (window.jQuery && jQuery.fn.sortable) {
        jQuery('#cfgList').sortable({
            handle: '.handle', items: '> .cfg-row', tolerance: 'pointer',
            stop: function () {
                var keys = Array.prototype.map.call(listEl.querySelectorAll(':scope > .cfg-row'), function (n) { return n.getAttribute('data-k'); });
                ROWS = keys.map(byKey);
                renderPreview();
            }
        });
    }

    /* ---------- modale: tabella collegata ---------- */
    var joinModal = new bootstrap.Modal(document.getElementById('cfgJoinModal'));
    function fillColumns(table, selected) {
        var colSel = document.getElementById('cfgJoinColumn');
        if (!table) { colSel.innerHTML = '<option value="">' + esc(L.choose) + '</option>'; return; }
        colSel.innerHTML = '<option value="">…</option>';
        fetch(COLUMNS_URL + '/' + encodeURIComponent(table), {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (resp) { return resp.json(); })
            .then(function (cols) {
                colSel.innerHTML = '<option value="">' + esc(L.choose) + '</option>' + cols.map(function (c) {
                    return '<option value="' + esc(c) + '"' + (c === selected ? ' selected' : '') + '>' + esc(c) + '</option>';
                }).join('');
            });
    }
    var joinRow = null;
    function openJoin(r) {
        current = r;
        joinRow = r;
        var tSel = document.getElementById('cfgJoinTable');
        var cur = r.join && r.join.table ? r.join.table : '';
        tSel.innerHTML = '<option value="">' + esc(L.join_none) + '</option>' + TABLES.map(function (t) {
            return '<option value="' + esc(t) + '"' + (t === cur ? ' selected' : '') + '>' + esc(t) + '</option>';
        }).join('');
        fillColumns(cur, r.join ? r.join.column : '');
        document.getElementById('cfgJoinNote').textContent = r.join_suggested ? L.join_suggested : '';
        joinModal.show();
    }
    document.getElementById('cfgJoinTable').addEventListener('change', function () {
        if (!current) { return; }
        current.join = this.value ? {table: this.value, column: ''} : null;
        current.join_suggested = false;
        fillColumns(this.value, '');
    });
    document.getElementById('cfgJoinColumn').addEventListener('change', function () {
        if (current && current.join) { current.join.column = this.value; }
    });
    document.getElementById('cfgJoinModal').addEventListener('hidden.bs.modal', function () {
        // un collegamento senza campo non e' valido: si scarta
        if (joinRow && joinRow.join && !joinRow.join.column) { joinRow.join = null; }
        renderList();
    });

    /* ---------- modale: colori dei badge ---------- */
    var colorsModal = new bootstrap.Modal(document.getElementById('cfgColorsModal'));
    function swatches(name, cur, attrs) {
        return PALETTE.map(function (h) {
            var id = name + '-' + h.slice(1);
            return '<input type="radio" class="btn-check" name="' + name + '" id="' + id + '" ' + attrs + ' value="' + h + '"' + (String(cur).toLowerCase() === h ? ' checked' : '') + '>'
                + '<label class="cfg-sw" for="' + id + '" style="background:' + h + '"></label>';
        }).join('') + '<input type="color" class="cfg-swc" ' + attrs + ' value="' + cur + '" title="' + esc(L.colors_custom) + '">';
    }
    function openColors(r) {
        current = r;
        var body = document.getElementById('cfgColorsBody');
        var h = '';
        if (isEnum(r)) {
            h += '<div class="small text-muted mb-2">' + esc(L.colors_help_enum) + '</div>';
            r.enum.forEach(function (v, i) {
                h += '<div class="d-flex align-items-center gap-3 mb-2"><span style="min-width:120px" id="cb-' + i + '">' + badgeHtml(v, badgeColor(r, v, i)) + '</span><div class="d-flex align-items-center">'
                    + swatches('cb-radio-' + i, badgeColor(r, v, i), 'data-bi="' + i + '"') + '</div></div>';
            });
        } else {
            h += '<div class="small text-muted mb-2">' + esc(L.colors_help_single) + '</div>'
                + '<div class="d-flex align-items-center gap-3"><span style="min-width:120px" id="cb-all">' + badgeHtml(L.colors_example, badgeColor(r, '', 0)) + '</span><div class="d-flex align-items-center">'
                + swatches('cb-radio-all', badgeColor(r, '', 0), 'data-bi="*"') + '</div></div>';
        }
        body.innerHTML = h;
        colorsModal.show();
    }
    document.getElementById('cfgColorsBody').addEventListener('input', pickColor);
    document.getElementById('cfgColorsBody').addEventListener('change', pickColor);
    function pickColor(e) {
        var t = e.target, bi = t.getAttribute('data-bi');
        if (bi === null || !current) { return; }
        var v = t.value;
        if (bi === '*') {
            current.badge.default = v;
            var a = document.getElementById('cb-all'); if (a) { a.innerHTML = badgeHtml(L.colors_example, v); }
        } else {
            var val = current.enum[parseInt(bi, 10)];
            colorsOf(current)[val] = v;
            var s = document.getElementById('cb-' + bi); if (s) { s.innerHTML = badgeHtml(val, v); }
        }
        renderPreview();
    }
    document.getElementById('cfgColorsModal').addEventListener('hidden.bs.modal', renderList);

    /* ---------- modale: espressione ---------- */
    var exprModal = new bootstrap.Modal(document.getElementById('cfgExprModal'));
    function exprBody(r) {
        var sql = r.calc.lang === 'sql';
        return '<div class="small fw-semibold mb-1">' + esc(L.expr_how) + '</div>'
            + '<div class="btn-group mb-2"><input type="radio" class="btn-check" name="ex-lang" id="ex-sql" value="sql"' + (sql ? ' checked' : '') + '><label class="btn btn-outline-primary" for="ex-sql">SQL</label>'
            + '<input type="radio" class="btn-check" name="ex-lang" id="ex-php" value="php"' + (sql ? '' : ' checked') + '><label class="btn btn-outline-primary" for="ex-php">PHP</label></div>'
            + '<div class="alert alert-light border small py-2">' + esc(sql ? L.expr_sql_help : L.expr_php_help) + '<br>' + esc(L.expr_examples) + ' '
            + (sql ? '<code>CONCAT(nome, \' \', cognome)</code> · <code>prezzo * quantita</code>' : '<code>number_format([prezzo] * [quantita], 2)</code> · <code>strtoupper(\'[nome]\')</code>') + '</div>'
            + '<label class="small">' + esc(L.expr_label) + '</label>'
            + '<textarea class="form-control font-monospace" rows="4" id="ex-text" placeholder="' + esc(sql ? 'prezzo * quantita' : '[prezzo] * [quantita]') + '">' + esc(r.calc.expr) + '</textarea>';
    }
    function openExpr(r) {
        current = r;
        document.getElementById('cfgExprBody').innerHTML = exprBody(r);
        exprModal.show();
    }
    document.getElementById('cfgExprBody').addEventListener('change', function (e) {
        if (e.target.name === 'ex-lang' && current) {
            current.calc.lang = e.target.value;
            var keep = document.getElementById('ex-text').value;
            current.calc.expr = keep;
            document.getElementById('cfgExprBody').innerHTML = exprBody(current);
        }
    });
    document.getElementById('cfgExprBody').addEventListener('input', function (e) {
        if (e.target.id === 'ex-text' && current) { current.calc.expr = e.target.value; }
    });
    document.getElementById('cfgExprModal').addEventListener('hidden.bs.modal', renderList);

    /* ---------- anteprima: dispositivo ---------- */
    document.getElementById('cfgPvSwitch').addEventListener('change', function (e) { pv = e.target.value; renderPreview(); });

    /* ---------- invio ---------- */
    document.getElementById('listForm').addEventListener('submit', function () {
        var orderby = order.mode === 'custom' ? ORDERBY_RAW : (order.mode === 'field' ? order.field + ',' + order.dir : '');
        var rows = ROWS.map(function (r) {
            var c = JSON.parse(JSON.stringify(r));
            delete c._k;
            return c;
        });
        document.getElementById('payload').value = JSON.stringify({rows: rows, limit: document.getElementById('cfgLimit').value, orderby: orderby});
    });

    renderList();
})();
</script>
@endpush

@endsection
