@extends('crudbooster::admin_template')
@section('content')
<!-- Allow tenant to see item -->
@include('qlik_items._relation', [
  'rel_title' => trans('crudbooster.qlik_item_tenant_title'),
  'rel_rows' => $tenants,
  'rel_available' => $available_tenants,
  'rel_opener' => trans('crudbooster.adm_add_tenant'),
  'rel_modal_title' => $page_title,
  'rel_empty_available' => trans('crudbooster.adm_no_tenants_available'),
  'rel_remove_url' => CRUDBooster::mainpath("$item_id/remove_tenant/:id"),
])
@endsection
