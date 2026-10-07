@extends('crudbooster::admin_template')

@section('content')
<div>
  <div>
    @include('groups._page_head', [
      'crumb_name' => $group->name,
      'title' => __('crudbooster.members'),
      'count' => count($members),
      'opener_label' => (CRUDBooster::isCreate() || CRUDBooster::isUpdate()) ? trans('crudbooster.button_add_member') : null,
    ])

    @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())
    @include('groups._add_card', [
      'card_title' => __('crudbooster.add_member'),
      'submit_label' => trans('crudbooster.button_add_member'),
    ])
    @endif
  </div><!--END AUTO MARGIN-->

  <!-- List members -->
  <div>
    <div class="mb-3">
      <input type="search" id="members-filter" class="form-control" style="max-width:320px"
        placeholder="{{ trans('crudbooster.adm_search_name_email') }}" autocomplete="off">
    </div>
    <div class="table-responsive rel-table">
      <table class='table table-hover align-middle mb-0' id="members-table">
        <thead>
          <tr>
            <th>{!! __('crudbooster.name') !!}</th>
            <th>{!! __('crudbooster.privilege') !!}</th>
            <th></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($members as $member)
          <tr data-search="{{ strtolower($member->name.' '.$member->email) }}">
            <td>
              <div class="d-flex align-items-center gap-2">
                <img width="32" height="32" style="border-radius:50%;object-fit:cover" src="{{UserHelper::icon($member->id)}}" alt="">
                <div>{{$member->name}}<div class="small text-secondary">{{$member->email}}</div></div>
              </div>
            </td>
            <td>{{$member->privilege}}</td>
            <td>
              @if($group->id == $member->primary_group)
              <span class="ch-pill ch-pill-vio" title="{{ trans('crudbooster.adm_primary_group') }}">
                <i class="bi bi-trophy-fill"></i> {{ trans('crudbooster.adm_primary_group') }}
              </span>
              @endif
            </td>
            <td class="text-end">
              @if(CRUDBooster::isDelete() && $button_edit && $group->id !== $member->primary_group)
              @include('groups._remove', ['url' => CRUDBooster::mainpath("$group_id/remove_member/$member->id")])
              @elseif($group->id == $member->primary_group)
              @include('groups._remove', ['disabled_hint' => trans('crudbooster.adm_not_removable')])
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-secondary">{{ trans('crudbooster.adm_no_members') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('bottom')
@include('groups._remove_script')
<script>
  $(function () {
    $('#members-filter').on('input', function () {
      var q = $(this).val().toLowerCase().trim();
      $('#members-table tbody tr[data-search]').each(function () {
        $(this).toggle(q === '' || $(this).data('search').indexOf(q) !== -1);
      });
    });
  });
</script>
@endpush
@endsection
