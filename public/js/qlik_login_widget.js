(async function main() {

    const isLoggedIn = await jwtLogin();
})();

async function jwtLogin(token) {
    const authHeader = `Bearer ${token}`;
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