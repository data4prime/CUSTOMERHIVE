function objectsOptionsOBJ(app) {

    var hidden_object = parent.document.getElementById('mashup_object_hidden');

    var hidden_app = parent.document.getElementById('mashup_app_hidden');

    // true se l'oggetto/app salvati nel padre corrispondono a quello indicato
    function isSelected(objectId) {
        return !!(hidden_object && hidden_app && hidden_object.value == objectId && hidden_app.value == mashupId);
    }

    var select = parent.document.getElementById('mashup_object');

    var option_cs = document.createElement('option');
    option_cs.className = 'masterobject-option';
    option_cs.value =  "CurrentSelections";
    option_cs.textContent = (typeof QLIK_I18N !== "undefined" && QLIK_I18N.current_selections) || "Current Selections";

    if (isSelected("CurrentSelections")) {
        option_cs.selected = true;
    }

    //if parent document is not available, stop script
    if (select) {
        select.appendChild(option_cs);
    }

    app.getAppObjectList('masterobject', function (reply) {

        $.each(reply.qAppObjectList.qItems, function(key, value) {
            var sheetId = value.qInfo.qId;
            var name = value.qData.name;
            var sheetDiv = document.createElement('option');
            sheetDiv.className = 'masterobject-option';
            sheetDiv.value = sheetId;
            sheetDiv.innerHTML = name+' ('+sheetId+')';

            if (isSelected(sheetId)) {
                sheetDiv.selected = true;
            }

            if (select) {
                select.appendChild(sheetDiv);
            }

            app.visualization.get(value.qInfo.qId).then(function(vis){
                vis.show(value.qInfo.qId);
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
    const authHeader = `Bearer ${qlik_token}`;
    const check = await checkLoggedIn();

    if (check.status === 401) {

        await fetch(`${host}/login/jwt-session`, {
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

    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;

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
            var text_danger = appdoc ? appdoc.getElementsByClassName('text-danger') : [];

            if (text_danger.length > 0) {
                text_danger[0].append(error.message);
            } else {
                console.error(error.message);
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
