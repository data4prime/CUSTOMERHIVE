(async function main() {

    const isLoggedIn = await jwtLogin();
})();

async function jwtLogin(token) {
    const authHeader = `Bearer ${token}`;
    console.log(authHeader);

    return await fetch(`${TENANT}/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': authHeader,
            'qlik-web-integration-id': '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj'
        },
    })
}