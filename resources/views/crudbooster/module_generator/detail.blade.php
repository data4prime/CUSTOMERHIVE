@extends('crudbooster::admin_template')

@section('content')
{{-- Dettaglio di un modulo come nel mockup: icona vera al posto del nome della classe
     e matrice (sola lettura) dei tenant su cui il modulo e' attivo. --}}
@php
    $mgIconClass = \App\Helpers\IconMap::toBi($row->icon);
@endphp
<div>
  <p>
    <a title='Main Module' href="{{ CRUDBooster::mainpath() }}">
      <i class='bi bi-chevron-left'></i>&nbsp;
      {{ trans('crudbooster.form_back_to_list', ['module' => CRUDBooster::getCurrentModule()->name]) }}
    </a>
  </p>

  <div class="card card-default">
    <div class="card-body" style="padding:20px 0 0 0">
      <div class="box-body">
        @push('head')
        <style type="text/css">
          #table-detail tr td:first-child { font-weight: bold; width: 25%; }
        </style>
        @endpush
        <div class="table-responsive">
          <table id="table-detail" class="table table-striped">
            <tr><td>{{ trans('crudbooster.name') }}</td><td>{{ $row->name }}</td></tr>
            <tr><td>{{ trans('crudbooster.mg_detail_table') }}</td><td>{{ $row->table_name }}</td></tr>
            <tr>
              <td>{{ trans('crudbooster.mg_detail_icon') }}</td>
              <td>
                @if($row->icon)
                <span class="d-inline-flex align-items-center gap-2"><i class="{{ $mgIconClass }}" style="font-size:20px"></i> <span class="text-secondary small">{{ $row->icon }}</span></span>
                @endif
              </td>
            </tr>
            <tr><td>{{ trans('crudbooster.mg_detail_path') }}</td><td>{{ $row->path }}</td></tr>
            <tr><td>{{ trans('crudbooster.mg_detail_controller') }}</td><td>{{ $row->controller }}</td></tr>
          </table>
        </div>

        <div class="px-3 pb-3">
          <label class="fw-semibold mb-2">{{ trans('crudbooster.mg_enable_on_tenants') }}</label>
          <div class="table-responsive rel-table">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  @foreach($tenants as $tenant)
                  <th class="text-center">{{ $tenant->name }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                <tr>
                  @foreach($tenants as $tenant)
                  <td class="text-center">
                    @if(ModuleHelper::is_enabled($row->id, $tenant->id))
                    <span class="ch-pill ch-pill-ok" title="{{ trans('crudbooster.mg_enabled') }}"><i class="bi bi-check-lg"></i></span>
                    @else
                    <span class="ch-pill ch-pill-gray" title="{{ trans('crudbooster.mg_disabled') }}"><i class="bi bi-dash-lg"></i></span>
                    @endif
                  </td>
                  @endforeach
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="box-footer" style="background: var(--ch-bg)">
        <div class="d-flex justify-content-end">
          @if(CRUDBooster::isSuperadmin())
          <a href="{{ CRUDBooster::mainpath('edit/' . $row->id) }}" class="btn btn-primary"><i class="bi bi-pencil-fill"></i> {{ trans('crudbooster.button_edit') }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
