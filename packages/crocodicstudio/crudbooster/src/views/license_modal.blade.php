@php 
 use crocodicstudio\crudbooster\helpers\LicenseHelper;

    $license = LicenseHelper::getLicenseInfo();


@endphp


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">{{ trans('crudbooster.license') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">



        <table class="table table-bordered">
          <tr>
            <th>Domain</th>
            <td>{{ $license['domain'] }}</td>
          </tr>
          <tr>
            <th>License Key</th>
            <td>{{ $license['license_key'] }}</td>
          </tr>
          <tr>
            <th>Status</th>
            <td>{{ $license['status'] }}</td>
          </tr>
          <tr>
            <th>Expiration Date</th>
            <td>{{ date('d-m-Y h:i:s', strtotime($license['expiration_date'])) }}</td>
          </tr>
          <!--<tr>
            <th>Is Trial</th>
            <td>{{ $license['is_trial'] }}</td>
          </tr>
          <tr>
            <th>Is Lifetime</th>
            <td>{{ $license['is_lifetime'] }}</td>
          </tr>-->
          <tr>
            <th>Clients Number</th>
            <td>{{ $license['clients_number'] }}</td>
          </tr>
          <tr>
            <th>Tenants Number</th>
            <td>{{ $license['tenants_number'] }}</td>
          </tr>
          <tr>
            <th>Mac Address</th>
            <td>{{ $license['mac_address'] }}</td>
          </tr>
          <tr>
            <th>Path</th>
            <td>{{ $license['path'] }}</td>
          </tr>
          <tr>
            <th>Expires In</th>
            <td>{{ $license['expires_in'] }} days</td>
          </tr>

        </table>


        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
     
      </div>
    </div>
  </div>
</div>