

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
    //console.log('objectsOptions');
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
    const authHeader = `Bearer ${qlik_token}`;

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


    await loadScript(`${host}/${prefix}/${src_js}`);

    var host_q = '';
    if (host.includes("https://") || host.includes("http://")) {
        host_q = host.split("//")[1];
    }

    var config = {
        host: host_q, 
        prefix: `/${prefix}/`, 
        port: 443, 
        isSecure: true, 
    };


    const baseUrl = (config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;


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


        qlik.getAppList(function(list){
		var str = "";
		list.forEach(function(value) {
			str +=  value.qDocName + "("+ value.qDocId +") ";
		});
		//console.log(str);
	}, config);


        //console.log('appId');
        //console.log(appId);

        //console.log('config');
        //console.log(config);


        var app = qlik.openApp(appId, config);
        //console.log('app');
        //console.log(app);


        objectsOptions(app);

        var title = document.getElementById('title');
        title.innerHTML = "";

        var mashup_object = parent.document.getElementById('mashup_object');
        mashup_object.removeAttribute('disabled');



    });

}

mainOP();
