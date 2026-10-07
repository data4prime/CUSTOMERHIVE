{{-- Testata delle pagine relazione (membri/tenant/item/gruppi), stile mockup (intervento 232):
     briciole "Modulo / Nome" + titolo con conteggio a sinistra, pulsante "aggiungi" a destra
     (apre il form di groups/_add_card). Parametri: $crumb_name, $title, $count, $opener_label (opz.) --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <div class="small text-secondary rel-crumb">
      <a href="{{ g('return_url') ?: CRUDBooster::mainpath() }}" class="text-decoration-none" title="{{ trans('crudbooster.form_back_to_list', ['module' => CRUDBooster::getCurrentModule()->name]) }}">
        <i class="bi bi-chevron-left"></i> {{ CRUDBooster::getCurrentModule()->name }}
      </a> / {{ $crumb_name }}
    </div>
    <h4 class="mb-0 rel-title">{{ $title }} <span class="ch-pill ch-pill-gray align-middle">{{ $count }}</span></h4>
  </div>
  @if(!empty($opener_label))
  @if(!empty($opener_modal))
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="{{ $opener_modal }}">
  @else
  <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#add-panel"
    aria-expanded="{{ !empty($alerts) ? 'true' : 'false' }}" aria-controls="add-panel">
  @endif
    <i class="bi bi-plus-lg"></i> {{ $opener_label }}
  </button>
  @endif
</div>
@push('head')
<style>
    .rel-table { border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); background: var(--ch-surface); overflow: hidden; }
    .rel-table th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--ch-text-muted); font-weight: 600; background: var(--ch-surface); white-space: nowrap; }
    .rel-table td, .rel-table th { padding: 10px 12px; }
</style>
@endpush
