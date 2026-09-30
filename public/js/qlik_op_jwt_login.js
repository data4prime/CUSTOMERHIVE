
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

//    LOGIN

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

// xrfkey casuale a ogni richiesta (16 caratteri alfanumerici, come richiesto da QRS)
function qlikXrfKey() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    return Array.from(bytes, b => chars[b % chars.length]).join('');
}

async function jwtLogin() {
    const xrfkey = qlikXrfKey();
    return await fetchWithTimeout(`${TENANT}/${PREFIX}/qrs/about?xrfkey=${xrfkey}`, {
        credentials: 'include',
        mode: 'cors',
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${JWTTOKEN}`,
            'X-Qlik-Xrfkey': xrfkey,
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
