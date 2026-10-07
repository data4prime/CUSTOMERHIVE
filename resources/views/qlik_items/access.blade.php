@extends('crudbooster::admin_template')
@section('content')
<!-- Allow group to see item -->
@include('qlik_items._relation', [
  'rel_title' => trans('crudbooster.qlik_item_access_title'),
  'rel_rows' => $groups,
  'rel_available' => $available_groups,
  'rel_opener' => trans('crudbooster.button_add_group'),
  'rel_modal_title' => $page_title,
  'rel_empty_available' => trans('crudbooster.adm_no_groups_available'),
  'rel_remove_url' => CRUDBooster::mainpath("$item_id/deauth/:id"),
])
@endsection
