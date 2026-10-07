{{--
    Parte comune del popup di scelta dei campi "*_datamodal" delle relazioni:
    ogni type_components/<tipo>_datamodal/browser.blade.php costruisce la propria
    query (cosa escludere, quali righe vedere) e include questo file. La lista
    (ricerca + tabella) e' in partials/ch_datamodal_list. Vedi docs/refactoring/215.

    Variabili attese dal file che include (piu' $columns e $q, che arrivano da
    CBController::getModalData):
      $result          righe da mostrare (Collection / array di oggetti)
      $datamodal_extra mappa opzionale chiave => proprieta' della riga da
                       mandare al form padre oltre a id/label. Default:
                       ['datamodal_description' => 'description'].
--}}
{{-- Stessi fogli di stile del resto dell'admin (Bootstrap 5, Bootstrap Icons, tema), in locale --}}
@include('crudbooster::partials.ch_head')

@include('crudbooster::admin_template_plugins')

<?php
$name = Request::get('name_column');
$coloms_alias = explode(',', 'ID,'.Request::get('columns_name_alias'));
if (count($coloms_alias) < 2) {
    $coloms_alias = $columns;
}
$datamodal_extra = $datamodal_extra ?? ['datamodal_description' => 'description'];

$dm_rows = [];
foreach ($result as $row) {
    $values = [];
    foreach ($columns as $col) {
        $values[] = $row->$col;
    }
    // 'select_to' e' l'id usato per filtrare la lista (vedi la query del tipo),
    // non coppie "campo:destinazione" come nel componente base 'datamodal'.
    $payload = [];
    $payload['datamodal_id'] = $row->id;
    $payload['datamodal_label'] = $row->{$columns[1]} ?: $row->id;
    foreach ($datamodal_extra as $extra_key => $extra_prop) {
        $payload[$extra_key] = $row->{$extra_prop};
    }
    $dm_rows[] = ['values' => $values, 'payload' => $payload];
}
?>
@include('crudbooster::partials.ch_datamodal_list', [
    'dm_name' => $name,
    'dm_headers' => $coloms_alias,
    'dm_rows' => $dm_rows,
])
