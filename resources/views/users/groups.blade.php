@extends('crudbooster::admin_template')

@section('content')
<?php $primaryGroupId = UserHelper::primary_group($user->id); ?>
<div>
  <div>
    @include('groups._page_head', [
      'crumb_name' => $user->name,
      'title' => trans('crudbooster.adm_groups'),
      'count' => count($groups),
      'opener_label' => (CRUDBooster::isCreate() || CRUDBooster::isUpdate()) ? trans('crudbooster.button_add_group') : null,
      'opener_modal' => '#add-group-modal',
    ])
  </div><!--END AUTO MARGIN-->

  {{-- Modale di scelta: un clic su un gruppo lo aggiunge (stesso POST di prima a add_group) --}}
  @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())
  <form id="add-group-form" method="post" action="{{ $action }}">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="return_url" value="{{ $return_url }}">
    <input type="hidden" name="ref_mainpath" value="{{ CRUDBooster::mainpath() }}">
    <input type="hidden" name="name" id="add-group-id" value="">
  </form>
  <div class="modal fade" id="add-group-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $modal_title }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="search" id="add-group-search" class="form-control mb-3" placeholder="{{ trans('crudbooster.filter_search') }}" autocomplete="off">
          <div class="list-group" id="add-group-list">
            @foreach($available_groups as $g)
            <button type="button" class="list-group-item list-group-item-action" data-id="{{ $g->id }}" data-text="{{ mb_strtolower($g->name . ' ' . $g->description) }}">
              <div class="fw-semibold">{{ $g->name }}</div>
              @if($g->description)<div class="small text-secondary">{{ $g->description }}</div>@endif
            </button>
            @endforeach
          </div>
          <div class="text-center text-secondary py-3 {{ count($available_groups) ? 'd-none' : '' }}" id="add-group-empty">{{ trans('crudbooster.adm_no_groups_available') }}</div>
        </div>
      </div>
    </div>
  </div>
  @push('bottom')
  <script>
    (function () {
      var list = document.getElementById('add-group-list');
      var empty = document.getElementById('add-group-empty');
      list.addEventListener('click', function (e) {
        var item = e.target.closest('[data-id]');
        if (!item) { return; }
        document.getElementById('add-group-id').value = item.getAttribute('data-id');
        document.getElementById('add-group-form').submit();
      });
      document.getElementById('add-group-search').addEventListener('input', function () {
        var q = this.value.toLowerCase().trim(), shown = 0;
        list.querySelectorAll('[data-id]').forEach(function (el) {
          var ok = el.getAttribute('data-text').indexOf(q) !== -1;
          el.classList.toggle('d-none', !ok);
          if (ok) { shown++; }
        });
        empty.classList.toggle('d-none', shown > 0);
      });
      document.getElementById('add-group-modal').addEventListener('shown.bs.modal', function () {
        document.getElementById('add-group-search').focus();
      });
    })();
  </script>
  @endpush
  @endif

  <!-- List groups -->
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
          @forelse($groups as $group)
          <tr>
            <td>{{ $group->name }}</td>
            <td>{{ $group->description }}</td>
            <td class="text-end">
              @if($group->id == $primaryGroupId)
              <span class="ch-pill ch-pill-vio" title="{{ trans('crudbooster.adm_primary_group') }}">
                <i class="bi bi-trophy-fill"></i> {{ trans('crudbooster.adm_primary_group') }}
              </span>
              <span class="small text-secondary ms-1">{{ trans('crudbooster.adm_not_removable') }}</span>
              @elseif(CRUDBooster::isDelete() && $button_edit)
              @include('groups._remove', ['url' => CRUDBooster::mainpath("$user_id/remove_group/$group->id")])
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
