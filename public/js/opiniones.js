/* Formulario de opiniones: vista previa de fotos y reducción automática (cualquier peso -> unos cientos de KB) */
(function () {
    'use strict';
    var input = document.getElementById('fotos');
    if (!input || !window.MayoImg) { return; }
    var prev = document.getElementById('fotosPrev');
    var msg = document.getElementById('fotosMsg');
    var form = input.form;
    var enviar = form ? form.querySelector('[type="submit"]') : null;
    var MAX = parseInt(input.dataset.max, 10) || 3;
    var MAX_KB = parseInt(input.dataset.maxKb, 10) || 300;
    var LIMITE_ORIGINAL = 40 * 1024 * 1024; // más que esto ni lo intentamos leer en el navegador
    var TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
    var files = [];

    function t(clave, datos) {
        var s = input.getAttribute('data-msg-' + clave) || '';
        Object.keys(datos || {}).forEach(function (k) { s = s.replace(':' + k, datos[k]); });
        return s;
    }
    function kb(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB'; }
    function say(texto, mal) { msg.textContent = texto || ''; msg.className = 'up-msg' + (mal ? ' err' : ' ok'); }

    function sync() {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f.file); });
        input.files = dt.files;
        prev.innerHTML = '';
        files.forEach(function (f, i) {
            var d = document.createElement('div'); d.className = 'op-thumb';
            var im = document.createElement('img'); im.src = f.url; im.alt = ''; im.width = 96; im.height = 72;
            var b = document.createElement('button'); b.type = 'button'; b.textContent = '×'; b.setAttribute('aria-label', t('remove'));
            b.addEventListener('click', function () { URL.revokeObjectURL(f.url); files.splice(i, 1); say(''); sync(); });
            var k = document.createElement('span'); k.textContent = kb(f.file.size);
            d.appendChild(im); d.appendChild(b); d.appendChild(k); prev.appendChild(d);
        });
    }

    input.addEventListener('change', function () {
        var nuevos = Array.prototype.slice.call(input.files), avisos = [], mal = false, cadena = Promise.resolve();
        if (!nuevos.length) { return; }
        say(t('working'));
        if (enviar) { enviar.disabled = true; }

        nuevos.forEach(function (f) {
            cadena = cadena.then(function () {
                if (files.length >= MAX) { mal = true; avisos.push(t('max', { n: MAX })); return; }
                if (TIPOS.indexOf(f.type) === -1) { mal = true; avisos.push(t('type', { name: f.name })); return; }
                if (f.size > LIMITE_ORIGINAL) { mal = true; avisos.push(t('big')); return; }

                // Si ya es liviana y no necesita reducirse, se deja tal cual (el servidor igual la optimiza)
                var listo = f.size <= MAX_KB * 1024 ? Promise.resolve(f) : window.MayoImg.comprimir(f, { lado: 1600, kb: MAX_KB });
                return listo.then(function (fin) {
                    if (fin !== f) { avisos.push(t('reduced', { name: f.name, from: kb(f.size), to: kb(fin.size) })); }
                    files.push({ file: fin, url: URL.createObjectURL(fin) });
                }).catch(function () { mal = true; avisos.push(t('fail', { name: f.name })); });
            });
        });

        cadena.then(function () {
            say(avisos.join(' '), mal);
            sync();
            if (enviar) { enviar.disabled = false; }
        });
    });
})();
