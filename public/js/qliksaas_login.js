

(async function main() {

    const isLoggedIn = await qlikLogin();

    const check = await checkLoggedIn();

    console.log(check.text());

    renderSingleIframe();
})();

async function qlikLogin() {
    const tokenRes = await (await getJWTToken());
    const loginRes = await jwtLogin(tokenRes);
    console.log(loginRes.text());

    return true;
}

async function checkLoggedIn() {
    return await fetch(`${TENANT}/api/v1/users/me`, {
        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': WEBINTEGRATIONID,
            'Authorization': 'Bearer ' + JWTTOKEN
        },
    })
}

//    Get the JWT and use it to obtain Qlik Cloud session cookie.

async function getJWTToken() {

    return "##JWTTOKEN##";
}

async function jwtLogin(token) {
    const authHeader = `Bearer ${JWTTOKEN}`;
    console.log(authHeader);
    console.log(WEBINTEGRATIONID);
    return await fetch(`${TENANT}/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': WEBINTEGRATIONID
        },
    })
}

async function getQCSHeaders() {

    const response = await fetch(`${TENANT}/api/v1/csrf-token`, {
        mode: 'cors',
        credentials: 'include',
        headers: {
            'qlik-web-integration-id': WEBINTEGRATIONID
        },
    })

    const csrfToken = new Map(response.headers).get('qlik-csrf-token');
    return {
        'qlik-web-integration-id': WEBINTEGRATIONID,
        'qlik-csrf-token': csrfToken,
    };
}


//    HELPER FUNCTION TO GENERATE IFRAME


function renderSingleIframe() {

    var iframe_ = document.querySelector('.qi_iframe');
    iframe_.src = iframe_.getAttribute('data-src');
    //var url = "{{ $item_url }}";
    //console.log(url);
    //document.querySelector('.qi_iframe').src = url;
    //document.getElementById('qlik_frame').src = url;

    //window.location.href = url;
}

