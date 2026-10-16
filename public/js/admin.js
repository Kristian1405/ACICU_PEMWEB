/* =========================
   FILE: admin.js
   Perilaku UI halaman Admin ACICU.

   Prinsip: TIDAK ada fitur yang berpura-pura berhasil.
   Setiap aksi yang belum didukung backend menampilkan pemberitahuan
   yang jujur kepada pengguna.
   ========================= */
(function () {
  "use strict";

  /* ---------- Toast ---------- */
  var toastTimer = null;

  function showToast(message) {
    var el = document.getElementById("adminToast");
    if (!el) {
      window.alert(message);
      return;
    }
    el.textContent = message;
    el.classList.add("show");
    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      el.classList.remove("show");
    }, 3200);
  }

  window.AdminUI = { toast: showToast };

  /* ---------- Sidebar (mobile) ---------- */
  function initSidebar() {
    var sidebar = document.getElementById("adminSidebar");
    var toggle = document.getElementById("adminMenuToggle");
    var overlay = document.getElementById("adminOverlay");
    if (!sidebar) return;

    function open() {
      sidebar.classList.add("open");
      if (overlay) overlay.classList.add("show");
    }
    function close() {
      sidebar.classList.remove("open");
      if (overlay) overlay.classList.remove("show");
    }

    if (toggle) {
      toggle.addEventListener("click", function () {
        if (sidebar.classList.contains("open")) close();
        else open();
      });
    }
    if (overlay) overlay.addEventListener("click", close);
  }

  /* ---------- Elemen ber-atribut khusus ---------- */
  function initDisabledActions() {
    // data-admin-disabled="pesan" -> tampilkan pesan, jangan lakukan apa pun.
    document.querySelectorAll("[data-admin-disabled]").forEach(function (el) {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        showToast(el.getAttribute("data-admin-disabled"));
      });
    });
  }

  function initLogout() {
    var btn = document.getElementById("adminLogout");
    if (!btn) return;
    btn.addEventListener("click", function () {
      showToast(
        "Belum ada sesi admin. Logout aktif setelah autentikasi admin terintegrasi.",
      );
    });
  }

  function initGlobalSearch() {
    document.querySelectorAll("[data-admin-global-search]").forEach(function (input) {
      input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          showToast(
            "Pencarian global belum tersedia karena data admin belum terhubung ke backend.",
          );
        }
      });
    });
  }

  /* ---------- Password toggle (login) ---------- */
  function initPasswordToggle() {
    var btn = document.getElementById("togglePassword");
    var input = document.getElementById("loginPassword");
    if (!btn || !input) return;
    btn.addEventListener("click", function () {
      var shown = input.type === "text";
      input.type = shown ? "password" : "text";
      btn.innerHTML = shown
        ? '<i class="fa-regular fa-eye"></i>'
        : '<i class="fa-regular fa-eye-slash"></i>';
      btn.setAttribute(
        "aria-label",
        shown ? "Tampilkan kata sandi" : "Sembunyikan kata sandi",
      );
    });
  }

  /* ---------- Login form ---------- */
  function initLoginForm() {
    var form = document.getElementById("adminLoginForm");
    if (!form) return;
    var notice = document.getElementById("authNotice");

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var user = document.getElementById("loginUser");
      var pass = document.getElementById("loginPassword");

      if (!user.value.trim() || !pass.value.trim()) {
        if (notice) {
          notice.textContent =
            "Username/email dan kata sandi wajib diisi terlebih dahulu.";
          notice.classList.add("show");
        }
        return;
      }

      // Integrasi autentikasi belum tersedia -> jangan mengaku berhasil.
      if (notice) {
        notice.innerHTML =
          "<strong>Autentikasi admin belum diintegrasikan.</strong><br />" +
          "Form ini belum terhubung ke backend, sehingga login belum dapat " +
          "diproses. Integrasi ke sistem autentikasi Laravel (Fortify) akan " +
          "dikerjakan pada tahap berikutnya.";
        notice.classList.add("show");
      }
      showToast("Login belum tersedia — backend autentikasi belum diintegrasikan.");
    });
  }

  /* ---------- Filter pencarian tabel (client-side) ---------- */
  function initTableSearch() {
    document.querySelectorAll("[data-table-search]").forEach(function (input) {
      var targetId = input.getAttribute("data-table-search");
      var table = document.getElementById(targetId);
      if (!table) return;
      var tbody = table.querySelector("tbody");
      if (!tbody) return;

      input.addEventListener("input", function () {
        var q = input.value.trim().toLowerCase();
        var rows = tbody.querySelectorAll("tr[data-row]");
        var visible = 0;

        rows.forEach(function (row) {
          var match = row.textContent.toLowerCase().indexOf(q) !== -1;
          row.style.display = match ? "" : "none";
          if (match) visible++;
        });

        // Tampilkan/sembunyikan baris "kosong" bawaan
        var emptyRow = tbody.querySelector("tr[data-empty]");
        if (emptyRow) {
          var totalRows = rows.length;
          emptyRow.style.display = totalRows === 0 || visible === 0 ? "" : "none";
          var msg = emptyRow.querySelector("[data-empty-text]");
          if (msg) {
            msg.textContent =
              totalRows === 0
                ? msg.getAttribute("data-default") || msg.textContent
                : "Tidak ada hasil yang cocok dengan pencarian.";
          }
        }
      });
    });
  }

  /* ---------- Filter pilihan (pills / select) ---------- */
  function initChoiceFilter() {
    document.querySelectorAll("[data-filter-group]").forEach(function (group) {
      var targetId = group.getAttribute("data-filter-group");
      var table = document.getElementById(targetId);
      var buttons = group.querySelectorAll("[data-filter-value]");
      if (!table || !buttons.length) return;
      var tbody = table.querySelector("tbody");

      buttons.forEach(function (btn) {
        btn.addEventListener("click", function () {
          buttons.forEach(function (b) {
            b.classList.remove("active");
          });
          btn.classList.add("active");
          var value = btn.getAttribute("data-filter-value").toLowerCase();

          var rows = tbody.querySelectorAll("tr[data-row]");
          var visible = 0;
          rows.forEach(function (row) {
            var category = (row.getAttribute("data-category") || "").toLowerCase();
            var match = value === "all" || value === "" || category === value;
            row.style.display = match ? "" : "none";
            if (match) visible++;
          });

          var emptyRow = tbody.querySelector("tr[data-empty]");
          if (emptyRow) {
            emptyRow.style.display = rows.length === 0 || visible === 0 ? "" : "none";
          }
        });
      });
    });
  }

  /* ---------- Tombol penyegaran ---------- */
  function initRefresh() {
    document.querySelectorAll("[data-admin-refresh]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        window.location.reload();
      });
    });
  }

  /* ---------- Export CSV (native, tanpa dependensi) ---------- */
  function csvEscape(value) {
    var v = String(value == null ? "" : value).replace(/\s+/g, " ").trim();
    if (/[",;]/.test(v)) {
      v = '"' + v.replace(/"/g, '""') + '"';
    }
    return v;
  }

  function initCsvExport() {
    document.querySelectorAll("[data-export-csv]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var tableId = btn.getAttribute("data-export-csv");
        var filename = btn.getAttribute("data-export-filename") || "export.csv";
        var table = document.getElementById(tableId);
        if (!table) {
          showToast("Tidak ada tabel untuk diekspor.");
          return;
        }

        var headers = [];
        table.querySelectorAll("thead th").forEach(function (th) {
          headers.push(csvEscape(th.textContent));
        });

        var lines = [headers.join(",")];
        table.querySelectorAll("tbody tr[data-row]").forEach(function (row) {
          if (row.style.display === "none") return;
          var cells = [];
          row.querySelectorAll("td").forEach(function (td) {
            cells.push(csvEscape(td.innerText));
          });
          lines.push(cells.join(","));
        });

        // BOM agar Excel membaca UTF-8 dengan benar
        var blob = new Blob(["\uFEFF" + lines.join("\r\n")], {
          type: "text/csv;charset=utf-8;",
        });
        var url = URL.createObjectURL(blob);
        var a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showToast("File CSV berhasil dibuat (" + (lines.length - 1) + " baris data).");
      });
    });
  }

  /* ---------- Filter tanggal laporan ---------- */
  function initReportFilters() {
    var applyBtn = document.getElementById("reportApply");
    var resetBtn = document.getElementById("reportReset");
    if (!applyBtn && !resetBtn) return;

    if (applyBtn) {
      applyBtn.addEventListener("click", function () {
        showToast(
          "Filter tanggal belum menyaring data karena belum ada transaksi pada backend.",
        );
      });
    }
    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        var from = document.getElementById("reportFrom");
        var to = document.getElementById("reportTo");
        if (from) from.value = "";
        if (to) to.value = "";
        showToast("Filter tanggal direset.");
      });
    }
  }

  /* ---------- Segmented toggle (dashboard) ---------- */
  function initSegmented() {
    document.querySelectorAll("[data-segmented]").forEach(function (group) {
      var buttons = group.querySelectorAll("button");
      buttons.forEach(function (btn) {
        btn.addEventListener("click", function () {
          buttons.forEach(function (b) {
            b.classList.remove("active");
          });
          btn.classList.add("active");
        });
      });
    });
  }

  /* ---------- Boot ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    initSidebar();
    initDisabledActions();
    initLogout();
    initGlobalSearch();
    initPasswordToggle();
    initLoginForm();
    initTableSearch();
    initChoiceFilter();
    initRefresh();
    initCsvExport();
    initReportFilters();
    initSegmented();
  });
})();
