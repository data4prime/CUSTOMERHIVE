{{-- Con il flag wizard_v2 una richiesta con header X-Wizard-Partial riceve solo il
     contenuto del passo (partial.blade.php) invece dell'intera pagina admin. --}}
@extends(config('module_generator.wizard_v2') && request()->header('X-Wizard-Partial') === '1' ? 'crudbooster::module_generator.partial' : 'crudbooster::admin_template')

@push('head')
<link rel='stylesheet' href='<?php echo asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css")?>'/>
    {{-- Aspetto di select2: public/css/ch-components.css (token --ch-*) --}}
@endpush

@push('bottom')
<script src='<?php echo asset("vendor/crudbooster/assets/select2/dist/js/select2.full.min.js")?>'></script>
<script>
$(function () {
  $('.select2').select2();
})
</script>
@if(config('module_generator.wizard_v2'))
<script>
// Navigazione tra i passi senza ricaricare la pagina: link dei passi e pulsanti
// "Indietro" (GET) e invio dei form (POST) vanno via fetch con l'header
// X-Wizard-Partial; della risposta si sostituisce #mg-wizard e si rieseguono
// gli script del passo. Per qualunque risposta che non sia un passo (lista moduli
// dopo il salvataggio, errori) si torna alla navigazione normale.
// Le modifiche non salvate non si perdono: cambiando passo, uscendo dalla pagina
// (anche chiudendola) o dopo qualche secondo di inattivita' si salva una bozza in
// sessione (postDraft), ripristinata al ritorno e cancellata col salvataggio vero.
(function () {
    if (window.__mgNav) { return; }
    window.__mgNav = true;
    var busy = false;
    var cur = null;
    var timer = null;

    /* ---------- bozza ---------- */
    // Stato del form del passo: i passi con editor (campi, lista, layout) preparano il
    // loro "payload" nel gestore di submit, che qui si fa girare con un evento finto
    // (mgDraft) che l'invio via fetch ignora.
    function snapshot(form) {
        var ev = new Event('submit', {bubbles: true, cancelable: true});
        ev.mgDraft = true;
        form.dispatchEvent(ev);
        var o = {};
        new FormData(form).forEach(function (v, k) {
            if (k === '_token' || typeof v !== 'string') { return; }
            (o[k] = o[k] || []).push(v);
        });
        return o;
    }

    // Passi senza editor (informazioni, configurazione): si rimettono i valori nei campi.
    function restoreFields(form, fields) {
        var names = {};
        Array.prototype.forEach.call(form.elements, function (el) {
            if (el.name && el.name !== '_token' && el.name !== 'id' && el.type !== 'submit') { names[el.name] = true; }
        });
        Object.keys(names).forEach(function (name) {
            var vals = fields[name] === undefined ? [] : [].concat(fields[name]);
            Array.prototype.forEach.call(form.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]'), function (el) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = vals.indexOf(el.value) >= 0;
                } else if (el.multiple) {
                    Array.prototype.forEach.call(el.options, function (o) { o.selected = vals.indexOf(o.value) >= 0; });
                } else if (vals.length) {
                    el.value = vals[0];
                } else {
                    return;
                }
                if (window.jQuery) { jQuery(el).trigger('change'); }
            });
        });
    }

    function formData(data) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        var tok = cur && cur.form.querySelector('[name="_token"]');
        if (tok) { fd.append('_token', tok.value); }
        return fd;
    }

    function sendDraft(beacon) {
        if (!cur || !cur.ready) { return Promise.resolve(); }
        var fields = JSON.stringify(snapshot(cur.form));
        if (cur.baseline === fields) {
            // tornati allo stato salvato: una bozza gia' inviata non ha piu' senso
            if (cur.lastSent && cur.lastSent !== fields) {
                cur.lastSent = null;
                var d = formData({step: cur.step});
                if (beacon && navigator.sendBeacon) { navigator.sendBeacon(cur.discardUrl + '/' + cur.id, d); return Promise.resolve(); }
                return fetch(cur.discardUrl + '/' + cur.id, {method: 'POST', body: d, credentials: 'same-origin', keepalive: true}).catch(function () {});
            }
            return Promise.resolve();
        }
        if (cur.lastSent === fields) { return Promise.resolve(); }
        cur.lastSent = fields;
        var fd = formData({step: cur.step, fields: fields});
        if (beacon && navigator.sendBeacon) { navigator.sendBeacon(cur.url + '/' + cur.id, fd); return Promise.resolve(); }
        return fetch(cur.url + '/' + cur.id, {method: 'POST', body: fd, credentials: 'same-origin', keepalive: true}).catch(function () {});
    }

    function init() {
        cur = null;
        var root = document.getElementById('mg-wizard');
        var form = root ? root.querySelector('form') : null;
        if (!root || !form || !root.getAttribute('data-mg-draft-url')) { return; }
        var c = cur = {form: form, step: root.getAttribute('data-mg-step'), id: root.getAttribute('data-mg-id'),
            url: root.getAttribute('data-mg-draft-url'), discardUrl: root.getAttribute('data-mg-discard-url'),
            ready: false, baseline: null, lastSent: null};
        // dopo che gli script del passo hanno finito di disegnare (alcuni usano setTimeout)
        setTimeout(function () {
            if (cur !== c) { return; }
            var d = root.getAttribute('data-mg-draft');
            if (d && !form.querySelector('[name="payload"]')) {
                try { restoreFields(form, JSON.parse(d)); } catch (err) {}
            }
            // pagina ripristinata (bozza o ritorno da un errore): non c'e' uno stato salvato da confrontare
            c.baseline = root.getAttribute('data-mg-restored') === '1' ? null : JSON.stringify(snapshot(form));
            c.ready = true;
        }, 150);
    }

    /* ---------- caricamento dei passi ---------- */
    function loadScripts(list) {
        return list.reduce(function (p, s) {
            return p.then(function () {
                var src = s.getAttribute('src');
                if (src) {
                    var abs = new URL(src, location.href).href;
                    var present = Array.prototype.some.call(document.scripts, function (x) { return x.src === abs; });
                    if (present) { return; }
                    return new Promise(function (resolve) {
                        var n = document.createElement('script');
                        n.async = false;
                        n.onload = n.onerror = resolve;
                        n.src = abs;
                        document.body.appendChild(n);
                    });
                }
                var n = document.createElement('script');
                n.textContent = s.textContent;
                document.body.appendChild(n);
                document.body.removeChild(n);
            });
        }, Promise.resolve());
    }

    function swap(doc, neu) {
        var seenStyles = {};
        Array.prototype.forEach.call(document.querySelectorAll('style'), function (s) { seenStyles[s.textContent.trim()] = true; });
        Array.prototype.forEach.call(doc.querySelectorAll('link[rel="stylesheet"], style'), function (el) {
            if (el.tagName === 'LINK') {
                var abs = new URL(el.getAttribute('href'), location.href).href;
                var present = Array.prototype.some.call(document.querySelectorAll('link[rel="stylesheet"]'), function (l) { return l.href === abs; });
                if (!present) { document.head.appendChild(document.importNode(el, true)); }
            } else if (!seenStyles[el.textContent.trim()]) {
                seenStyles[el.textContent.trim()] = true;
                document.head.appendChild(document.importNode(el, true));
            }
        });

        var scripts = Array.prototype.slice.call(doc.querySelectorAll('script'));
        Array.prototype.forEach.call(neu.querySelectorAll('script'), function (s) { s.parentNode.removeChild(s); });
        var old = document.getElementById('mg-wizard');
        old.parentNode.replaceChild(document.importNode(neu, true), old);

        var toasts = doc.querySelector('.ch-toast-container');
        if (toasts) {
            Array.prototype.forEach.call(document.querySelectorAll('.ch-toast-container'), function (c) { c.parentNode.removeChild(c); });
            var t = document.importNode(toasts, true);
            document.body.appendChild(t);
            Array.prototype.forEach.call(t.querySelectorAll('.ch-toast'), function (el) { new bootstrap.Toast(el).show(); });
        }
        return loadScripts(scripts);
    }

    // skipDraft: si arriva da un salvataggio o da uno scarto, non si scrive nessuna bozza.
    function go(url, body, fallback, skipDraft) {
        if (busy) { return; }
        busy = true;
        var root = document.getElementById('mg-wizard');
        root.classList.add('mg-loading');
        var first = (body || skipDraft) ? Promise.resolve() : sendDraft(false);
        if (body && cur) { cur.ready = false; }
        first
            .then(function () {
                return fetch(url, {method: body ? 'POST' : 'GET', body: body || undefined, credentials: 'same-origin', headers: {'X-Wizard-Partial': '1'}});
            })
            .then(function (res) {
                return res.text().then(function (text) { return {res: res, text: text}; });
            })
            .then(function (o) {
                var doc = new DOMParser().parseFromString(o.text, 'text/html');
                var neu = doc.getElementById('mg-wizard');
                if (!neu) {
                    cur = null;
                    if (o.res.ok) { location.href = o.res.url; return; }
                    document.open(); document.write(o.text); document.close();
                    return;
                }
                return swap(doc, neu).then(function () {
                    if (location.href !== o.res.url) { history.pushState({mg: 1}, '', o.res.url); }
                    window.scrollTo(0, 0);
                    init();
                });
            })
            .catch(function () { fallback(); })
            .then(function () {
                busy = false;
                var r = document.getElementById('mg-wizard');
                if (r) { r.classList.remove('mg-loading'); }
            });
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
        var x = e.target.closest ? e.target.closest('[data-mg-discard]') : null;
        if (x && cur) {
            e.preventDefault();
            var c = cur;
            c.ready = false;
            fetch(c.discardUrl + '/' + c.id, {method: 'POST', body: formData({step: c.step}), credentials: 'same-origin'})
                .catch(function () {})
                .then(function () { go(location.href, null, function () { location.reload(); }, true); });
            return;
        }
        var a = e.target.closest ? e.target.closest('.mg-stepper a.mg-st, a.mg-nav') : null;
        var h = a ? a.getAttribute('href') : null;
        if (!h || h === '#' || !a.closest('#mg-wizard')) { return; }
        e.preventDefault();
        go(a.href, null, function () { location.href = a.href; });
    });

    document.addEventListener('submit', function (e) {
        if (e.mgDraft) { return; }
        var f = e.target;
        if (e.defaultPrevented || !f.closest || !f.closest('#mg-wizard') || (f.getAttribute('method') || 'get').toLowerCase() !== 'post') { return; }
        e.preventDefault();
        var fd = new FormData(f);
        if (e.submitter && e.submitter.name) { fd.append(e.submitter.name, e.submitter.value); }
        go(f.action, fd, function () { f.submit(); });
    });

    window.addEventListener('popstate', function () {
        if (document.getElementById('mg-wizard')) { go(location.href, null, function () { location.reload(); }); }
    });

    // bozza automatica: dopo qualche secondo senza interazioni, e all'uscita dalla pagina
    ['input', 'change', 'click'].forEach(function (name) {
        document.addEventListener(name, function (e) {
            if (!cur || !cur.ready || !e.target.closest || !e.target.closest('#mg-wizard')) { return; }
            clearTimeout(timer);
            timer = setTimeout(function () { sendDraft(false); }, 2000);
        }, true);
    });
    window.addEventListener('pagehide', function () { sendDraft(true); });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') { sendDraft(true); }
    });

    init();
})();
</script>
@endif
@endpush

@section("content")
@php
    $mgStep = (int) ($active_tab ?? 0);
    $mgId = (int) ($id ?? 0);
    $mgDraftOn = config('module_generator.wizard_v2') && $mgStep >= 1 && $mgStep <= 5;
    $mgDraft = $mgDraftOn ? session('mg_draft.'.$mgId.'.'.$mgStep) : null;
    $mgDraft = is_array($mgDraft) ? $mgDraft : null;
@endphp
<div id="mg-wizard"
     @if($mgDraftOn)
     data-mg-step="{{ $mgStep }}" data-mg-id="{{ $mgId }}"
     data-mg-draft-url="{{ CRUDBooster::mainpath('draft') }}" data-mg-discard-url="{{ CRUDBooster::mainpath('draft-discard') }}"
     data-mg-restored="{{ ($mgDraft !== null || session()->hasOldInput()) ? 1 : 0 }}"
     @if($mgDraft !== null) data-mg-draft="{{ json_encode($mgDraft) }}" @endif
     @endif>
  @include('crudbooster::module_generator.navigation')
  @if($mgDraft !== null)
  <div class="alert alert-warning py-2 small d-flex justify-content-between align-items-center" id="mg-draft-banner">
    <span><i class="bi bi-info-circle-fill"></i> {{ trans('crudbooster.mg_draft_notice') }}</span>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-mg-discard>{{ trans('crudbooster.mg_draft_discard') }}</button>
  </div>
  @endif
  @yield('inner_content')
</div>
@endsection
