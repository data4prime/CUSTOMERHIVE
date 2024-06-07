<footer class="main-footer">
    <!-- To the right -->
    <div class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{ trans('crudbooster.powered_by') }} Data4Prime
    </div>
    <div style="margin-right:15px;" class="pull-{{ trans('crudbooster.right') }} hidden-xs">
        {{Session::get('appname')}} {{ config('app.version') }}
    </div>
    <!-- Default to the left -->
    <strong>{{ trans('crudbooster.copyright') }} &copy; <?php echo date('Y') ?>. {{ trans('crudbooster.all_rights_reserved') }} .</strong>
</footer>
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
                /*paths: {
                    "qlik": "js/qlik"

                    }*/
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

				//qlik.setLanguage("en");

				var app = qlik.openApp('5a174d39-0d26-4871-bbe9-583252deaeb2', config);
                console.log("DOPOO OPEN APP");
				


				
			});
} catch (e) {
        console.error("Errore durante l'apertura dell'app:", e);
    }

  </script>