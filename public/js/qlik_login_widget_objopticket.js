

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

async function mainOP() {
    /*const authHeader = `Bearer ${qlik_token}`;

    const response = await fetch(`${host}/${prefix}/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'X-Qlik-Xrfkey': '0123456789abcdef',
            'Authorization': authHeader,
        },
    });

    const data = await response.json();
    console.log(data);
    */

    await loadScript(`${host}/${src_js}`);

    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }

    var config = {
        host: host_q, 
        prefix: `/`, 
        port: 443, 
        isSecure: true, 
    };
    console.log('config: ');
    console.log(config);

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

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

mainOP();


/*
const authHeader = `Bearer ${qlik_token}`;
console.log(authHeader);
fetch(`${host}/${prefix}/qrs/about?xrfkey=0123456789abcdef`, {
    credentials: 'include',
    mode: 'cors',
    method: 'GET',
    headers: {
        'X-Qlik-Xrfkey': '0123456789abcdef',
        'Authorization': authHeader,
    },
})
.then(response => {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    var script = document.createElement('script');
    script.src = `${host}/${prefix}/resources/assets/external/requirejs/require.js`;
    script.type = 'text/javascript';
    script.async = false;
    document.head.appendChild(script);

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
    return response;
})
.catch(error => {
    console.error('Error:', error);
});
*/













/*
async function jwtLoginOP(token) {
    const authHeader = `Bearer ${token}`;
    console.log(authHeader);
    const response = await fetch(`${host}/${prefix}/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'X-Qlik-Xrfkey': '0123456789abcdef',
            'Authorization': authHeader,
        },
    });
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response;
}
*/

//function jwtLoginOP(token) {

//}

/*
function mainOP() {
    const title = document.getElementById('title');
    
    jwtLoginOP(qlik_token)
        .then(login => {
            return login.text();
        })
        .then(response => {
        })
        .catch(error => {
            title.innerHTML = "Errore durante il login: " + error.message;
            parent.document.getElementById('configuration').style.height = '80%';
        });
}

mainOP();
console.log("qlik_login_widget_objop");
*/

/*
async function mainOP() {
    try {
        const login = await jwtLoginOP(qlik_token);
        const response = await login.text();
    } catch (error) {
        const title = document.getElementById('title');
        title.innerHTML = "Errore durante il login: " + error.message;
        parent.document.getElementById('configuration').style.height = '80%';
    }
}
(async () => {
    await mainOP();
    console.log("qlik_login_widget_objop");
})();
*/
/*

async function mainOP() {

    const login = await jwtLoginOP(qlik_token);
    if (!login.ok) {
        var title = document.getElementById('title');
        title.innerHTML = "Errore durante il login: " + login.status + ' - ' + login.statusText;
        parent.document.getElementById('configuration').style.height = '80%';
        return;
    }
    var response = await login.text();

}


async function jwtLoginOP(token) {
    const authHeader = `Bearer ${token}`;
    console.log(authHeader);
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

const main_op = await mainOP();
console.log("qlik_login_widget_objop");

*/

