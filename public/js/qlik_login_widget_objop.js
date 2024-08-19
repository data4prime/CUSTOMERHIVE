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

        var script = document.createElement('script');
    script.src = baseUrl + 'jwt/resources/assets/external/requirejs/require.js';
    script.type = 'text/javascript';
    document.head.appendChild(script);

    console.log('response: '+response);

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


    mainOP();
console.log("qlik_login_widget_objop");

