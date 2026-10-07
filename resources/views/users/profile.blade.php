@extends('crudbooster::admin_template')
@section('content')
@php
    // Senza foto: niente avatar di default, le iniziali del nome (come in Utenti).
    $photoUrl = empty($row->photo)
        ? ''
        : (preg_match('#^https?://#', $row->photo) ? $row->photo : asset($row->photo));
    $photoInitials = '';
    foreach (array_slice(preg_split('/\s+/u', trim((string) $row->name), -1, PREG_SPLIT_NO_EMPTY), 0, 2) as $w) {
        $photoInitials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    $photoInitials = $photoInitials ?: '?';
    $currentTenant = \App\Tenant::find($row->tenant);
    $currentGroup = \App\Group::find($row->primary_group);
    $hasTotp = !empty($row->two_factor_confirmed_at);
@endphp

{{-- Pagina profilo a sezioni (Generale / MFA / Sistema / Password). Ogni
     sezione e' un pannello con il suo endpoint JSON (AdminCmsUsersController::
     postProfile*): si cambia sezione e si salva senza ricaricare la pagina.
     Testi sempre da trans(); quelli usati dal JS passano da $jsText. --}}
<style>
  .ch-profile h1 { font-size: 22px; margin: 0 0 20px; }
  .ch-profile-layout { display: flex; gap: 24px; align-items: flex-start; }
  .ch-profile-content { flex: 1; min-width: 0; }
  .ch-profile-nav { width: 250px; flex: none; padding: 8px; background: var(--ch-surface, var(--ch-surface)); border: 1px solid var(--ch-border, var(--ch-border)); border-radius: var(--ch-radius-lg, 14px); box-shadow: var(--ch-shadow-sm); }
  .ch-profile-nav a { display: flex; gap: 12px; align-items: center; padding: 11px 14px; border-radius: var(--ch-radius-md, 10px); color: var(--ch-text-secondary, var(--ch-text-secondary)); cursor: pointer; font-weight: 500; text-decoration: none; }
  .ch-profile-nav a:hover { background: var(--ch-bg, var(--ch-bg)); }
  .ch-profile-nav a.is-active { background: var(--ch-accent-soft, var(--ch-accent-soft)); color: var(--ch-accent-dark, var(--ch-accent-dark)); font-weight: 600; }
  .ch-profile-nav .ch-nav-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--ch-bg, var(--ch-bg)); display: grid; place-items: center; font-size: 15px; }
  .ch-profile-nav a.is-active .ch-nav-icon { background: var(--ch-surface); }
  .ch-pane { display: none; background: var(--ch-surface, var(--ch-surface)); border: 1px solid var(--ch-border, var(--ch-border)); border-radius: var(--ch-radius-lg, 14px); box-shadow: var(--ch-shadow-sm); padding: 24px; }
  .ch-pane.is-active { display: block; }
  .ch-pane h2 { font-size: 16px; margin: 0 0 4px; }
  .ch-pane .ch-sub { color: var(--ch-text-muted, var(--ch-text-muted)); margin: 0 0 20px; }
  .ch-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .ch-full { grid-column: 1 / -1; }
  .ch-pane label { display: block; font-size: 12px; font-weight: 600; color: var(--ch-text-secondary, var(--ch-text-secondary)); margin-bottom: 5px; }
  .ch-hint { font-size: 12px; color: var(--ch-text-muted, var(--ch-text-muted)); margin-top: 4px; }
  .ch-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--ch-border, var(--ch-border)); }
  .ch-alert { border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; }
  .ch-alert.is-ok { background: var(--ch-success-soft, var(--ch-success-soft)); color: var(--ch-success, var(--ch-success)); }
  .ch-alert.is-error { background: var(--ch-danger-soft, var(--ch-danger-soft)); color: var(--ch-danger, var(--ch-danger)); }
  .ch-lock { font-size: 12px; color: var(--ch-warning, var(--ch-warning)); background: var(--ch-warning-soft, var(--ch-warning-soft)); border-radius: 8px; padding: 6px 10px; display: inline-block; margin-bottom: 14px; }
  .ch-email-panel { border: 1px solid var(--ch-border, var(--ch-border)); border-radius: 10px; padding: 16px; background: var(--ch-bg, var(--ch-bg)); margin-top: 10px; }
  .ch-strength { height: 5px; border-radius: 3px; background: var(--ch-border, var(--ch-bg)); margin-top: 8px; overflow: hidden; }
  .ch-strength i { display: block; height: 100%; width: 0; transition: width .2s, background .2s; }
  .ch-mfa-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--ch-border, var(--ch-border)); gap: 12px; flex-wrap: wrap; }
  .ch-mfa-row:last-child { border-bottom: 0; }
  .ch-qlik-row { border: 1px solid var(--ch-border, var(--ch-border)); border-radius: 10px; padding: 16px; background: var(--ch-bg, var(--ch-bg)); margin-bottom: 12px; }
  .ch-qlik-row .ch-qlik-remove { margin-top: 12px; }
  @media (max-width: 800px) {
    .ch-profile-layout { flex-direction: column-reverse; }
    .ch-profile-nav { width: 100%; display: flex; gap: 6px; overflow-x: auto; }
    .ch-profile-nav a { white-space: nowrap; }
    .ch-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="ch-profile">
  <h1><i class="{{ CRUDBooster::getCurrentModule()->icon }}"></i> {!! $page_title !!}</h1>

  <div class="ch-profile-layout">
    <div class="ch-profile-content">

      {{-- ============ GENERALE ============ --}}
      <section class="ch-pane is-active" id="pane-general" data-pane="general">
        <h2>{{ trans('crudbooster.profile_section_general') }}</h2>
        <p class="ch-sub">{{ trans('crudbooster.profile_section_general_sub') }}</p>
        <div class="ch-alert" role="alert" hidden></div>

        <form class="ch-ajax" method="post" action="{{ CRUDBooster::adminPath('users/profile-general') }}" enctype="multipart/form-data" novalidate>
          @csrf
          {{-- Stesso componente del campo "image" (foto in Utenti): senza foto caricata mostra l'avatar di default per ruolo. --}}
          <div class="mb-4">
            @include('crudbooster::partials.ch_image', [
              'name' => 'photo', 'ch_label' => trans('crudbooster.profile_photo_change'), 'src' => $photoUrl,
              'has_file' => !empty($row->photo), 'shape' => 'circle', 'size' => 96, 'initials' => $photoInitials,
              'change_label' => trans('crudbooster.profile_photo_change'), 'help' => trans('crudbooster.profile_photo_hint'),
            ])
            @include('crudbooster::default.type_components.image.asset')
          </div>

          <div class="ch-grid">
            <div>
              <label for="ch-name">{{ trans('crudbooster.profile_field_name') }}</label>
              <input type="text" class="form-control" id="ch-name" name="name" value="{{ $row->name }}" required>
            </div>
            <div>
              <label>{{ trans('crudbooster.profile_field_email') }}</label>
              <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" id="ch-email-current" value="{{ $row->email }}" disabled>
                <button type="button" class="btn btn-secondary" id="ch-email-toggle" style="white-space:nowrap;">{{ trans('crudbooster.profile_email_change_button') }}</button>
              </div>
            </div>
            <div>
              <label for="ch-expiry">{{ trans('crudbooster.profile_field_expiry') }}</label>
              <input type="date" class="form-control" id="ch-expiry" name="data_scadenza" value="{{ empty($row->data_scadenza) ? '' : date('Y-m-d', strtotime($row->data_scadenza)) }}" @if(!$canManage) disabled @endif>
              @if(!$canManage)<div class="ch-hint">{{ trans('crudbooster.profile_readonly_admin_only') }}</div>@endif
            </div>
            <div>
              <label for="ch-status">{{ trans('crudbooster.profile_field_status') }}</label>
              <select class="form-select" id="ch-status" name="status" @if(!$canManage) disabled @endif>
                <option value="Active" @if($row->status === 'Active') selected @endif>{{ trans('crudbooster.profile_status_active') }}</option>
                <option value="Inactive" @if($row->status !== 'Active') selected @endif>{{ trans('crudbooster.profile_status_inactive') }}</option>
              </select>
              @if(!$canManage)<div class="ch-hint">{{ trans('crudbooster.profile_readonly_admin_only') }}</div>@endif
            </div>
            <div>
              <label for="ch-lang">{{ trans('crudbooster.profile_field_language') }}</label>
              <select class="form-select" id="ch-lang" name="lang">
                <option value="en" @if($row->lang === 'en') selected @endif>English</option>
                <option value="it" @if($row->lang === 'it') selected @endif>Italiano</option>
              </select>
            </div>
          </div>

          <div class="ch-actions">
            <button type="submit" class="btn btn-primary">{{ trans('crudbooster.profile_button_save') }}</button>
          </div>
        </form>

        {{-- Cambio email: pannello separato dal form sopra (nessun nome sugli
             input, FormData costruito a mano dal JS) cosi' la password
             attuale non viaggia mai insieme al salvataggio di "Generale". --}}
        <div class="ch-email-panel" id="ch-email-panel" hidden>
          <div class="ch-alert" role="alert" hidden></div>
          <p class="ch-hint" style="margin-top:0;">{{ trans('crudbooster.profile_email_change_warning') }}</p>

          <div id="ch-email-step1">
            <div class="ch-grid">
              <div>
                <label for="ch-new-email">{{ trans('crudbooster.profile_email_new') }}</label>
                <input type="email" class="form-control" id="ch-new-email" autocomplete="off">
              </div>
              <div>
                <label for="ch-email-password">{{ trans('crudbooster.profile_email_current_password') }}</label>
                <input type="password" class="form-control" id="ch-email-password" autocomplete="current-password">
              </div>
            </div>
            <div class="ch-actions" style="border-top:0;padding-top:0;">
              <button type="button" class="btn btn-secondary" id="ch-email-cancel">{{ trans('crudbooster.profile_button_cancel') }}</button>
              @php
                $emailMode = \App\Helpers\MfaHelper::emailChangeMode(\App\Helpers\UserHelper::me());
                $emailSendLabel = $emailMode === 'email' ? 'profile_email_send_code' : ($emailMode === 'totp' ? 'profile_email_continue' : 'profile_email_change_button');
              @endphp
              <button type="button" class="btn btn-primary" id="ch-email-send">{{ trans('crudbooster.' . $emailSendLabel) }}</button>
            </div>
          </div>

          <div id="ch-email-step2" hidden>
            <div class="ch-grid">
              <div id="ch-email-code-wrap">
                <label for="ch-email-code">{{ trans('crudbooster.profile_email_code_label') }}</label>
                <input type="text" class="form-control" id="ch-email-code" inputmode="numeric" autocomplete="one-time-code" maxlength="6">
              </div>
              <div id="ch-email-totp-wrap" hidden>
                <label for="ch-email-totp">{{ trans('crudbooster.profile_email_totp_label') }}</label>
                <input type="text" class="form-control" id="ch-email-totp" inputmode="numeric" autocomplete="one-time-code" maxlength="6">
              </div>
            </div>
            <div class="ch-actions" style="border-top:0;padding-top:0;">
              <button type="button" class="btn btn-secondary" id="ch-email-cancel2">{{ trans('crudbooster.profile_button_cancel') }}</button>
              <button type="button" class="btn btn-primary" id="ch-email-confirm">{{ trans('crudbooster.profile_email_confirm_button') }}</button>
            </div>
          </div>
        </div>
      </section>

      {{-- ============ MFA ============ --}}
      <section class="ch-pane" id="pane-mfa" data-pane="mfa">
        <h2>{{ trans('crudbooster.profile_section_mfa') }}</h2>
        <p class="ch-sub">{{ trans('crudbooster.profile_section_mfa_sub') }}</p>

        <div class="ch-mfa-row">
          <div><strong>{{ trans('crudbooster.mfa_page_title_setup') }}</strong></div>
          @if($hasTotp)
            <span class="badge bg-success">{{ trans('crudbooster.mfa_status_enabled') }}</span>
          @else
            <span class="badge bg-secondary">{{ trans('crudbooster.mfa_status_disabled') }}</span>
          @endif
        </div>

        @if($hasTotp)
          <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-disable') }}" class="ch-mfa-row">
            @csrf
            <div style="flex:1;min-width:220px;">
              <label for="ch-mfa-disable-pwd">{{ trans('crudbooster.mfa_disable_confirm_password') }}</label>
              <input type="password" id="ch-mfa-disable-pwd" name="password" required autocomplete="current-password" class="form-control">
            </div>
            <button type="submit" class="btn btn-danger">{{ trans('crudbooster.mfa_button_disable') }}</button>
          </form>

          <div class="ch-mfa-row">
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-regenerate-backup-codes') }}">
              @csrf
              <button type="submit" class="btn btn-secondary" onclick="return confirm({{ json_encode(trans('crudbooster.mfa_regenerate_codes_warning')) }});">
                {{ trans('crudbooster.mfa_button_regenerate_codes') }}
              </button>
            </form>
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-revoke-devices') }}">
              @csrf
              <button type="submit" class="btn btn-secondary">{{ trans('crudbooster.mfa_button_revoke_devices') }}</button>
            </form>
          </div>
        @else
          <div class="ch-mfa-row">
            <a href="{{ CRUDBooster::adminPath('users/mfa-setup') }}" class="btn btn-primary">{{ trans('crudbooster.mfa_button_setup') }}</a>
            <form method="post" action="{{ CRUDBooster::adminPath('users/mfa-revoke-devices') }}">
              @csrf
              <button type="submit" class="btn btn-secondary">{{ trans('crudbooster.mfa_button_revoke_devices') }}</button>
            </form>
          </div>
        @endif

        @if($mfa_trusted_devices->isNotEmpty())
          <div style="margin-top:20px;">
            <h5>{{ trans('crudbooster.mfa_devices_title') }}</h5>
            <p class="ch-hint">{{ trans('crudbooster.mfa_devices_intro') }}</p>
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
                        <button type="submit" class="btn btn-secondary btn-sm">{{ trans('crudbooster.mfa_button_revoke_device') }}</button>
                      </form>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </section>

      {{-- ============ SISTEMA ============ --}}
      <section class="ch-pane" id="pane-system" data-pane="system">
        <h2>{{ trans('crudbooster.profile_section_system') }}</h2>
        <p class="ch-sub">{{ trans('crudbooster.profile_section_system_sub') }}</p>
        <div class="ch-alert" role="alert" hidden></div>

        @if($canManage)
          <form class="ch-ajax" method="post" action="{{ CRUDBooster::adminPath('users/profile-system') }}" novalidate>
            @csrf
            <div class="ch-grid">
              <div>
                <label for="ch-tenant">{{ trans('crudbooster.profile_field_tenant') }}</label>
                <select class="form-select" id="ch-tenant" name="tenant" @if(!$canEditTenant) disabled @endif>
                  @foreach($tenants as $t)
                    <option value="{{ $t->id }}" @if((int) $t->id === (int) $row->tenant) selected @endif>{{ $t->name }}</option>
                  @endforeach
                </select>
                @if(!$canEditTenant)<div class="ch-hint">{{ trans('crudbooster.profile_tenant_locked_hint') }}</div>@endif
              </div>
              <div>
                <label for="ch-group">{{ trans('crudbooster.profile_field_primary_group') }}</label>
                <select class="form-select" id="ch-group" name="primary_group"></select>
              </div>
            </div>
            <div class="ch-actions">
              <button type="submit" class="btn btn-primary">{{ trans('crudbooster.profile_button_save') }}</button>
            </div>
          </form>
        @else
          <div class="ch-lock"><i class="bi bi-lock-fill"></i> {{ trans('crudbooster.profile_system_locked_hint') }}</div>
          <div class="ch-grid">
            <div>
              <label>{{ trans('crudbooster.profile_field_tenant') }}</label>
              <input type="text" class="form-control" value="{{ optional($currentTenant)->name }}" disabled>
            </div>
            <div>
              <label>{{ trans('crudbooster.profile_field_primary_group') }}</label>
              <input type="text" class="form-control" value="{{ optional($currentGroup)->name }}" disabled>
            </div>
          </div>
        @endif
      </section>

      {{-- ============ PASSWORD ============ --}}
      <section class="ch-pane" id="pane-password" data-pane="password">
        <h2>{{ trans('crudbooster.profile_section_password') }}</h2>
        <p class="ch-sub">{{ trans('crudbooster.profile_section_password_sub') }} {{ trans('crudbooster.reset_password_policy_hint') }}</p>
        <div class="ch-alert" role="alert" hidden></div>

        <form id="ch-password-form" method="post" action="{{ CRUDBooster::adminPath('users/profile-password-start') }}" novalidate>
          @csrf
          <div class="ch-grid">
            <div class="ch-full">
              <label for="ch-pwd-current">{{ trans('crudbooster.profile_password_current') }}</label>
              <input type="password" class="form-control" id="ch-pwd-current" name="current_password" autocomplete="current-password">
            </div>
            <div>
              <label for="ch-pwd-new">{{ trans('crudbooster.profile_password_new') }}</label>
              <input type="password" class="form-control" id="ch-pwd-new" name="password" autocomplete="new-password">
              <div class="ch-strength"><i id="ch-pwd-strength"></i></div>
              <div class="ch-hint" id="ch-pwd-strength-label"></div>
            </div>
            <div>
              <label for="ch-pwd-confirm">{{ trans('crudbooster.profile_password_confirm') }}</label>
              <input type="password" class="form-control" id="ch-pwd-confirm" name="password_confirmation" autocomplete="new-password">
              <div class="ch-hint" id="ch-pwd-match"></div>
            </div>
          </div>
          <div class="ch-actions" id="ch-pwd-step1-actions">
            <button type="submit" class="btn btn-primary">{{ trans('crudbooster.' . (\App\Helpers\MfaHelper::emailChangeMode(\App\Helpers\UserHelper::me()) === 'none' ? 'profile_password_confirm_button' : 'profile_password_send_code')) }}</button>
          </div>
        </form>

        <div id="ch-pwd-step2" hidden style="margin-top:16px;">
          <div class="ch-grid">
            <div>
              <label for="ch-pwd-code" id="ch-pwd-code-label"></label>
              <input type="text" class="form-control" id="ch-pwd-code" inputmode="numeric" autocomplete="one-time-code" maxlength="6">
            </div>
          </div>
          <div class="ch-actions" style="border-top:0;padding-top:0;">
            <button type="button" class="btn btn-secondary" id="ch-pwd-cancel">{{ trans('crudbooster.profile_button_cancel') }}</button>
            <button type="button" class="btn btn-primary" id="ch-pwd-confirm-btn">{{ trans('crudbooster.profile_password_confirm_button') }}</button>
          </div>
        </div>
      </section>

      @if(!empty($qlikEnabled))
      {{-- ============ QLIK (solo con modulo in licenza) ============ --}}
      <section class="ch-pane" id="pane-qlik" data-pane="qlik">
        <h2>{{ trans('crudbooster.profile_section_qlik') }}</h2>
        <p class="ch-sub">{{ trans('crudbooster.profile_section_qlik_sub') }}</p>
        <div class="ch-alert" role="alert" hidden></div>

        @if(!$canManage)<div class="ch-lock"><i class="bi bi-lock-fill"></i> {{ trans('crudbooster.profile_qlik_locked_hint') }}</div>@endif

        <form class="ch-ajax" method="post" action="{{ CRUDBooster::adminPath('users/profile-qlik') }}" novalidate>
          @csrf
          <div id="ch-qlik-rows">
            @foreach($qlikUsers as $qu)
              <div class="ch-qlik-row">
                <div class="ch-grid">
                  <div>
                    <label>{{ trans('crudbooster.profile_qlik_field_conf') }}</label>
                    <select class="form-select" data-f="qlik_conf_id" @if(!$canManage) disabled @endif>
                      <option value="">{{ trans('crudbooster.profile_qlik_select_conf') }}</option>
                      @foreach($qlikConfs as $c)
                        <option value="{{ $c->id }}" data-type="{{ $c->type }}" @if((int) $c->id === (int) $qu->qlik_conf_id) selected @endif>{{ $c->confname }} ({{ $c->type }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label>{{ trans('crudbooster.profile_qlik_field_login') }}</label>
                    <input type="text" class="form-control" data-f="qlik_login" value="{{ $qu->qlik_login }}" @if(!$canManage) disabled @endif>
                  </div>
                  <div>
                    <label>{{ trans('crudbooster.profile_qlik_field_directory') }}</label>
                    <input type="text" class="form-control" data-f="user_directory" value="{{ $qu->user_directory }}" @if(!$canManage) disabled @endif>
                    <div class="ch-hint">{{ trans('crudbooster.profile_qlik_onprem_hint') }}</div>
                  </div>
                  <div>
                    <label>{{ trans('crudbooster.profile_qlik_field_idp') }}</label>
                    <input type="text" class="form-control" data-f="idp_qlik" value="{{ $qu->idp_qlik }}" readonly>
                    <div class="ch-hint">{{ trans('crudbooster.profile_qlik_idp_hint') }}</div>
                  </div>
                </div>
                @if($canManage)<button type="button" class="btn btn-secondary btn-sm ch-qlik-remove">{{ trans('crudbooster.profile_qlik_remove') }}</button>@endif
              </div>
            @endforeach
          </div>
          <p class="ch-hint" id="ch-qlik-none" @if($qlikUsers->isNotEmpty()) hidden @endif>{{ trans('crudbooster.profile_qlik_none') }}</p>

          @if($canManage)
            <div class="ch-actions">
              <button type="button" class="btn btn-secondary" id="ch-qlik-add">{{ trans('crudbooster.profile_qlik_add') }}</button>
              <button type="submit" class="btn btn-primary">{{ trans('crudbooster.profile_button_save') }}</button>
            </div>
          @endif
        </form>

        @if($canManage)
        <template id="ch-qlik-template">
          <div class="ch-qlik-row">
            <div class="ch-grid">
              <div>
                <label>{{ trans('crudbooster.profile_qlik_field_conf') }}</label>
                <select class="form-select" data-f="qlik_conf_id">
                  <option value="">{{ trans('crudbooster.profile_qlik_select_conf') }}</option>
                  @foreach($qlikConfs as $c)
                    <option value="{{ $c->id }}" data-type="{{ $c->type }}">{{ $c->confname }} ({{ $c->type }})</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label>{{ trans('crudbooster.profile_qlik_field_login') }}</label>
                <input type="text" class="form-control" data-f="qlik_login">
              </div>
              <div>
                <label>{{ trans('crudbooster.profile_qlik_field_directory') }}</label>
                <input type="text" class="form-control" data-f="user_directory">
                <div class="ch-hint">{{ trans('crudbooster.profile_qlik_onprem_hint') }}</div>
              </div>
              <div>
                <label>{{ trans('crudbooster.profile_qlik_field_idp') }}</label>
                <input type="text" class="form-control" data-f="idp_qlik" readonly>
                <div class="ch-hint">{{ trans('crudbooster.profile_qlik_idp_hint') }}</div>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm ch-qlik-remove">{{ trans('crudbooster.profile_qlik_remove') }}</button>
          </div>
        </template>
        @endif
      </section>
      @endif

    </div>

    {{-- Menu sezioni: a destra (a sinistra c'e' gia' la sidebar dei moduli) --}}
    <nav class="ch-profile-nav" aria-label="{{ trans('crudbooster.label_button_profile') }}">
      <a data-pane-link="general" class="is-active"><span class="ch-nav-icon"><i class="bi bi-person-fill"></i></span>{{ trans('crudbooster.profile_section_general') }}</a>
      <a data-pane-link="mfa"><span class="ch-nav-icon"><i class="bi bi-shield-fill"></i></span>{{ trans('crudbooster.profile_section_mfa') }}</a>
      <a data-pane-link="system"><span class="ch-nav-icon"><i class="bi bi-gear-wide-connected"></i></span>{{ trans('crudbooster.profile_section_system') }}</a>
      @if(!empty($qlikEnabled))
      <a data-pane-link="qlik"><span class="ch-nav-icon"><i class="bi bi-bar-chart-fill"></i></span>{{ trans('crudbooster.profile_section_qlik') }}</a>
      @endif
      <a data-pane-link="password"><span class="ch-nav-icon"><i class="bi bi-key-fill"></i></span>{{ trans('crudbooster.profile_section_password') }}</a>
    </nav>
  </div>
</div>

@php
    $jsText = [
        'genericError' => trans('crudbooster.profile_generic_error'),
        'matchOk' => trans('crudbooster.profile_password_match_ok'),
        'matchKo' => trans('crudbooster.profile_password_match_ko'),
        'weak' => trans('crudbooster.profile_password_strength_weak'),
        'medium' => trans('crudbooster.profile_password_strength_medium'),
        'strong' => trans('crudbooster.profile_password_strength_strong'),
        'codeLabelEmail' => trans('crudbooster.profile_password_code_label_email'),
        'codeLabelTotp' => trans('crudbooster.profile_password_code_label_totp'),
    ];
    $jsGroups = [
        'byTenant' => $groupsByTenant,
        'current' => $currentGroup ? ['id' => (int) $currentGroup->id, 'name' => $currentGroup->name] : null,
        'currentTenant' => (int) $row->tenant,
        'currentGroupId' => (int) $row->primary_group,
    ];
@endphp
<script>
(function () {
  var T = {!! json_encode($jsText) !!};
  var G = {!! json_encode($jsGroups) !!};
  var TOKEN = {!! json_encode(csrf_token()) !!};
  var URLS = {
    emailStart: {!! json_encode(CRUDBooster::adminPath('users/profile-email-start')) !!},
    emailConfirm: {!! json_encode(CRUDBooster::adminPath('users/profile-email-confirm')) !!},
    passwordConfirm: {!! json_encode(CRUDBooster::adminPath('users/profile-password-confirm')) !!}
  };

  var panes = document.querySelectorAll('.ch-pane');
  var links = document.querySelectorAll('[data-pane-link]');

  function showPane(name) {
    var found = false;
    panes.forEach(function (p) { if (p.dataset.pane === name) { found = true; } });
    if (!found) { name = 'general'; }
    panes.forEach(function (p) { p.classList.toggle('is-active', p.dataset.pane === name); });
    links.forEach(function (l) { l.classList.toggle('is-active', l.dataset.paneLink === name); });
  }
  links.forEach(function (l) {
    l.addEventListener('click', function () {
      var name = l.dataset.paneLink;
      showPane(name);
      if (history.replaceState) { history.replaceState(null, '', '#' + name); }
    });
  });
  window.addEventListener('hashchange', function () { showPane(location.hash.replace('#', '')); });
  showPane(location.hash.replace('#', ''));

  function post(url, formData) {
    return fetch(url, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      if (r.redirected) { location.href = r.url; return new Promise(function () {}); }
      return r.json().catch(function () { return { ok: false, message: T.genericError }; });
    }).catch(function () { return { ok: false, message: T.genericError }; });
  }

  function flash(container, ok, message) {
    var box = container.querySelector('.ch-alert');
    if (!box) { return; }
    box.textContent = message;
    box.classList.toggle('is-ok', !!ok);
    box.classList.toggle('is-error', !ok);
    box.hidden = false;
  }

  function busy(btn, state) { if (btn) { btn.disabled = state; } }

  // Qlik: righe dinamiche. Prima del submit gli input prendono il nome
  // rows[i][campo] (saltando le righe senza configurazione); va registrato
  // PRIMA del listener generico qui sotto, che costruisce il FormData.
  var qlikRows = document.getElementById('ch-qlik-rows');
  if (qlikRows) {
    var qlikForm = qlikRows.closest('form');
    var qlikNone = document.getElementById('ch-qlik-none');
    var qlikTpl = document.getElementById('ch-qlik-template');
    var qlikToggleNone = function () { qlikNone.hidden = qlikRows.children.length > 0; };
    // Campi per tipo di configurazione: SaaS -> solo IDP Subject;
    // On-Premise (ogni altro tipo) -> login + user directory. Senza
    // configurazione scelta non se ne mostra nessuno.
    var qlikSyncRow = function (row) {
      var sel = row.querySelector('[data-f=qlik_conf_id]');
      var opt = sel && sel.selectedOptions[0];
      var type = (opt && opt.value) ? (opt.dataset.type === 'SAAS' ? 'saas' : 'onprem') : '';
      var show = { qlik_login: type === 'onprem', user_directory: type === 'onprem', idp_qlik: type === 'saas' };
      Object.keys(show).forEach(function (f) {
        var el = row.querySelector('[data-f=' + f + ']');
        if (el) { el.parentElement.hidden = !show[f]; }
      });
    };
    qlikRows.addEventListener('change', function (e) {
      if (e.target.dataset.f === 'qlik_conf_id') { qlikSyncRow(e.target.closest('.ch-qlik-row')); }
    });
    qlikRows.querySelectorAll('.ch-qlik-row').forEach(qlikSyncRow);
    qlikForm.addEventListener('submit', function () {
      var i = 0;
      qlikRows.querySelectorAll('.ch-qlik-row').forEach(function (row) {
        var conf = row.querySelector('[data-f=qlik_conf_id]');
        row.querySelectorAll('[data-f]').forEach(function (el) {
          if (conf && conf.value) { el.name = 'rows[' + i + '][' + el.dataset.f + ']'; } else { el.removeAttribute('name'); }
        });
        if (conf && conf.value) { i++; }
      });
    });
    qlikRows.addEventListener('click', function (e) {
      if (e.target.classList.contains('ch-qlik-remove')) {
        e.target.closest('.ch-qlik-row').remove();
        qlikToggleNone();
      }
    });
    var qlikAdd = document.getElementById('ch-qlik-add');
    if (qlikAdd && qlikTpl) {
      qlikAdd.addEventListener('click', function () {
        qlikRows.appendChild(qlikTpl.content.cloneNode(true));
        qlikSyncRow(qlikRows.lastElementChild);
        qlikToggleNone();
      });
    }
  }

  // Form "semplici" (Generale, Sistema): un submit, un endpoint, una risposta.
  document.querySelectorAll('form.ch-ajax').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var pane = form.closest('.ch-pane');
      var btn = form.querySelector('button[type=submit]');
      busy(btn, true);
      post(form.action, new FormData(form)).then(function (res) {
        busy(btn, false);
        flash(pane, res.ok, res.message);
        if (res.ok && res.photo_url) {
          var avatar = form.querySelector('[data-ch-image-img]');
          if (avatar) { avatar.src = res.photo_url; avatar.hidden = false; }
        }
        if (res.ok && res.reload) { setTimeout(function () { location.reload(); }, 700); }
      });
    });
  });

  // Sistema: menu a cascata Tenant -> Primary Group.
  var tenantSel = document.getElementById('ch-tenant');
  var groupSel = document.getElementById('ch-group');
  function fillGroups() {
    if (!groupSel) { return; }
    var tenantId = tenantSel ? parseInt(tenantSel.value, 10) : G.currentTenant;
    var list = (G.byTenant && G.byTenant[tenantId]) ? G.byTenant[tenantId].slice() : [];
    var selected = (tenantId === G.currentTenant) ? G.currentGroupId : null;
    if (selected && G.current && !list.some(function (g) { return g.id === selected; })) { list.unshift(G.current); }
    groupSel.innerHTML = '';
    list.forEach(function (g) {
      var o = document.createElement('option');
      o.value = g.id; o.textContent = g.name;
      if (g.id === selected) { o.selected = true; }
      groupSel.appendChild(o);
    });
  }
  if (tenantSel) { tenantSel.addEventListener('change', fillGroups); }
  fillGroups();

  // ---- Cambio email (Generale): password + codice al nuovo indirizzo ----
  var emailPanel = document.getElementById('ch-email-panel');
  var emailToggle = document.getElementById('ch-email-toggle');
  function emailReset() {
    document.getElementById('ch-email-step1').hidden = false;
    document.getElementById('ch-email-step2').hidden = true;
    document.getElementById('ch-email-totp-wrap').hidden = true;
    ['ch-new-email', 'ch-email-password', 'ch-email-code', 'ch-email-totp'].forEach(function (id) { document.getElementById(id).value = ''; });
    emailPanel.querySelector('.ch-alert').hidden = true;
  }
  if (emailToggle) {
    emailToggle.addEventListener('click', function () { emailReset(); emailPanel.hidden = !emailPanel.hidden; });
    ['ch-email-cancel', 'ch-email-cancel2'].forEach(function (id) {
      document.getElementById(id).addEventListener('click', function () { emailReset(); emailPanel.hidden = true; });
    });
    document.getElementById('ch-email-send').addEventListener('click', function () {
      var btn = this, fd = new FormData();
      fd.append('new_email', document.getElementById('ch-new-email').value);
      fd.append('current_password', document.getElementById('ch-email-password').value);
      busy(btn, true);
      post(URLS.emailStart, fd).then(function (res) {
        busy(btn, false);
        flash(emailPanel, res.ok, res.message);
        if (res.ok && res.email) {
          // Niente TOTP e SMTP non configurato: email gia' cambiata, nessun codice.
          document.getElementById('ch-email-current').value = res.email;
          flash(document.getElementById('pane-general'), true, res.message);
          emailReset(); emailPanel.hidden = true;
        } else if (res.ok) {
          document.getElementById('ch-email-step1').hidden = true;
          document.getElementById('ch-email-step2').hidden = false;
          document.getElementById('ch-email-code-wrap').hidden = !res.needs_email_code;
          document.getElementById('ch-email-totp-wrap').hidden = !res.needs_totp;
        }
      });
    });
    document.getElementById('ch-email-confirm').addEventListener('click', function () {
      var btn = this, fd = new FormData();
      fd.append('code', document.getElementById('ch-email-code').value);
      fd.append('totp_code', document.getElementById('ch-email-totp').value);
      busy(btn, true);
      post(URLS.emailConfirm, fd).then(function (res) {
        busy(btn, false);
        flash(emailPanel, res.ok, res.message);
        if (res.ok) {
          document.getElementById('ch-email-current').value = res.email;
          flash(document.getElementById('pane-general'), true, res.message);
          emailReset(); emailPanel.hidden = true;
        } else if (res.expired) {
          document.getElementById('ch-email-step1').hidden = false;
          document.getElementById('ch-email-step2').hidden = true;
        }
      });
    });
  }

  // ---- Password: tre campi + codice ----
  var pwdForm = document.getElementById('ch-password-form');
  var pwdPane = document.getElementById('pane-password');
  var pwdNew = document.getElementById('ch-pwd-new');
  var pwdConfirm = document.getElementById('ch-pwd-confirm');
  var pwdStep2 = document.getElementById('ch-pwd-step2');
  var pwdStep1Actions = document.getElementById('ch-pwd-step1-actions');

  function strength(p) {
    if (p.length < 12) { return p.length ? 'weak' : ''; }
    var classes = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/].filter(function (re) { return re.test(p); }).length;
    return (p.length >= 16 || classes >= 3) ? 'strong' : 'medium';
  }
  function updatePwdFeedback() {
    var s = strength(pwdNew.value);
    var bar = document.getElementById('ch-pwd-strength');
    var widths = { '': '0', weak: '30%', medium: '65%', strong: '100%' };
    var colors = { '': 'transparent', weak: 'var(--ch-danger, #d1373f)', medium: 'var(--ch-warning, #b7791f)', strong: 'var(--ch-success, #0f9d58)' };
    bar.style.width = widths[s]; bar.style.background = colors[s];
    document.getElementById('ch-pwd-strength-label').textContent = s ? T[s] : '';
    var m = document.getElementById('ch-pwd-match');
    if (!pwdConfirm.value) { m.textContent = ''; return; }
    var same = pwdNew.value === pwdConfirm.value;
    m.textContent = same ? T.matchOk : T.matchKo;
    m.style.color = same ? 'var(--ch-success, #0f9d58)' : 'var(--ch-danger, #d1373f)';
  }
  pwdNew.addEventListener('input', updatePwdFeedback);
  pwdConfirm.addEventListener('input', updatePwdFeedback);

  function pwdLock(locked) {
    pwdForm.querySelectorAll('input').forEach(function (i) { i.disabled = locked; });
    pwdStep1Actions.hidden = locked;
    pwdStep2.hidden = !locked;
  }
  function pwdReset() {
    pwdForm.reset(); pwdLock(false); updatePwdFeedback();
    document.getElementById('ch-pwd-code').value = '';
  }

  pwdForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = pwdForm.querySelector('button[type=submit]');
    busy(btn, true);
    post(pwdForm.action, new FormData(pwdForm)).then(function (res) {
      busy(btn, false);
      flash(pwdPane, res.ok, res.message);
      if (res.ok && res.changed) {
        // Niente TOTP e SMTP non configurato: password gia' cambiata, nessun codice.
        pwdReset();
      } else if (res.ok) {
        document.getElementById('ch-pwd-code-label').textContent = res.method === 'totp' ? T.codeLabelTotp : T.codeLabelEmail;
        pwdLock(true);
      }
    });
  });
  document.getElementById('ch-pwd-cancel').addEventListener('click', function () { pwdReset(); pwdPane.querySelector('.ch-alert').hidden = true; });
  document.getElementById('ch-pwd-confirm-btn').addEventListener('click', function () {
    var btn = this, fd = new FormData();
    fd.append('code', document.getElementById('ch-pwd-code').value);
    busy(btn, true);
    post(URLS.passwordConfirm, fd).then(function (res) {
      busy(btn, false);
      flash(pwdPane, res.ok, res.message);
      if (res.ok) { pwdReset(); } else if (res.expired) { pwdLock(false); }
    });
  });
})();
</script>
@endsection
