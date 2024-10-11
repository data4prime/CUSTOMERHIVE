<footer class="main-footer">
    <!-- To the right -->
    <!--<div class="pull-{{ trans('crudbooster.right') }} hidden-xs">

        &nbsp;|&nbsp;<a  data-toggle="modal" data-target="#licenseModal">{{ trans('crudbooster.license') }}</a>
    </div>-->

    <div class="pull-{{ trans('crudbooster.right') }} hidden-xs">

        &nbsp;|&nbsp;<a data-bs-toggle="modal"   data-bs-target="#licenseModal">{{ trans('crudbooster.license') }} BS</a>
    </div>


    <div class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{ trans('crudbooster.powered_by') }} Data4Prime
    </div>

    <div style="margin-right:15px;" class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{Session::get('appname')}} {{ config('app.version') }}
    </div>
    <!-- Default to the left -->
    <strong>{{ trans('crudbooster.copyright') }} &copy; <?php echo date('Y') ?>. {{ trans('crudbooster.all_rights_reserved') }} .</strong>
</footer>







