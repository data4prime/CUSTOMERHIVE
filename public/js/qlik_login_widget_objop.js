async function main() {


/*if (webIntegrationId !== '') {
    
    const check = await checkLoggedIn();
    console.log('check: ');
    console.log(check);

    if (check.status === 401) {*/
        //const isLoggedIn = await getTicket();
        /*console.log('isLoggedIn: ');
        console.log(isLoggedIn);

    }
}*/

    var selState;
    var query;
    var filters;

    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }

    var config = {
        host: host_q, 
        prefix: "", 
        port: 443, 
        isSecure: true, 
    };

    if (webIntegrationId !== '') {
        config.webIntegrationId = webIntegrationId;
    }



    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;


    var req_conf = {
        baseUrl: baseUrl + '/resources',
    };

    if (webIntegrationId !== '') {
        req_conf.webIntegrationId = webIntegrationId;
    }

    console.log('baseUrl: '+baseUrl);
    require.config(req_conf);

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

        var x = document.cookie;

        var app = qlik.openApp(appId, config);
        /*var sessionAppFromApp = qlik.sessionAppFromApp(appId, config);
        sessionAppFromApp.doReload().then(function(result){
                if( result ){
                    console.log('Reload successful');

                    console.log(result);
                    //sessionAppFromApp.getObject('QV01', 'HrZsPG');
                } 
                else {
                    console.log('Reload failed');
                }
            });
        var app = sessionAppFromApp;
        console.log('sessionAppFromApp: ');
        console.log(sessionAppFromApp);*/
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

async function getTicket() {

    console.log('ticket_data_json: ');
    console.log(ticket_data);

const config = {
    method: 'post',
    url: ticket_data.url,
    headers: ticket_data.headers,
    data: JSON.stringify(ticket_data.body),
    httpsAgent: new require('https').Agent({
        cert: fs.readFileSync(ticket_data.QRSCertfile),
        key: fs.readFileSync(ticket_data.QRSCertkeyfile),
        passphrase: ticket_data.QRSCertkeyfilePassword,
        rejectUnauthorized: false 
    })
};

// Esecuzione della richiesta
axios(config)
    .then(response => {
        console.log('Response:', response.data);
    })
    .catch(error => {
        console.error('Error:', error.response ? error.response.data : error.message);
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
