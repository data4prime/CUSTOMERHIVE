


@php 


use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;
use crocodicstudio\crudbooster\controllers\QlikMashupController;

$token = HelpersQlikHelper::getJWTToken(1, 3);

$conf = QlikMashupController::getConf($qlik_conf);

dd(gettype($conf));

@endphp 
@if (isset($conf) || !empty($conf)) 
<script type="text/javascript"  src="{{$conf->host}}/resources/assets/external/requirejs/require.js"></script>

<div id="{{$mashup->appid}}" class="small-box [color]">
<script  type="text/javascript" >

var qlik_token = '{{$token}}';
var host = '{{$conf->host}}';
var prefix = '{{$conf->prefix}}';
var port = '{{$conf->port}}';
var webIntegrationId = '{{$conf->webIntegrationId}}';
var appId = '{{$mashup->appid}}';
var componentID = '{{$componentID}}';

</script>





    
  <div id="title"></div>

<div id="currentselection"></div>

<div class="text-danger" ></div>
</div>
<script defer  src="{{asset('js/qlik_login_widget.js')}}"></script>
@endif
<!-- 
@if(isset($componentID))
	<h1>{{$componentID}}</h1>
@else 
	<h1>Component ID not found</h1>
@endif


@if(isset($token))
	<h1>{{$token}}</h1>
@else
	<h1>Token not found</h1>
@endif
-->


