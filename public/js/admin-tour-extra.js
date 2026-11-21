/* Editor de tours: formato de texto, idioma base, sugerencias de incluye/no incluye e itinerario traducido */
(function () {
    'use strict';
    var form = document.getElementById('tourForm');
    if (!form) { return; }
    function $(s, r) { return (r || document).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
    function avisar(el) { el.dispatchEvent(new Event('input', { bubbles: true })); }

    /* ---------- Barra de formato (negrita, cursiva, subtítulo, listas, recuadro, enlace) ---------- */
    function envolver(ta, a, b, ejemplo) {
        var s = ta.selectionStart, e = ta.selectionEnd, t = ta.value, sel = t.slice(s, e) || ejemplo;
        ta.value = t.slice(0, s) + a + sel + b + t.slice(e);
        ta.focus(); ta.setSelectionRange(s + a.length, s + a.length + sel.length); avisar(ta);
    }
    function prefijo(ta, p, ejemplo) {
        var s = ta.selectionStart, e = ta.selectionEnd, t = ta.value;
        var ini = t.lastIndexOf('\n', s - 1) + 1, sel = t.slice(ini, e) || ejemplo;
        var nuevo = sel.split('\n').map(function (l, i) { return (p === '1. ' ? (i + 1) + '. ' : p) + l; }).join('\n');
        ta.value = t.slice(0, ini) + nuevo + t.slice(e);
        ta.focus(); ta.setSelectionRange(ini, ini + nuevo.length); avisar(ta);
    }
    var ACC = {
        b: function (ta) { envolver(ta, '**', '**', 'texto importante'); },
        i: function (ta) { envolver(ta, '*', '*', 'texto'); },
        h: function (ta) { prefijo(ta, '## ', 'Subtítulo'); },
        ul: function (ta) { prefijo(ta, '- ', 'elemento'); },
        ol: function (ta) { prefijo(ta, '1. ', 'elemento'); },
        q: function (ta) { prefijo(ta, '> ', 'Aviso importante'); },
        a: function (ta) {
            var url = window.prompt('Dirección del enlace (https://…)');
            if (url) { envolver(ta, '[', '](' + url + ')', 'texto del enlace'); }
        }
    };
    function areaDe(bar) {
        var n = bar.nextElementSibling;
        return n && n.tagName === 'TEXTAREA' ? n : null;
    }
    form.addEventListener('click', function (e) {
        var b = e.target.closest('[data-md]');
        if (!b) { return; }
        e.preventDefault();
        var ta = areaDe(b.closest('[data-md-bar]'));
        if (ta && !ta.disabled && ACC[b.dataset.md]) { ACC[b.dataset.md](ta); }
    });
    form.addEventListener('keydown', function (e) {
        if (!(e.ctrlKey || e.metaKey) || e.target.tagName !== 'TEXTAREA') { return; }
        var prev = e.target.previousElementSibling;
        if (!prev || !prev.hasAttribute('data-md-bar')) { return; }
        var k = e.key.toLowerCase();
        if (k === 'b') { e.preventDefault(); ACC.b(e.target); }
        if (k === 'i') { e.preventDefault(); ACC.i(e.target); }
    });

    /* ---------- Idioma base: en Idiomas solo quedan los que faltan ---------- */
    var NOMBRES = { es: 'Español', pt: 'Português', en: 'English' };
    function base() { var r = $('[name=idioma_base]:checked', form); return r ? r.value : 'es'; }
    function apagar(caja, off) {
        caja.classList.toggle('lang-off', off);
        $$('input,textarea', caja).forEach(function (i) { i.disabled = off; });
    }
    function aplicarBase() {
        var b = base();
        $$('[data-lang-card]', form).forEach(function (c) { apagar(c, c.dataset.langCard === b); });
        $$('[data-lang-field]', form).forEach(function (f) { apagar(f, f.dataset.langField === b); });
        $$('[data-sug-lang]', form).forEach(function (c) { c.hidden = c.dataset.sugLang !== b; });
        $$('[data-base-name]', form).forEach(function (n) { n.textContent = NOMBRES[b]; });
        $$('[data-missing-names]', form).forEach(function (n) {
            n.textContent = Object.keys(NOMBRES).filter(function (k) { return k !== b; }).map(function (k) { return NOMBRES[k]; }).join(' y ');
        });
    }
    form.addEventListener('change', function (e) { if (e.target.name === 'idioma_base') { aplicarBase(); avisar(form); } });
    // al agregar filas nuevas (precios) se heredan las del idioma base
    form.addEventListener('click', function (e) { if (e.target.closest('[data-add]')) { setTimeout(aplicarBase, 0); } });

    /* ---------- Incluye / No incluye: sugerencias con un toque y contador ---------- */
    function contar() {
        $$('.inc-col', form).forEach(function (col) {
            var vals = $$('.rowx input', col).map(function (i) { return i.value.trim().toLowerCase(); }).filter(Boolean);
            var n = $('[data-inc-count]', col);
            if (n) { n.textContent = vals.length; }
            $$('[data-suggest]', col).forEach(function (c) { c.classList.toggle('used', vals.indexOf(c.dataset.suggest.toLowerCase()) !== -1); });
        });
    }
    form.addEventListener('click', function (e) {
        var c = e.target.closest('[data-suggest]');
        if (c) {
            var col = c.closest('.inc-col'), val = c.dataset.suggest;
            var inputs = $$('.rowx input', col);
            if (inputs.some(function (i) { return i.value.trim().toLowerCase() === val.toLowerCase(); })) { return; }
            var vacio = inputs.filter(function (i) { return !i.value.trim(); })[0];
            if (!vacio) { $('[data-add]', col).click(); inputs = $$('.rowx input', col); vacio = inputs[inputs.length - 1]; }
            vacio.value = val; avisar(vacio); contar();
        }
        if (e.target.closest('[data-del]')) { setTimeout(contar, 0); }
    });
    form.addEventListener('input', function (e) { if (e.target.closest && e.target.closest('.inc-col')) { contar(); } });
    contar();

    /* ---------- Itinerario traducido: un bloque por paso en cada idioma ---------- */
    function pasos() {
        return $$('#pane-itinerario .step', form).map(function (row) {
            var t = $('input[name$="[titulo]"]', row), d = $('textarea[name$="[descripcion]"]', row);
            var m = t && t.name.match(/^itinerario\[(.+?)\]\[titulo\]$/);
            return m ? { key: m[1], titulo: t.value.trim(), desc: d ? d.value.trim() : '' } : null;
        }).filter(function (p) { return p && (p.titulo || p.desc); });
    }
    function sincronizar() {
        var ps = pasos(), keys = ps.map(function (p) { return p.key; });
        $$('[data-tr-itin]', form).forEach(function (box) {
            var lista = $('.tr-itin-list', box), tpl = $('template[data-tr-step-tpl]', box), vacio = $('[data-tr-empty]', box);
            $$('.tr-step', lista).forEach(function (el) { if (keys.indexOf(el.dataset.trStep) === -1) { el.remove(); } });
            ps.forEach(function (p, idx) {
                var el = $('.tr-step[data-tr-step="' + p.key + '"]', lista);
                if (!el) {
                    var w = document.createElement('div');
                    w.innerHTML = tpl.innerHTML.replace(/__i__/g, p.key).trim();
                    el = w.firstElementChild;
                }
                lista.appendChild(el);   // mantiene el orden del itinerario
                $('[data-tr-title]', el).textContent = p.titulo || 'Paso del itinerario';
                $('[data-tr-num]', el).textContent = idx + 1;
            });
            if (vacio) { vacio.hidden = ps.length > 0; }
        });
        aplicarBase();
    }
    window.mayoSyncItinerarioTr = sincronizar;
    form.addEventListener('click', function (e) { if (e.target.closest('[data-tab="idiomas"]')) { sincronizar(); } });

    aplicarBase();
    sincronizar();
})();
