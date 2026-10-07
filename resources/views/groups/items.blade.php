@extends('crudbooster::admin_template')

@section('content')
<!-- Add item -->
<div>
  <div>
    @include('groups._page_head', [
      'crumb_name' => $group->name,
      'title' => trans('crudbooster.adm_items'),
      'count' => count($items),
      'opener_label' => trans('crudbooster.allow_item'),
    ])

    @include('groups._add_card', [
      'card_title' => __('crudbooster.allow_item'),
      'submit_label' => trans('crudbooster.button_add_item'),
    ])
  </div><!--END AUTO MARGIN-->

  <!-- List items -->
  <div>
    <div class="table-responsive rel-table">
      <table class='table table-hover align-middle mb-0'>
        <thead>
          <tr>
            <th>{!! __('crudbooster.title') !!}</th>
            <th>{!! __('crudbooster.subtitle') !!}</th>
            <th>Url</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $item)
          <tr>
            <td>{{$item->title}}</td>
            <td>{{$item->subtitle}}</td>
            <td>{{$item->url}}</td>
            <td class="text-end">
              @if(CRUDBooster::isDelete() && $button_edit)
              @include('groups._remove', ['url' => CRUDBooster::mainpath("$group_id/remove_item/$item->id")])
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-secondary">{{ trans('crudbooster.adm_no_rows') }}</td></tr>
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
