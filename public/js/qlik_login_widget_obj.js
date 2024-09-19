

function objectsOptionsOBJ(app) {

    var hidden_object = parent.document.getElementById('mashup_object_hidden');

    var hidden_app = parent.document.getElementById('mashup_app_hidden');

    var option_cs = document.createElement('option');
    option_cs.className = 'masterobject-option';
    option_cs.value =  "CurrentSelections";
    option_cs.innerHTML = "Current Selections";


    if (hidden_object && (hidden_object.value == "CurrentSelections"  && hidden_app.value == mashupId)) {

    option_cs.selected = true;
    }

    //if parent document is not available, stop script
    if (parent.document.getElementById('mashup_object')) {
        parent.document.getElementById('mashup_object').appendChild(option_cs);
    }



    //console.log('objectsOptions');
    //console.log(app.getAppObjectList('masterobject'));
    app.getAppObjectList('masterobject', function (reply) {
        //console.log('reply: ');
        //console.log(reply);

	var str = "";

	$.each(reply.qAppObjectList.qItems, function(key, value) {
        var sheetId = value.qInfo.qId;
        var sheetTitle = value.qData.title;
		var name = value.qData.name;
        var sheetDiv = document.createElement('option');
		sheetDiv.className = 'masterobject-option';
		sheetDiv.value = sheetId;
		sheetDiv.innerHTML = name+' ('+sheetId+')';

        if (hidden_object.value == sheetId && hidden_app.value == mashupId) {
            sheetDiv.selected = true;
        }

        parent.document.getElementById('mashup_object').appendChild(sheetDiv);

        app.visualization.get(value.qInfo.qId).then(function(vis){
		    vis.show(value.qInfo.qId);
	    });
		str +=  value.qData.title + ' ';
		$.each(value.qData.cells, function(k,v){
			str +=  v.name + ' ';
 
		});
	});
});


}

async function checkLoggedIn() {

    return await fetch(`${host}/api/v1/users/me`, {
        mode: 'cors',
        credentials: 'include',
        headers: {
            'Authorization': `Bearer ${qlik_token}`,
            'qlik-web-integration-id': webIntegrationId
        },
    })
}

async function loadScript(url) {
    return new Promise((resolve, reject) => {
        var script = document.createElement('script');
        script.src = url;
        script.type = 'text/javascript';
        script.async = false;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Error loading ${url}`));
        document.head.appendChild(script);
    });
}

async function main() {
    const authHeader = `Bearer ${qlik_token}`;
    const check = await checkLoggedIn();
    //console.log('check: ');
    //console.log(check);

    if (check.status === 401) {

        const response = await fetch(`${host}/login/jwt-session`, {
            credentials: 'include',
            mode: 'cors',
            method: 'POST',
            headers: {
                'Authorization': authHeader,
                'qlik-web-integration-id': webIntegrationId
            },
        });

    }


    await loadScript(`${host}/resources/assets/external/requirejs/require.js`);

    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }

    var config = {
        host: host_q, 
        prefix: `/`, 
        port: 443, 
        isSecure: true, 
        webIntegrationId: webIntegrationId
    };
    //console.log('config: ');
    //console.log(config);

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

    //console.log('baseUrl: '+baseUrl);

    require.config({
		baseUrl: baseUrl + 'resources',
        webIntegrationId: config.webIntegrationId

	});

    require(["js/qlik"], function (qlik) {
        if (!qlik) {
            console.error("Il modulo qlik non è stato caricato correttamente.");
            return;
        }
    
        
        qlik.setOnError(function (error) {
            var appdoc = document.getElementById(appId);
            var text_danger = appdoc.getElementsByClassName('text-danger');

            
            if (text_danger.length > 0) {
                text_danger[0].append(error.message);
            } else {
                alert(error.message);
            }
        });

        var app = qlik.openApp(appId, config);

        objectsOptionsOBJ(app);

        var title = document.getElementById('title');
        title.innerHTML = "";

        if (parent.document.getElementById('mashup_object')) {
            var mashup_object = parent.document.getElementById('mashup_object');
            mashup_object.removeAttribute('disabled');
        }

        

    });

}

main();
























/*
async function main() {

console.log('main');
if (webIntegrationId && webIntegrationId !== '') {
    
    const check = await checkLoggedIn();
    console.log('check: ');
    console.log(check);

    if (check.status === 401) {
        const isLoggedIn = await jwtLogin();
        console.log('isLoggedIn: ');
        console.log(isLoggedIn);

    }
}




    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }

    console.log('host_q: '+host_q);
    console.log('host: '+host);

    var config = {
        host: host_q, 
        prefix: "/", 
        port: 443, 
        isSecure: true, 
        webIntegrationId: webIntegrationId
    };

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;



    console.log('baseUrl: '+baseUrl);

    console.log('config: ');
    console.log(config);


    require.config({
        baseUrl: baseUrl + 'resources',
        webIntegrationId:  webIntegrationId
    });

    require(["js/qlik"], function (qlik) {
        if (!qlik) {
            console.error("Il modulo qlik non è stato caricato correttamente.");
            return;
        }
        
        qlik.setOnError(function (error) {
            var appdoc = document.getElementById(appId);
            var text_danger = appdoc.getElementsByClassName('text-danger');
            
            if (text_danger.length > 0) {
                text_danger[0].append(error.message);
            } else {
                console.error(error);
                alert(error.message);
            }
        });

        var app = qlik.openApp(appId, config);

        console.log('app: ');
        console.log(app);

        objectsOptions(app);

    
        var title = document.getElementById('title');
        title.innerHTML = "";

        var mashup_object = parent.document.getElementById('mashup_object');
        mashup_object.removeAttribute('disabled');

    });
}



function objectsOptions(app) {

var hidden_object = parent.document.getElementById('mashup_object_hidden');

var hidden_app = parent.document.getElementById('mashup_app_hidden');

var option_cs = document.createElement('option');
option_cs.className = 'masterobject-option';
option_cs.value =  "CurrentSelections";
option_cs.innerHTML = "Current Selections";


if (hidden_object && (hidden_object.value == "CurrentSelections"  && hidden_app.value == mashupId)) {

 option_cs.selected = true;
}


parent.document.getElementById('mashup_object').appendChild(option_cs);
    console.log('objectsOptions');
    console.log(app.getAppObjectList('masterobject'));
    app.getAppObjectList('masterobject', function (reply) {
        console.log('reply: ');
        console.log(reply);
        var str = "";

        $.each(reply.qAppObjectList.qItems, function (key, value) {
            var sheetId = value.qInfo.qId;
            var sheetTitle = value.qData.title;
            var name = value.qData.name;

            var option = document.createElement('option');
            option.className = 'masterobject-option';
            option.value = sheetId;
            option.innerHTML = name + ' (' + sheetId + ')';

            if ( hidden_object.value == sheetId && hidden_app.value == mashupId) {

                option.selected = true;
            }

            parent.document.getElementById('mashup_object').appendChild(option);
        });
    });


}

async function jwtLogin() {
    const authHeader = 'Bearer ' + qlik_token;

    const response = await fetch(`${host}/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': webIntegrationId
        }
    });

    return response.ok;
}

async function checkLoggedIn() {
    const response = await fetch(`${host}/api/v1/users/me`, {
        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': webIntegrationId,
            'Authorization': 'Bearer ' + qlik_token
        }
    });

    return response;
}


main();




*/

