@php 


use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;


$token = HelpersQlikHelper::getJWTToken(1, 3);


@endphp 

<script  type="text/javascript" >

var qlik_token = '{{$token}}';

</script>



<script type="text/javascript"  src="https://data4primesaas.eu.qlikcloud.com/resources/assets/external/requirejs/require.js"></script>
<script  src="{{asset('js/qlik_login_widget.js')}}"></script> 
<script defer type="text/javascript" >
/*
var selState;
			var query;
			var filters;
			var config = {
				host: "data4primesaas.eu.qlikcloud.com", 
				prefix: "/", 
				port: 443, 
				isSecure: true,
				webIntegrationId: '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj',

			};
						const baseUrl = ( config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;
                console.log(baseUrl);

				require.config({
						baseUrl: baseUrl + 'resources',
						webIntegrationId: config.webIntegrationId			
			});


			require( ["js/qlik"], function ( qlik ) {
                if (!qlik) {
                        console.error("Il modulo qlik non è stato caricato correttamente.");
                        return;
                    }
                qlik.setOnError( function (error){
                        alert(error.message);
                    });



				var app = qlik.openApp('5a174d39-0d26-4871-bbe9-583252deaeb2', config);
                console.log("DOPOO OPEN APP");
				


				
			});
*/
			

  </script>


