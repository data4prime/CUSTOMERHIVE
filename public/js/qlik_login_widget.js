(async function main() {

    const isLoggedIn = await jwtLogin();
    console.log(isLoggedIn);
})();

async function jwtLogin(token) {
    const authHeader = 'Bearer '+qlik_token ;
    console.log(authHeader);

    return await fetch(`https://data4primesaas.eu.qlikcloud.com/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj'
        },
    })
}