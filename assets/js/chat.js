// TechZone — Chat produk (user <-> admin).
// Ruang chat (#tz-chat) dan daftar percakapan admin (#tz-chatlist). Semua teks dirender lewat textContent (aman dari XSS).
(function () {
  'use strict';

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function withQuery(api, qs) { return api + (api.indexOf('?') > -1 ? '&' : '?') + qs; }
  function getJson(url) {
    return fetch(url, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) {
      if (!r.ok) { var e = new Error('http ' + r.status); e.status = r.status; throw e; }
      return r.json();
    });
  }
  function postForm(url, data) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    return fetch(url, { method: 'POST', credentials: 'same-origin', body: body, headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { j.__status = r.status; return j; }); });
  }

  /* ------------------------------------------------------------------ ruang chat */
  var root = document.getElementById('tz-chat');
  if (root) {
    var api = root.getAttribute('data-api');
    var threadId = root.getAttribute('data-thread');
    var csrf = root.getAttribute('data-csrf');
    var maxLen = parseInt(root.getAttribute('data-max') || '1000', 10);
    var box = document.getElementById('tz-chat-msgs');
    var form = document.getElementById('tz-chat-form');
    var input = document.getElementById('tz-chat-input');
    var sendBtn = form ? form.querySelector('.tz-chat__send') : null;
    var prodBar = document.getElementById('tz-chat-prod');
    var prodSend = document.getElementById('tz-chat-sendprod');
    var prodX = document.getElementById('tz-chat-prodx');

    var lastId = 0, seen = {}, lastDay = '', firstLoad = true, busy = false, timer = null, sending = false;

    function nearBottom() { return box.scrollHeight - box.scrollTop - box.clientHeight < 90; }
    function toBottom() { box.scrollTop = box.scrollHeight; }

    // fromSend: pesan milik sendiri yang baru dikirim. lastId TIDAK dimajukan, supaya balasan lawan bicara
    // yang masuk tepat sebelumnya tetap terambil oleh polling berikutnya (duplikat dibuang lewat `seen`).
    function renderMessage(m, fromSend) {
      if (!fromSend && m.id > lastId) lastId = m.id;
      if (seen[m.id]) return false;
      seen[m.id] = true;

      if (m.day !== lastDay) {
        lastDay = m.day;
        box.appendChild(el('div', 'tz-chat__day', m.dayText));
      }
      var row = el('div', 'tz-cm ' + (m.sender === 'bot' ? 'tz-cm--bot' : (m.mine ? 'tz-cm--me' : 'tz-cm--them')));
      var bubble = el('div', 'tz-cm__bubble');

      if (m.type === 'product') {
        if (m.product) {
          var card = el('a', 'tz-cmcard'); card.href = m.product.url;
          var img = el('img'); img.src = m.product.image; img.alt = ''; img.loading = 'lazy'; card.appendChild(img);
          var t = el('span', 'tz-cmcard__txt');
          t.appendChild(el('b', null, m.product.name));
          t.appendChild(el('em', null, m.product.price));
          card.appendChild(t);
          bubble.appendChild(card);
        } else {
          bubble.appendChild(el('div', 'tz-cm__text', 'Produk tidak tersedia lagi.'));
        }
      } else {
        bubble.appendChild(el('div', 'tz-cm__text', m.body));
      }
      bubble.appendChild(el('time', 'tz-cm__time', m.time));
      row.appendChild(bubble);
      if (m.sender === 'bot') row.appendChild(el('small', 'tz-cm__tag', 'Dikirim oleh chatbot'));
      box.appendChild(row);
      return true;
    }

    function renderBatch(list) {
      var stick = firstLoad || nearBottom();
      var loading = box.querySelector('.tz-chat__note');
      if (loading) loading.remove();
      var added = false;
      list.forEach(function (m) { if (renderMessage(m)) added = true; });
      if (stick && (added || firstLoad)) toBottom();
      firstLoad = false;
    }

    function poll() {
      if (busy) return Promise.resolve();
      busy = true;
      return getJson(withQuery(api, 'action=poll&thread=' + encodeURIComponent(threadId) + '&after=' + lastId))
        .then(function (d) { renderBatch(d.messages || []); })
        .catch(function (e) {
          if (firstLoad) {
            var n = box.querySelector('.tz-chat__note');
            if (n) n.textContent = e && e.status === 404 ? 'Percakapan tidak ditemukan.' : 'Gagal memuat percakapan. Coba muat ulang halaman.';
          }
        })
        .then(function () { busy = false; });
    }
    function schedule() {
      clearTimeout(timer);
      timer = setTimeout(function () { poll().then(schedule); }, document.hidden ? 15000 : 4000);
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll().then(schedule); } });

    function sendError(msg) {
      var n = el('p', 'tz-chat__note tz-chat__note--err', msg);
      box.appendChild(n); toBottom();
      setTimeout(function () { n.remove(); }, 4000);
    }
    function send(data, done) {
      if (sending) return;
      sending = true;
      data.thread = threadId; data.csrf_token = csrf;
      postForm(api, data).then(function (j) {
        if (j && j.ok && j.message) {
          renderMessage(j.message, true);
          toBottom();
          if (done) done();
        } else if (j && j.error === 'too_many') { sendError('Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.'); }
        else if (j && j.error === 'csrf') { sendError('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'); }
        else if (j && j.error === 'empty') { /* abaikan */ }
        else { sendError('Pesan gagal terkirim. Coba lagi.'); }
      }).catch(function () { sendError('Pesan gagal terkirim. Periksa koneksi lalu coba lagi.'); })
        .then(function () { sending = false; if (sendBtn) sendBtn.disabled = false; });
    }

    function grow() {
      input.style.height = 'auto';
      input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }
    input.addEventListener('input', grow);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true })); }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var text = input.value.trim();
      if (!text || sending) return;
      if (text.length > maxLen) text = text.slice(0, maxLen);
      if (sendBtn) sendBtn.disabled = true;
      send({ action: 'send', body: text }, function () { input.value = ''; grow(); input.focus(); });
    });

    if (prodSend) prodSend.addEventListener('click', function () {
      prodSend.disabled = true;
      send({ action: 'send_product' }, function () { if (prodBar) prodBar.classList.add('is-sent'); });
      setTimeout(function () { prodSend.disabled = false; }, 800);
    });
    if (prodX) prodX.addEventListener('click', function () { prodBar.hidden = true; });

    poll().then(schedule);
  }

  /* ------------------------------------------------------------------ daftar percakapan (admin) */
  var list = document.getElementById('tz-chatlist');
  if (list) {
    var lapi = list.getAttribute('data-api');
    var activeId = list.getAttribute('data-active') || '';
    var qInput = document.getElementById('tz-chatsearch');
    var ltimer = null, lbusy = false, lastSig = '';

    function renderList(threads) {
      var sig = JSON.stringify(threads) + '|' + activeId;
      if (sig === lastSig) return;
      lastSig = sig;
      list.textContent = '';
      if (!threads.length) {
        list.appendChild(el('p', 'tz-chatlist__empty', qInput && qInput.value.trim() ? 'Tidak ada percakapan yang cocok.' : 'Belum ada percakapan.'));
        return;
      }
      threads.forEach(function (t) {
        var a = el('a', 'tz-chatrow' + (t.unread > 0 ? ' is-unread' : '') + (String(t.id) === activeId ? ' is-active' : ''));
        a.href = '?thread=' + t.id;
        var img = el('img'); img.src = t.image; img.alt = ''; img.loading = 'lazy'; a.appendChild(img);
        var txt = el('span', 'tz-chatrow__txt');
        txt.appendChild(el('b', null, t.user));
        txt.appendChild(el('em', null, t.product));
        txt.appendChild(el('small', null, (t.sender === 'admin' ? 'Anda: ' : '') + t.preview));
        a.appendChild(txt);
        var meta = el('span', 'tz-chatrow__meta');
        meta.appendChild(el('time', null, t.time));
        if (t.unread > 0) meta.appendChild(el('i', null, t.unread > 9 ? '9+' : String(t.unread)));
        a.appendChild(meta);
        list.appendChild(a);
      });
    }
    function loadList() {
      if (lbusy) return Promise.resolve();
      lbusy = true;
      var q = qInput ? qInput.value.trim() : '';
      return getJson(withQuery(lapi, 'action=threads&q=' + encodeURIComponent(q)))
        .then(function (d) { renderList(d.threads || []); })
        .catch(function () {})
        .then(function () { lbusy = false; });
    }
    function lschedule() {
      clearTimeout(ltimer);
      ltimer = setTimeout(function () { loadList().then(lschedule); }, document.hidden ? 20000 : 8000);
    }
    if (qInput) {
      var qt = null;
      qInput.addEventListener('input', function () { clearTimeout(qt); qt = setTimeout(function () { lastSig = ''; loadList(); }, 250); });
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) loadList().then(lschedule); });
    loadList().then(lschedule);
  }
})();
