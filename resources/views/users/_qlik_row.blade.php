{{--
    Una riga (card) di associazione utente -> configurazione Qlik. Condivisa tra
    il profilo (users/profile) e la scheda Qlik della modifica utente
    (users/_qlik_field). Variabili: $qlikConfs, $canManage, $qu (riga esistente
    o null per il template), $inputPrefix (solo nella modifica utente: se
    presente ogni input prende name="<prefisso><campo>[]", il contratto di
    salvataggio del campo 'child' di CBController; nel profilo i nomi li
    assegna il JS al submit).
--}}
@php
    $qu = $qu ?? null;
    $inputPrefix = $inputPrefix ?? null;
    $nm = function ($f) use ($inputPrefix) {
        return $inputPrefix !== null ?' name="' . e($inputPrefix . $f) . '[]"' : '';
    };
    $dis = empty($canManage) ? ' disabled' : '';
@endphp
<div class="ch-qlik-row">
  <div class="ch-grid">
    <div>
      <label>{{ trans('crudbooster.profile_qlik_field_conf') }}</label>
      <select class="form-select" data-f="qlik_conf_id"{!! $nm('qlik_conf_id') !!}{!! $dis !!}>
        <option value="">{{ trans('crudbooster.profile_qlik_select_conf') }}</option>
        @foreach($qlikConfs as $c)
          <option value="{{ $c->id }}" data-type="{{ $c->type }}" @if($qu && (int) $c->id === (int) $qu->qlik_conf_id) selected @endif>{{ $c->confname }} ({{ $c->type }})</option>
        @endforeach
      </select>
    </div>
    <div>
      <label>{{ trans('crudbooster.profile_qlik_field_login') }}</label>
      <input type="text" class="form-control" data-f="qlik_login"{!! $nm('qlik_login') !!} value="{{ $qu->qlik_login ?? '' }}"{!! $dis !!}>
    </div>
    <div>
      <label>{{ trans('crudbooster.profile_qlik_field_directory') }}</label>
      <input type="text" class="form-control" data-f="user_directory"{!! $nm('user_directory') !!} value="{{ $qu->user_directory ?? '' }}"{!! $dis !!}>
      <div class="ch-hint">{{ trans('crudbooster.profile_qlik_onprem_hint') }}</div>
    </div>
    <div>
      <label>{{ trans('crudbooster.profile_qlik_field_idp') }}</label>
      <input type="text" class="form-control" data-f="idp_qlik"{!! $nm('idp_qlik') !!} value="{{ $qu->idp_qlik ?? '' }}" readonly>
      <div class="ch-hint">{{ trans('crudbooster.profile_qlik_idp_hint') }}</div>
    </div>
  </div>
  @if(!empty($canManage))<button type="button" class="btn btn-secondary btn-sm ch-qlik-remove">{{ trans('crudbooster.profile_qlik_remove') }}</button>@endif
</div>
