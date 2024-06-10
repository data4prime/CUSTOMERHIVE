@php 


use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;


$token = HelpersQlikHelper::getJWTToken(1, 3);


@endphp 

<script  type="text/javascript" >

var qlik_token = '{{$token}}';

</script>



<script type="text/javascript"  src="https://data4primesaas.eu.qlikcloud.com/resources/assets/external/requirejs/require.js"></script>
<!--<script  src="{{asset('js/qlik_login_widget.js')}}"></script> -->

<!-- se componentID esiste -->
@if(isset($componentID))
	<h1>{{$componentID}}</h1>
@else 
	<h1>Component ID not found</h1>
@endif

<!-- se token esiste -->
@if(isset($token))
	<h1>{{$token}}</h1>
@else
	<h1>Token not found</h1>
@endif



