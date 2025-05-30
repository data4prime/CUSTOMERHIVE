@php 
 use crocodicstudio\crudbooster\helpers\LicenseHelper;

    $license = LicenseHelper::getLicenseInfo();

    //dd($license);

    if (!$license) {

        $license = [
            'domain' => 'N/A',
            'license_key' => 'N/A',
            'status' => 'N/A',
            'expiration_date' => 'N/A',
            'is_trial' => 'N/A',
            'is_lifetime' => 'N/A',
            'clients_number' => 'N/A',
            'tenants_number' => 'N/A',
            'path' => 'N/A',
            'expires_in' => 'N/A',
            'is_trial' => 'N/A',
            'modules' => []
        ];
    }


@endphp


<div class="modal fade" id="licenseModal" tabindex="-1" role="dialog" aria-labelledby="licenseModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="justify-content: space-between;">
        <h5 class="modal-title" id="licenseModalLabel">{{ trans('crudbooster.license') }}</h5>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          
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
            <td>@if($license['expiration_date'])
                  {{ date('d-m-Y h:i:s', strtotime($license['expiration_date'])) }}
              @else
                  <span></span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Is Trial</th>
            <td>{{ ($license['is_trial'] == 1) ? 'YES' : 'NO' }}</td>
          </tr>
          <tr></tr>
            <th>Is Lifetime</th>
            <td>{{ ($license['is_lifetime'] == 1) ? 'YES' : 'NO' }}</td>
          </tr>
<!--
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
            <th>Path</th>
            <td>{{ $license['path'] }}</td>
          </tr>
          <tr>
            <th>Expires In</th>
            <td>{{ $license['expires_in'] }} days</td>
          </tr>

          <tr>
            <th>Modules</th>
            <td>
                @foreach ($license['modules'] as $module)
                  <span class="badge rounded-pill bg-light text-dark">{{$module['name']}}</span>
                @endforeach
            </td>
          </tr>

        </table>


        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
     
      </div>
    </div>
  </div>
</div>