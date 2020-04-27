<footer class="main-footer">
    <!-- To the right -->
    <div class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{ trans('crudbooster.powered_by') }} Data Synapsi
    </div>
    <div style="margin-right:15px;" class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{Session::get('appname')}} {{\Tremby\LaravelGitVersion\GitVersionHelper::getVersion()}}
    </div>
    <!-- Default to the left -->
    <strong>{{ trans('crudbooster.copyright') }} &copy; <?php echo date('Y') ?>. {{ trans('crudbooster.all_rights_reserved') }} .</strong>
</footer>
