<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('crudbooster::partials.ch_head')
    @include('crudbooster::partials.ch_scripts', ['ch_shell' => false])
</head>
<body>
<div class="container">
    <div class="pb-2 mt-4 mb-3 border-bottom">
        <h1>API Documentation {{Session::get('appname')}}</h1>
    </div>

    <div class='box'>

        <div class='box-body'>

            <style>
                .table-api tbody tr td a {
                    color: var(--ch-danger);
                    font-family: var(--ch-font);
                }
            </style>

            <script>
                $(function () {
                    $(".link_name_api").click(function () {
                        $(".detail_api").slideUp();
                        $(this).parent("td").find(".detail_api").slideDown();
                    })
                    $(".selected_text").each(function () {
                        var n = $(this).text();
                        if (n.indexOf('api_') == 0) {
                            $(this).attr('class', 'selected_text text-danger');
                        }
                    })
                })
            </script>

            <div class='mb-3 row'>
                <label>API BASE URL</label>
                <input type='text' readonly class='form-control' title='Hanya klik dan otomatis copy to clipboard (kecuali Safari)'
                       onClick="this.setSelectionRange(0, this.value.length); document.execCommand('copy');" value='{{url('api')}}'/>
            </div>
            <div class='mb-3 row'>
                <label>How To Use</label><br/>
                SECRETKEY : ABCDEF123456 <br/>
                TIME : UNIX CURRENT TIME <br/>
                <label>Header :</label><br/>
                X-Authorization-Token : md5( SECRETKEY + TIME + USER_AGENT )<br/>
                X-Authorization-Time : TIME<br>
                X-user : User Email
            </div>

            {{--
                Binario api2 (Bearer token via Laravel Sanctum), additivo e
                parallelo ad api/ sopra - vedi docs/refactoring/086/090.
                Stesso permalink di ogni riga della tabella sotto, prefisso
                diverso.
            --}}
            <div class='mb-3 row'>
                <label>{{ trans('crudbooster.api_doc_api2_base_url_label') }}</label>
                <input type='text' readonly class='form-control'
                       onClick="this.setSelectionRange(0, this.value.length); document.execCommand('copy');" value='{{url('api2')}}'/>
            </div>
            <div class='mb-3 row'>
                <label>{{ trans('crudbooster.api_doc_api2_howto_title') }}</label><br/>
                <label>{{ trans('crudbooster.api_doc_api2_howto_header') }}</label><br/>
                Authorization : Bearer &lt;token&gt;<br/>
                {!! trans('crudbooster.api_doc_api2_howto_hint', [
                    'link' => '<a href="'.url(config('crudbooster.ADMIN_PATH').'/api_tokens').'">'.trans('crudbooster.api_doc_api2_howto_hint_link_label').'</a>',
                ]) !!}
            </div>
            <table class='table table-striped table-api table-bordered'>
                <thead>
                <tr class='info'>
                    <th width='2%'>No</th>
                    <th>API Name
                        <span class='float-end'>
                      <a class='btn btn-sm btn-warning' target="_blank" href='{{url("download-documentation-postman")}}'>Export For POSTMAN <sup>Beta</sup></a>
                    </span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php $no = 0;?>
                @foreach($apis as $api)
                    <?php
                    $parameters = ($api->parameters) ? unserialize($api->parameters) : array();
                    $responses = ($api->responses) ? unserialize($api->responses) : array();
                    ?>
                    <tr>
                        <td><?= ++$no;?></td>
                        <td>
                            <a href='javascript:void(0)' title='API {{$ac->nama}}' style='color:var(--ch-blue)' class='link_name_api'><?=$api->nama;?></a> &nbsp;
                            <div class='detail_api' style='display:none'>
                                <table class='table table-bordered'>
                                    <tr>
                                        <td width='12%'><strong>URL</strong></td>
                                        <td><input title='Click and copied !' type='text' class='form-control' readonly
                                                   onClick="this.setSelectionRange(0, this.value.length); document.execCommand('copy');"
                                                   value="/{{$api->permalink}}"/></td>
                                    </tr>
                                    <tr>
                                        <td><strong>METHOD</strong></td>
                                        <td>{{strtoupper($api->method_type)}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>PARAMETER</strong></td>
                                        <td>
                                            <table class='table table-bordered table-hover'>
                                                <thead>
                                                <tr class='active'>
                                                    <th width="3%">No</th>
                                                    <th width="5%">Type</th>
                                                    <th>Parameter Names</th>
                                                    <th>Description / Validate / Rule</th>
                                                    <th>Mandatory</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php $i = 0;?>
                                                @foreach($parameters as $param)
                                                    @if($param['used'])
                                                        <?php
                                                        $param_exception = ['in', 'not_in', 'digits_between'];
                                                        if ($param['config'] && substr($param['config'], 0, 1) != '*' && ! in_array($param['type'], $param_exception)) continue;?>
                                                        <tr>
                                                            <td>{{++$i}}</td>
                                                            <td width="5%"><em>{{$param['type']}}</em></td>
                                                            <td>{{$param['name']}}</td>
                                                            <td>

                                                                @if(substr($param['config'],0,1) == '*')
                                                                    <span class='text-info'>{{substr($param['config'],1)}}</span>
                                                                @else
                                                                    {{$param['config']}}
                                                                @endif

                                                            </td>
                                                            <td>{!! ($param['required'])?"<span class='badge text-bg-primary'>REQUIRED</span>":"<span class='badge text-bg-secondary'>OPTIONAL</span>"!!}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @if($i == 0)
                                                    <tr>
                                                        <td colspan='4' align="center"><i class='bi bi-search'></i> There is no parameter</td>
                                                    </tr>
                                                @endif
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>RESPONSE</strong></td>
                                        <td>
                                            <table class='table table-bordered table-hover'>
                                                <thead>
                                                <tr class='active'>
                                                    <th width="3%">No</th>
                                                    <th width="5%">Type</th>
                                                    <th>Response Names</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php $i = 1;?>
                                                <tr>
                                                    <td>{{$i++}}</td>
                                                    <td><em>integer</em></td>
                                                    <td>api_status</td>
                                                </tr>
                                                <tr>
                                                    <td>{{$i++}}</td>
                                                    <td><em>string</em></td>
                                                    <td>api_message</td>
                                                </tr>

                                                @if($api->aksi == 'list')
                                                    <tr class='active'>
                                                        <td>#</td>
                                                        <td>Array</td>
                                                        <td><strong>data</strong></td>
                                                    </tr>
                                                @endif

                                                @if($api->aksi == 'list' || $api->aksi == 'detail')
                                                    @foreach($responses as $resp)
                                                        @if($resp['used'])
                                                            <tr>
                                                                <td>{{$i++}}</td>
                                                                <td width="5%"><em>{{$resp['type']}}</em></td>
                                                                <td>{{ ($api->aksi=='list')?'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- ':'' }} {{$resp['name']}}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @endif

                                                @if($api->aksi == 'save_add')
                                                    <tr>
                                                        <td width="5%">{{$i++}}</td>
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

    <hr>
    <div align="center">
        &copy; Copyright {{date('Y')}}. All Right Reserved. API Documentation
    </div>


</div>
</body>
</html>