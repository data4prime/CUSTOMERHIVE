(async function main() {

    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();
var selState;
			var query;
			var filters;

var host_q = '';
if (host.includes("https://") || host.includes("http://")) {
    host_q = host.split("//")[1];
}

console.log(host_q);
			var config = {
				host: host_q, 
				prefix: "/", 
				port: 443, 
				isSecure: true, 
			};
			if (webIntegrationId != '') {
				config.webIntegrationId = webIntegrationId;
			}
						const baseUrl = ( config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

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

                        var appdoc = document.getElementById(appId);
                        var text_danger =  appdoc.getElementsByClassName('text-danger');    

                        text_danger[0].append(error.message);
                    });

                var x = document.cookie;

				var app = qlik.openApp(appId, config);




navbar();

/*
app.getAppObjectList( 'masterobject', function(reply){
	var str = "";

	$.each(reply.qAppObjectList.qItems, function(key, value) {
        console.log(value);
        var sheetId = value.qInfo.qId;
        var sheetTitle = value.qData.title;
        console.log(sheetId);
        console.log(sheetTitle);
        var sheetDiv = document.createElement('div');
        sheetDiv.id = sheetId;
		sheetDiv.className = 'masterobject';
        document.getElementById(appId).appendChild(sheetDiv);

        app.visualization.get(value.qInfo.qId).then(function(vis){
		    vis.show(value.qInfo.qId);
	    });
		str +=  value.qData.title + ' ';
		$.each(value.qData.cells, function(k,v){
			str +=  v.name + ' ';
 
		});
	});
});
*/
function navbar() {
console.log(document.getElementById('configuration'));
app.getObject($('#currentselection'), 'CurrentSelections');
}



				
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

    return await fetch(`${host}/api/v1/users/me`, {

        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': webIntegrationId,
            'Authorization': 'Bearer ' + qlik_token
        },
    })
}