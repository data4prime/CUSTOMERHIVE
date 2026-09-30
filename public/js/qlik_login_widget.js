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

// Mostra un errore nel contenitore del widget invece di restare su "Loading..."
function showWidgetError(message) {
    console.error(message);
    var title = document.getElementById('title');
    if (title) {
        title.textContent = message;
    }
}

async function main() {
    try {
        await mainInner();
    } catch (e) {
        showWidgetError((e && e.message) || String(e));
    }
}

async function mainInner() {

    if (webIntegrationId && webIntegrationId !== '') {
        const check = await checkLoggedIn();

        if (check.status === 401) {
            await jwtLogin();
        }
    } else {
        const authHeader = `Bearer ${qlik_token}`;

        // Il login On-Premise avviene tramite questa chiamata (cookie di sessione):
        // la risposta non serve, ma va atteso il completamento.
        await fetch(`${host}/${prefix}/qrs/about?xrfkey=0123456789abcdef`, {
            credentials: 'include',
            mode: 'cors',
            method: 'GET',
            headers: {
                'X-Qlik-Xrfkey': '0123456789abcdef',
                'Authorization': authHeader,
            },
        });
    }

    if (webIntegrationId && webIntegrationId !== '') {
        await loadScript(`${host}/${src}`);
    } else {
        await loadScript(`${host}/${prefix}/${src}`);
    }

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

    if (webIntegrationId == '') {
        config.prefix = "/"+prefix+"/";
    }

    if (webIntegrationId !== '') {
        config.webIntegrationId = webIntegrationId;
    }

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

    var configuration = {
        baseUrl: baseUrl + 'resources',
    };

    if (webIntegrationId !== '') {
        configuration.webIntegrationId = webIntegrationId;
    }

    //console.log('configuration');
    //console.log(configuration);

    require.config(configuration);

    require(["js/qlik"], function (qlik) {
        if (!qlik) {
            console.error("Il modulo qlik non è stato caricato correttamente.");
            return;
        }
        
        qlik.setOnError(function (error) {
            var appdoc = document.getElementById(appId);
            var text_danger = appdoc ? appdoc.getElementsByClassName('text-danger') : [];

            if (text_danger.length > 0) {
                text_danger[0].append(error.message);
            } else {
                console.error(error.message);
            }
        });

        var app = qlik.openApp(appId, config);
        objectDisplay(app);

        var title = document.getElementById('title');
        title.innerHTML = "";

    });
}
function objectDisplay(app) {
    var title = document.getElementById('title');
        title.textContent = (typeof QLIK_I18N !== "undefined" && QLIK_I18N.object_loading) || "Loading Object. Please wait...";
    if (objectid == 'CurrentSelections') {
        navbar(app);
    } else {
    app.visualization.get(objectid).then(function (vis) {
                    vis.show(objectid);
                });
    }
}
function navbar(app) {

    app.getObject($('#CurrentSelections'), 'CurrentSelections');
    app.getObject($(parent.document).find('#CurrentSelections'), 'CurrentSelections');
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


main();
