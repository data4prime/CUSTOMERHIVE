async function main() {
    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();
    
    var selState;
    var query;
    var filters;

    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }


    
    var config = {
        host: host_q, 
        prefix: "/", 
        port: 443, 
        isSecure: true, 
    };

    if (webIntegrationId !== '') {
        config.webIntegrationId = webIntegrationId;
    }

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

    require.config({
        baseUrl: baseUrl + 'resources/',
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
                console.error(error.message);
            }
        });

        var x = document.cookie;

        var app = qlik.openApp(appId, config);

        objectsOptions(app);

        var title = document.getElementById('title');
        title.innerHTML = "";

        var mashup_object = parent.document.getElementById('mashup_object');
        mashup_object.removeAttribute('disabled');
        console.log("objectsOptions(app)");



    });
}



function objectsOptions(app) {
    app.getAppObjectList('masterobject', function (reply) {
        var str = "";

        $.each(reply.qAppObjectList.qItems, function (key, value) {
            var sheetId = value.qInfo.qId;
            var sheetTitle = value.qData.title;
            var name = value.qData.name;

            var hidden_object = parent.document.getElementById('mashup_object_hidden');
            var hidden_app = parent.document.getElementById('mashup_app_hidden');
            

            var sheetDiv = document.createElement('option');
            sheetDiv.className = 'masterobject-option';
            sheetDiv.value = sheetId;
            sheetDiv.innerHTML = name + ' (' + sheetId + ')';
            console.log("hidden_object: " + hidden_object.value);
            console.log("hidden_app: " + hidden_app.value);

            if (hidden_object.value == objectid && hidden_app.value == mashupId) {
                sheetDiv.selected = true;
            }

            parent.document.getElementById('mashup_object').appendChild(sheetDiv);
        });
    });


}

async function jwtLogin() {
    const authHeader = 'Bearer ' + qlik_token;
    //console.log(authHeader);

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

    return response.ok;
}

// Avvia la funzione principale


main();
