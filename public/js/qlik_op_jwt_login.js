
//    CONFIGURATION

/*
const TENANT = '<?php echo $tenant ?>';
console.log(TENANT);
const WEBINTEGRATIONID = '{{ $web_int_id }}';
const APPID = '##APP##';
const JWTTOKEN = "{{ $token}}";*/

//    MAIN

(async function main() {

    const isLoggedIn = await qlikLogin();
    //console.log("print isLoggedIn");
    //console.log(isLoggedIn);
    //console.log("CHECK");
    const check = await checkLoggedIn();


    console.log(check.text());

    //const qcsHeaders = await getQCSHeaders();

    renderSingleIframe();
})();

//    LOGIN

async function qlikLogin() {
    const tokenRes = await (await getJWTToken());
    //console.log("Login Res");
    const loginRes = await jwtLogin(tokenRes);
    console.log(loginRes.text());

    return true;
}

async function checkLoggedIn() {
    //console.log("JWTTOKEN");
    //console.log(JWTTOKEN);
    return await fetch(`${TENANT}/api/v1/users/me`, {
        //redirect: 'follow'
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
    //console.log(WEBINTEGRATIONID);
    return await fetch(`${TENANT}/qrs/about?xrfkey=0123456789abcdef`, {
        credentials: 'include',
        mode: 'no-cors',
        method: 'GET',
        headers: {
            'Authorization': authHeader,
            //'qlik-web-integration-id': WEBINTEGRATIONID
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
    //console.log(response);

    const csrfToken = new Map(response.headers).get('qlik-csrf-token');
    //console.log(csrfToken);
    return {
        'qlik-web-integration-id': WEBINTEGRATIONID,
        'qlik-csrf-token': csrfToken,
    };
}


//    HELPER FUNCTION TO GENERATE IFRAME

function renderSingleIframe() {
    //var url = "{{ $item_url }}";
    //console.log(url);
    //document.querySelector('.qi_iframe').src = url;
    //document.getElementById('qlik_frame').src = url;

    //window.location.href = url;
}

