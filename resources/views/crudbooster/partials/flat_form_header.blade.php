{{-- Intestazione delle pagine nuovo/modifica/dettaglio in stile mockup (intervento 232):
     briciole "Modulo / Nome" + titolo a sinistra, azioni del modulo a destra.
     Inclusa da default.form (e dalle viste di form proprie) solo se il controller
     ha chiamato App\Helpers\FlatForm::share(). Nelle pagine con questa intestazione
     il form non e' dentro una card unica: ogni blocco e' la sua card. --}}
@php
    $fhDetail = isset($command) && $command === 'detail';
    $fhEdit = CRUDBooster::getCurrentMethod() === 'getEdit' && !$fhDetail;
    $fhTitles = $flat_form_titles ?? ['', '', ''];
    $fhTitle = $fhDetail ? $fhTitles[2] : ($fhEdit ? $fhTitles[1] : $fhTitles[0]);
    $fhBack = g('return_url') ?: CRUDBooster::mainpath();
    $fhName = @$row->name;
@endphp
@push('head')
<style>
    .flat-form > .card-body { padding: 0 !important; }
    /* Form senza schede/blocchi: i campi stanno in una card */
    .flat-form .flat-card { background: var(--ch-surface); border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); box-shadow: var(--ch-shadow-sm); padding: 16px; margin: 0 15px 15px; }
    /* Blocco che contiene solo una griglia child (es. scheda Qlik): niente card attorno alla card della griglia */
    .flat-form .cb-block:has(.ch-grid-card) > .card, .flat-form .cbd-block:has(.ch-grid-card) > .card { border: 0; box-shadow: none; background: transparent; }
    .flat-form .cb-block:has(.ch-grid-card) .card-body, .flat-form .cbd-block:has(.ch-grid-card) .card-body { padding: 0; }
    /* Sfondo pagina un filo piu' scuro, cosi' le card bianche si staccano (solo pagine con questa intestazione) */
    body:has(.flat-form) .content-wrapper { background: color-mix(in srgb, var(--ch-bg) 60%, var(--ch-border)); }
    .flat-form .card, .flat-form .flat-card { background: var(--ch-surface); border: 1px solid var(--ch-border-strong); border-radius: var(--ch-radius-md); box-shadow: var(--ch-shadow-sm); }
    .flat-form .card > .card-header { background: transparent; border-bottom: 1px solid var(--ch-border); padding: 12px 16px; }
    .flat-form .card > .card-body { padding: 16px; }
    /* Schede: sottolineate, senza riquadro, accento sulla scheda attiva */
    .flat-form .nav-tabs { gap: 4px; border-bottom: 1px solid var(--ch-border-strong); margin: 0 15px 16px; padding: 0; }
    .flat-form .nav-tabs .nav-link { background: transparent; border: 0; border-bottom: 2px solid transparent; border-radius: 0; margin-bottom: -1px; padding: 10px 16px; font-weight: 500; color: var(--ch-text-secondary); }
    .flat-form .nav-tabs .nav-link:hover { color: var(--ch-text); border-bottom-color: var(--ch-border-strong); }
    .flat-form .nav-tabs .nav-link.active { color: var(--ch-accent); border-bottom-color: var(--ch-accent); background: transparent; font-weight: 600; }
    .flat-form .box-footer .col-form-label { display: none; }
    .flat-form .box-footer .col-sm-10 { flex: 0 0 100%; max-width: 100%; }
    .flat-form .box-footer { padding: 12px 15px; }
    .flat-form .box-footer .mb-3 { margin-bottom: 0 !important; }
</style>
@endpush
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <div class="small text-secondary rel-crumb">
      <a href="{{ $fhBack }}" class="text-decoration-none" title="{{ trans('crudbooster.form_back_to_list', ['module' => CRUDBooster::getCurrentModule()->name]) }}">
        <i class="bi bi-chevron-left"></i> {{ CRUDBooster::getCurrentModule()->name }}
      </a>
      @if(@$row->id && $fhName) / {{ $fhName }} @endif
    </div>
    <h4 class="mb-0 rel-title">{{ $fhTitle }}</h4>
  </div>
  @if(!empty($flat_form_actions))
  <div>@include($flat_form_actions, ['part' => 'button'])</div>
  @endif
</div>
@if(!empty($flat_form_actions))
@include($flat_form_actions, ['part' => 'after'])
@endif
