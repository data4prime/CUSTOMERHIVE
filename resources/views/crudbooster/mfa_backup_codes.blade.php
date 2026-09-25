@extends('crudbooster::admin_template')
@section('content')
<div>
  <div class="card card-default">
    <div class="card-header">
      <strong><i class="fa fa-shield"></i> {!! $page_title !!}</strong>
    </div>
    <div class="card-body">

      <p><span class="badge bg-success">{{ trans('crudbooster.mfa_enabled_success') }}</span></p>
      <p><strong>{{ trans('crudbooster.mfa_backup_codes_warning') }}</strong></p>

      <div class="bg-light p-3 mb-3" style="font-family: monospace; font-size: 16px; line-height: 2; max-width: 320px;">
        @foreach($codes as $code)
        <div>{{ $code }}</div>
        @endforeach
      </div>

      <a href="{{ CRUDBooster::adminPath('users/profile') }}" class="btn btn-primary">
        {{ trans('crudbooster.mfa_button_continue') }}
      </a>

    </div>
  </div>
</div>
@endsection
