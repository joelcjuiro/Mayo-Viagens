/* Formulario de opiniones: vista previa de fotos y compresión automática a 300 KB */
(function () {
    'use strict';
    var input = document.getElementById('fotos');
    if (!input) { return; }
    var prev = document.getElementById('fotosPrev');
    var msg = document.getElementById('fotosMsg');
    var MAX = parseInt(input.dataset.max, 10) || 3;
    var MAX_KB = parseInt(input.dataset.maxKb, 10) || 300;
    var TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
    var files = [];

    function kb(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB'; }
    function say(t, bad) { msg.textContent = t || ''; msg.className = 'up-msg' + (bad ? ' err' : ' ok'); }

    function leer(file) {
        return new Promise(function (ok, fail) {
            var url = URL.createObjectURL(file), img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); fail(); };
            img.src = url;
        });
    }
    function comprimir(file) {
        var limite = (MAX_KB - 6) * 1024;
        return leer(file).then(function (img) {
            var escala = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight)), q = 0.85, n = 0;
            function paso() {
                var c = document.createElement('canvas');
                c.width = Math.max(1, Math.round(img.naturalWidth * escala));
                c.height = Math.max(1, Math.round(img.naturalHeight * escala));
                var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
                return new Promise(function (r) { c.toBlob(r, 'image/jpeg', q); }).then(function (b) {
                    if (b && b.size <= limite) { return new File([b], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }); }
                    if (++n > 12) { throw new Error('x'); }
                    if (q > 0.5) { q -= 0.1; } else { escala *= 0.85; }
                    return paso();
                });
            }
            return paso();
        });
    }

    function sync() {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f.file); });
        input.files = dt.files;
        prev.innerHTML = '';
        files.forEach(function (f, i) {
            var d = document.createElement('div'); d.className = 'op-thumb';
            var im = document.createElement('img'); im.src = f.url; im.alt = '';
            var b = document.createElement('button'); b.type = 'button'; b.textContent = '×'; b.setAttribute('aria-label', 'Quitar');
            b.addEventListener('click', function () { URL.revokeObjectURL(f.url); files.splice(i, 1); say(''); sync(); });
            var k = document.createElement('span'); k.textContent = kb(f.file.size);
            d.appendChild(im); d.appendChild(b); d.appendChild(k); prev.appendChild(d);
        });
    }

    input.addEventListener('change', function () {
        var nuevos = Array.prototype.slice.call(input.files), avisos = [], mal = false, cadena = Promise.resolve();
        say('…');
        nuevos.forEach(function (f) {
            cadena = cadena.then(function () {
                if (files.length >= MAX) { mal = true; avisos.push('Máximo ' + MAX + ' fotos.'); return; }
                if (TIPOS.indexOf(f.type) === -1) { mal = true; avisos.push('«' + f.name + '» no es JPG, PNG ni WebP.'); return; }
                var listo = f.size <= MAX_KB * 1024 ? Promise.resolve(f) : comprimir(f);
                return listo.then(function (fin) {
                    if (fin !== f) { avisos.push('«' + f.name + '» se redujo de ' + kb(f.size) + ' a ' + kb(fin.size) + '.'); }
                    files.push({ file: fin, url: URL.createObjectURL(fin) });
                }).catch(function () { mal = true; avisos.push('No se pudo procesar «' + f.name + '».'); });
            });
        });
        cadena.then(function () { say(avisos.join(' '), mal); sync(); });
    });
})();
