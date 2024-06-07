<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.2.3 -->





<!--<script type="text/javascript" src="https://sense.izsvenezie.it/pub/resources/assets/external/requirejs/require.js"></script>-->

<script>
    var ASSET_URL = "{{asset('/')}}";
    var APP_NAME = "{{Session::get('appname')}}";
    var ADMIN_PATH = '{{url(config("crudbooster.ADMIN_PATH")) }}';
    var NOTIFICATION_JSON = "{{route('NotificationsControllerGetLatestJson')}}";
    var NOTIFICATION_INDEX = "{{route('NotificationsControllerGetIndex')}}";

    var NOTIFICATION_YOU_HAVE = "{{trans('crudbooster.notification_you_have')}}";
    var NOTIFICATION_NOTIFICATIONS = "{{trans('crudbooster.notification_notification')}}";
    var NOTIFICATION_NEW = "{{trans('crudbooster.notification_new')}}";

    $(function () {
        $('.datatables-simple').DataTable();
    })
</script>
<script src="{{asset('vendor/crudbooster/assets/js/main.js').'?r='.time()}}"></script>


<script type="text/javascript"  src="https://data4primesaas.eu.qlikcloud.com/resources/assets/external/requirejs/require.js"></script>
  <script type="text/javascript" >
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

            try {

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
} catch (e) {
        console.error("Errore durante l'apertura dell'app:", e);
    }

  </script>


