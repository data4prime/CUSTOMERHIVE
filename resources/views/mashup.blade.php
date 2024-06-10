@php 


use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;


$token = HelpersQlikHelper::getJWTToken(1, 3);


@endphp 

<script  type="text/javascript" >

var qlik_token = '{{$token}}';

</script>



<script type="text/javascript"  src="https://data4primesaas.eu.qlikcloud.com/resources/assets/external/requirejs/require.js"></script>
<!--<script  src="{{asset('js/qlik_login_widget.js')}}"></script> -->

<h1>{{$componentID}}</h1>
<h2>{{$token}}</h2>


