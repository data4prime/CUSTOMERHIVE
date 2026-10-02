@foreach($addaction as $a)
<?php
    foreach ($row as $key => $val) {
      $a['url'] = str_replace("[".$key."]", $val, $a['url']);
    }

    $confirm_box = '';
    if (isset($a['confirmation']) && ! empty($a['confirmation']) && $a['confirmation']) {
      $a['confirmation_title'] = ! empty($a['confirmation_title']) ? $a['confirmation_title'] : trans('crudbooster.confirmation_title');
      $a['confirmation_text'] = ! empty($a['confirmation_text']) ? $a['confirmation_text'] : trans('crudbooster.confirmation_text');
      $a['confirmation_type'] = ! empty($a['confirmation_type']) ? $a['confirmation_type'] : 'warning';
      $a['confirmation_showCancelButton'] = empty($a['confirmation_showCancelButton']) ? 'true' : 'false';
      $a['confirmation_confirmButtonColor'] = ! empty($a['confirmation_confirmButtonColor']) ? $a['confirmation_confirmButtonColor'] : '#DD6B55';
      $a['confirmation_confirmButtonText'] = ! empty($a['confirmation_confirmButtonText']) ? $a['confirmation_confirmButtonText'] : trans('crudbooster.confirmation_yes');;
      $a['confirmation_cancelButtonText'] = ! empty($a['confirmation_cancelButtonText']) ? $a['confirmation_cancelButtonText'] : trans('crudbooster.confirmation_no');;
      $a['confirmation_closeOnConfirm'] = empty($a['confirmation_closeOnConfirm']) ? 'true' : 'false';

      $confirm_box = '
      swal({
          title: "'.$a['confirmation_title'].'",
          text: "'.$a['confirmation_text'].'",
          type: "'.$a['confirmation_type'].'",
          showCancelButton: '.$a['confirmation_showCancelButton'].',
          confirmButtonColor: "'.$a['confirmation_confirmButtonColor'].'",
          confirmButtonText: "'.$a['confirmation_confirmButtonText'].'",
          cancelButtonText: "'.$a['confirmation_cancelButtonText'].'",
          closeOnConfirm: '.$a['confirmation_closeOnConfirm'].', },
          function(){  location.href="'.$a['url'].'"});

      ';
    }

    $label = $a['label'];
    $title = isset($a['title']) ? $a['title'] : $a['label'] ;
    $icon = $a['icon'];
    $color = !isset($a['color']) ? 'primary' : $a['color'];
    $confirmation = isset($a['confirmation']) ? $a['confirmation'] : '';
    $target = isset($a['target']) ?: '_self';

    $url = $a['url'];
    if (isset($confirmation) && ! empty($confirmation)) {
      $url = "javascript:;";
    }

    if (isset($a['showIf'])) {

      $query = $a['showIf'];

      foreach ($row as $key => $val) {
          $query = str_replace("[".$key."]", '"'.$val.'"', $query);
      }

      @eval("if($query) {
        echo \"<a class='btn btn-sm btn-\$color' title='\$title' onclick='\$confirm_box' href='\$url' target='\$target'><i class='\$icon'></i>$label</a>&nbsp;\";
      }");
    } else {
      /*echo "<a class='btn btn-sm btn-$color' title='$title' onclick='$confirm_box' href='$url' target='$target'><i class='$icon'></i>$label</a>&nbsp;";*/
      echo "<a class='btn btn-sm btn-" . e($color) . "' title='" . e($title) . "' onclick='" . e($confirm_box) . "' href='" . e($url) . "' target='" . e($target) . "'>
              <i class='" . e($icon) . "'></i>" . e($label) . "
            </a>&nbsp;";

    }
    ?>
@endforeach
@if($button_action_style == 'button_text')
{{-- ba-wide: gli stili con testo escono dal riquadro 30x30 solo-icona di
     theme.css (.button_action .btn), vedi docs/refactoring/205. --}}
<div class='ba-wide ba-text'>

@if(CRUDBooster::isRead() && $button_detail)
<a class='btn btn-sm btn-primary btn-detail' title='{{trans("crudbooster.action_detail_data")}}'
  href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'>
  {{trans("crudbooster.action_detail_data")}}
</a>
@endif

@if(CRUDBooster::isUpdate() && $button_edit)
<a class='btn btn-sm btn-success btn-edit' title='{{trans("crudbooster.action_edit_data")}}'
  href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field }}'>
  {{trans("crudbooster.action_edit_data")}}
</a>
@endif

@if(CRUDBooster::isDelete() && $button_delete)
<?php $url = CRUDBooster::mainpath("delete/".$row->$pk);?>
<a class='btn btn-sm btn-danger btn-delete' title='{{trans("crudbooster.action_delete_data")}}' href='javascript:;'
  onclick='{{CRUDBooster::deleteConfirm($url)}}'>
  {{trans("crudbooster.action_delete_data")}}
</a>
@endif
</div>
@elseif($button_action_style == 'button_icon_text')
<div class='ba-wide ba-icon-text'>

@if(CRUDBooster::isRead() && $button_detail)
<a class='btn btn-sm btn-primary btn-detail' title='{{trans("crudbooster.action_detail_data")}}'
  href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'>
  <i class='bi bi-eye-fill'></i> {{trans("crudbooster.action_detail_data")}}
</a>
@endif

@if(CRUDBooster::isUpdate() && $button_edit)
<a class='btn btn-sm btn-success btn-edit' title='{{trans("crudbooster.action_edit_data")}}'
  href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field }}'>
  <i class='bi bi-pencil-fill'></i> {{trans("crudbooster.action_edit_data")}}
</a>
@endif

@if(CRUDBooster::isDelete() && $button_delete)
<?php $url = CRUDBooster::mainpath("delete/".$row->$pk);?>
<a class='btn btn-sm btn-danger btn-delete' title='{{trans("crudbooster.action_delete_data")}}' href='javascript:;'
  onclick='{{CRUDBooster::deleteConfirm($url)}}'>
  <i class='bi bi-trash-fill'></i> {{trans("crudbooster.action_delete_data")}}
</a>
@endif
</div>

@elseif($button_action_style == 'button_icon_strict')
{{--
  Come lo stile di default (icone, ultimo @else sotto), ma rispetta
  davvero $button_detail/$button_edit/$button_delete anche per il
  superadmin - lo stile di default delega a ModuleHelper::can_view()/
  can_edit()/can_delete(), che per il superadmin ritornano sempre true
  a prescindere dai flag del modulo ("admin can always see everything",
  scelta voluta e non toccata qui per non cambiare comportamento in
  tutti gli altri moduli). Usare questo stile quando un'azione va
  nascosta per chiunque, superadmin incluso (es. ApiTokensController:
  un token non ha un vero "modifica"/"dettaglio").
--}}

@if(CRUDBooster::isRead() && $button_detail)
<a class='btn btn-sm btn-primary btn-detail' title='{{trans("crudbooster.action_detail_data")}}'
  href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'><i
    class='bi bi-eye-fill'></i></a>
@endif

@if(CRUDBooster::isUpdate() && $button_edit)
<a class='btn btn-sm btn-success btn-edit' title='{{trans("crudbooster.action_edit_data")}}'
  href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field}}'><i
    class='bi bi-pencil-fill'></i></a>
@endif

@if(CRUDBooster::isDelete() && $button_delete)
<?php $url = CRUDBooster::mainpath("delete/".$row->$pk);?>
<a class='btn btn-sm btn-danger btn-delete' title='{{trans("crudbooster.action_delete_data")}}' href='javascript:;'
  onclick='{{CRUDBooster::deleteConfirm($url)}}'><i class='bi bi-trash-fill'></i></a>
@endif

@elseif(in_array($button_action_style, ['dropdown', 'button_dropdown'], true))

{{-- Markup Bootstrap 5: un solo bottone con la freccia, menu allineato a
     destra e con posizione "fixed" (non tagliato dal contenitore della
     tabella). Stile in theme.css (.ba-dropdown), docs/refactoring/205. --}}
<div class='dropdown ba-wide ba-dropdown'>
  <button type='button' class='btn btn-sm dropdown-toggle btn-action' data-bs-toggle='dropdown' data-bs-popper-config='{"strategy":"fixed"}' aria-expanded='false'>
    {{trans("crudbooster.action_label")}}
  </button>
  <ul class='dropdown-menu dropdown-menu-end' role='menu'>
    @foreach($addaction as $a)
    <?php
          foreach ($row as $key => $val) {
              $a['url'] = str_replace("[".$key."]", $val, $a['url']);
          }

          $label = $a['label'];
          $url = $a['url']."?return_url=".urlencode(Request::fullUrl());
          $icon = $a['icon'];
          $color = $a['color'] ?: 'primary';

          if (isset($a['showIf'])) {

              $query = $a['showIf'];

              foreach ($row as $key => $val) {
                  $query = str_replace("[".$key."]", '"'.$val.'"', $query);
              }

              @eval("if($query) {
                  echo \"<li><a class='dropdown-item' title='\$label' href='\$url'><i class='\$icon'></i> \$label</a></li>\";
              }");
          } else {
              echo "<li><a class='dropdown-item' title='$label' href='$url'><i class='$icon'></i> $label</a></li>";
          }
          ?>
    @endforeach

    @if(CRUDBooster::isRead() && $button_detail)
    <li>
      <a class='dropdown-item btn-detail' title='{{trans("crudbooster.action_detail_data")}}'
        href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'>
        <i class='bi bi-eye-fill'></i> {{trans("crudbooster.action_detail_data")}}
      </a>
    </li>
    @endif

    @if(CRUDBooster::isUpdate() && $button_edit)
    <li>
      <a class='dropdown-item btn-edit' title='{{trans("crudbooster.action_edit_data")}}'
        href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field}}'>
        <i class='bi bi-pencil-fill'></i> {{trans("crudbooster.action_edit_data")}}
      </a>
    </li>
    @endif

    @if(CRUDBooster::isDelete() && $button_delete)
    <?php $url = CRUDBooster::mainpath("delete/".$row->$pk); ?>
    @if(count($addaction) || (CRUDBooster::isRead() && $button_detail) || (CRUDBooster::isUpdate() && $button_edit))
    <li><hr class='dropdown-divider'></li>
    @endif
    <li>
      <a class='dropdown-item btn-delete' title='{{trans("crudbooster.action_delete_data")}}' href='javascript:;'
        onclick='{{CRUDBooster::deleteConfirm($url)}}'>
        <i class='bi bi-trash-fill'></i> {{trans("crudbooster.action_delete_data")}}
      </a>

    </li>
    @endif
  </ul>
</div>
@else

@if(ModuleHelper::can_view($this_module, $row))
<a class='btn btn-sm btn-primary btn-detail' title='{{trans("crudbooster.action_detail_data")}}'
  href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'><i
    class='bi bi-eye-fill'></i></a>
@endif

@if(ModuleHelper::can_edit($this_module, $row))
<a class='btn btn-sm btn-success btn-edit' title='{{trans("crudbooster.action_edit_data")}}'
  href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field}}'><i
    class='bi bi-pencil-fill'></i></a>
@endif

@if(ModuleHelper::can_delete($this_module, $row))
<?php $url = CRUDBooster::mainpath("delete/".$row->$pk);?>
<a class='btn btn-sm btn-danger btn-delete' title='{{trans("crudbooster.action_delete_data")}}' href='javascript:;'
  onclick='{{CRUDBooster::deleteConfirm($url)}}'><i class='bi bi-trash-fill'></i></a>
@endif

@endif