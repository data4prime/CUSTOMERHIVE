@extends('crudbooster::admin_template',['target_layout' => isset($target_layout) ? $target_layout : ''])
@section('content')
<div>

  @if(CRUDBooster::getCurrentMethod() != 'getProfile' && $button_cancel)
  @if(g('return_url'))
  <p>
    <a title='Return' href='{{g("return_url")}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @else
  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @endif
  @endif

  <div class="card card-default">
    <div class="card-header">
      <strong>
        <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
      </strong>
    </div>

    <div class="card-body" style="padding:20px 0px 0px 0px">
      <?php
          $action = (@$row) ? CRUDBooster::mainpath("edit-save/$row->id") : CRUDBooster::mainpath("add-save");
          $return_url = isset($return_url) ? $return_url: g('return_url');
          ?>
      <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type='hidden' name='return_url' value='{{ @$return_url }}' />
        <input type='hidden' name='ref_mainpath' value='{{ CRUDBooster::mainpath() }}' />
        <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}' />
        @if($hide_form)
        <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
        @endif
        <div class="box-body" id="parent-form-area">
          @if(isset($command) && isset($command) && $command == 'detail')
          @include("crudbooster::default.form_detail")
          @else
          @include("crudbooster::default.form_body")
          @endif
        </div><!-- /.box-body -->

        <div class="box-footer" style="background: #F5F5F5">

          <div class="mb-3 row">
            <label class="col-form-label col-sm-2"></label>
            <div class="col-sm-10">
              @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
              @if(g('return_url'))
              <a href='{{g("return_url")}}' class='btn btn-default'>
                <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @else
              <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'>
                <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @endif
              @endif
              @if(isset($command) && $command == 'detail' && CRUDBooster::isUpdate() && $button_edit && @$row)
              <a href='{{ CRUDBooster::mainpath("edit/".$row->id)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field }}'
                class='btn btn-success'>
                <i class='fa fa-pencil'></i> {{trans("crudbooster.action_edit_data")}}
              </a>
              @endif

              @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

              @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
              <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}'
                class='btn btn-success'>
              @endif

              @if($button_save && isset($command) && isset($command) && $command != 'detail')
              <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}' class='btn btn-success'>
              @endif

              @endif
            </div>
          </div>
        </div><!-- /.box-footer-->
      </form>
    </div>
  </div>

  @if(CRUDBooster::getCurrentMethod() == 'getProfile')
  {{-- Sezione MFA sulla pagina profilo: caso speciale gia' esistente sopra
       per getProfile (nasconde il link "torna indietro"), stesso criterio
       qui - vedi docs/refactoring/096-mfa-fase-1-enrollment-totp.md.
       Il box "System Information" sopra (Tenant/Primary Group) si chiude
       correttamente prima di questa sezione grazie al fix in
       form_body.blade.php (riconosce anche 'primary_group', non solo
       'group') - vedi docs/refactoring/103-fix-save-invisibile-profilo-utenti.md.
       Il campo Tenant NON e' stato rimosso dal form: un tentativo in
       quel senso ha causato una corruzione dati reale, scartato. --}}
  <div class="card card-default">
    <div class="card-header">
      <strong><i class="fa fa-shield"></i> {{ trans('crudbooster.mfa_page_title_setup') }}</strong>
    </div>
    <div class="card-body">

      <div class="mb-3 row">
        <label class="col-form-label col-sm-2"></label>
        <div class="col-sm-10">
          @if(!empty($row->two_factor_confirmed_at))
            <span class="badge bg-success">{{ trans('crudbooster.mfa_status_enabled') }}</span>
          @else
            <span class="badge bg-secondary">{{ trans('crudbooster.mfa_status_disabled') }}</span>
          @endif
        </div>
      </div>

      @if(!empty($row->two_factor_confirmed_at))
        <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-disable') }}">
          @csrf
          <div class="mb-3 row">
            <label class="col-form-label col-sm-2">{{ trans('crudbooster.mfa_disable_confirm_password') }}</label>
            <div class="col-sm-10">
              <input type="password" name="password" required autocomplete="current-password" class="form-control">
            </div>
          </div>
          <div class="mb-3 row">
            <label class="col-form-label col-sm-2"></label>
            <div class="col-sm-10">
              <button type="submit" class="btn btn-danger">{{ trans('crudbooster.mfa_button_disable') }}</button>
            </div>
          </div>
        </form>

        <div class="mb-3 row">
          <label class="col-form-label col-sm-2"></label>
          <div class="col-sm-10">
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-regenerate-backup-codes') }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-default me-2" onclick="return confirm({{ json_encode(trans('crudbooster.mfa_regenerate_codes_warning')) }});">
                {{ trans('crudbooster.mfa_button_regenerate_codes') }}
              </button>
            </form>
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-revoke-devices') }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-default">{{ trans('crudbooster.mfa_button_revoke_devices') }}</button>
            </form>
          </div>
        </div>
      @else
        <div class="mb-3 row">
          <label class="col-form-label col-sm-2"></label>
          <div class="col-sm-10">
            <a href="{{ CRUDBooster::adminPath('users/mfa-setup') }}" class="btn btn-primary me-2">
              {{ trans('crudbooster.mfa_button_setup') }}
            </a>
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-revoke-devices') }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-default">{{ trans('crudbooster.mfa_button_revoke_devices') }}</button>
            </form>
          </div>
        </div>
      @endif

      @if($mfa_trusted_devices->isNotEmpty())
      <div class="mb-3 row">
        <label class="col-form-label col-sm-2"></label>
        <div class="col-sm-10">
          <hr>
          <h5>{{ trans('crudbooster.mfa_devices_title') }}</h5>
          <p class="help-block">{{ trans('crudbooster.mfa_devices_intro') }}</p>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>{{ trans('crudbooster.mfa_devices_col_device') }}</th>
                  <th>{{ trans('crudbooster.mfa_devices_col_first_seen') }}</th>
                  <th>{{ trans('crudbooster.mfa_devices_col_last_used') }}</th>
                  <th>{{ trans('crudbooster.mfa_devices_col_expires') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($mfa_trusted_devices as $device)
                <tr>
                  <td>{{ $device->user_agent ?: '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($device->created_at)->format('d/m/Y H:i') }}</td>
                  <td>{{ $device->last_used_at ? \Illuminate\Support\Carbon::parse($device->last_used_at)->format('d/m/Y H:i') : '—' }}</td>
                  <td>{{ \Illuminate\Support\Carbon::parse($device->trusted_until)->format('d/m/Y') }}</td>
                  <td>
                    <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-revoke-device/' . $device->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-default btn-sm">{{ trans('crudbooster.mfa_button_revoke_device') }}</button>
                    </form>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      @endif

    </div>
  </div>
  @endif

</div><!--END AUTO MARGIN-->

@endsection