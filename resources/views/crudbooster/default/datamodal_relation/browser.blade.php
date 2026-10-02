{{--
    Parte comune del popup di scelta dei campi "*_datamodal" delle relazioni:
    intestazione, ricerca e tabella. Ogni type_components/<tipo>_datamodal/
    browser.blade.php costruisce la propria query (cosa escludere, quali
    righe vedere) e include questo file. Vedi docs/refactoring/215.

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
?>
<form method='get' action="">
  {!! CRUDBooster::getUrlParameters(['q']) !!}
  <input type="text" placeholder="{{trans('crudbooster.datamodal_search_and_enter')}}" name="q"
    title="{{trans('crudbooster.datamodal_enter_to_search')}}" value="{{Request::get('q')}}" class="form-control">
</form>

<table id='table_dashboard' class='table table-striped table-bordered table-sm' style="margin-bottom: 0px">
  <thead>
    @foreach($coloms_alias as $col)
    <th>{{ $col }}</th>
    @endforeach
    <th width="5%">{{trans('crudbooster.datamodal_select')}}</th>
  </thead>
  <tbody>
    @foreach($result as $row)
    <tr>
      @foreach($columns as $col)
      <?php
                $img_extension = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                $ext = pathinfo($row->$col, PATHINFO_EXTENSION);
                if ($ext && in_array($ext, $img_extension)) {
                    echo "<td><a href='".asset($row->$col)."' data-lightbox='roadtrip'><img src='".asset($row->$col)."' width='50px' height='30px'/></a></td>";
                } else {
                    echo "<td>".str_limit(strip_tags($row->$col), 50)."</td>";
                }
                ?>
      @endforeach
      <?php
            // 'select_to' e' l'id usato per filtrare la lista (vedi la query
            // del tipo), non coppie "campo:destinazione" come nel componente
            // base 'datamodal': quel formato produceva una chiave vuota nel
            // JSON, che in JS ($('#' + chiave)) diventava il selettore
            // invalido '#' e mandava in eccezione lo script prima che il
            // popup potesse chiudersi.
            $select_data_result = [];
            $select_data_result['datamodal_id'] = $row->id;
            $select_data_result['datamodal_label'] = $row->{$columns[1]} ?: $row->id;
            foreach ($datamodal_extra as $extra_key => $extra_prop) {
                $select_data_result[$extra_key] = $row->{$extra_prop};
            }
            ?>
      <td>
        <a class='btn btn-primary' href='javascript:void(0)'
          onclick='parent.selectAdditionalData{{$name}}({!! json_encode($select_data_result) !!})'>
          <i class='bi bi-check-circle-fill'></i> {{trans('crudbooster.datamodal_select')}}
        </a>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
