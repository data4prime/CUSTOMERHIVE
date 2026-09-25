@extends('crudbooster::admin_template')
@section('content')
<div>
  <div class="card card-default">
    <div class="card-header">
      <strong><i class="fa fa-shield"></i> {!! $page_title !!}</strong>
    </div>
    <div class="card-body">

      <p>{{ trans('crudbooster.mfa_setup_intro') }}</p>

      <div style="margin: 20px 0;">
        {!! $qr_svg !!}
      </div>

      <p>
        {{ trans('crudbooster.mfa_label_manual_key') }}
        <code style="font-size: 14px;">{{ $secret }}</code>
      </p>

      <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-confirm') }}">
        @csrf
        <div class="mb-3 row">
          <label class="col-form-label col-sm-2">{{ trans('crudbooster.mfa_label_confirm_code') }}</label>
          <div class="col-sm-10">
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}"
                   maxlength="6" required autofocus class="form-control" placeholder="000000">
          </div>
        </div>
        <div class="mb-3 row">
          <label class="col-form-label col-sm-2"></label>
          <div class="col-sm-10">
            <button type="submit" class="btn btn-primary">{{ trans('crudbooster.mfa_button_activate') }}</button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection
