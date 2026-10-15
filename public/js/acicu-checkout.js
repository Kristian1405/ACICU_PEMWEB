/* =========================
   FILE: acicu-checkout.js
   Cart + Pre-Order + Simulasi Pembayaran + Status Pesanan
   Semua data disimpan di localStorage (browser), BUKAN server.
   ========================= */

(function () {
  "use strict";

  const CART_KEY = "acicu_cart_v1";
  const ORDER_KEY = "acicu_order_v1";

  /* ---------- Safe localStorage helpers ---------- */
  function safeRead(key, fallback) {
    try {
      const raw = window.localStorage.getItem(key);
      if (!raw) return fallback;
      const parsed = JSON.parse(raw);
      return parsed == null ? fallback : parsed;
    } catch (e) {
      return fallback;
    }
  }

  function safeWrite(key, value) {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
      /* storage penuh / diblokir - abaikan dengan aman */
    }
  }

  /* ---------- Formatting ---------- */
  function formatRupiah(num) {
    const n = Number(num) || 0;
    return "Rp" + n.toLocaleString("id-ID");
  }

  /* Format waktu ke zona Asia/Jakarta (WIB) */
  function formatWIB(date) {
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d.getTime())) return "-";
    try {
      return new Intl.DateTimeFormat("id-ID", {
        timeZone: "Asia/Jakarta",
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      }).format(d) + " WIB";
    } catch (e) {
      return d.toLocaleString("id-ID");
    }
  }

  function formatJamWIB(date) {
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d.getTime())) return "-";
    try {
      return new Intl.DateTimeFormat("id-ID", {
        timeZone: "Asia/Jakarta",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      }).format(d) + " WIB";
    } catch (e) {
      return "-";
    }
  }

  /* ---------- Cart ---------- */
  const Cart = {
    get() {
      const data = safeRead(CART_KEY, []);
      return Array.isArray(data) ? data : [];
    },
    save(items) {
      safeWrite(CART_KEY, items);
    },
    add(item) {
      const items = Cart.get();
      const existing = items.find((i) => i.id === item.id && i.variant === (item.variant || ""));
      if (existing) {
        existing.qty += item.qty || 1;
      } else {
        items.push({
          id: item.id,
          name: item.name,
          price: Number(item.price) || 0,
          image: item.image || "",
          variant: item.variant || "",
          qty: item.qty || 1,
        });
      }
      Cart.save(items);
      return items;
    },
    updateQty(id, variant, delta) {
      const items = Cart.get();
      const it = items.find((i) => i.id === id && i.variant === (variant || ""));
      if (!it) return items;
      it.qty += delta;
      if (it.qty < 1) it.qty = 1;
      Cart.save(items);
      return items;
    },
    remove(id, variant) {
      const items = Cart.get().filter((i) => !(i.id === id && i.variant === (variant || "")));
      Cart.save(items);
      return items;
    },
    clear() {
      Cart.save([]);
    },
    count() {
      return Cart.get().reduce((sum, i) => sum + (Number(i.qty) || 0), 0);
    },
    subtotal() {
      return Cart.get().reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.qty) || 0), 0);
    },
  };

  /* ---------- Order store ---------- */
  const Order = {
    current() {
      const o = safeRead(ORDER_KEY, null);
      return o && typeof o === "object" ? o : null;
    },
    save(order) {
      safeWrite(ORDER_KEY, order);
    },
    clear() {
      try {
        window.localStorage.removeItem(ORDER_KEY);
      } catch (e) {
        /* ignore */
      }
    },
  };

  function generateOrderId() {
    const n = Math.floor(1000 + Math.random() * 9000);
    return "PO-" + n;
  }

  /* Hitung jadwal persiapan dapur = 2 jam sebelum slot pengantaran */
  function computeKitchenSchedule(slot) {
    const map = { "11:00": "09:00", "15:00": "13:00" };
    return map[slot] || "-";
  }

  /* ---------- Render helpers ---------- */
  function renderCartPanel() {
    const body = document.getElementById("cartBody");
    const footer = document.getElementById("cartFooter");
    const subtotalEl = document.getElementById("cartSubtotal");
    if (!body) return;

    const items = Cart.get();

    if (items.length === 0) {
      body.innerHTML =
        '<div class="cart-empty">' +
        '<i class="fa-solid fa-cart-shopping"></i>' +
        "<p>Keranjang masih kosong</p>" +
        '<span>Yuk pilih camilan favoritmu di menu!</span>' +
        "</div>";
      if (footer) footer.style.display = "none";
      updateBadge();
      return;
    }

    let html = "";
    items.forEach(function (i) {
      const key = i.id + "|" + (i.variant || "");
      html +=
        '<div class="cart-item" data-key="' + key + '">' +
        '<img src="' + i.image + '" alt="' + i.name + '" class="cart-item-img" />' +
        '<div class="cart-item-info">' +
        '<h4>' + i.name + "</h4>" +
        (i.variant ? '<span class="cart-item-variant">' + i.variant + "</span>" : "") +
        '<span class="cart-item-price">' + formatRupiah(i.price) + "</span>" +
        '<div class="cart-item-qty">' +
        '<button class="qty-btn" data-action="dec" data-id="' + i.id + '" data-variant="' + (i.variant || "") + '">-</button>' +
        '<span class="qty-val">' + i.qty + "</span>" +
        '<button class="qty-btn" data-action="inc" data-id="' + i.id + '" data-variant="' + (i.variant || "") + '">+</button>' +
        "</div>" +
        "</div>" +
        '<button class="cart-item-remove" data-action="remove" data-id="' + i.id + '" data-variant="' + (i.variant || "") + '">' +
        '<i class="fa-solid fa-trash"></i>' +
        "</button>" +
        '<div class="cart-item-line">' + formatRupiah(i.price * i.qty) + "</div>" +
        "</div>";
    });

    body.innerHTML = html;
    if (footer) footer.style.display = "block";
    if (subtotalEl) subtotalEl.textContent = formatRupiah(Cart.subtotal());
    updateBadge();
  }

  function updateBadge() {
    const badge = document.getElementById("cartBadge");
    if (!badge) return;
    const c = Cart.count();
    if (c > 0) {
      badge.textContent = c;
      badge.style.display = "flex";
    } else {
      badge.style.display = "none";
    }
  }

  function openCart() {
    const panel = document.getElementById("cartPanel");
    const overlay = document.getElementById("cartOverlay");
    if (panel) panel.classList.add("open");
    if (overlay) overlay.classList.add("open");
  }

  function closeCart() {
    const panel = document.getElementById("cartPanel");
    const overlay = document.getElementById("cartOverlay");
    if (panel) panel.classList.remove("open");
    if (overlay) overlay.classList.remove("open");
  }

  /* ---------- Cart page wiring ---------- */
  function initCartUI() {
    const cartToggle = document.getElementById("cartToggle");
    const cartClose = document.getElementById("cartClose");
    const overlay = document.getElementById("cartOverlay");
    const cartBody = document.getElementById("cartBody");

    if (cartToggle) cartToggle.addEventListener("click", openCart);
    if (cartClose) cartClose.addEventListener("click", closeCart);
    if (overlay) overlay.addEventListener("click", closeCart);

    if (cartBody) {
      cartBody.addEventListener("click", function (e) {
        const btn = e.target.closest("button[data-action]");
        if (!btn) return;
        const action = btn.getAttribute("data-action");
        const id = btn.getAttribute("data-id");
        const variant = btn.getAttribute("data-variant") || "";
        if (action === "inc") Cart.updateQty(id, variant, 1);
        else if (action === "dec") Cart.updateQty(id, variant, -1);
        else if (action === "remove") Cart.remove(id, variant);
        renderCartPanel();
      });
    }

    document.querySelectorAll(".add-to-cart").forEach(function (btn) {
      btn.addEventListener("click", function () {
        Cart.add({
          id: btn.getAttribute("data-id"),
          name: btn.getAttribute("data-name"),
          price: btn.getAttribute("data-price"),
          image: btn.getAttribute("data-image"),
          variant: btn.getAttribute("data-variant") || "",
          qty: 1,
        });
        renderCartPanel();
        openCart();

        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Ditambahkan';
        btn.classList.add("added");
        btn.disabled = true;
        setTimeout(function () {
          btn.innerHTML = orig;
          btn.classList.remove("added");
          btn.disabled = false;
        }, 900);
      });
    });

    renderCartPanel();
    updateBadge();
  }

  /* ---------- Pre-Order page ---------- */
  function initPreorder() {
    const form = document.getElementById("preorderForm");
    const summaryEl = document.getElementById("orderSummary");
    const subtotalEl = document.getElementById("summarySubtotal");
    const totalEl = document.getElementById("summaryTotal");
    const btnSubmit = document.getElementById("btnSubmitOrder");

    const items = Cart.get();

    if (items.length === 0) {
      if (summaryEl) {
        summaryEl.innerHTML =
          '<div class="cart-empty small">' +
          '<i class="fa-solid fa-cart-shopping"></i>' +
          "<p>Belum ada item</p>" +
          '<span>Tambahkan camilan dari menu terlebih dahulu.</span>' +
          "</div>";
      }
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Keranjang Kosong";
      }
      return;
    }

    let html = "";
    items.forEach(function (i) {
      html +=
        '<div class="summary-item">' +
        '<img src="' + i.image + '" alt="' + i.name + '" />' +
        '<div class="summary-item-info">' +
        "<h4>" + i.name + "</h4>" +
        (i.variant ? '<span class="summary-item-variant">' + i.variant + "</span>" : "") +
        '<span class="summary-item-qty">' + i.qty + " x " + formatRupiah(i.price) + "</span>" +
        "</div>" +
        '<span class="summary-item-line">' + formatRupiah(i.price * i.qty) + "</span>" +
        "</div>";
    });
    if (summaryEl) summaryEl.innerHTML = html;

    const subtotal = Cart.subtotal();
    if (subtotalEl) subtotalEl.textContent = formatRupiah(subtotal);
    if (totalEl) totalEl.textContent = formatRupiah(subtotal);

    /* Validasi form */
    function clearErrors() {
      ["errNama", "errWa", "errAlamat", "errTanggal", "errSlot"].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.textContent = "";
      });
      form.classList.remove("has-errors");
    }

    function validate() {
      clearErrors();
      let ok = true;
      const nama = document.getElementById("nama");
      const wa = document.getElementById("whatsapp");
      const alamat = document.getElementById("alamat");
      const tanggal = document.getElementById("tanggal");
      const slot = document.getElementById("slot");

      if (!nama.value.trim()) {
        document.getElementById("errNama").textContent = "Nama wajib diisi.";
        ok = false;
      }
      const waVal = wa.value.replace(/\s|-/g, "");
      if (!waVal || !/^(0|62|\+62)\d{8,13}$/.test(waVal)) {
        document.getElementById("errWa").textContent = "Nomor WhatsApp tidak valid.";
        ok = false;
      }
      if (!alamat.value.trim() || alamat.value.trim().length < 8) {
        document.getElementById("errAlamat").textContent = "Alamat wajib diisi (minimal 8 karakter).";
        ok = false;
      }
      if (!tanggal.value) {
        document.getElementById("errTanggal").textContent = "Tanggal wajib dipilih.";
        ok = false;
      }
      if (!slot.value) {
        document.getElementById("errSlot").textContent = "Slot pengantaran wajib dipilih.";
        ok = false;
      }
      return ok;
    }

    /* Default tanggal = hari ini */
    const tgl = document.getElementById("tanggal");
    if (tgl && !tgl.value) {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, "0");
      const d = String(now.getDate()).padStart(2, "0");
      tgl.min = y + "-" + m + "-" + d;
    }

    if (btnSubmit) {
      btnSubmit.addEventListener("click", function () {
        if (!validate()) {
          const firstErr = form.querySelector(".error-msg:not(:empty)");
          if (firstErr) firstErr.scrollIntoView({ behavior: "smooth", block: "center" });
          return;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

        const order = {
          id: generateOrderId(),
          items: Cart.get(),
          subtotal: subtotal,
          ongkir: 0,
          total: subtotal,
          recipient: {
            nama: document.getElementById("nama").value.trim(),
            whatsapp: document.getElementById("whatsapp").value.trim(),
            alamat: document.getElementById("alamat").value.trim(),
            catatan: document.getElementById("catatan").value.trim(),
          },
          delivery: {
            tanggal: document.getElementById("tanggal").value,
            slot: document.getElementById("slot").value,
          },
          kitchenSchedule: computeKitchenSchedule(document.getElementById("slot").value),
          orderedAt: new Date().toISOString(),
          paymentStatus: "menunggu",
        };

        Order.save(order);

        setTimeout(function () {
          Cart.clear();
          window.location.href = "/acicu/pembayaran";
        }, 500);
      });
    }
  }

  /* ---------- Payment page ---------- */
  function initPayment() {
    const order = Order.current();

    if (!order) {
      window.location.href = "/acicu/pre-order";
      return;
    }

    const idEl = document.getElementById("payOrderId");
    if (idEl) idEl.textContent = order.id;

    const summaryEl = document.getElementById("payOrderSummary");
    if (summaryEl) summaryEl.innerHTML = renderItemsHtml(order.items);

    const totalEl = document.getElementById("payTotal");
    if (totalEl) totalEl.textContent = formatRupiah(order.total);

    const recipEl = document.getElementById("payRecipient");
    if (recipEl) {
      recipEl.innerHTML =
        "<p><strong>" + order.recipient.nama + "</strong></p>" +
        "<p>" + order.recipient.whatsapp + "</p>" +
        "<p>" + order.recipient.alamat + "</p>" +
        "<p><i class='fa-solid fa-calendar'></i> " + formatTanggalIndo(order.delivery.tanggal) +
        " &middot; Slot " + order.delivery.slot + " WIB</p>";
    }

    document.querySelectorAll(".payment-method").forEach(function (label) {
      label.addEventListener("click", function () {
        document.querySelectorAll(".payment-method").forEach((l) => l.classList.remove("selected"));
        label.classList.add("selected");
      });
    });

    const statusEl = document.getElementById("paymentStatus");
    const actionsEl = document.getElementById("paymentActions");
    const resultEl = document.getElementById("paymentResult");

    function finish(status) {
      order.paymentStatus = status;
      Order.save(order);

      if (actionsEl) actionsEl.style.display = "none";
      if (statusEl) statusEl.style.display = "none";
      if (resultEl) {
        resultEl.style.display = "block";
        if (status === "berhasil") {
          resultEl.innerHTML =
            '<div class="result-box success">' +
            '<i class="fa-solid fa-circle-check"></i>' +
            "<h3>Simulasi Pembayaran Berhasil</h3>" +
            "<p>Ini hanya simulasi frontend. Status disimpan di browser, bukan server.</p>" +
            '<a href="/acicu/status" class="btn-submit" style="display:block;text-align:center;text-decoration:none;margin-top:12px;">Lihat Status Pesanan</a>' +
            "</div>";
        } else {
          resultEl.innerHTML =
            '<div class="result-box failed">' +
            '<i class="fa-solid fa-circle-xmark"></i>' +
            "<h3>Simulasi Pembayaran Gagal / Dibatalkan</h3>" +
            "<p>Pesanan tidak diproses. Kamu bisa mencoba lagi dari formulir.</p>" +
            '<a href="/acicu/pre-order" class="btn-back" style="display:block;text-align:center;text-decoration:none;margin-top:12px;">Coba Lagi</a>' +
            "</div>";
        }
      }
    }

    const btnSuccess = document.getElementById("btnPaySuccess");
    const btnFail = document.getElementById("btnPayFail");
    if (btnSuccess) btnSuccess.addEventListener("click", () => finish("berhasil"));
    if (btnFail) btnFail.addEventListener("click", () => finish("gagal"));
  }

  /* ---------- Status page ---------- */
  function initStatus() {
    const order = Order.current();

    if (!order) {
      const ids = document.getElementById("statusOrderId");
      if (ids) ids.textContent = "Tidak ada pesanan";
      const tl = document.getElementById("statusTimeline");
      if (tl)
        tl.innerHTML =
          '<p class="empty-status">Belum ada pesanan simulasi. <a href="/acicu">Mulai dari menu</a>.</p>';
      const badge = document.getElementById("statusBadgeContainer");
      if (badge) badge.innerHTML = "";
      return;
    }

    const idEl = document.getElementById("statusOrderId");
    if (idEl) idEl.textContent = order.id;

    const itemsEl = document.getElementById("statusOrderItems");
    if (itemsEl) itemsEl.innerHTML = renderItemsHtml(order.items);

    const totalEl = document.getElementById("statusTotal");
    if (totalEl) totalEl.textContent = formatRupiah(order.total);

    const recipEl = document.getElementById("statusRecipient");
    if (recipEl) {
      recipEl.innerHTML =
        "<p><strong>" + order.recipient.nama + "</strong></p>" +
        "<p>" + order.recipient.whatsapp + "</p>" +
        "<p>" + order.recipient.alamat + "</p>" +
        (order.recipient.catatan ? "<p class='note'>Catatan: " + order.recipient.catatan + "</p>" : "");
    }

    /* Badge status pembayaran (simulasi) */
    const badgeEl = document.getElementById("statusBadgeContainer");
    if (badgeEl) {
      let cls = "pending",
        icon = "fa-clock",
        text = "Menunggu Pembayaran";
      if (order.paymentStatus === "berhasil") {
        cls = "success";
        icon = "fa-circle-check";
        text = "Pembayaran Berhasil (Simulasi)";
      } else if (order.paymentStatus === "gagal") {
        cls = "failed";
        icon = "fa-circle-xmark";
        text = "Pembayaran Gagal (Simulasi)";
      }
      badgeEl.innerHTML =
        '<div class="status-badge ' + cls + '">' +
        '<i class="fa-solid ' + icon + '"></i> ' + text +
        "</div>";
    }

    /* Timeline rencana (bukan status operasional nyata) */
    const tl = document.getElementById("statusTimeline");
    if (tl) {
      const orderedAt = formatWIB(order.orderedAt);
      const deliveryDate = formatTanggalIndo(order.delivery.tanggal);
      tl.innerHTML =
        '<h3><i class="fa-solid fa-calendar-days"></i> Jadwal Rencana Simulasi</h3>' +
        '<p class="timeline-note">' +
        "Ini estimasi jadwal, bukan status operasional dapur. Dapur tidak benar-benar " +
        "memproses pesanan karena belum ada backend." +
        "</p>" +
        '<div class="timeline-list">' +
        timelineRow("check", "Pesanan dibuat (simulasi)", orderedAt) +
        timelineRow("list", "Persiapan dapur dijadwalkan", order.kitchenSchedule + " WIB (" + deliveryDate + ")") +
        timelineRow("truck", "Slot pengantaran", order.delivery.slot + " WIB (" + deliveryDate + ")") +
        "</div>";
    }

    const btnNew = document.getElementById("btnNewOrder");
    if (btnNew) {
      btnNew.addEventListener("click", function () {
        Order.clear();
        Cart.clear();
        window.location.href = "/acicu";
      });
    }
  }

  function timelineRow(icon, title, time) {
    return (
      '<div class="timeline-row">' +
      '<span class="timeline-dot"><i class="fa-solid fa-' + icon + '"></i></span>' +
      '<div class="timeline-content"><strong>' + title + "</strong><span>" + time + "</span></div>" +
      "</div>"
    );
  }

  function renderItemsHtml(items) {
    if (!items || !items.length) return "";
    let html = "";
    items.forEach(function (i) {
      html +=
        '<div class="summary-item">' +
        '<img src="' + i.image + '" alt="' + i.name + '" />' +
        '<div class="summary-item-info">' +
        "<h4>" + i.name + "</h4>" +
        (i.variant ? '<span class="summary-item-variant">' + i.variant + "</span>" : "") +
        '<span class="summary-item-qty">' + i.qty + " x " + formatRupiah(i.price) + "</span>" +
        "</div>" +
        '<span class="summary-item-line">' + formatRupiah(i.price * i.qty) + "</span>" +
        "</div>";
    });
    return html;
  }

  function formatTanggalIndo(isoDate) {
    if (!isoDate) return "-";
    const parts = isoDate.split("-");
    if (parts.length !== 3) return isoDate;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return new Intl.DateTimeFormat("id-ID", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric",
    }).format(d);
  }

  /* ---------- Public API ---------- */
  window.AcicuCheckout = {
    Cart: Cart,
    Order: Order,
    formatRupiah: formatRupiah,
    formatWIB: formatWIB,
    initCart: initCartUI,
    initPreorder: initPreorder,
    initPayment: initPayment,
    initStatus: initStatus,
  };
})();
