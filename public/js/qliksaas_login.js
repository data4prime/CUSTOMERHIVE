
// Timeout (ms) per le chiamate di login verso Qlik
const QLIK_FETCH_TIMEOUT = 15000;

(async function main() {

    let ok = false;
    try {
        ok = await qlikLogin();
    } catch (e) {
        ok = false;
    }

    if (!ok) {
        showQlikLoginError();
    }

    // L'iframe viene comunque caricato: se il login non e' riuscito sara'
    // Qlik stesso a chiedere le credenziali.
    renderSingleIframe();
})();

async function qlikLogin() {
    const loginRes = await jwtLogin();
    return loginRes.ok;
}

// fetch con timeout, cosi' un Qlik non raggiungibile non blocca la pagina
async function fetchWithTimeout(url, options) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), QLIK_FETCH_TIMEOUT);
    try {
        return await fetch(url, Object.assign({}, options, { signal: controller.signal }));
    } finally {
        clearTimeout(timer);
    }
}

// Ottiene il cookie di sessione Qlik Cloud a partire dal JWT.
async function jwtLogin() {
    return await fetchWithTimeout(`${TENANT}/login/jwt-session`, {
        credentials: 'include',
        mode: 'cors',
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${JWTTOKEN}`,
            'qlik-web-integration-id': WEBINTEGRATIONID
        },
    });
}

function showQlikLoginError() {
    const iframe_ = document.querySelector('.qi_iframe');
    if (!iframe_ || typeof QLIK_I18N === 'undefined' || !QLIK_I18N.login_failed) {
        return;
    }
    const msg = document.createElement('div');
    msg.className = 'text-danger qi_login_error';
    msg.setAttribute('role', 'alert');
    msg.textContent = QLIK_I18N.login_failed;
    iframe_.parentNode.insertBefore(msg, iframe_);
}

//    HELPER FUNCTION TO GENERATE IFRAME

function renderSingleIframe() {

    var iframe_ = document.querySelector('.qi_iframe');
    iframe_.src = iframe_.getAttribute('data-src');
}
