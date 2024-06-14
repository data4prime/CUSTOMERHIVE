(async function main() {

    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();
    //console.log(isLoggedIn);
var selState;
			var query;
			var filters;

var host_q = '';
if (host.includes("https://") || host.includes("http://")) {
    host_q = host.split("//")[1];
}


			var config = {
				host: host_q, //the address of your Qlik Engine Instance
				prefix: "/", //or the virtual proxy to be used. for example "/anonymous/"
				port: 443, //or the port to be used if different from the default port  
				isSecure: true, //should be true if connecting over HTTPS
				//webIntegrationId: webIntegrationId
			};
			//if webIntegrationId is not empty, add it to the config object
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

                        var appdoc = document.getElementById(appId);//.getElementsByClassName('text-danger')[0].append(error.message);
                        //console.log(appdoc);
                        var text_danger =  appdoc.getElementsByClassName('text-danger');    
                        //console.log(text_danger);

                        text_danger[0].append(error.message);
                    });

                var x = document.cookie;

				var app = qlik.openApp(appId, config);
app.getObject($('#currentselection'), 'CurrentSelections');


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