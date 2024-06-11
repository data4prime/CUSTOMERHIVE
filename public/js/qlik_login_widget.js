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
var host_q = '';
if (host.includes("https://") || host.includes("http://")) {
    host_q = host.split("//")[1];
}

/*var config = {
				host: host_q, 
				prefix: '', 
				port: port == null ? '443' : port, 
				isSecure: true,
				webIntegrationId: webIntegrationId,

			};*/
			var config = {
				host: "data4primesaas.eu.qlikcloud.com", //the address of your Qlik Engine Instance
				prefix: "/", //or the virtual proxy to be used. for example "/anonymous/"
				port: 443, //or the port to be used if different from the default port  
				isSecure: true, //should be true if connecting over HTTPS
				webIntegrationId: webIntegrationId //only needed in SaaS editions
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
                console.log(config);
                console.log(appId);
				var app = qlik.openApp(appId, config);
                console.log(app);

            getSheets();
            getAppDetails();



function getSheets(){
	app.getAppObjectList( 'sheet', function(reply){
	var str = "";
	console.log(reply);
	$.each(reply.qAppObjectList.qItems, function(key, value) {
		console.log(value.qName);
		str=str+ "<br>" + value.qData.title + "    -    " + value.qInfo.qId;
		app.getFullPropertyTree(value.qInfo.qId).then(function(reply){
		});	
	});
	//$('#QV01').html(str)
	});
	}
	
	function getAppDetails(){
	app.getAppLayout(function(layout){
	console.log("Layout")
	console.log(layout);
	//$('#QV01').html(JSON.stringify(layout))
	});
	}
	function getList(listType){
	app.getList(listType, function(reply){	
		var str="";
		$.each(reply["q"+listType].qItems, function(key, value) {
			
			str=str+ "<br>" + value.qData.title + "    -    " + value.qInfo.qId;
			});
		//$('#QV01').html(str);
		});
	}
	function getVariables(){
	var str="";
	app.getList("VariableList", function(reply){
	$.each(reply.qVariableList.qItems, function(key, value) {
		str=str+ "<br>" + value.qName + "    -    " + value.qInfo.qId;;
		app.variable.getContent(value.qName).then(function(model){
   		console.log(model);
		});
	});
//$('#QV01').html(str);
	});
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