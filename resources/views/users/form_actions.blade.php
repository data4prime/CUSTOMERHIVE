{{-- Azioni dell'intestazione utenti (modifica e dettaglio): "Invia link di reset password".
     Stesse condizioni della vecchia scheda "Sicurezza": non su se stessi e solo se il ruolo
     puo' agire su quell'utente. $part: 'button' (in alto a destra) o 'after' (avviso + script). --}}
@php
    $uaId = @$row->id;
    $uaIsEdit = CRUDBooster::getCurrentMethod() === 'getEdit' || (isset($command) && $command === 'detail');
    $uaCanReset = $uaIsEdit && $uaId && (int) $uaId !== (int) CRUDBooster::myId() && \App\Helpers\UserHelper::can_do_on_user('edit', $uaId);
@endphp
@if($uaCanReset && $part === 'button')
<button type="button" class="btn btn-secondary" id="ch-reset-btn" title="{{ trans('crudbooster.user_reset_password_hint') }}">
  <i class="bi bi-key-fill"></i> {{ trans('crudbooster.user_reset_password_button') }}
</button>
@endif
@if($uaCanReset && $part === 'after')
<div class="alert" id="ch-reset-alert" role="alert" hidden></div>
<script>
(function () {
    var btn = document.getElementById('ch-reset-btn');
    var box = document.getElementById('ch-reset-alert');
    btn.addEventListener('click', function () {
        if (!confirm({!! json_encode(trans('crudbooster.user_reset_password_confirm')) !!})) { return; }
        btn.disabled = true;
        fetch({!! json_encode(CRUDBooster::adminPath('users/user-reset-password/' . $uaId)) !!}, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': {!! json_encode(csrf_token()) !!}, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).catch(function () {
            return { ok: false, message: {!! json_encode(trans('crudbooster.profile_generic_error')) !!} };
        }).then(function (res) {
            btn.disabled = false;
            box.textContent = res.message;
            box.className = 'alert ' + (res.ok ? 'alert-success' : 'alert-danger');
            box.hidden = false;
        });
    });
})();
</script>
@endif
