/* Editor del blog: formato, URL automática, contador, portada liviana y traducción automática */
(function () {
    'use strict';
    var $ = function (id) { return document.getElementById(id); };
    var area = $('aContenido');

    // ---- Barra de formato (Markdown) ----
    function envolver(a, b, ejemplo) {
        var s = area.selectionStart, e = area.selectionEnd, t = area.value, sel = t.slice(s, e) || ejemplo;
        area.value = t.slice(0, s) + a + sel + b + t.slice(e);
        area.focus(); area.setSelectionRange(s + a.length, s + a.length + sel.length);
    }
    function prefijo(p, ejemplo) {
        var s = area.selectionStart, e = area.selectionEnd, t = area.value;
        var ini = t.lastIndexOf('\n', s - 1) + 1, sel = t.slice(ini, e) || ejemplo;
        var nuevo = sel.split('\n').map(function (l, i) { return (p === '1. ' ? (i + 1) + '. ' : p) + l; }).join('\n');
        area.value = t.slice(0, ini) + nuevo + t.slice(e);
        area.focus();
    }
    var acciones = {
        b: function () { envolver('**', '**', 'texto'); },
        i: function () { envolver('*', '*', 'texto'); },
        h2: function () { prefijo('## ', 'Título de sección'); },
        h3: function () { prefijo('### ', 'Subtítulo'); },
        ul: function () { prefijo('- ', 'elemento'); },
        ol: function () { prefijo('1. ', 'elemento'); },
        q: function () { prefijo('> ', 'Aviso importante'); },
        a: function () {
            var url = window.prompt('Dirección del enlace (https://…)');
            if (url) { envolver('[', '](' + url + ')', 'texto del enlace'); }
        }
    };
    document.querySelectorAll('[data-md]').forEach(function (b) {
        b.addEventListener('click', function () { acciones[b.getAttribute('data-md')](); });
    });

    // ---- Contador de caracteres ----
    document.querySelectorAll('[data-count-for]').forEach(function (c) {
        var el = $(c.getAttribute('data-count-for'));
        var act = function () { c.textContent = el.value.length; };
        el.addEventListener('input', act); act();
    });

    // ---- URL automática desde el título (solo mientras no la hayas editado) ----
    var titulo = $('aTitulo'), slug = $('aSlug'), tocado = slug.value !== '';
    slug.addEventListener('input', function () { tocado = true; });
    titulo.addEventListener('input', function () {
        if (tocado) { return; }
        slug.value = titulo.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
    });

    // ---- Portada: se reduce en el navegador antes de subirla ----
    var inp = $('aPortada'), msg = $('aPortadaMsg');
    if (inp && window.MayoImg) {
        inp.addEventListener('change', function () {
            var f = inp.files[0];
            if (!f) { msg.textContent = ''; return; }
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { msg.textContent = 'Usa una imagen JPG, PNG o WebP.'; inp.value = ''; return; }
            msg.textContent = 'Reduciendo la imagen…';
            window.MayoImg.comprimir(f, { lado: 1600, kb: 320 }).then(function (nf) {
                var dt = new DataTransfer(); dt.items.add(nf); inp.files = dt.files;
                msg.textContent = 'Lista: de ' + Math.round(f.size / 1024) + ' KB a ' + Math.round(nf.size / 1024) + ' KB.';
            }).catch(function () { msg.textContent = 'No se pudo reducir aquí; se reducirá en el servidor.'; });
        });
    }

    // ---- Idioma base: solo se piden los idiomas que faltan ----
    var form = $('artForm');
    function baseLang() { var r = form.querySelector('[name=idioma_base]:checked'); return r ? r.value : 'es'; }
    function aplicarBase() {
        var b = baseLang();
        form.querySelectorAll('[data-lang-card]').forEach(function (c) {
            var off = c.dataset.langCard === b;
            c.classList.toggle('lang-off', off);
            c.querySelectorAll('input,textarea').forEach(function (i) { i.disabled = off; });
        });
    }
    form.addEventListener('change', function (e) { if (e.target.name === 'idioma_base') { aplicarBase(); } });
    aplicarBase();

    // ---- Traducción automática (usa el mismo servicio que los tours) ----
    var btn = $('btnTraducir');
    if (btn) {
        var out = $('trMsg');
        btn.addEventListener('click', function () {
            var source = baseLang();
            var base = {
                titulo: titulo.value, resumen: $('aResumen').value, contenido: area.value,
                meta_descripcion: (form.querySelector('[name="meta_descripcion"]') || {}).value || '',
                imagen_alt: (form.querySelector('[name="imagen_alt"]') || {}).value || ''
            };
            var campos = Object.keys(base).filter(function (k) { return base[k].trim() !== ''; });
            if (!campos.length) { out.textContent = 'Escribe primero el texto del artículo.'; return; }
            var hay = Array.prototype.some.call(form.querySelectorAll('[data-tr]:not(:disabled)'), function (el) { return el.value.trim() !== ''; });
            if (hay && !window.confirm('Ya hay traducciones escritas. ¿Reemplazarlas?')) { return; }

            btn.disabled = true; out.textContent = 'Traduciendo…';
            var items = campos.map(function (k) { return { id: k, text: base[k] }; });
            fetch(form.getAttribute('data-traducir'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ source: source, items: items })
            }).then(function (r) { return r.json(); }).then(function (d) {
                btn.disabled = false;
                var res = d.ok ? d.traducciones : null, n = 0;
                ['es', 'pt', 'en'].forEach(function (l) {
                    if (l === source) { return; }
                    campos.forEach(function (k) {
                        var v = res && res[k] && res[k][l];
                        var el = form.querySelector('[data-tr="' + l + ':' + k + '"]');
                        if (v && el) { el.value = v; n++; el.closest('details').open = true; }
                    });
                });
                out.textContent = n ? 'Listo. Revisa las traducciones antes de publicar.' : ((d && d.message) || 'No se pudo traducir ahora.');
            }).catch(function () { btn.disabled = false; out.textContent = 'No se pudo conectar con el traductor.'; });
        });
    }
})();
