async function main() {
    const isLoggedIn = await jwtLogin();
    const check = await checkLoggedIn();

    console.log("isLoggedIn");
    console.log(isLoggedIn);
    console.log("check");
    console.log(check);
    
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
        //console.log("objectsOptions(app)");



    });
}



function objectsOptions(app) {

//console.log(parent.document.querySelectorAll('#mashup_object_hidden'));

var hidden_object = parent.document.getElementById('mashup_object_hidden');

var hidden_app = parent.document.getElementById('mashup_app_hidden');

var option_cs = document.createElement('option');
option_cs.className = 'masterobject-option';
option_cs.value =  "CurrentSelections";
option_cs.innerHTML = "Current Selections";


if (hidden_object.value == "CurrentSelections"  && hidden_app.value == mashupId) {
//console.log("CurrentSelections");
//console.log("TRUE");
 option_cs.selected = true;
}


parent.document.getElementById('mashup_object').appendChild(option_cs);

    app.getAppObjectList('masterobject', function (reply) {
        var str = "";

        $.each(reply.qAppObjectList.qItems, function (key, value) {
            var sheetId = value.qInfo.qId;
            var sheetTitle = value.qData.title;
            var name = value.qData.name;

            /*var hidden_object = parent.document.getElementById('mashup_object_hidden');
var hidden_app = parent.document.getElementById('mashup_app_hidden');*/
            

            var option = document.createElement('option');
            option.className = 'masterobject-option';
            option.value = sheetId;
            option.innerHTML = name + ' (' + sheetId + ')';

            if ( hidden_object.value == sheetId && hidden_app.value == mashupId) {

                /*
                console.log("--------------------");
                console.log("hidden_object.value " + hidden_object.value + " == " + objectid);
                console.log("objectid " + objectid);
                console.log("hidden_app.value " + hidden_app.value + " == " + mashupId);
                console.log("mashupId " + mashupId);
                console.log("--------------------");
                */

                option.selected = true;
            }

            parent.document.getElementById('mashup_object').appendChild(option);
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

    return response;
}

// Avvia la funzione principale


main();
