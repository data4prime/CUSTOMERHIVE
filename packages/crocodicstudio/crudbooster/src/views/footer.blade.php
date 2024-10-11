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

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
  Launch demo modal
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>







