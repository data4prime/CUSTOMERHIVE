@extends('crudbooster::admin_template')

@section('content')

<ul class="nav flex-row">
    <li class="nav-item">
        <a class="nav-link active" href="https://staging.thecustomerhive.com/admin/api_generator">
            <i class="fa fa-file"></i> API Documentation
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="https://staging.thecustomerhive.com/admin/api_generator/screet-key">
            <i class="fa fa-key"></i> API Secret Key
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="https://staging.thecustomerhive.com/admin/api_generator/generator">
            <i class="fa fa-cog"></i> API Generator
        </a>
    </li>
</ul>

<!--
<ul class="nav nav-tabs">
    <li class="active"><a href="{{ CRUDBooster::mainpath() }}"><i class='fa fa-file'></i> API Documentation</a></li>
    <li><a href="{{ CRUDBooster::mainpath('screet-key') }}"><i class='fa fa-key'></i> API Secret Key</a></li>
    <li><a href="{{ CRUDBooster::mainpath('generator') }}"><i class='fa fa-cog'></i> API Generator</a></li>
</ul>
-->

<div class='box'>

    <div class='box-body'>

        @push('head')
        <style>
            .table-api tbody tr td a {
                color: #db0e00;
                font-family: arial;
            }
        </style>
        @endpush

        @push('bottom')
        <script>
            $(function () {
                /*$(".link_name_api").click(function () {
                    $(".detail_api").slideUp();
                    $(this).parent("td").find(".detail_api").slideDown();
                })*/

                $(document).ready(function() {
                    $('#toggleLink').on('click', function(event) {
                        event.preventDefault();

                        //get the td parent of the a element
                        var td = $(this).parent().parent();
                        //console.log(td);

                        //get the detail_api div from td
                        var detail_api = td.find('#detail_api');
                        console.log(detail_api);

                        //toggle the detail_api div
                        detail_api.collapse('toggle');




                    });
                });

                $(".selected_text").each(function () {
                    var n = $(this).text();
                    if (n.indexOf('api_') == 0) {
                        $(this).attr('class', 'selected_text text-danger');
                    }
                })
            })

            function deleteApi(id) {
                var url = "{{url(config('crudbooster.ADMIN_PATH').'/api_generator/delete-api')}}/" + id;
                swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this data!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: false
                }, function () {
                    $.get(url, function (resp) {
                        if (resp.status == 1) {
                            swal("Deleted!", "The data has been deleted.", "success");
                            location.href = document.location.href;
                        }
                    })
                });
            }
        </script>
        @endpush

        <div class="mb-4">
            <label for="apiBaseUrl" class="form-label">API BASE URL</label>
            <input type="text" id="apiBaseUrl" readonly class="form-control" 
                title="Hanya klik dan otomatis copy to clipboard (kecuali Safari)" 
                onClick="this.select(); document.execCommand('copy');" 
                value="{{url('api')}}" />
            <div class="form-text">Clicca sul campo sopra per copiare l'URL negli appunti.</div>
        </div>


        <!--<div class='mb-3 row'>
            <label>API BASE URL</label>
            <input type='text' readonly class='form-control'
                title='Hanya klik dan otomatis copy to clipboard (kecuali Safari)'
                onClick="this.setSelectionRange(0, this.value.length); document.execCommand('copy');" 
                value='{{url('api')}}' />
        </div>-->
        <div class="mb-4">
            <h5>How To Use</h5>
            <div class="mb-2">
                <strong>SCREETKEY:</strong> ABCDEF123456
            </div>
            <div class="mb-2">
                <strong>TIME:</strong> UNIX CURRENT TIME
            </div>
            <div class="mb-2">
                <strong>Header:</strong>
            </div>
            <div class="mb-2">
                <code>X-Authorization-Token:</code> <span class="text-muted">md5(SCREETKEY + TIME + USER_AGENT)</span>
            </div>
            <div class="mb-2">
                <code>X-Authorization-Time:</code> TIME
            </div>
            <div class="mb-2">
                <code>X-user:</code> User Email
            </div>
        </div>

<!--
        <div class='mb-3 row'>
            <label>How To Use</label><br />
            SCREETKEY : ABCDEF123456 <br />
            TIME : UNIX CURRENT TIME <br />
            <label>Header :</label><br />
            X-Authorization-Token : md5( SCREETKEY + TIME + USER_AGENT )<br />
            X-Authorization-Time : TIME<br>
            X-user : User Email
        </div>
-->
<table class="table table-striped table-api table-bordered">
    <thead>
        <tr class="table-primary">
            <th width="2%">No</th>
            <th>
                API Name
                <span class="float-end">
                    <a class="btn btn-sm btn-warning" target="_blank" href="{{CRUDBooster::mainpath('download-postman')}}">
                        Export For POSTMAN <sup>Beta</sup>
                    </a>
                </span>
            </th>
        </tr>
    </thead>
    <tbody>
        @php $no = 0; @endphp
        @foreach($apis as $api)
            @php
                //$parameters = ($api->parameters) ? json_decode(json_encode(unserialize($api->parameters))) : array();
                $parameters = ($api->parameters) ? unserialize($api->parameters) : array();
                $responses = ($api->responses) ? unserialize($api->responses) : array();
            @endphp
            <tr>
                <td>@php echo  ++$no; @endphp</td>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <a id="toggleLink" href="javascript:void(0)" title="API {{ isset($api->nama) ? $api->nama : '' }}" class="link_name_api text-primary">
                            @php echo  $api->nama; @endphp
                        </a>
                        <div class="d-flex">
                            <a title="Delete this API" onclick="deleteApi({{ $api->id }})" href="javascript:void(0)" class="text-danger me-2">
                                <i class="fa fa-trash"></i>
                            </a>
                            <a title="Edit This API" href="{{ url(config('crudbooster.ADMIN_PATH').'/api_generator/edit-api/'.$api->id) }}" class="text-warning">
                                <i class="fa fa-pencil"></i>
                            </a>
                        </div>
                    </div>

                    <!--<a href="javascript:void(0)" title="API {{isset($api->nama) ? $api->nama : ''}}" class="link_name_api text-primary">
                        <?= $api->nama; ?>
                    </a>
                    <div class="d-flex justify-content-end">
                        <a title="Delete this API" onclick="deleteApi({{$api->id}})" href="javascript:void(0)" class="text-danger me-2">
                            <i class="fa fa-trash"></i>
                        </a>
                        <a title="Edit This API" href="{{ url(config('crudbooster.ADMIN_PATH').'/api_generator/edit-api/'.$api->id) }}" class="text-warning">
                            <i class="fa fa-pencil"></i>
                        </a>
                    </div>-->

                    <!--<span class="float-end">
                        <a title="Delete this API" onclick="deleteApi({{$api->id}})" href="javascript:void(0)">
                            <i class="fa fa-trash text-danger"></i>
                        </a>
                        &nbsp;
                        <a title="Edit This API" href="{{url(config('crudbooster.ADMIN_PATH').'/api_generator/edit-api').'/'.$api->id}}">
                            <i class="fa fa-pencil text-warning"></i>
                        </a>
                    </span>-->
<!--style="display:none"-->
                    <div id="detail_api" class=" collapse mt-3"   >
                        <table class="table table-bordered mt-3">
                            <tr>
                                <td width="12%"><strong>URL</strong></td>
                                <td>
                                    <input title="Click to copy!" type="text" class="form-control" readonly 
                                           onClick="this.select(); document.execCommand('copy');" 
                                           value="/{{$api->permalink}}" />
                                </td>
                            </tr>
                            <tr>
                                <td><strong>METHOD</strong></td>
                                <td>{{ strtoupper($api->method_type) }}</td>
                            </tr>
                            <tr>
                                <td><strong>PARAMETER</strong></td>
                                <td>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr class="table-active">
                                                <th width="3%">No</th>
                                                <th width="5%">Type</th>
                                                <th>Parameter Names</th>
                                                <th>Description / Validate / Rule</th>
                                                <th>Mandatory</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                                @php $i = 0; @endphp
                                                @foreach($parameters as $param)


                                                    @if($param['used'])
                                                        <tr>
                                                            <td>{{ ++$i }}</td>
                                                            <td width="5%"><em>{{ $param['type'] }}</em></td>
                                                            <td>{{ $param['name'] }}</td>
                                                            <td>{{ $param['config'] }}</td>
                                                            <td>
                                                                {!! $param['required'] ? "<span class='badge bg-primary'>REQUIRED</span>" : "<span class='badge bg-secondary'>OPTIONAL</span>" !!}
                                                            </td>
                                                        </tr>
                                                    @endif


                                                @endforeach
                                                @if($i == 0)
                                                    <tr>
                                                        <td colspan="5" class="text-center"><i class="fa fa-search"></i> There are no parameters</td>
                                                    </tr>
                                                @endif

                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>RESPONSE</strong></td>
                                <td>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr class="table-active">
                                                <th width="3%">No</th>
                                                <th width="5%">Type</th>
                                                <th>Response Names</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1; ?>
                                            <tr>
                                                <td>{{ $i++ }}</td>
                                                <td><em>integer</em></td>
                                                <td>api_status</td>
                                            </tr>
                                            <tr>
                                                <td>{{ $i++ }}</td>
                                                <td><em>string</em></td>
                                                <td>api_message</td>
                                            </tr>

                                            @if($api->aksi == 'list')
                                                <tr class="table-active">
                                                    <td>#</td>
                                                    <td>Array</td>
                                                    <td><strong>data</strong></td>
                                                </tr>
                                            @endif

                                            @if($api->aksi == 'list' || $api->aksi == 'detail')
                                                @foreach($responses as $resp)
                                                    @if($resp['used'])
                                                        <tr>
                                                            <td>{{ $i++ }}</td>
                                                            <td width="5%"><em>{{ $resp['type'] }}</em></td>
                                                            <td>{{ ($api->aksi == 'list') ? '' : '' }} {{ $resp['name'] }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif

                                            @if($api->aksi == 'save_add')
                                                <tr>
                                                    <td width="5%">{{ $i++ }}</td>
                                                    <td><em>integer</em></td>
                                                    <td>id</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>DESCRIPTION</strong></td>
                                <td><em>{!! $api->keterangan !!}</em></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>



    </div><!--END BODY-->
</div><!--END BOX-->

@endsection