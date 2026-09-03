<?php

if ((isset($form['datatable']) && isset($form['relationship_table'])) && $form['datatable'] && $form['relationship_table']) {
    $datatable_array = explode(",", $form['datatable']);
    $datatable_tab = $datatable_array[0];
    $datatable_field = $datatable_array[1];
    $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
    $foreignKey2 = CRUDBooster::getForeignKey($datatable_tab, $form['relationship_table']);

    $ids = DB::table($form['relationship_table'])->where($form['relationship_table'].'.'.$foreignKey, $id)->pluck($foreignKey2)->toArray();
    $value = DB::table($datatable_tab)->select($datatable_field)->whereIn('id', $ids)->pluck($datatable_field)->toArray();
} elseif (isset($form['dataquery']) && $form['dataquery']) {
    $dataquery = $form['dataquery'];
    $query = DB::select($dataquery);
    if ($query) {
        foreach ($query as $q) {
            if ($q->value == $value) {
                echo $q->label;
                break;
            }
        }
    }
} else {
    $value = explode(";", $value);
}

//dd($value);
foreach ($value as $k => $v) {

    if ($v == 1) {
        echo "<span class='ch-bool-badge ch-bool-yes'><span class='ch-bool-dot'></span>" . e(trans('crudbooster.confirmButtonText')) . "</span>";
    } else {
        echo "<span class='ch-bool-badge ch-bool-no'><span class='ch-bool-dot'></span>" . e(trans('crudbooster.confirmation_no')) . "</span>";
    }
}
?>