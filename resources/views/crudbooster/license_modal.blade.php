@php 
 use App\Helpers\LicenseHelper;

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
        <h5 class="modal-title" id="licenseModalLabel"><i class="bi bi-key-fill"></i> {{ trans('crudbooster.license') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('crudbooster.license_modal_close') }}"></button>
      </div>
      <div class="modal-body">
        <table class="ch-lic">
          <tr>
            <th>{{ trans('crudbooster.license_modal_domain') }}</th>
            <td>{{ $license['domain'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_key') }}</th>
            <td>{{ $license['license_key'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_status') }}</th>
            <td>{{ $license['status'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_expiration') }}</th>
            <td>@if($license['expiration_date']){{ date('d-m-Y h:i:s', strtotime($license['expiration_date'])) }}@endif</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_trial') }}</th>
            <td>{{ ($license['is_trial'] == 1) ? trans('crudbooster.license_modal_yes') : trans('crudbooster.license_modal_no') }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_lifetime') }}</th>
            <td>{{ ($license['is_lifetime'] == 1) ? trans('crudbooster.license_modal_yes') : trans('crudbooster.license_modal_no') }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_clients') }}</th>
            <td>{{ $license['clients_number'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_tenants') }}</th>
            <td>{{ $license['tenants_number'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_path') }}</th>
            <td>{{ $license['path'] }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_expires_in') }}</th>
            <td>{{ trans('crudbooster.license_modal_days', ['n' => $license['expires_in']]) }}</td>
          </tr>
          <tr>
            <th>{{ trans('crudbooster.license_modal_modules') }}</th>
            <td>
              @foreach ($license['modules'] as $module)
                <span class="ch-pill ch-pill-vio">{{ $module['name'] }}</span>
              @endforeach
            </td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('crudbooster.license_modal_close') }}</button>
      </div>
    </div>
  </div>
</div>
