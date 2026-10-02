{{-- Layout "parziale" del wizard (flag module_generator.wizard_v2): usato al posto di
     admin_template quando la richiesta porta l'header X-Wizard-Partial (navigazione
     tra i passi senza ricaricare la pagina, vedi template.blade.php). Restituisce
     solo stili, contenuto del passo, notifica e script: niente header, sidebar,
     chat, footer. Docs/refactoring/200. --}}
@stack('head')
@yield('content')
@if (Session::get('message') != '')
<div class="ch-toast-container" aria-live="polite" aria-atomic="true">
    <div class="toast ch-toast ch-toast-{{ Session::get('message_type') }}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
        <i class="bi {{ ['success' => 'bi-check-circle-fill', 'danger' => 'bi-x-circle-fill', 'warning' => 'bi-exclamation-triangle-fill'][Session::get('message_type')] ?? 'bi-info-circle-fill' }} ch-toast-icon"></i>
        <div class="ch-toast-body">
            <strong>{{ trans('crudbooster.alert_'.Session::get('message_type')) }}</strong>
            {!! Session::get('message') !!}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
@endif
@stack('bottom')
