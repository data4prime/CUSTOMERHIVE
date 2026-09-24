<?php

if ((isset($form['datatable']) && isset($form['relationship_table'])) && $form['datatable'] && $form['relationship_table']) {
    $datatable_array = explode(",", $form['datatable']);
    $datatable_tab = $datatable_array[0];
    $datatable_field = $datatable_array[1];
    $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
    $foreignKey2 = CRUDBooster::getForeignKey($datatable_tab, $form['relationship_table']);

    $ids = DB::table($form['relationship_table'])->where($form['relationship_table'].'.'.$foreignKey, $id)->pluck($foreignKey2)->toArray();
    $value = DB::table($datatable_tab)->select($datatable_field)->whereIn('id', $ids)->pluck($datatable_field)->toArray();
} elseif (isset($form['datatable']) && $form['datatable']) {
    // Bug preesistente: mancava isset() qui (a differenza del branch
    // gemello sopra) - un campo 'radio' senza 'datatable' impostato (es.
    // uno con solo 'dataenum', come MenusController::target_layout)
    // andava in errore "Undefined array key" su questa pagina di
    // dettaglio.
    $datatable = explode(',', $form['datatable']);
    $table = $datatable[0];
    $field = $datatable[1];
    $r = CRUDBooster::first($table, ['id' => $value])->$field;
    if ($r) {
        $value = [$r];
    } else {
        $value = [];
    }
} elseif (isset($form['dataquery']) && $form['dataquery']) {
    $dataquery = $form['dataquery'];
    $query = DB::select($dataquery);
    if ($query) {
        foreach ($query as $q) {
            if ($q->value == $value) {
                $value = [$q->label];
                break;
            }
        }
        if (! $value) $value = [];
    }
} else {
    $value = explode(";", $value);
    if (isset($form['dataenum'])) {
        // Stessa sintassi "valore|Etichetta" del form di modifica (type_
        // components/radio/component.blade.php): senza questo mapping
        // veniva mostrato il valore grezzo salvato (es. "0") invece
        // dell'etichetta (es. "Standard") - stesso bug corretto per
        // type=>select in component_detail.blade.php.
        $dataenum = $form['dataenum'];
        $dataenum = is_array($dataenum) ? $dataenum : explode(';', $dataenum);
        $value = array_map(function ($raw) use ($dataenum) {
            foreach ($dataenum as $d) {
                if (strpos($d, '|') !== false) {
                    [$val, $lab] = explode('|', $d, 2);
                } else {
                    $val = $lab = $d;
                }
                if ((string) $raw === (string) $val) {
                    return $lab;
                }
            }
            return $raw;
        }, $value);
    }
}

foreach ($value as $v) {
    echo "<span class='badge'>$v</span> ";
}
?>