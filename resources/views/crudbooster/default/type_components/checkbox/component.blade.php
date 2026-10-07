@php
    // Standard checkbox: singolo booleano = switch (B), elenco di opzioni = checkbox quadrate (A).
    // Il markup vive in partials/ch_check, lo stile in public/css/ch-components.css.
    $isDisabled = !empty($disabled);
    $single = !isset($form['dataenum']) && !isset($form['datatable']) && !isset($form['dataquery']);
    $chOptions = []; // ogni voce: ['value' => ..., 'label' => ..., 'checked' => bool, 'data_val' => ...]

    if ($single) {
        $chOptions[] = [
            'value' => '1',
            'label' => '',
            'checked' => (!empty($checked) OR !empty($value)),
            'data_val' => null,
        ];
    }

    if (isset($form['dataenum']) && $form['dataenum'] != '') {
        $valueArr = array_map('trim', explode(";", (string) $value));
        $dataenum = $form['dataenum'];
        $dataenum = (is_array($dataenum)) ? $dataenum : explode(";", $dataenum);
        foreach ($dataenum as $d) {
            if (strpos($d, '|')) {
                $val = substr($d, 0, strpos($d, '|'));
                $label = substr($d, strpos($d, '|') + 1);
            } else {
                $val = $label = $d;
            }
            $chOptions[] = [
                'value' => $val,
                'label' => $label,
                'checked' => in_array($val, $valueArr),
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

        if (\Schema::hasColumn($tables[0], 'deleted_at')) {
            $selects_data->where('deleted_at', NULL);
        }

        if (!empty($form['datatable_where'])) {
            $selects_data->whereraw($form['datatable_where']);
        }

        for ($i = 1; $i <= count($tables) - 1; $i++) {
            $tab = $tables[$i];
            $selects_data->leftjoin($tab, $tab . '.id', '=', 'id_' . $tab);
        }

        $selects_data->addselect($datatable_field);
        $selects_data = $selects_data->orderby($datatable_field, "asc")->get();

        if (!empty($form['relationship_table'])) {
            $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
            $foreignKey2 = CRUDBooster::getForeignKey($datatable_tab, $form['relationship_table']);
            $relIds = DB::table($form['relationship_table'])->where($form['relationship_table'] . '.' . $foreignKey, $id)->pluck($foreignKey2)->toArray();

            foreach ($selects_data as $d) {
                $chOptions[] = [
                    'value' => $d->id,
                    'label' => $d->{$datatable_field},
                    'checked' => in_array($d->id, $relIds),
                    'data_val' => null,
                ];
            }
        } else {
            $valueArr = explode(';', (string) $value);
            foreach ($selects_data as $d) {
                $val = $d->{$datatable_field};
                if ($val == '' || !$d->id) continue;
                $chOptions[] = [
                    'value' => $d->id,
                    'label' => $val,
                    'checked' => in_array($val, $valueArr),
                    'data_val' => $val,
                ];
            }
        }
    }

    if (isset($form['dataquery'])) {
        $query = DB::select($form['dataquery']);
        $valueArr = explode(';', (string) $value);
        if ($query) {
            foreach ($query as $q) {
                $chOptions[] = [
                    'value' => $q->value,
                    'label' => $q->label,
                    'checked' => in_array($q->value, $valueArr),
                    'data_val' => $q->value,
                ];
            }
        }
    }
@endphp
<div class='mb-3 row {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}'
id='form-group-{{$name}}' style="{!! @$form['style'] !!}">
  <label class='col-form-label col-sm-2'>{{$form['label']}}
    @if(isset($required))
    <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}" >*</span>
    @endif
  </label>
  <div class="{{ isset($col_width)? $col_width : 'col-sm-10'}}">
    @foreach($chOptions as $chOpt)
        @include('crudbooster::partials.ch_check', [
            'name' => $name . '[]',
            'value' => $chOpt['value'],
            'label' => $chOpt['label'],
            'checked' => $chOpt['checked'],
            'disabled' => $isDisabled,
            'switch' => $single,
            'aria' => $form['label'],
            'data_val' => $chOpt['data_val'],
        ])
    @endforeach
    <div class="text-danger">{!! $errors->first($name)?"<i class='bi bi-info-circle-fill'></i> ".e($errors->first($name)):"" !!}
    </div>
    <p class='help-block'>{{ @$form['help'] }}</p>
  </div>
</div>
@if(isset($is_public) && $name == 'public_access')
   @if (isset($link))
<div class="mb-3 row {{$header_group_class}} {{ ($errors->first($name))?'has-error':'' }}">
  <div class="col-sm-2 col-form-label" style="padding-top: 7px">
    <label class="">Public URL</label>
  </div>
  <div class="col-sm-10">
    <a id="copyLink" href="{{ isset($link) ? $link : '' }}" target="_blank">{{ isset($link) ? $link : '' }}</a>
    &nbsp
    <button id="copyButton" class="btn btn-info">Copy</button>
  </div>
</div>
    @endif
@endif
