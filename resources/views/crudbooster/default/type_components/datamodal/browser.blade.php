{{-- Stessi fogli di stile del resto dell'admin (Bootstrap 5, Bootstrap Icons, tema), in locale --}}
@include('crudbooster::partials.ch_head')

@include('crudbooster::admin_template_plugins')

<?php
$name = Request::get('name_column');
$coloms_alias = explode(',', 'ID,'.Request::get('columns_name_alias'));
if (count($coloms_alias) < 2) {
    $coloms_alias = $columns;
}

$dm_rows = [];
foreach ($result as $row) {
    $values = [];
    foreach ($columns as $col) {
        $values[] = $row->$col;
    }
    $payload = [];
    $payload['datamodal_id'] = $row->id;
    $payload['datamodal_label'] = $row->{$columns[1]} ?: $row->id;
    $select_data = Request::get('select_to');
    if ($select_data) {
        foreach (explode(',', $select_data) as $s) {
            $s_exp = explode(':', $s);
            // Alcuni chiamanti passano un id nudo per filtrare la lista, non
            // coppie "campo:destinazione": senza questo controllo si otteneva
            // una chiave vuota nel JSON ($('#') = selettore invalido).
            if (!isset($s_exp[1]) || $s_exp[1] === '') {
                continue;
            }
            $field_name = $s_exp[0];
            $payload[$s_exp[1]] = isset($row->$field_name) ? $row->$field_name : '';
        }
    }
    $dm_rows[] = ['values' => $values, 'payload' => $payload];
}
?>
@include('crudbooster::partials.ch_datamodal_list', [
    'dm_name' => $name,
    'dm_headers' => $coloms_alias,
    'dm_rows' => $dm_rows,
    'dm_paginator' => $result->appends(Request::all()),
])
