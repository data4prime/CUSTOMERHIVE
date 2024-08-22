

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


    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;


    require.config({
		baseUrl: baseUrl + 'resources',
        paths : {
            qlik: 'js/qlik.js?qlikTicket='+qlik_ticket3+'&v=' + (new Date()).getTime()
		}
	});

    

    require(["qlik"], function (qlik) {
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

        var title = document.getElementById('title');
        title.innerHTML = "";

        var mashup_object = parent.document.getElementById('mashup_object');
        mashup_object.removeAttribute('disabled');

    });

}

mainOP();

