<footer class="main-footer">
    <strong>{{ trans('crudbooster.copyright') }} &copy; {{ date('Y') }}. {{ trans('crudbooster.all_rights_reserved') }}</strong>

    <div class="ch-footer-right hidden-xs">
        <a data-bs-toggle="modal" data-bs-target="#licenseModal">{{ trans('crudbooster.license') }}</a>
        <span class="ch-footer-sep">&middot;</span>
        <span>{{ trans('crudbooster.powered_by') }} Data4Prime</span>
        <span class="ch-footer-sep">&middot;</span>
        <span>{{ Session::get('appname') }} {{ config('app.version') }}</span>
    </div>
</footer>
