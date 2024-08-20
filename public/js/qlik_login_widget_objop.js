async function mainOP() {

    const login = await jwtLoginOP(qlik_token);

    //if code is not 200, add the response to the error message
    if (!login.ok) {
        //find div with id 'title'
        var title = document.getElementById('title');
        //add the error message to the div
        title.innerHTML = "Errore durante il login: " + login.status + ' - ' + login.statusText;
        //console.error('Errore durante il login: ' + login.status + ' - ' + login.statusText);

        //in parent document find iframe with id 'configuration' and change height to 80%
        parent.document.getElementById('configuration').style.height = '80%';


        return;
    }
 var response = await login.text();



        

    console.log('response: '+response);


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
    console.log('config: ');
    console.log(config);

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;
/*var script = document.createElement('script');
    script.src = baseUrl + 'jwt/resources/assets/external/requirejs/require.js';
    script.type = 'text/javascript';
    document.head.appendChild(script);
*/

    //wait until require.js is loaded
   /* await new Promise((resolve, reject) => {
        script.onload = resolve;
        script.onerror = reject;
    });*/





    console.log('baseUrl: '+baseUrl);

    require.config({
		baseUrl: baseUrl + 'resources',
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

        //document.cookie;

        var app = qlik.openApp(app, config);
        console.log('app: ');
        console.log(app);

        objectsOptions(app);

       


        var title = document.getElementById('title');
        title.innerHTML = "";

        var mashup_object = parent.document.getElementById('mashup_object');
        mashup_object.removeAttribute('disabled');

    });


}


async function jwtLoginOP(token) {
    const authHeader = `Bearer ${token}`;
    console.log(authHeader);
    //console.log(WEBINTEGRATIONID);
    return await fetch(`${host}/${prefix}/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'X-Qlik-Xrfkey': '0123456789abcdef',
            'Authorization': authHeader,
        },
    });
}


  const main_op = mainOP();
console.log("qlik_login_widget_objop");





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


        $.each(reply.qAppObjectList.qItems, function () {
            var sheetId = value.qInfo.qId;

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

/*
async function jwtLogin(token) {
    const authHeader = `Bearer ${token}`;
    console.log(authHeader);
    //console.log(WEBINTEGRATIONID);
    return await fetch(`${host}/${prefix}/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'X-Qlik-Xrfkey': '0123456789abcdef',
            'Authorization': authHeader,
        },
    });
}
*/
/*
$(document).ready(function () {
    console.log("document ready -------------------");
*/
    //main();
console.log("qlik_login_widget_objop2");
/*
});
*/




/*

return await fetch(`https://qse.datasynapsi.cloud/jwt/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiJtYXJjby56YW1waWVyaSIsInVzZXJEaXJlY3RvcnkiOiJDSElWRSJ9.LQoHYvaegttx85sVZzH-uBdwNAB0WhZZxBn8DfSM8Z89_HyhTl3zLIg_Xkn_ezhGjjCvIqFNK3csgKi_4-GqrIyT3VYbooXjfLzSjhKsVTvzxY4c7deVlspU8nnt6Fo6YAelOuJWTWE8gYOEaWyuT_yBcEXdsIu7Zcj02SYn-FnBnhIKhKgf3v8iLO2mpKpXNR3RHivfeY3OWyWOMqFfMEgj2NQb2YUOJujvTyDQIZ8d0MwWFy0csveP9QLp2leX5iU6KqOsfn7NMMJUEJZqTIq2VvlCisjcTO6rDzT-3tkf9uy2WRoVPwOW7yzXTEGLtcGM8EeaZDsutyu_Iv1wPA',
        },
    });
*/