
(async function main() {

    const isLoggedIn = await qlikLogin();

    renderSingleIframe();


})();

//    LOGIN

async function qlikLogin() {
    const tokenRes = await (await getJWTToken());
    const loginRes = await jwtLogin(tokenRes);


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
    return await fetch(`${TENANT}/${PREFIX}/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'Authorization': authHeader,
            'X-Qlik-Xrfkey': '0123456789abcdef',
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
    console.log(iframe_);

    iframe_.src = iframe_.getAttribute('data-src');

/*
    var iframe_ = document.querySelector('.qi_iframe');
    iframe_.src = iframe_.getAttribute('data-src');
*/

}

