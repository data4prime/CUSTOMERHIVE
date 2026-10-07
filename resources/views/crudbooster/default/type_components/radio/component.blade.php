@php
    // Standard radio: 2-5 opzioni brevi = controllo segmentato, altrimenti elenco di radio tonde.
    // Il markup vive in partials/ch_radio, lo stile in public/css/ch-components.css.
    $rdOpts = []; // ogni voce: ['value' => ..., 'label' => ..., 'checked' => bool, 'data_val' => ...]
    $rdHasSource = isset($form['dataenum']) || isset($form['datatable']) || isset($form['dataquery']);

    if (isset($form['dataenum']) && $form['dataenum'] != '') {
        $valueArr = array_map('trim', explode(";", (string) $value));
        $dataenum = $form['dataenum'];
        $dataenum = (is_array($dataenum)) ? $dataenum : explode(";", $dataenum);
        foreach ($dataenum as $k => $d) {
            if (strpos($d, '|')) {
                $val = substr($d, 0, strpos($d, '|'));
                $label = substr($d, strpos($d, '|') + 1);
            } else {
                $val = $label = $d;
            }
            $rdOpts[] = [
                'value' => $val,
                'label' => $label,
                'checked' => (($valueArr && in_array($val, $valueArr)) || (($k == 0 && isset($form['validation'])) && CRUDBooster::isCreate())),
                'data_val' => $val,
            ];
        }
    }

    if (!empty($form['datatable'])) {
        $datatable_array = explode(",", $form['datatable']);
        $datatable_tab = $datatable_array[0];
        $datatable_field = $datatable_array[1];

        $tables = explode('.', $datatable_tab);
        $selects_data = DB::table($tables[0])->select($tables[0] . ".id");

        if (CRUDBooster::isColumnExists($tables[0], 'deleted_at')) {
            $selects_data->where('deleted_at', NULL);
        }

        if (!empty($form['datatable_where'])) {
            $selects_data->whereraw($form['datatable_where']);
        }

        for ($i = 1; $i <= count($tables) - 1; $i++) {
            $tab = $tables[$i];
            $parent_table = $tables[$i - 1];
            $fk_field = CRUDBooster::getForeignKey($parent_table, $tab);
            $pk = CRUDBooster::findPrimaryKey($tab) ?: 'id';
            $selects_data->leftjoin($tab, $tab . '.' . $pk, '=', $fk_field);
        }

        // Because we use join statement, we need to select specified field to avoid ambigous
        $select_field = end($tables) . '.' . $datatable_field;
        $select_field_alias = end($tables) . '_' . $datatable_field;
        $selects_data->addselect($select_field . ' as ' . $select_field_alias);
        $selects_data = $selects_data->orderby(end($tables) . '.' . $datatable_field, "asc")->get();

        foreach ($selects_data as $d) {
            $val = $d->{$select_field_alias};
            if ($val == '' || !$d->id) continue;
            $rdOpts[] = [
                'value' => $d->id,
                'label' => $val,
                'checked' => ($value == $d->id),
                'data_val' => $val,
            ];
        }
    }

    if (isset($form['dataquery'])) {
        $query = DB::select($form['dataquery']);
        if ($query) {
            foreach ($query as $q) {
                $rdOpts[] = [
                    'value' => $q->value,
                    'label' => $q->label,
                    'checked' => ($value == $q->value),
                    'data_val' => $q->value,
                ];
            }
        }
    }
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
    style="{{ isset($form['style']) ? $form['style'] : '' }}">
    <label class='col-form-label col-sm-2'>{{$form['label']}}
        @if($required)
        <span class='text-danger' title='{!! trans("crudbooster.this_field_is_required") !!}'>*</span>
        @endif
    </label>
    <div class="{{$col_width?:'col-sm-10'}}">

        @if(!$rdHasSource)
        <em>{{trans('crudbooster.there_is_no_option')}}</em>
        @endif

        @include('crudbooster::partials.ch_radio', [
            'name' => $name,
            'rd_options' => $rdOpts,
            'disabled' => !empty($disabled),
        ])

        <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):""
            !!}</div>
        <p class='help-block'>{{ @$form['help'] }}</p>
    </div>
</div>
