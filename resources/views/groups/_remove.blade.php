{{-- Rimozione con conferma sulla riga (intervento 232). Il link finale e' lo
     stesso GET di prima: cambia solo che serve un secondo clic.
     Parametri: $url (rimozione), $disabled_hint (opzionale, testo al posto del pulsante) --}}
@if(!empty($disabled_hint))
<span class="small text-secondary">{{ $disabled_hint }}</span>
@else
<span class="rm-wrap">
  <button type="button" class="btn btn-danger btn-sm rm-ask" title="{{ trans('crudbooster.adm_remove') }}">
    <i class="bi bi-trash-fill"></i> {{ trans('crudbooster.adm_remove') }}
  </button>
  <span class="rm-confirm d-none">
    <span class="small text-danger fw-semibold me-1">{{ trans('crudbooster.adm_remove_confirm') }}</span>
    <a class="btn btn-danger btn-sm" href="{{ $url }}">{{ trans('crudbooster.confirmation_yes') }}</a>
    <button type="button" class="btn btn-secondary btn-sm rm-cancel">{{ trans('crudbooster.button_cancel') }}</button>
  </span>
</span>
@endif
