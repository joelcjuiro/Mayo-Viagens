/* Editor de tours: paneles, imágenes (máx. 300 KB), filas repetibles, URL y video */
(function () {
    'use strict';
    var form = document.getElementById('tourForm');
    if (!form) { return; }
    var MAX_KB = parseInt(form.dataset.maxKb, 10) || 300;
    var TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    function $(s, r) { return (r || document).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
    function fmtKb(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB'; }

    /* ---------------- Paneles (pestañas) ---------------- */
    var tabs = $$('[data-tab]');
    var panes = $$('[data-pane]');
    var panelActual = $('#panelActual');
    function openPanel(name) {
        if (!panes.some(function (p) { return p.dataset.pane === name; })) { name = 'detalle'; }
        tabs.forEach(function (t) { t.setAttribute('aria-selected', t.dataset.tab === name ? 'true' : 'false'); });
        panes.forEach(function (p) { p.hidden = p.dataset.pane !== name; });
        panelActual.value = name;
        try { history.replaceState(null, '', '#' + name); } catch (e) {}
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { openPanel(t.dataset.tab); window.scrollTo({ top: 0, behavior: 'smooth' }); }); });
    var errores = [];
    try { errores = JSON.parse(form.dataset.errors || '[]'); } catch (e) {}
    errores.forEach(function (k) { var d = $('[data-dot="' + k + '"]'); if (d) { d.className = 'dot-s err'; } });
    var hash = (location.hash || '').replace('#', '');
    openPanel(errores[0] || (panes.some(function (p) { return p.dataset.pane === hash; }) ? hash : form.dataset.initialPanel) || 'detalle');

    /* ---------------- Subida de imágenes ---------------- */
    function loadImage(file) {
        return new Promise(function (ok, fail) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); fail(new Error('No se pudo leer la imagen')); };
            img.src = url;
        });
    }

    // Reduce una imagen pesada hasta que quepa en el límite (JPG, hasta 1800 px de lado).
    function comprimir(file, limite) {
        return loadImage(file).then(function (img) {
            var escala = Math.min(1, 1800 / Math.max(img.naturalWidth, img.naturalHeight));
            var calidad = 0.86;
            var intento = 0;
            function paso() {
                var w = Math.max(1, Math.round(img.naturalWidth * escala));
                var h = Math.max(1, Math.round(img.naturalHeight * escala));
                var c = document.createElement('canvas');
                c.width = w; c.height = h;
                var ctx = c.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                return new Promise(function (ok) { c.toBlob(ok, 'image/jpeg', calidad); }).then(function (blob) {
                    if (blob && blob.size <= limite) {
                        return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
                    }
                    if (++intento > 12) { throw new Error('No se pudo reducir a ' + MAX_KB + ' KB'); }
                    if (calidad > 0.5) { calidad -= 0.1; } else { escala *= 0.85; }
                    return paso();
                });
            }
            return paso();
        });
    }

    function Uploader(root) {
        var max = parseInt(root.dataset.max, 10) || 7;
        var maxKb = parseInt(root.dataset.maxKb, 10) || MAX_KB;
        var limite = (maxKb - 6) * 1024; // margen para no rozar el límite del servidor
        var grid = $('[data-up-grid]', root);
        var pick = $('[data-up-pick]', root);
        var real = $('[data-up-files]', root);
        var orders = $('[data-up-orders]', root);
        var msg = $('[data-up-msg]', root);
        var count = $('[data-up-count]', root);
        var items = [];
        try { JSON.parse(root.dataset.initial || '[]').forEach(function (i) { items.push({ kind: 'old', ruta: i.ruta, url: i.url }); }); } catch (e) {}

        function say(text, cls) { msg.textContent = text || ''; msg.className = 'up-msg' + (cls ? ' ' + cls : ''); }

        function sync() {
            var dt = new DataTransfer();
            var n = 0;
            orders.innerHTML = '';
            items.forEach(function (it) {
                var h = document.createElement('input');
                h.type = 'hidden'; h.name = root.dataset.orderName;
                if (it.kind === 'old') { h.value = 'old:' + it.ruta; } else { dt.items.add(it.file); h.value = 'new:' + (n++); }
                orders.appendChild(h);
            });
            real.files = dt.files;
            count.textContent = items.length + ' de ' + max + ' imágenes';
            form.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function render() {
            grid.innerHTML = '';
            items.forEach(function (it, i) {
                var d = document.createElement('div');
                d.className = 'up-item' + (i === 0 ? ' cover' : '');
                var img = document.createElement('img');
                img.src = it.url; img.alt = ''; img.onerror = function () { img.style.opacity = '.25'; };
                d.appendChild(img);
                if (i === 0) { var b = document.createElement('span'); b.className = 'badge-c'; b.textContent = 'Portada'; d.appendChild(b); }
                if (it.kind === 'new') { var k = document.createElement('span'); k.className = 'kb'; k.textContent = fmtKb(it.file.size); d.appendChild(k); }
                var tools = document.createElement('div'); tools.className = 'tools';
                [['◀', -1, 'Mover antes'], ['▶', 1, 'Mover después'], ['×', 0, 'Quitar']].forEach(function (def) {
                    if (def[1] === -1 && i === 0) { return; }
                    if (def[1] === 1 && i === items.length - 1) { return; }
                    var btn = document.createElement('button');
                    btn.type = 'button'; btn.textContent = def[0]; btn.setAttribute('aria-label', def[2]); btn.title = def[2];
                    btn.addEventListener('click', function () {
                        if (def[1] === 0) {
                            if (it.kind === 'new') { URL.revokeObjectURL(it.url); }
                            items.splice(i, 1);
                        } else {
                            var j = i + def[1]; var t = items[i]; items[i] = items[j]; items[j] = t;
                        }
                        say(''); render(); sync();
                    });
                    tools.appendChild(btn);
                });
                d.appendChild(tools);
                grid.appendChild(d);
            });
            if (items.length < max) {
                var add = document.createElement('button');
                add.type = 'button'; add.className = 'up-add'; add.textContent = '+ Agregar imagen';
                add.addEventListener('click', function () { pick.click(); });
                grid.appendChild(add);
            }
        }

        function agregar(files) {
            var lista = Array.prototype.slice.call(files);
            var chain = Promise.resolve();
            var avisos = [], errores = 0;
            lista.forEach(function (f) {
                chain = chain.then(function () {
                    if (items.length >= max) { errores++; avisos.push('Máximo ' + max + ' imágenes.'); return; }
                    if (TIPOS.indexOf(f.type) === -1) { errores++; avisos.push('«' + f.name + '» no es JPG, PNG ni WebP.'); return; }
                    var listo = f.size <= maxKb * 1024 ? Promise.resolve(f) : comprimir(f, limite);
                    return listo.then(function (final) {
                        if (final !== f) { avisos.push('«' + f.name + '» se comprimió de ' + fmtKb(f.size) + ' a ' + fmtKb(final.size) + '.'); }
                        items.push({ kind: 'new', file: final, url: URL.createObjectURL(final) });
                    }).catch(function () { errores++; avisos.push('«' + f.name + '» no se pudo reducir a ' + maxKb + ' KB.'); });
                });
            });
            say('Procesando…');
            chain.then(function () {
                say(avisos.join(' '), errores ? 'err' : 'ok');
                render(); sync();
            });
        }

        pick.addEventListener('change', function () { if (pick.files.length) { agregar(pick.files); } pick.value = ''; });
        render(); sync();
    }
    function initUploaders(scope) { $$('[data-up]', scope).forEach(function (u) { if (!u._up) { u._up = true; Uploader(u); } }); }
    initUploaders(document);

    /* ---------------- Filas repetibles ---------------- */
    function renumber(rep) {
        $$('[data-num]', rep).forEach(function (n, i) { n.textContent = i + 1; });
    }
    $$('[data-repeat]').forEach(function (rep) {
        var tpl = $('template[data-tpl]', rep);
        var next = parseInt(rep.dataset.next, 10) || 0;
        var cont = rep.parentElement;
        var addBtn = $('[data-add]', cont);

        function addRow(focus) {
            var html = tpl.innerHTML.replace(/__i__/g, String(next++));
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild;
            rep.insertBefore(row, tpl);
            initUploaders(row);
            renumber(rep);
            if (focus) { var inp = $('input[type=text],input[type=number],textarea', row); if (inp) { inp.focus(); } }
            form.dispatchEvent(new Event('input', { bubbles: true }));
            return row;
        }
        if (addBtn) { addBtn.addEventListener('click', function () { addRow(true); }); }

        rep.addEventListener('click', function (e) {
            var del = e.target.closest('[data-del]');
            var mv = e.target.closest('[data-move]');
            var row = e.target.closest('.rowx, .price-row, .step');
            if (!row || row.parentElement !== rep) { return; }
            if (del) {
                row.remove();
                // si quedó vacío, dejar una fila en blanco para escribir
                if (!$('.rowx, .price-row, .step', rep) && (rep.hasAttribute('data-steps') || rep.closest('#pane-precio'))) { addRow(false); }
                renumber(rep);
                form.dispatchEvent(new Event('input', { bubbles: true }));
            } else if (mv) {
                var dir = parseInt(mv.dataset.move, 10);
                var sib = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
                if (sib && sib.tagName !== 'TEMPLATE') { dir < 0 ? rep.insertBefore(row, sib) : rep.insertBefore(sib, row); renumber(rep); }
            }
        });

        // Enter en una lista simple: agrega la siguiente fila en vez de enviar el formulario
        rep.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.matches('.rowx input')) { e.preventDefault(); addRow(true); }
        });
        renumber(rep);
    });

    /* ---------------- URL (slug) automática ---------------- */
    var nombre = $('#fNombre'), slug = $('#fSlug'), urlPrev = $('#urlPrev');
    var base = urlPrev.textContent.replace(/[^/]*$/, '');
    function slugify(t) {
        return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 150);
    }
    function pintarUrl() { urlPrev.textContent = base + (slug.value || '…'); }
    nombre.addEventListener('input', function () { if (slug.dataset.auto === '1') { slug.value = slugify(nombre.value); pintarUrl(); } });
    slug.addEventListener('input', function () { slug.dataset.auto = slug.value ? '0' : '1'; slug.value = slug.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-{2,}/g, '-'); pintarUrl(); });

    /* ---------------- Vista previa del video ---------------- */
    var vid = $('#fVideo'), yt = $('#ytPrev');
    function pintarVideo() {
        var m = (vid.value || '').match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/);
        yt.innerHTML = '';
        if (m) {
            var f = document.createElement('iframe');
            f.src = 'https://www.youtube-nocookie.com/embed/' + m[1];
            f.title = 'Vista previa del video'; f.loading = 'lazy'; f.allowFullscreen = true;
            yt.appendChild(f); yt.hidden = false;
        } else { yt.hidden = true; }
    }
    vid.addEventListener('change', pintarVideo);
    vid.addEventListener('blur', pintarVideo);
    pintarVideo();


/* ---------------- Traducción automática ---------------- */
(function () {
    var box = document.getElementById('autoTrad');
    if (!box) { return; }
    var btn = document.getElementById('trBtn'), msg = document.getElementById('trMsg');
    var token = (form.querySelector('input[name=_token]') || {}).value || '';
    function say(t, bad) { msg.textContent = t || ''; msg.className = 'up-msg' + (bad ? ' err' : ' ok'); }
    function val(sel) { var e = form.querySelector(sel); return e ? e.value.trim() : ''; }
    function byName(n) { return form.querySelector('[name="' + n + '"]'); }

    function reunir() {
        var items = [];
        function add(id, t) { if (t && t.trim()) { items.push({ id: id, text: t.trim() }); } }
        add('nombre', val('[name=nombre]'));
        add('duracion', val('[name=duracion]'));
        add('descripcion', val('[name=descripcion]'));
        $$('[name="incluye_items[]"]', form).forEach(function (e, i) { add('inc:' + i, e.value); });
        $$('[name="no_incluye_items[]"]', form).forEach(function (e, i) { add('noinc:' + i, e.value); });
        $$('#pane-itinerario .step', form).forEach(function (row) {
            var t = $('input[name$="[titulo]"]', row); if (!t) { return; }
            var m = t.name.match(/^itinerario\[(.+?)\]\[titulo\]$/); if (!m) { return; }
            add('paso:' + m[1] + ':titulo', t.value);
            var d = $('textarea[name$="[descripcion]"]', row); if (d) { add('paso:' + m[1] + ':descripcion', d.value); }
        });
        $$('#pane-precio .price-row', form).forEach(function (row) {
            var e = $('input[name$="[etiqueta]"]', row); if (!e) { return; }
            var m = e.name.match(/^precios\[(.+?)\]\[etiqueta\]$/); if (!m) { return; }
            add('precio:' + m[1] + ':etiqueta', e.value);
            var d = $('textarea[name$="[descripcion]"]', row); if (d) { add('precio:' + m[1] + ':descripcion', d.value); }
        });
        return items;
    }

    btn.addEventListener('click', function () {
        var items = reunir();
        if (!items.length) { say('Primero escribe el nombre o la descripción del tour.', true); return; }
        var source = document.getElementById('trSource').value;
        var over = document.getElementById('trOver').checked;
        btn.disabled = true; say('Traduciendo ' + items.length + ' textos…');

        fetch(box.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ source: source, items: items })
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
            if (!res.ok || !res.j.ok) { throw new Error((res.j && (res.j.message || (res.j.errors && Object.values(res.j.errors)[0][0]))) || 'No se pudo traducir.'); }
            var tr = res.j.traducciones, n = 0;
            function poner(name, value) {
                var e = byName(name);
                if (e && value && (over || !e.value.trim())) { e.value = value; n++; }
            }
            ['pt', 'es', 'en'].forEach(function (l) {
                if (l === source) { return; }
                ['nombre', 'duracion', 'descripcion'].forEach(function (c) { if (tr[c] && tr[c][l]) { poner('traducciones[' + l + '][' + c + ']', tr[c][l]); } });
                ['inc', 'noinc'].forEach(function (p) {
                    var lineas = [], i = 0;
                    while (tr[p + ':' + i]) { lineas.push(tr[p + ':' + i][l] || ''); i++; }
                    if (lineas.length) { poner('traducciones[' + l + '][' + (p === 'inc' ? 'incluye' : 'no_incluye') + ']', lineas.join('\n')); }
                });
                Object.keys(tr).forEach(function (id) {
                    var m = id.match(/^(paso|precio):(.+):(titulo|descripcion|etiqueta)$/); if (!m || !tr[id][l]) { return; }
                    var grupo = m[1] === 'paso' ? 'itinerario' : 'precios';
                    poner(grupo + '[' + m[2] + '][tr][' + l + '][' + m[3] + ']', tr[id][l]);
                });
            });
            say('Listo: se llenaron ' + n + ' campos. Revísalos y guarda el tour.');
            form.dispatchEvent(new Event('input', { bubbles: true }));
        }).catch(function (e) { say(e.message, true); })
        .then(function () { btn.disabled = false; });
    });
})();

    /* ---------------- Puntos de avance por panel ---------------- */
    function marcar(name, ok) { var d = $('[data-dot="' + name + '"]'); if (d && d.className.indexOf('err') === -1) { d.className = 'dot-s' + (ok ? ' ok' : ''); } }
    function avance() {
        var tieneImg = $$('#pane-detalle [data-up-orders] input').length > 0;
        marcar('detalle', nombre.value.trim() && $('#pane-detalle textarea[name=descripcion]').value.trim() && tieneImg);
        marcar('itinerario', $$('#pane-itinerario input[name$="[titulo]"]').some(function (i) { return i.value.trim(); }));
        marcar('precio', $$('#pane-precio input[name$="[precio]"]').some(function (i) { return parseFloat(i.value) > 0; }));
        marcar('publicacion', $$('#pane-publicacion input[name="secciones[]"]').some(function (i) { return i.checked; }));
        marcar('idiomas', $$('#pane-idiomas input[type=text],#pane-idiomas textarea').some(function (i) { return i.value.trim(); }));
    }
    form.addEventListener('input', avance);
    form.addEventListener('change', avance);
    avance();

    /* ---------------- Envío ---------------- */
    var btnBorrador = $('#btnBorrador');
    btnBorrador.addEventListener('click', function (e) {
        if (form.dataset.publicado === '1' && !confirm('Este tour está publicado. Si lo guardas como borrador dejará de verse en el sitio. ¿Continuar?')) {
            e.preventDefault();
        }
    });
    form.addEventListener('submit', function () {
        // evita doble clic mientras sube
        $$('.actionbar button', form).forEach(function (b) { setTimeout(function () { b.disabled = true; }, 0); });
    });
    window.addEventListener('pageshow', function () { $$('.actionbar button', form).forEach(function (b) { b.disabled = false; }); });
})();
