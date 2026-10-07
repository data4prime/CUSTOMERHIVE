{{--
    Campo "child" (master -> dettaglio): griglia modificabile. Ogni riga e' una riga della
    tabella figlia; le celle sono i campi (si scrive direttamente nella tabella) e la riga
    si salva insieme al record principale. Contratto con il salvataggio (CBController):
    per ogni colonna c'e' un input  name="<slug-label>-<colonna>[]"  per riga, nello stesso ordine.
    Colonne: text, number, textarea, select, radio, datamodal, upload, hidden; opzioni per colonna:
    required, readonly, min, max, formula ("[qta] * [prezzo]"), sum (totale in fondo), help.
--}}
@php
    $name = str_slug($form['label'], '');
    $chCols = $form['columns'] ?? null;
@endphp
<div class='mb-3 row {{$header_group_class}}' id='form-group-{{$name}}'>

@if($chCols)
@php
    // ---- normalizza le colonne ------------------------------------------------
    $visibleCols = [];
    foreach ($chCols as $i => $c) {
        $c['required'] = !empty($c['required']);
        $c['readonly'] = !empty($c['readonly']);
        $c['formula'] = $c['formula'] ?? '';
        $c['help'] = $c['help'] ?? '';
        $c['max'] = $c['max'] ?? '';
        $c['min'] = $c['min'] ?? '';
        $c['sum'] = !empty($c['sum']);
        $c['upload_type'] = $c['upload_type'] ?? '';
        $c['datamodal_size'] = $c['datamodal_size'] ?? '';
        $c['datamodal_height'] = $c['datamodal_height'] ?? '';
        $c['datamodal_columns_alias'] = $c['datamodal_columns_alias'] ?? '';
        $c['datamodal_paginate'] = $c['datamodal_paginate'] ?? '';
        $c['datamodal_where'] = $c['datamodal_where'] ?? '';
        $c['datamodal_select_to'] = $c['datamodal_select_to'] ?? '';
        $c['datatable'] = $c['datatable'] ?? '';
        $c['datatable_where'] = $c['datatable_where'] ?? '';
        $c['parent_select'] = $c['parent_select'] ?? '';
        $c['dataenum'] = $c['dataenum'] ?? '';
        $c['value'] = $c['value'] ?? '';
        $c['nc'] = $name . $c['name'];            // prefisso di funzioni/id per colonna
        $chCols[$i] = $c;
        if ($c['type'] != 'hidden') { $visibleCols[] = $c; }
    }
    $colspan = count($visibleCols) + 1;
    $hasSum = collect($visibleCols)->contains(function ($c) { return $c['sum']; });

    // ---- opzioni dei select (una query per colonna, usata da tutte le righe) ----
    $selectOptions = [];
    foreach ($chCols as $c) {
        if ($c['type'] != 'select') { continue; }
        $opts = [];
        if ($c['datatable']) {
            $tableJoin = explode(',', $c['datatable'])[0];
            $titleField = explode(',', $c['datatable'])[1];
            // niente record eliminati (soft delete) tra le opzioni
            $optWhere = $c['datatable_where'] ?: null;
            if (CRUDBooster::isColumnExists($tableJoin, 'deleted_at')) {
                $optWhere = 'deleted_at IS NULL' . ($optWhere ? ' AND (' . $optWhere . ')' : '');
            }
            $data = CRUDBooster::get($tableJoin, $optWhere, "$titleField ASC");
            foreach ($data as $d) { $opts[] = [$d->id, $d->$titleField]; }
        } else {
            $dataenum = $c['dataenum'];
            $dataenum = is_array($dataenum) ? $dataenum : explode(';', (string) $dataenum);
            foreach ($dataenum as $d) {
                $enum = explode('|', $d);
                $opts[] = count($enum) == 2 ? [$enum[0], $enum[1]] : [$enum[0], $enum[0]];
            }
        }
        $selectOptions[$c['name']] = $opts;
    }

    // ---- righe esistenti -------------------------------------------------------
    $data_child = DB::table($form['table'])->where($form['foreign_key'], isset($id) ? $id : 0);
    foreach ($chCols as $c) {
        $data_child->addselect($form['table'].'.'.$c['name']);
        if ($c['type'] == 'datamodal') {
            $datamodal_title = explode(',', $c['datamodal_columns'])[0];
            $datamodal_table = $c['datamodal_table'];
            $data_child->join($c['datamodal_table'], $c['datamodal_table'].'.id', '=', $c['name']);
            $data_child->addselect($c['datamodal_table'].'.'.$datamodal_title.' as '.$datamodal_table.'_'.$datamodal_title);
        }
    }
    $data_child = $data_child->orderby($form['table'].'.id', 'desc')->get();

    // ---- HTML di una cella ($rk = chiave di riga, per i radio) --------------------
    $chCell = function ($c, $val, $label, $rk) use ($name, $selectOptions) {
        $n = $name . '-' . $c['name'] . '[]';
        $req = $c['required'] ? ' required' : '';
        $ro = $c['readonly'] ? ' readonly' : '';
        $attrCol = ' data-col="' . e($c['name']) . '"';
        switch ($c['type']) {
            case 'number':
                return '<input type="number" class="form-control" name="' . e($n) . '" value="' . e($val) . '" step="' . e($c['step'] ?? 'any') . '"'
                    . ($c['min'] !== '' ? ' min="' . e($c['min']) . '"' : '') . ($c['max'] !== '' ? ' max="' . e($c['max']) . '"' : '') . $req . $ro . $attrCol . '>';
            case 'textarea':
                return '<textarea rows="1" class="form-control" name="' . e($n) . '"' . $req . $ro . $attrCol . '>' . e($val) . '</textarea>';
            case 'select':
                $h = '<select class="form-control" name="' . e($n) . '"' . $req . $attrCol . '><option value="">' . e(trans('crudbooster.text_prefix_option') . ' ' . $c['label']) . '</option>';
                foreach ($selectOptions[$c['name']] ?? [] as $o) {
                    $h .= '<option value="' . e($o[0]) . '"' . ((string) $o[0] === (string) $val ? ' selected' : '') . '>' . e($o[1]) . '</option>';
                }
                return $h . '</select>';
            case 'radio':
                $dataenum = $c['dataenum'];
                $dataenum = is_array($dataenum) ? $dataenum : (strpos((string) $dataenum, ';') !== false ? explode(';', $dataenum) : [$dataenum]);
                $h = '<input type="hidden" name="' . e($n) . '" value="' . e($val) . '"' . $attrCol . '><div class="ch-seg ch-seg-sm" role="radiogroup">';
                foreach ($dataenum as $enumRaw) {
                    $enum = explode('|', trim($enumRaw));
                    $rv = $enum[0];
                    $rl = count($enum) == 2 ? $enum[1] : $enum[0];
                    $h .= '<label><input type="radio" data-ch-radio name="ui-' . e($c['nc']) . '-' . $rk . '" value="' . e($rv) . '"' . ((string) $rv === (string) $val ? ' checked' : '') . '><span>' . e($rl) . '</span></label>';
                }
                return $h . '</div>';
            case 'datamodal':
                return '<div class="ch-cell-pick" role="button" tabindex="0" onclick="showModal' . $c['nc'] . '(this)">'
                    . '<input type="hidden" class="input-id" name="' . e($n) . '" value="' . e($val) . '"' . $attrCol . '>'
                    . '<input type="text" class="form-control input-label" readonly tabindex="-1" value="' . e($label) . '" placeholder="' . e(trans('crudbooster.datamodal_choose')) . '"' . $req . '>'
                    . '<i class="bi bi-search"></i></div>';
            case 'upload':
                $isImg = $c['upload_type'] == 'image';
                $fn = $val !== '' ? basename($val) : '';
                return '<div class="ch-cell-upload">'
                    . '<input type="hidden" class="input-id" name="' . e($n) . '" value="' . e($val) . '"' . $attrCol . '>'
                    . ($isImg ? '<a data-lightbox="roadtrip" class="ch-upl-img" href="' . ($val !== '' ? e(asset($val)) : '#') . '"' . ($val === '' ? ' style="display:none"' : '') . '><img src="' . ($val !== '' ? e(asset($val)) : '') . '" alt=""></a>' : '')
                    . '<a class="ch-upl-name" ' . ($val !== '' ? 'href="' . e(asset($val)) . '"' : '') . ' data-image="' . ($isImg ? '1' : '0') . '">' . e($fn) . '</a>'
                    . '<button type="button" class="ch-upl-btn" onclick="showFakeUpload' . $c['nc'] . '(this)" title="' . e(trans('crudbooster.datamodal_browse_file')) . '"><i class="bi bi-paperclip"></i></button>'
                    . '<span class="ch-upl-loading" style="display:none"><i class="bi ch-spin bi-arrow-repeat"></i></span></div>';
            default: // text
                return '<input type="text" class="form-control" name="' . e($n) . '" value="' . e($val) . '"'
                    . ($c['max'] !== '' ? ' maxlength="' . e($c['max']) . '"' : '') . $req . $ro . $attrCol . '>';
        }
    };
    $chRow = function ($d, $rk) use ($chCols, $chCell, $name) {
        $h = '<tr class="ch-row">';
        $hidden = '';
        foreach ($chCols as $c) {
            $val = $d ? (string) ($d->{$c['name']} ?? '') : (string) $c['value'];
            $label = '';
            if ($d && $c['type'] == 'datamodal') {
                $label = (string) ($d->{$c['datamodal_table'] . '_' . explode(',', $c['datamodal_columns'])[0]} ?? '');
            }
            if ($c['type'] == 'hidden') {
                $hidden .= '<input type="hidden" name="' . e($name . '-' . $c['name'] . '[]') . '" value="' . e($val) . '" data-col="' . e($c['name']) . '">';
                continue;
            }
            $h .= '<td class="cell ch-td-' . e($c['type']) . '">' . $chCell($c, $val, $label, $rk) . '</td>';
        }
        $h .= '<td class="ch-act">' . $hidden . '<button type="button" class="ch-icb dng" onclick="deleteRow' . $name . '(this)" title="' . e(trans('crudbooster.text_delete')) . '"><i class="bi bi-trash"></i></button></td></tr>';
        return $h;
    };
@endphp
    <div class="col-sm-12">
        <div id="card-form-{{$name}}" class="ch-grid-card">
            <div class="ch-grid-hd"><i class='bi bi-list'></i> {{$form['label']}}</div>
            <div class="table-responsive">
                <table id="table-{{$name}}" class="ch-grid">
                    <thead>
                        <tr>
                            @foreach($visibleCols as $c)
                            <th>{{$c['label']}}@if($c['required']) <span class="text-danger" title="{{trans('crudbooster.this_field_is_required')}}">*</span>@endif</th>
                            @endforeach
                            <th class="ch-act"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data_child as $ri => $d)
                        {!! $chRow($d, 'e' . $ri) !!}
                        @endforeach
                        @if(count($data_child) == 0)
                        <tr class="trNull"><td colspan="{{$colspan}}" class="ch-grid-empty">{{trans('crudbooster.table_data_not_found')}}</td></tr>
                        @endif
                    </tbody>
                    @if($hasSum)
                    <tfoot>
                        <tr>
                            @foreach($visibleCols as $ci => $c)
                            @if($c['sum'])
                            <td class="ch-sum" data-sum-col="{{$c['name']}}">0</td>
                            @elseif($ci == 0)
                            <td class="ch-sum-label">{{trans('crudbooster.child_total')}}</td>
                            @else
                            <td></td>
                            @endif
                            @endforeach
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <button type="button" id="btn-add-table-{{$name}}" class="ch-grid-add" onclick="addRow{{$name}}()">
                <i class="bi bi-plus-lg"></i> {{trans('crudbooster.child_add_row')}}
            </button>
            @foreach($visibleCols as $c)
            @if($c['help'])<div class="help-block ch-grid-help"><b>{{$c['label']}}</b>: {{$c['help']}}</div>@endif
            @endforeach
        </div>
    </div>

    <template id="tpl-{{$name}}">{!! $chRow(null, '__R__') !!}</template>

    {{-- Finestre di scelta (datamodal) e campi file nascosti: uno per colonna, condivisi da tutte le righe --}}
    @foreach($chCols as $c)
    @if($c['type'] == 'datamodal')
    <div id='modal-datamodal-{{$c['nc']}}' class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog {{ $c['datamodal_size'] == 'large' ? 'modal-lg' : '' }}" role="document">
            <div class="modal-content">
                <div class="modal-header" style="justify-content: space-between;">
                    <h4 class="modal-title"><i class='bi bi-search'></i> {{trans('crudbooster.datamodal_browse_data')}} {{$c['label']}}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id='iframe-modal-{{$c['nc']}}' style="border:0;height:{{$c['datamodal_height'] ?: '430px'}};width: 100%" src=""></iframe>
                </div>
            </div>
        </div>
    </div>
    @elseif($c['type'] == 'upload')
    <input type="file" id="fake-upload-{{$c['nc']}}" style="display: none">
    @endif
    @endforeach

    @push('bottom')
    <script type="text/javascript">
    (function () {
        var NAME = {!! json_encode($name) !!};
        var $tbody = $('#table-' + NAME + ' tbody');
        var seq = {{ count($data_child) }} + 1000;
        var COLSPAN = {{ $colspan }};
        var T_EMPTY = {!! json_encode(trans('crudbooster.table_data_not_found')) !!};
        var T_DELETE = {!! json_encode(trans('crudbooster.delete_title_confirm')) !!};

        function rowCount() { return $tbody.find('tr.ch-row').length; }
        function syncEmpty() {
            $tbody.find('.trNull').remove();
            if (!rowCount()) { $tbody.append('<tr class="trNull"><td colspan="' + COLSPAN + '" class="ch-grid-empty">' + T_EMPTY + '</td></tr>'); }
        }
        function colVal($tr, col) { return $tr.find('[data-col="' + col + '"]').first().val(); }

        // ---- formule e totali ---------------------------------------------------
        var FORMULAS = {
            @foreach($chCols as $c)
            @if($c['formula'])
            {!! json_encode($c['name']) !!}: function ($tr) {
                var v = {!! preg_replace_callback('/\[(\w+)\]/', function ($m) { return "colVal(\$tr, '" . $m[1] . "')"; }, $c['formula']) !!};
                $tr.find('[data-col="{{$c['name']}}"]').first().val(v);
            },
            @endif
            @endforeach
        };
        function runFormulas($tr) {
            $.each(FORMULAS, function (col, fn) { try { fn($tr); } catch (e) { console.warn('child formula', col, e); } });
        }
        function totals() {
            $('#table-' + NAME + ' tfoot [data-sum-col]').each(function () {
                var col = $(this).data('sum-col'), s = 0;
                $tbody.find('tr.ch-row [data-col="' + col + '"]').each(function () { s += parseFloat(String(this.value).replace(',', '.')) || 0; });
                $(this).text(s.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            });
        }

        // ---- aggiungi / elimina riga -------------------------------------------
        window['addRow' + NAME] = function () {
            var html = $('#tpl-' + NAME).html().replace(/__R__/g, 'n' + (++seq));
            $tbody.find('.trNull').remove();
            var $r = $(html);
            $tbody.append($r);
            runFormulas($r); totals();
            $r.find('input.form-control:visible, select.form-control').first().trigger('focus');
        };
        window['deleteRow' + NAME] = function (btn) {
            if (confirm(T_DELETE)) { $(btn).closest('tr').remove(); syncEmpty(); totals(); }
        };

        $tbody.on('input change', 'input, select, textarea', function () {
            var $tr = $(this).closest('tr');
            runFormulas($tr); totals();
        });
        // radio: il valore inviato sta nell'input nascosto (i radio non inviano nulla se non scelti)
        $tbody.on('change', 'input[data-ch-radio]', function () {
            $(this).closest('td').find('input[type=hidden]').val(this.value).trigger('change');
        });
        $tbody.on('keydown', '.ch-cell-pick', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });

        runFormulas($tbody.find('tr.ch-row')); totals();
        @foreach($chCols as $c)

        // ---- colonna {{$c['name']}} ({{$c['type']}}) ---------------------------------
        @if($c['type'] == 'datamodal')
        (function () {
            var NC = {!! json_encode($c['nc']) !!}, COL = {!! json_encode($c['name']) !!}, $cur = null, urlSet = false;
            var url = "{{CRUDBooster::mainpath('modal-data')}}?table={{$c['datamodal_table']}}&columns=id,{{$c['datamodal_columns']}}&name_column={{$c['nc']}}&where={{urlencode($c['datamodal_where'])}}&select_to={{ urlencode($c['datamodal_select_to']) }}&columns_name_alias={{urlencode($c['datamodal_columns_alias'])}}&paginate={{urlencode($c['datamodal_paginate'])}}";
            window['showModal' + NC] = function (el) {
                $cur = $(el).closest('tr');
                if (!urlSet) { urlSet = true; $('#iframe-modal-' + NC).attr('src', url); }
                $('#modal-datamodal-' + NC).modal('show');
            };
            window['hideModal' + NC] = function () { $('#modal-datamodal-' + NC).modal('hide'); };
            window['selectAdditionalData' + NC] = function (json) {
                if ($cur) {
                    $.each(json, function (key, val) {
                        if (key == 'datamodal_id') { $cur.find('[data-col="' + COL + '"]').val(val); }
                        else if (key == 'datamodal_label') { $cur.find('[data-col="' + COL + '"]').closest('.ch-cell-pick').find('.input-label').val(val); }
                        else if (key != '') { $cur.find('[data-col="' + key + '"]').not('.input-id').val(val).trigger('change'); }
                    });
                    $cur.find('[data-col="' + COL + '"]').trigger('change');
                }
                window['hideModal' + NC]();
            };
        })();
        @elseif($c['type'] == 'upload')
        (function () {
            var NC = {!! json_encode($c['nc']) !!}, $cell = null, uploading = false;
            var maxSize = {{ $c['max'] !== '' ? (int) $c['max'] : 2000 }};
            var isImageOnly = {{ $c['upload_type'] == 'image' ? 'true' : 'false' }};
            var allowed = {!! json_encode(explode(',', (string) config('crudbooster.UPLOAD_TYPES'))) !!};
            window['showFakeUpload' + NC] = function (btn) {
                if (uploading) { return false; }
                $cell = $(btn).closest('td');
                $('#fake-upload-' + NC).val('').trigger('click');
            };
            $('#fake-upload-' + NC).on('change', function (event) {
                var file = event.target.files[0];
                if (!file || !$cell) { return; }
                if (Math.round(file.size / 1024) > maxSize) { sweetAlert({!! json_encode(trans('crudbooster.alert_warning')) !!}, {!! json_encode(trans('crudbooster.your_file_size_is_too_big')) !!}, 'warning'); return; }
                var ext = file.name.split('.').pop().toLowerCase();
                var okExt = isImageOnly ? ['jpg', 'jpeg', 'png', 'gif', 'bmp'] : allowed;
                if ($.inArray(ext, okExt) == -1) { sweetAlert({!! json_encode(trans('crudbooster.alert_warning')) !!}, {!! json_encode(trans('crudbooster.your_file_extension_is_not_allowed')) !!}, 'warning'); return; }
                var data = new FormData(); data.append('userfile', file);
                var $c = $cell;
                uploading = true; $c.find('.ch-upl-loading').show(); $('#btn-add-table-' + NAME).prop('disabled', true);
                $.ajax({
                    url: '{{CRUDBooster::mainpath("upload-file")}}', type: 'POST', data: data, cache: false, processData: false, contentType: false,
                    success: function (path) {
                        $c.find('.input-id').val(path).trigger('change');
                        var base = String(path).split('/').reverse()[0];
                        $c.find('.ch-upl-name').text(base).attr('href', {!! json_encode(asset('/')) !!} + path);
                        $c.find('.ch-upl-img').attr('href', {!! json_encode(asset('/')) !!} + path).show().find('img').attr('src', {!! json_encode(asset('/')) !!} + path);
                    },
                    complete: function () { uploading = false; $c.find('.ch-upl-loading').hide(); $('#btn-add-table-' + NAME).prop('disabled', false); }
                });
            });
        })();
        @elseif($c['type'] == 'select' && $c['parent_select'])
        // select collegato: le opzioni dipendono dal select "padre" della stessa riga
        $tbody.on('change', 'select[data-col="{{$c['parent_select']}}"]', function () {
            var $tr = $(this).closest('tr'), $cur = $tr.find('select[data-col="{{$c['name']}}"]'), fk = $(this).val();
            var datatable = {!! json_encode($c['datatable']) !!}.split(','), where = {!! json_encode($c['datatable_where']) !!};
            var first = {!! json_encode(trans('crudbooster.text_prefix_option') . ' ' . $c['label']) !!};
            var keep = $cur.val();
            if (fk === '' || fk === null) { $cur.html($('<option>').val('').text(first)).trigger('change'); return; }
            $.get("{{CRUDBooster::mainpath('data-table')}}?table=" + datatable[0].trim() + "&label=" + datatable[1].trim() + "&fk_name={{$c['parent_select']}}&fk_value=" + fk + "&datatable_where=" + encodeURI(where), function (resp) {
                $cur.html($('<option>').val('').text(first));
                $.each(resp || [], function (i, o) { $cur.append($('<option>').val(o.select_value).text(o.select_label).prop('selected', String(o.select_value) === String(keep))); });
                $cur.trigger('change');
            });
        });
        @endif
        @endforeach
    })();
    </script>
    @endpush

@else

    <div style="border:1px dashed var(--ch-danger);padding:20px;margin:20px">
        <span style="background: yellow;color: black;font-weight: bold">CHILD {{$name}} : COLUMNS ATTRIBUTE IS MISSING
            !</span>
        <p>You need to set the "columns" attribute manually</p>
    </div>
@endif
</div>
