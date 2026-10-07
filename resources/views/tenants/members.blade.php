@extends('crudbooster::admin_template')

@section('content')

  @include('groups._page_head', [
    'crumb_name' => $tenant->name,
    'title' => trans('crudbooster.adm_users'),
    'count' => count($members),
  ])

<!-- List members -->
<div class="box">
  <div class="box-header mb-3">
    <input type="search" id="members-filter" class="form-control" style="max-width:280px"
      placeholder="{{ trans('crudbooster.adm_search_name_email') }}" autocomplete="off">
  </div>
  <div class="box-body table-responsive no-padding rel-table">
    <table class='table table-hover align-middle mb-0' id="members-table">
      <thead>
        <tr>
          <th>{!! __('crudbooster.name') !!}</th>
          <th>{{ trans('crudbooster.adm_role') }}</th>
          <th>{{ trans('crudbooster.adm_primary_group') }}</th>
          <th>{{ trans('crudbooster.adm_status') }}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($members as $member)
        <tr data-search="{{ strtolower($member->name.' '.$member->email) }}">
          <td>
            <div class="d-flex align-items-center gap-2">
              {!! \App\Http\Controllers\System\LogsController::avatarHtml($member->name, $member->photo, $member->id) !!}
              <div>{{$member->name}}<div class="small text-secondary">{{$member->email}}</div></div>
            </div>
          </td>
          <td>
            @if($member->is_superadmin == 1)
            <span class="ch-pill ch-pill-ok">{{ trans('crudbooster.superadmin') }}</span>
            @elseif($member->is_tenantadmin == 1)
            <span class="ch-pill ch-pill-warn">{{ trans('crudbooster.tenantadmin') }}</span>
            @else
            <span class="ch-pill ch-pill-gray">{{ $member->privilege ?: '-' }}</span>
            @endif
          </td>
          <td>{{ $member->primary_group_name ?: '-' }}</td>
          <td>
            @if($member->status == 'Inactive')
            <span class="ch-pill ch-pill-bad">{{ trans('crudbooster.adm_status_inactive') }}</span>
            @else
            <span class="ch-pill ch-pill-ok">{{ trans('crudbooster.adm_status_active') }}</span>
            @endif
          </td>
          <td class="text-end">
            <a class='btn btn-success btn-sm' href='{{CRUDBooster::adminpath("users/edit/$member->id")}}'>
              <i class="bi bi-pencil-fill"></i> {{ trans('crudbooster.adm_open_user') }}
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-secondary">{{ trans('crudbooster.adm_no_members') }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@push('bottom')
<script>
  // Filtro lato client sulla lista (nome/email), nessuna chiamata al server.
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
