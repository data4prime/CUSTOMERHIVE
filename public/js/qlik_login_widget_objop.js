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
    document.head.appendChild(script);
    return response;
})
.catch(error => {
    console.error('Error:', error);
});














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

