@extends('crudbooster::admin_template')

@section('content')
<!-- Allow tenant to group -->
<div>
  <div>
    @include('groups._page_head', [
      'crumb_name' => $group->name,
      'title' => trans('crudbooster.Tenants'),
      'count' => count($tenants),
      'opener_label' => trans('crudbooster.adm_add_tenant'),
    ])

    @include('groups._add_card', [
      'card_title' => $page_title,
      'card_icon' => 'bi bi-plus-circle',
      'submit_label' => trans('crudbooster.adm_add_tenant'),
    ])
  </div><!--END AUTO MARGIN-->

  <!-- List tenants -->
  <div>
    <div class="table-responsive rel-table">
      <table class='table table-hover align-middle mb-0'>
        <thead>
          <tr>
            <th>{!! __('crudbooster.name') !!}</th>
            <th>{!! __('crudbooster.description') !!}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($tenants as $tenant)
          <tr>
            <td>{{$tenant->name}}</td>
            <td>{{$tenant->description}}</td>
            <td class="text-end">
              @if(CRUDBooster::isDelete() && $button_edit)
              @include('groups._remove', ['url' => CRUDBooster::mainpath("$group_id/remove_tenant/$tenant->id")])
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="3" class="text-center text-secondary">{{ trans('crudbooster.adm_no_rows') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@push('bottom')
@include('groups._remove_script')
@endpush
@endsection
