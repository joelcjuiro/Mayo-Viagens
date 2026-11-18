/* Mayo Viagens · extras: lightbox de fotos, compartir, consentimiento de cookies y Google Analytics */
(function () {
    'use strict';

    /* ---------- Lightbox: la foto grande se descarga solo cuando se abre ---------- */
    var box, imgEl, cierre, prevBtn, nextBtn, actuales = [], pos = 0, retorno = null;

    function crear() {
        box = document.createElement('div');
        box.className = 'lb';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.hidden = true;
        box.innerHTML = '<button type="button" class="lb-x" aria-label="Cerrar">×</button>' +
            '<button type="button" class="lb-nav lb-prev" aria-label="Anterior">‹</button>' +
            '<figure><img alt="" decoding="async"><span class="lb-load" aria-hidden="true"></span></figure>' +
            '<button type="button" class="lb-nav lb-next" aria-label="Siguiente">›</button>';
        document.body.appendChild(box);
        imgEl = box.querySelector('img');
        cierre = box.querySelector('.lb-x');
        prevBtn = box.querySelector('.lb-prev');
        nextBtn = box.querySelector('.lb-next');
        cierre.addEventListener('click', cerrar);
        prevBtn.addEventListener('click', function () { ir(-1); });
        nextBtn.addEventListener('click', function () { ir(1); });
        box.addEventListener('click', function (e) { if (e.target === box || e.target.tagName === 'FIGURE') { cerrar(); } });
        imgEl.addEventListener('load', function () { box.classList.remove('loading'); });
        imgEl.addEventListener('error', function () { box.classList.remove('loading'); });
        document.addEventListener('keydown', function (e) {
            if (box.hidden) { return; }
            if (e.key === 'Escape') { cerrar(); }
            if (e.key === 'ArrowRight') { ir(1); }
            if (e.key === 'ArrowLeft') { ir(-1); }
        });
    }

    function mostrar() {
        box.classList.add('loading');
        imgEl.src = actuales[pos].href;
        var varias = actuales.length > 1;
        prevBtn.hidden = nextBtn.hidden = !varias;
    }
    function ir(d) { pos = (pos + d + actuales.length) % actuales.length; mostrar(); }
    function abrir(lista, i, desde) {
        if (!box) { crear(); }
        actuales = lista; pos = i; retorno = desde;
        box.hidden = false;
        document.documentElement.classList.add('lb-open');
        mostrar();
        cierre.focus();
    }
    function cerrar() {
        box.hidden = true;
        imgEl.removeAttribute('src');
        document.documentElement.classList.remove('lb-open');
        if (retorno && retorno.focus) { retorno.focus(); }
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[data-lightbox]') : null;
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) { return; }
        e.preventDefault();
        var grupo = a.getAttribute('data-lightbox');
        var lista = Array.prototype.slice.call(document.querySelectorAll('a[data-lightbox="' + grupo + '"]'));
        abrir(lista, Math.max(0, lista.indexOf(a)), a);
    });

    /* ---------- Compartir y copiar enlace ---------- */
    document.querySelectorAll('[data-share]').forEach(function (b) {
        if (!navigator.share) { b.hidden = true; return; }
        b.addEventListener('click', function () {
            navigator.share({ title: document.title, url: location.href }).catch(function () {});
        });
    });
    document.querySelectorAll('[data-copy]').forEach(function (b) {
        b.addEventListener('click', function () {
            var url = b.getAttribute('data-copy') || location.href;
            var ok = function () {
                var antes = b.textContent;
                b.textContent = b.getAttribute('data-copied') || '✓';
                setTimeout(function () { b.textContent = antes; }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(ok, function () {});
            } else {
                var t = document.createElement('textarea'); t.value = url; document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); ok(); } catch (err) {}
                document.body.removeChild(t);
            }
        });
    });

    /* ---------- Cookies y Google Analytics (solo se carga si el visitante acepta) ---------- */
    var CLAVE = 'mayo.consent';
    function leerConsent() { try { return localStorage.getItem(CLAVE); } catch (e) { return null; } }
    function guardarConsent(v) { try { localStorage.setItem(CLAVE, v); } catch (e) {} }

    function cargarGA() {
        var id = window.MAYO_GA;
        if (!id || window.__mayoGA) { return; }
        window.__mayoGA = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'granted' });
        window.gtag('js', new Date());
        window.gtag('config', id);
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        document.head.appendChild(s);
    }
    function cuandoLibre(fn) {
        if ('requestIdleCallback' in window) { window.requestIdleCallback(fn, { timeout: 4000 }); } else { setTimeout(fn, 2500); }
    }

    var banner = document.querySelector('[data-consent]');
    if (banner && window.MAYO_GA) {
        var previo = leerConsent();
        if (previo === 'granted') { cuandoLibre(cargarGA); }
        else if (previo !== 'denied') { banner.hidden = false; }

        banner.querySelector('[data-consent-accept]').addEventListener('click', function () {
            guardarConsent('granted'); banner.hidden = true; cargarGA();
        });
        banner.querySelector('[data-consent-reject]').addEventListener('click', function () {
            guardarConsent('denied'); banner.hidden = true;
        });
        document.querySelectorAll('[data-consent-open]').forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); banner.hidden = false; });
        });
    } else {
        document.querySelectorAll('[data-consent-open]').forEach(function (b) { b.hidden = true; });
    }

    /* ---------- Eventos que importan al negocio (solo si hay consentimiento y Analytics cargado) ---------- */
    function evento(nombre, datos) { if (window.gtag) { window.gtag('event', nombre, datos || {}); } }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[href*="wa.me"], a[href*="api.whatsapp.com"]') : null;
        if (a) { evento('whatsapp_click', { page_path: location.pathname }); }
    });
    document.addEventListener('submit', function (e) {
        var f = e.target, ruta = '';
        try { ruta = new URL(f.action, location.href).pathname; } catch (err) { return; }
        if (ruta.indexOf('/reservas') === 0) { evento('generate_lead', { form: 'reserva' }); }
        else if (ruta.indexOf('/contacto') === 0) { evento('generate_lead', { form: 'contacto' }); }
        else if (ruta.indexOf('/opiniones') === 0) { evento('review_submit'); }
    }, true);
})();
