(async function main() {

    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();
    console.log(isLoggedIn);
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


			require( ["js/qlik"], function ( qlik, jQuery ) {
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
})();

async function jwtLogin(token) {
    const authHeader = 'Bearer '+qlik_token ;
    console.log(authHeader);

    return await fetch(`https://data4primesaas.eu.qlikcloud.com/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj'
        },
    })
}

async function checkLoggedIn(token) {
    //console.log("JWTTOKEN");
    //console.log(JWTTOKEN);
    return await fetch(`https://data4primesaas.eu.qlikcloud.com/api/v1/users/me`, {
        //redirect: 'follow'
        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj',
            'Authorization': 'Bearer ' + qlik_token
        },
    })
}