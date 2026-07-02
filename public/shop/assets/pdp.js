/* VowNook Shop — product page behaviour (shared).
   Each page defines window.PDP = { key, price, cards:[ids], fields:[ids],
   designs:bool, colours:bool, defaultCard } before this script loads.
   cards.js (VNCards) must be loaded first. */
(function () {
  'use strict';
  var cfg = window.PDP || {};
  var cv = document.getElementById('pdpCanvas');
  var st = { design: cfg.defaultDesign || 'Heirloom', cw: 'Champagne', color: VNCards.CW.Champagne, card: cfg.defaultCard || (cfg.cards && cfg.cards[0]) || 'inv' };

  function data() {
    var d = {};
    (cfg.fields || []).forEach(function (id) {
      var el = document.getElementById('f_' + id);
      if (el) d[id] = el.value;
    });
    return d;
  }
  function draw() { if (cv) VNCards.render(cv, st, data()); }

  /* card-type tabs */
  var tabsHost = document.getElementById('pdpTabs');
  if (tabsHost && cfg.cards && cfg.cards.length > 1) {
    var labels = {};
    VNCards.CARDS.forEach(function (c) { labels[c.id] = c.label; });
    cfg.cards.forEach(function (id, i) {
      var t = document.createElement('button');
      t.type = 'button';
      t.className = 'tab' + (i === 0 ? ' on' : '');
      t.textContent = labels[id] || id;
      t.addEventListener('click', function () {
        tabsHost.querySelectorAll('.tab').forEach(function (x) { x.classList.remove('on'); });
        t.classList.add('on'); st.card = id; showLive(); draw();
      });
      tabsHost.appendChild(t);
    });
  }

  /* design chips */
  var designsHost = document.getElementById('pdpDesigns');
  if (designsHost && cfg.designs !== false) {
    VNCards.DESIGNS.forEach(function (d) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'pick' + (d === st.design ? ' on' : '');
      b.textContent = d;
      b.addEventListener('click', function () {
        designsHost.querySelectorAll('.pick').forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on'); st.design = d; showLive(); draw();
      });
      designsHost.appendChild(b);
    });
  }

  /* colourway dots */
  var dotsHost = document.getElementById('pdpColours');
  if (dotsHost) {
    Object.keys(VNCards.CW).forEach(function (name, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'dot' + (i === 0 ? ' on' : '');
      b.style.background = VNCards.CW[name];
      b.setAttribute('aria-label', name + ' colourway');
      b.title = name;
      b.addEventListener('click', function () {
        dotsHost.querySelectorAll('.dot').forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on'); st.cw = name; st.color = VNCards.CW[name]; showLive(); draw();
      });
      dotsHost.appendChild(b);
    });
  }

  /* live redraw on typing */
  document.querySelectorAll('.custom input').forEach(function (i) { i.addEventListener('input', function () { showLive(); draw(); }); });

  /* media thumbs: live preview vs product photos */
  var media = document.getElementById('pdpMedia');
  var photo = document.getElementById('pdpPhoto');
  function showLive() {
    if (!media) return;
    media.classList.remove('showing-photo');
    document.querySelectorAll('.thumbs button').forEach(function (x) { x.classList.remove('on'); });
    var lv = document.querySelector('.thumbs .live'); if (lv) lv.classList.add('on');
  }
  document.querySelectorAll('.thumbs button[data-src]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (photo) photo.src = b.dataset.src;
      media.classList.add('showing-photo');
      document.querySelectorAll('.thumbs button').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
    });
  });
  var liveThumb = document.querySelector('.thumbs .live');
  if (liveThumb) liveThumb.addEventListener('click', showLive);

  /* buy — same checkout contract as the shop grid */
  document.querySelectorAll('.buy').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault();
      var orig = b.textContent; b.disabled = true; b.style.pointerEvents = 'none'; b.textContent = 'One moment…';
      fetch('/api/shop/checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ product: b.dataset.name || cfg.key }) })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          if (!res.ok || !res.d.url) throw new Error(res.d.error || 'Checkout is not available right now.');
          window.location.href = res.d.url;
        })
        .catch(function (err) { b.disabled = false; b.style.pointerEvents = ''; b.textContent = orig; alert(err.message); });
    });
  });

  /* sticky mobile buy bar after the hero */
  var bar = document.getElementById('buybar');
  var hero = document.querySelector('.pdp');
  if (bar && hero && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (es) { bar.classList.toggle('show', !es[0].isIntersecting); }, { threshold: 0 }).observe(hero);
  }

  /* reveals */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { threshold: 0.12 });
    document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
  }

  draw();
  VNCards.loadFonts().then(draw);
})();
