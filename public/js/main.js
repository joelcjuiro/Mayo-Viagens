// Menú móvil
const btn = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');
if (btn && nav) {
  btn.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
  });
  nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    nav.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
  }));
}

// Aviso superior (se recuerda durante la sesión)
const promo = document.querySelector('[data-promo]');
if (promo) {
  try { if (sessionStorage.getItem('mayo.promo') === '1') promo.hidden = true; } catch (e) {}
  promo.querySelector('[data-promo-close]')?.addEventListener('click', () => {
    promo.hidden = true;
    try { sessionStorage.setItem('mayo.promo', '1'); } catch (e) {}
  });
}

// Carruseles (tours y reseñas): un solo manejador por carrusel
document.querySelectorAll('[data-slider]').forEach(slider => {
  const scope = slider.closest('section') || document;
  const paso = () => Math.max(slider.clientWidth * 0.8, 280);
  scope.querySelectorAll('[data-slide]').forEach(b => b.addEventListener('click', () => {
    slider.scrollBy({ left: paso() * Number(b.dataset.slide), behavior: 'smooth' });
  }));
  slider.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') { e.preventDefault(); slider.scrollBy({ left: paso(), behavior: 'smooth' }); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); slider.scrollBy({ left: -paso(), behavior: 'smooth' }); }
  });
});

// Cierra el selector de idioma al hacer clic fuera
document.addEventListener('click', e => {
  document.querySelectorAll('.lang-menu[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); });
});

// Mega menú
(function () {
  var bar = document.querySelector('[data-bar]');
  if (!bar) return;
  var items = bar.querySelectorAll('[data-mega]');
  var timer;
  var canHover = window.matchMedia('(hover: hover) and (min-width: 1101px)');

  function closeAll(except) {
    items.forEach(function (it) {
      if (it === except) return;
      it.classList.remove('open');
      it.querySelector('[data-mega-btn]').setAttribute('aria-expanded', 'false');
    });
  }
  function openItem(it) {
    clearTimeout(timer);
    closeAll(it);
    it.classList.add('open');
    it.querySelector('[data-mega-btn]').setAttribute('aria-expanded', 'true');
  }

  items.forEach(function (it) {
    var btn = it.querySelector('[data-mega-btn]');
    btn.addEventListener('click', function () {
      if (it.classList.contains('open')) { closeAll(); } else { openItem(it); }
    });
    it.addEventListener('mouseenter', function () { if (canHover.matches) openItem(it); });
    it.addEventListener('mouseleave', function () {
      if (canHover.matches) { timer = setTimeout(function () { closeAll(); }, 180); }
    });

    var sels = it.querySelectorAll('[data-sel]');
    sels.forEach(function (s) {
      function activate() {
        sels.forEach(function (x) { x.classList.toggle('on', x === s); });
        it.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.dataset.pane !== s.dataset.sel; });
      }
      s.addEventListener('mouseenter', activate);
      s.addEventListener('focus', activate);
    });
  });

  document.addEventListener('click', function (e) { if (!bar.contains(e.target)) closeAll(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
})();

// Burbuja de WhatsApp
(function () {
  var bubble = document.querySelector('[data-wa-bubble]');
  if (!bubble) return;
  try { if (sessionStorage.getItem('wa-closed')) bubble.hidden = true; } catch (e) {}
  var close = document.querySelector('[data-wa-close]');
  if (close) close.addEventListener('click', function () {
    bubble.hidden = true;
    try { sessionStorage.setItem('wa-closed', '1'); } catch (e) {}
  });
})();

// Video del inicio: se pausa fuera de pantalla y no se carga en móvil/ahorro de datos
(function () {
  var v = document.querySelector('.hero2 video');
  if (!v) return;
  var ahorro = (navigator.connection && navigator.connection.saveData) || window.matchMedia('(max-width: 760px)').matches || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (ahorro) { v.removeAttribute('autoplay'); v.pause(); v.removeAttribute('src'); v.load(); return; }
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { v.play().catch(function () {}); } else { v.pause(); } });
    }, { threshold: 0.05 }).observe(v);
  }
  document.addEventListener('visibilitychange', function () { if (document.hidden) { v.pause(); } });
})();
