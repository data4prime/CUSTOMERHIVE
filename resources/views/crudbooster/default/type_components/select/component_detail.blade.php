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
    // Stessa sintassi "valore|Etichetta" gestita dal form di modifica
    // (type_components/select/component.blade.php): senza questo
    // mapping, un dataenum con valore ed etichetta diversi (es.
    // '1|Yes', '0|No') mostrava qui il valore grezzo invece
    // dell'etichetta (es. "1" invece di "Yes"). Nessun cambiamento per i
    // dataenum dove valore ed etichetta coincidono già (es. 'Active').
    $dataenum = $form['dataenum'];
    $dataenum = is_array($dataenum) ? $dataenum : explode(';', $dataenum);
    $label = $value;
    foreach ($dataenum as $d) {
        if (strpos($d, '|') !== false) {
            [$val, $lab] = explode('|', $d, 2);
        } else {
            $val = $lab = $d;
        }
        if ((string) $value === (string) $val) {
            $label = $lab;
            break;
        }
    }
    echo $label;
}
?>