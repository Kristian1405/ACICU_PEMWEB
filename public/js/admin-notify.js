/* Notifikasi pesanan/pembayaran baru di panel admin:
   cek ringkasan tiap 45 detik -> toast + bunyi + judul tab "(n) ...". */
(function () {
  "use strict";
  var cfg = window.ACICU_ADMIN || {};
  if (!cfg.summaryUrl) return;

  var KEY = "acicu_admin_seen_v1";
  var baseTitle = document.title;
  var unread = 0;

  function readSeen() {
    try { return JSON.parse(localStorage.getItem(KEY) || "null"); } catch (e) { return null; }
  }
  function writeSeen(list) {
    try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
  }

  function beep() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      var ctx = new Ctx();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.frequency.value = 880; gain.gain.value = 0.15;
      osc.start(); osc.stop(ctx.currentTime + 0.25);
    } catch (e) {}
  }

  function toast(text) {
    var el = document.createElement("div");
    el.setAttribute("role", "status");
    el.style.cssText = "position:fixed;right:16px;bottom:16px;z-index:9999;background:#222;color:#fff;" +
      "padding:12px 16px;border-radius:10px;max-width:320px;font:14px Poppins,sans-serif;cursor:pointer;" +
      "box-shadow:0 6px 20px rgba(0,0,0,.25)";
    el.textContent = text;
    el.addEventListener("click", function () { window.location.href = cfg.ordersUrl; });
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 9000);
  }

  function rp(n) { return "Rp" + Number(n).toLocaleString("id-ID"); }

  function check() {
    fetch(cfg.summaryUrl, { headers: { Accept: "application/json" }, credentials: "same-origin" })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.events) return;
        var keys = data.events.map(function (e) { return e.key; });
        var seen = readSeen();
        if (seen === null) { writeSeen(keys); return; }  /* kunjungan pertama: tanpa bunyi */

        var fresh = data.events.filter(function (e) { return seen.indexOf(e.key) === -1; });
        if (fresh.length) {
          fresh.forEach(function (e) {
            toast(e.status === "berhasil"
              ? "Pembayaran masuk: " + e.id + " - " + e.nama + " (" + rp(e.total) + ")"
              : "Pesanan baru: " + e.id + " - " + e.nama + " (" + rp(e.total) + ")");
          });
          beep();
          unread += fresh.length;
          document.title = "(" + unread + ") " + baseTitle;
          writeSeen(keys.concat(seen).slice(0, 60));
        }
      })
      .catch(function () {});
  }

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) { unread = 0; document.title = baseTitle; }
  });

  check();
  setInterval(check, 45000);
})();
