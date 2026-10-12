// TechZone — JavaScript umum (tema "Noir & Aurum")
// Semua efek bersifat progressive enhancement: jika JS mati, halaman tetap terbaca.

(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var html = document.documentElement;

  /* ---------- Intro loader (sekali per sesi) ---------- */
  function initLoader() {
    var loader = document.getElementById('tz-loader');
    if (!loader) return;
    if (html.classList.contains('tz-seen') || reduceMotion) { loader.classList.add('is-gone'); return; }
    var started = Date.now(), finished = false;
    function finish() {
      if (finished) return; finished = true;
      var wait = Math.max(0, 900 - (Date.now() - started));
      setTimeout(function () {
        loader.classList.add('is-done');
        try { sessionStorage.setItem('tz_intro_seen', '1'); } catch (e) {}
        setTimeout(function () { loader.classList.add('is-gone'); }, 1300);
      }, wait);
    }
    if (document.readyState === 'complete') finish(); else window.addEventListener('load', finish);
    setTimeout(finish, 3200); // pengaman
  }
  initLoader();

  document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Scroll reveal + stagger otomatis ---------- */
    var revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length) {
      var groups = new Map();
      revealEls.forEach(function (el) {
        if (el.style.transitionDelay) return;
        var p = el.parentElement, n = groups.get(p) || 0;
        el.style.transitionDelay = Math.min(n * 70, 420) + 'ms';
        groups.set(p, n + 1);
      });
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) { entry.target.classList.add('is-visible'); io.unobserve(entry.target); }
          });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
      } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
      }
    }

    /* ---------- Judul terpecah per kata ---------- */
    document.querySelectorAll('[data-split]').forEach(function (el) {
      var base = parseInt(el.getAttribute('data-split-delay') || '300', 10), i = 0;
      (function walk(node) {
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
          if (child.nodeType === 3) {
            var frag = document.createDocumentFragment();
            child.textContent.split(/(\s+)/).forEach(function (part) {
              if (!part) return;
              if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
              var w = document.createElement('span'); w.className = 'tz-word';
              var inner = document.createElement('span'); inner.textContent = part;
              inner.style.animationDelay = (base + i++ * 85) + 'ms'; w.appendChild(inner); frag.appendChild(w);
            });
            node.replaceChild(frag, child);
          } else if (child.nodeType === 1) { walk(child); }
        });
      })(el);
    });

    /* ---------- Navbar, progress bar, tombol ke atas ---------- */
    var siteHeader = document.getElementById('techzone-header');
    var progress   = document.getElementById('tz-progress');
    var toTop      = document.getElementById('tz-totop');
    var ringCircle = toTop ? toTop.querySelector('circle') : null;
    var ticking = false;
    function onScroll() {
      var y = window.scrollY || 0;
      var max = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
      var pct = Math.min(1, y / max);
      if (siteHeader) siteHeader.classList.toggle('is-scrolled', y > 12);
      if (progress) progress.style.transform = 'scaleX(' + pct.toFixed(4) + ')';
      if (toTop) { toTop.classList.toggle('is-visible', y > 480); if (ringCircle) ringCircle.style.strokeDashoffset = (151 - 151 * pct).toFixed(1); }
      if (!reduceMotion) document.querySelectorAll('[data-parallax]').forEach(function (el) {
        var speed = parseFloat(el.getAttribute('data-parallax')) || 0.15;
        el.style.transform = 'translate3d(0,' + (y * speed).toFixed(1) + 'px,0)';
      });
      ticking = false;
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();
    if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ---------- Ripple ---------- */
    document.querySelectorAll('.ripple-wrap').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        var rect = btn.getBoundingClientRect(), size = Math.max(rect.width, rect.height);
        var ripple = document.createElement('span');
        ripple.className = 'ripple-el';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top  = (e.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 700);
      });
    });

    /* ---------- Counter animasi ---------- */
    document.querySelectorAll('[data-count-to]').forEach(function (el) {
      var target = parseInt(el.getAttribute('data-count-to'), 10) || 0, started = false;
      var run = function () {
        if (started) return; started = true;
        var duration = 1600, startTime = null;
        var step = function (ts) {
          if (!startTime) startTime = ts;
          var p = Math.min((ts - startTime) / duration, 1), eased = 1 - Math.pow(1 - p, 4);
          el.textContent = Math.floor(eased * target).toLocaleString('id-ID');
          if (p < 1) requestAnimationFrame(step); else el.textContent = target.toLocaleString('id-ID');
        };
        requestAnimationFrame(step);
      };
      if ('IntersectionObserver' in window) {
        var cio = new IntersectionObserver(function (entries) {
          entries.forEach(function (en) { if (en.isIntersecting) { run(); cio.unobserve(en.target); } });
        }, { threshold: 0.4 });
        cio.observe(el);
      } else { run(); }
    });

    /* ---------- Form: tampil/sembunyi password, kekuatan password, loading ---------- */
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.querySelector(btn.getAttribute('data-toggle-password'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var ic = btn.querySelector('i'); if (ic) ic.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
      });
    });
    document.querySelectorAll('[data-strength]').forEach(function (input) {
      var meter = document.querySelector(input.getAttribute('data-strength'));
      if (!meter) return;
      var label = meter.querySelector('span'), names = ['', 'Lemah', 'Cukup', 'Baik', 'Kuat'];
      input.addEventListener('input', function () {
        var v = input.value, score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
        if (!v) score = 0;
        meter.setAttribute('data-level', score);
        if (label) label.textContent = names[score] || '\u00a0';
      });
    });
    document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
      form.addEventListener('submit', function () {
        var btn = form.querySelector('.tz-submit');
        if (btn && form.checkValidity()) setTimeout(function () { btn.classList.add('is-loading'); }, 0);
      });
    });
    window.addEventListener('pageshow', function () {
      document.querySelectorAll('.tz-submit.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
    });

    /* ---------- Jam & tanggal (topbar admin) ---------- */
    var clock = document.querySelector('[data-clock]');
    if (clock) {
      var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
      var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
      var tick = function () {
        var d = new Date(), pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var t = clock.querySelector('[data-clock-time]'), dt = clock.querySelector('[data-clock-date]');
        if (t)  t.textContent  = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        if (dt) dt.textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
      };
      tick(); setInterval(tick, 1000);
    }

    /* ---------- Tema terang / gelap (toko) ---------- */
    var themeBtn = document.getElementById('tz-theme-toggle');
    if (themeBtn) {
      themeBtn.addEventListener('click', function () {
        var dark = html.getAttribute('data-theme') === 'dark';
        html.classList.add('tz-theme-anim');
        if (dark) { html.removeAttribute('data-theme'); } else { html.setAttribute('data-theme', 'dark'); }
        try { localStorage.setItem('tz_theme', dark ? 'light' : 'dark'); } catch (e) {}
        setTimeout(function () { html.classList.remove('tz-theme-anim'); }, 600);
      });
    }

    /* ---------- Hitung mundur batas pembayaran ---------- */
    document.querySelectorAll('[data-countdown]').forEach(function (box) {
      var end = new Date(box.getAttribute('data-countdown')).getTime();
      var out = box.querySelector('[data-countdown-text]');
      if (!out || isNaN(end)) return;
      function tick() {
        var left = Math.floor((end - Date.now()) / 1000);
        if (left <= 0) { out.textContent = 'waktu habis'; box.classList.add('is-expired'); return; }
        var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), d = left % 60;
        out.textContent = (h > 0 ? h + ' jam ' : '') + m + ' menit ' + (d < 10 ? '0' : '') + d + ' detik';
        setTimeout(tick, 1000);
      }
      tick();
    });

    /* ---------- Salin teks (nomor resi) ---------- */
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy'), label = btn.querySelector('span');
        function done() { if (label) { var old = label.textContent; label.textContent = 'Tersalin!'; setTimeout(function () { label.textContent = old; }, 1600); } }
        function legacy() { var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) {} t.remove(); }
        if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(done, legacy); } else { legacy(); }
      });
    });

    /* ---------- Saran pencarian + riwayat pencarian ---------- */
    var HKEY = 'tz_recent_search';
    function getHistory() { try { return JSON.parse(localStorage.getItem(HKEY) || '[]') || []; } catch (e) { return []; } }
    function saveHistory(q) {
      q = (q || '').trim(); if (q.length < 2) return;
      var list = getHistory().filter(function (x) { return x.toLowerCase() !== q.toLowerCase(); });
      list.unshift(q);
      try { localStorage.setItem(HKEY, JSON.stringify(list.slice(0, 6))); } catch (e) {}
    }
    function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }

    document.querySelectorAll('form[data-suggest]').forEach(function (form) {
      var input = form.querySelector('input[name="q"]'), url = form.getAttribute('data-suggest');
      if (!input || !url) return;
      var box = el('div', 'tz-suggest'); box.setAttribute('role', 'listbox'); form.appendChild(box);
      var timer = null, ctrl = null, active = -1;

      function close() { box.classList.remove('is-open'); active = -1; }
      function items() { return box.querySelectorAll('a[data-item]'); }
      function setActive(i) {
        var list = items(); if (!list.length) return;
        active = (i + list.length) % list.length;
        list.forEach(function (a, n) { a.classList.toggle('is-active', n === active); });
      }
      function showHistory() {
        var list = getHistory(); box.textContent = '';
        if (!list.length) { close(); return; }
        var head = el('div', 'tz-suggest__head'); head.appendChild(el('span', null, 'Pencarian terakhir'));
        var clear = el('button', 'tz-suggest__clear', 'Hapus'); clear.type = 'button';
        clear.addEventListener('mousedown', function (e) { e.preventDefault(); try { localStorage.removeItem(HKEY); } catch (x) {} close(); });
        head.appendChild(clear); box.appendChild(head);
        list.forEach(function (q) {
          var a = el('a', 'tz-suggest__row tz-suggest__row--hist'); a.setAttribute('data-item', '1');
          a.href = form.action + '?q=' + encodeURIComponent(q);
          a.appendChild(el('i', 'fa-solid fa-clock-rotate-left')); a.appendChild(el('span', null, q)); box.appendChild(a);
        });
        box.classList.add('is-open');
      }
      function render(data, q) {
        box.textContent = '';
        var has = (data.products && data.products.length) || (data.categories && data.categories.length);
        if (!has) {
          box.appendChild(el('div', 'tz-suggest__empty', 'Tidak ada saran untuk "' + q + '". Tekan Enter untuk mencari.'));
        }
        (data.categories || []).forEach(function (c) {
          var a = el('a', 'tz-suggest__row tz-suggest__row--cat'); a.setAttribute('data-item', '1'); a.href = c.url;
          a.appendChild(el('i', 'fa-solid fa-layer-group')); a.appendChild(el('span', null, 'Kategori: ' + c.name)); box.appendChild(a);
        });
        (data.products || []).forEach(function (p) {
          var a = el('a', 'tz-suggest__row'); a.setAttribute('data-item', '1'); a.href = p.url;
          var img = el('img'); img.src = p.image; img.alt = ''; img.loading = 'lazy'; a.appendChild(img);
          var t = el('div', 'tz-suggest__txt'); t.appendChild(el('b', null, p.name));
          t.appendChild(el('small', null, p.category + (p.stock ? '' : ' · Stok habis'))); a.appendChild(t);
          a.appendChild(el('em', null, p.price)); box.appendChild(a);
        });
        if (has) {
          var all = el('a', 'tz-suggest__all'); all.setAttribute('data-item', '1'); all.href = form.action + '?q=' + encodeURIComponent(q);
          all.appendChild(el('span', null, 'Lihat semua hasil untuk "' + q + '"')); all.appendChild(el('i', 'fa-solid fa-arrow-right')); box.appendChild(all);
        }
        box.classList.add('is-open'); active = -1;
      }
      function search() {
        var q = input.value.trim();
        if (q.length < 2) { q.length === 0 ? showHistory() : close(); return; }
        if (ctrl) ctrl.abort();
        ctrl = window.AbortController ? new AbortController() : null;
        fetch(url + '?q=' + encodeURIComponent(q), ctrl ? { signal: ctrl.signal } : {})
          .then(function (r) { return r.json(); }).then(function (d) { if (input.value.trim() === q) render(d, q); })
          .catch(function () {});
      }
      input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 220); });
      input.addEventListener('focus', function () { if (input.value.trim().length < 2) showHistory(); else search(); });
      input.addEventListener('keydown', function (e) {
        if (!box.classList.contains('is-open')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); var a = items()[active]; if (a) window.location.href = a.href; }
        else if (e.key === 'Escape') { close(); }
      });
      form.addEventListener('submit', function () { saveHistory(input.value); });
      box.addEventListener('click', function (e) { var a = e.target.closest('a[data-item]'); if (a && a.classList.contains('tz-suggest__row--hist')) { var q = a.textContent.trim(); saveHistory(q); } });
      document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
    });

    /* ---------- Dropdown lonceng notifikasi: klik lagi untuk menutup ---------- */
    document.querySelectorAll('[data-notif]').forEach(function (box) {
      var btn = box.querySelector('[data-notif-toggle]');
      if (!btn) return;
      function setOpen(open) { box.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
      btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(!box.classList.contains('is-open')); });
      document.addEventListener('click', function (e) { if (!box.contains(e.target)) setOpen(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    });

    /* ---------- Angka pemberitahuan admin: diperbarui otomatis tiap 30 detik ---------- */
    var adminSide = document.getElementById('techzone-admin-sidebar');
    if (adminSide && adminSide.getAttribute('data-counts-url')) {
      var countsUrl = adminSide.getAttribute('data-counts-url');
      var applyCounts = function (data) {
        Object.keys(data).forEach(function (key) {
          var n = parseInt(data[key], 10) || 0, txt = n > 99 ? '99+' : String(n);
          document.querySelectorAll('[data-badge="' + key + '"]').forEach(function (el) {
            var before = parseInt(el.textContent, 10) || 0;
            if (el.textContent !== txt) el.textContent = txt;
            if (n > 0) el.removeAttribute('hidden'); else el.setAttribute('hidden', '');
            if (n > before) { el.classList.add('is-bump'); setTimeout(function () { el.classList.remove('is-bump'); }, 800); }
          });
        });
      };
      var pollCounts = function () {
        if (document.hidden) return;
        fetch(countsUrl, { credentials: 'same-origin', cache: 'no-store' })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (d) { if (d && !d.error) applyCounts(d); })
          .catch(function () {});
      };
      setInterval(pollCounts, 30000);
      document.addEventListener('visibilitychange', function () { if (!document.hidden) pollCounts(); });
    }

    // Mode ringan: efek berat (partikel, cahaya kursor, tilt 3D, tombol magnetik) sengaja tidak dipakai.
  });
})();
