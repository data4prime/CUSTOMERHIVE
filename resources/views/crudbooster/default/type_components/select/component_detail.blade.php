<?php
if (isset($form['datatable'])) {
    $datatable = explode(',', $form['datatable']);
    $table = $datatable[0];
    $field = $datatable[1];
    //il valore selezionato puo' puntare a una riga ormai cancellata (FK
    //orfana, es. un privilegio eliminato): mostrare vuoto invece di
    //far crashare la pagina di dettaglio
    $related = CRUDBooster::first($table, ['id' => $value]);
    echo $related ? $related->$field : '';
}
if (isset($form['dataquery'])) {
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
}
if (isset($form['dataenum'])) {
    echo $value;
}
?>