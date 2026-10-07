{{--
  Campo "scelta da una finestra di ricerca" (datamodal e relazioni *_datamodal):
  una riga come un select, con lente a destra; il clic (o Invio/Spazio) apre la finestra.
  Il codice che riceve la scelta (selectAdditionalData<name>) cerca '#<name> .input-id'
  e '#<name> .input-label': i nomi restano quelli di sempre.

  Parametri: dm_name, dm_value (id), dm_display (testo mostrato), dm_required (bool),
             dm_module_path (opzionale: link "apri modulo"), dm_module_label (title del link)
--}}
@php $dmHas = (string) ($dm_display ?? '') !== ''; @endphp
<div id="{{ $dm_name }}" class="input-group ch-input ch-pick{{ $dmHas ? ' has-value' : '' }}" role="button" tabindex="0"
     onclick="showModal{{ $dm_name }}()"
     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();showModal{{ $dm_name }}();}">
    <input type="hidden" name="{{ $dm_name }}" class="input-id" value="{{ $dm_value ?? '' }}">
    <input type="text" class="form-control input-label{{ !empty($dm_required) ? ' required' : '' }}"{{ !empty($dm_required) ? ' required' : '' }}
           value="{{ $dm_display ?? '' }}" placeholder="{{ trans('crudbooster.datamodal_choose') }}" readonly tabindex="-1">
    @if(!empty($dm_module_path))
    <a class="input-group-text ch-addon-suf ch-pick-link" href="{{ CRUDBooster::adminPath() }}/{{ $dm_module_path }}" target="_blank"
       title="{{ $dm_module_label ?? '' }}" onclick="event.stopPropagation()"><i class="bi bi-box-arrow-up-right"></i></a>
    @endif
    @if(empty($dm_required))
    <button type="button" class="ch-clear" tabindex="-1" aria-label="×" onclick="event.stopPropagation(); chDatamodalClear('{{ $dm_name }}')"><i class="bi bi-x-lg"></i></button>
    @endif
    <span class="input-group-text ch-addon-suf ch-pick-lens"><i class="bi bi-search"></i></span>
</div>
