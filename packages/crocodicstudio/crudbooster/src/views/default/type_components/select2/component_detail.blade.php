<?php
$datatable = isset($form['datatable']) ? $form['datatable'] : '';
if (isset($datatable) && ! isset($form['relationship_table'])) {
    $datatable = !empty($datatable) ? explode(',', $datatable) : '';
    if (!empty($datatable)) {
        $table = $datatable[0];
    $field = $datatable[1];
    echo CRUDBooster::first($table, ['id' => $value])->$field;
    }
    
}

if (isset($datatable) && isset($form['relationship_table'])) {
    $datatable_table = explode(',', $datatable)[0];
    $datatable_field = explode(',', $datatable)[1];
    if(isset($form['datatable_orig']) && $form['datatable_orig']  != ''){
        $params = explode("|", $form['datatable_orig']);
        if(!isset($params[2])) $params[2] = "id";
        $values = explode(",", DB::table($params[0])->where($params[2], $id)->first()->{$params[1]});
        $tableData = DB::table($datatable_table)->whereIn("id", $values)->select($datatable_field)->pluck($datatable_field)->toArray();
    } else {
        $foreignKey = CRUDBooster::getForeignKey($table, $form['relationship_table']);
        $foreignKey2 = CRUDBooster::getForeignKey($datatable_table, $form['relationship_table']);
        $ids = DB::table($form['relationship_table'])->where($foreignKey, $id)->pluck($foreignKey2)->toArray();

        $tableData = DB::table($datatable_table)->whereIn('id', $ids)->pluck($datatable_field)->toArray();
    }

    echo implode(", ", $tableData);
}

if (isset($form['dataenum'])) {
    echo $value;
}

?>
