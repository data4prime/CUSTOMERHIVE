@extends('crudbooster::admin_template')

@section('content')

<ul class="nav flex-row">
    <li class="nav-item">
        <a class="nav-link active" href="/admin/api_generator">
            <i class="fa fa-file"></i> {{ trans('crudbooster.api_documentation') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/admin/api_generator/screet-key">
            <i class="fa fa-key"></i> {{ trans('crudbooster.api_secret_key') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/admin/api_generator/generator">
            <i class="fa fa-cog"></i> {{ trans('crudbooster.api_generator') }}
        </a>
    </li>
</ul>


    <div class='box'>

        <div class='box-body'>


            <p><a title='Generate API Key' class='btn btn-primary' href='javascript:void(0)' onclick='generate_screet_key()'>
                <i class='fa fa-key'></i>{{ trans('crudbooster.Generate_Screet_Key') }}</a></p>

            <table id='table-apikey' class='table table-striped table-bordered'>
                <thead>
                <tr>
                    <th width="3%">No</th>
                    <th>Secret Key</th>
                    <th width="10%">Hit</th>
                    <th width="10%">Status</th>
                    <th width="15%">-</th>
                </tr>
                </thead>
                <tbody>
                <?php $no = 0;?>
                @foreach($apikeys as $row)
                    <tr>
                        <td>{{ ++$no }}</td>
                        <td>{{ $row->screetkey }}</td>
                        <td>{{ $row->hit }}</td>
                        <td>{!! ($row->status=='active')?"<span class='label label-success'>Active</span>":"<span class='label label-default'>Non Active</span>" !!}</td>
                        <td>
                            @if($row->status == 'active')
                                <a class='btn btn-sm btn-default' href='{{ CRUDBooster::mainpath("status-apikey?id=$row->id&status=0") }}'>Non Active</a>
                            @else
                                <a class='btn btn-sm btn-default' href='{{ CRUDBooster::mainpath("status-apikey?id=$row->id&status=1") }}'>Active</a>
                            @endif

                            <a class='btn btn-sm btn-danger' href='javascript:void(0)' onclick='deleteApi({{$row->id}})'>
                                {{ trans('crudbooster.delete_button') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                @if(count($apikeys)==0)
                    <tr class='no-screetkey'>
                        <td colspan='5' align="center">{{ trans('crudbooster.no_secret_key_found') }}, <a href='javascript:void(0)' onclick='generate_screet_key()'>
                                {{ trans('crudbooster.click_here_to_generate_one') }}</a></td>
                    </tr>
                @endif
                </tbody>
            </table>

            @push('bottom')
                <script>
                    var lastno = <?=$no?>;

                    function generate_screet_key() {
                        $.get("<?php echo route('ApiCustomControllerGetGenerateScreetKey')?>", function (resp) {
                            lastno += 1;
                            $('#table-apikey').append("<tr><td>" + lastno + "</td><td>" + resp.key + "</td><td>0</td><td><span class='label label-success'>Active</span></td><td>" +
                                "<a class='btn btn-sm btn-default' href='{{CRUDBooster::mainpath("status-apikey")}}?id=" + resp.id + "&status=0'>Non Active</a> <a class='btn btn-sm btn-danger' href='javascript:void(0)' onclick='deleteApi(" + resp.id + ")'>Delete</a> </td></tr>"
                            );
                            $('.no-screetkey').remove();
                            swal("Success!", "Your new Secret Key has been generated successfully", "success");
                        })
                    }

                    function deleteApi(id) {
                        swal({
                            title: "Are you sure ?",
                            text: "You will not be able to recover this data!",
                            type: "warning", showCancelButton: true, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes, delete it!", closeOnConfirm: false
                        }, function () {
                            $.get("{{CRUDBooster::mainpath('delete-api-key')}}?id=" + id, function (resp) {
                                if (resp.status == 1) {
                                    swal("Success!", "The Secret Key has been deleted !", "success");
                                } else {
                                    swal("Upps!", "The Secret Key can't delete !", "warning");
                                }
                                location.href = document.location.href;
                            })
                        })
                    }
                </script>
            @endpush

        </div><!--END BODY-->
    </div><!--END BOX-->

@endsection