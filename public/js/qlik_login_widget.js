(async function main() {

    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();
    //console.log(isLoggedIn);
var selState;
			var query;
			var filters;
			/*var config = {
				host: host, 
				prefix: '', 
				port: port == null ? '443' : port, 
				isSecure: true,
				webIntegrationId: webIntegrationId,

			};*/

//se host contiene il protocollo, elimina tutto tranne l'host
if (host.includes("https://") || host.includes("http://")) {
    host = host.split("//")[1];
}

var config = {
				host: host, 
				prefix: '', 
				port: port == null ? '443' : port, 
				isSecure: true,
				webIntegrationId: webIntegrationId,

			};
						const baseUrl = ( config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;
                //console.log(baseUrl);
                //console.log(config);

				require.config({
						baseUrl: baseUrl + '/resources/',
						webIntegrationId: config.webIntegrationId			
			});


			require( ["js/qlik"], function ( qlik ) {
                if (!qlik) {
                        console.error("Il modulo qlik non è stato caricato correttamente.");
                        return;
                    }
                qlik.setOnError( function (error){
                        //alert(error.message);
                        //document.getElementById(appId).getElementsByClassName('text-danger').append(error.message);
                        var appdoc = document.getElementById(appId);//.getElementsByClassName('text-danger')[0].append(error.message);
                        //console.log(appdoc);
                        var text_danger =  appdoc.getElementsByClassName('text-danger');    
                        //console.log(text_danger);

                        text_danger[0].append(error.message);
                    });

                var x = document.cookie;
                //console.log(x);
                //config.host = '';
				var app = qlik.openApp(appId, config);



				
			});
})();

async function jwtLogin(token) {
    const authHeader = 'Bearer '+qlik_token ;
    console.log(authHeader);

    return await fetch(`${host}/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': webIntegrationId
        },
    })
}

async function checkLoggedIn(token) {
    //console.log("JWTTOKEN");
    //console.log(JWTTOKEN);
    return await fetch(`${host}/api/v1/users/me`, {
        //redirect: 'follow'
        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': webIntegrationId,
            'Authorization': 'Bearer ' + qlik_token
        },
    })
}