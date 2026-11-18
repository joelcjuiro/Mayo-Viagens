/*
 * Reduce una foto en el navegador ANTES de subirla: así un pasajero puede elegir una foto de 12 MB
 * del celular y al servidor solo viajan unos cientos de KB.
 * Usa WebP; si el navegador no sabe crear WebP (Safari antiguo) usa JPG.
 *
 *   MayoImg.comprimir(file, { lado: 1600, kb: 300 })  ->  Promise<File>
 */
(function (w) {
    'use strict';

    function viaImg(file) {
        return new Promise(function (ok, fail) {
            var url = URL.createObjectURL(file), img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); fail(new Error('read')); };
            img.src = url;
        });
    }

    function leer(file) {
        if (w.createImageBitmap) {
            return w.createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return viaImg(file); });
        }
        return viaImg(file);
    }

    function aBlob(canvas, tipo, calidad) {
        return new Promise(function (ok) { canvas.toBlob(ok, tipo, calidad); });
    }

    function comprimir(file, opt) {
        opt = opt || {};
        var lado = opt.lado || 1600;
        var limite = (opt.kb || 300) * 1024;
        var base = file.name.replace(/\.[^.]+$/, '') || 'foto';

        return leer(file).then(function (img) {
            var ancho = img.naturalWidth || img.width;
            var alto = img.naturalHeight || img.height;
            var escala = Math.min(1, lado / Math.max(ancho, alto));
            var calidad = 0.82, intentos = 0, tipo = 'image/webp';

            function paso() {
                var c = document.createElement('canvas');
                c.width = Math.max(1, Math.round(ancho * escala));
                c.height = Math.max(1, Math.round(alto * escala));
                var x = c.getContext('2d');
                x.fillStyle = '#fff';
                x.fillRect(0, 0, c.width, c.height);
                x.drawImage(img, 0, 0, c.width, c.height);

                return aBlob(c, tipo, calidad).then(function (b) {
                    if (!b) { throw new Error('blob'); }
                    // Si el navegador no soporta WebP devuelve PNG (pesado): cambiamos a JPG
                    if (tipo === 'image/webp' && b.type !== 'image/webp') { tipo = 'image/jpeg'; return paso(); }
                    if (b.size <= limite || ++intentos > 12) {
                        if (img.close) { img.close(); }
                        return new File([b], base + (b.type === 'image/webp' ? '.webp' : '.jpg'), { type: b.type });
                    }
                    if (calidad > 0.5) { calidad -= 0.08; } else { escala *= 0.85; }
                    return paso();
                });
            }
            return paso();
        });
    }

    w.MayoImg = { comprimir: comprimir };
})(window);
