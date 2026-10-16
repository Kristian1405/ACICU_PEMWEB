/* =========================
   FILE: acicu-midtrans.js
   Pembayaran QRIS otomatis via Midtrans Snap.

   Alur:
     1. Klik "Bayar via QRIS" -> minta Snap token dari server Laravel.
     2. snap.pay(token) -> Midtrans menampilkan QRIS (Scan untuk membayar).
     3. Hasil (sukses/pending/gagal) diteruskan ke callback halaman.

   Catatan: Server Key TIDAK ada di file ini; hanya Client Key (publik).
   Status akhir yang tersimpan di server/Firestore ditentukan oleh webhook
   Midtrans -> PaymentController::notification().
   ========================= */

(function () {
  "use strict";

  var cfg = window.ACICU_CONFIG || {};
  var endpoints = cfg.endpoints || {};
  var midtrans = cfg.midtrans || {};

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  }

  function buildPayload(order) {
    var recipient = order.recipient || {};

    return {
      order_id: order.id,
      gross_amount: Number(order.total) || 0,
      items: (order.items || []).map(function (i) {
        return {
          id: i.id,
          name: i.name,
          variant: i.variant || "",
          price: Number(i.price) || 0,
          quantity: Number(i.qty) || 1,
        };
      }),
      customer: {
        first_name: recipient.nama || "",
        phone: recipient.whatsapp || "",
        address: recipient.alamat || "",
      },
    };
  }

  /**
   * Minta server memverifikasi status transaksi ke Midtrans (fallback webhook).
   * Server yang menulis status ke Firestore (klien tidak diizinkan oleh rules).
   */
  function verify(orderId) {
    if (!endpoints.verify || !orderId) {
      return Promise.resolve({});
    }

    return fetch(endpoints.verify, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: JSON.stringify({ order_id: orderId }),
    })
      .then(function (res) {
        return res.json().catch(function () {
          return {};
        });
      })
      .catch(function () {
        return {};
      });
  }

  /**
   * @param {object} order  Pesanan dari localStorage.
   * @param {(status: string) => void} onResult  finish() milik acicu-checkout.js
   */
  function init(order, onResult) {
    var btn = document.getElementById("btnPayQris");
    if (!btn) return;

    if (!midtrans.clientKey) {
      btn.disabled = true;
      btn.title = "Client Key Midtrans belum dikonfigurasi.";
      return;
    }

    var original = btn.innerHTML;

    function restore() {
      btn.disabled = false;
      btn.innerHTML = original;
    }

    function fail(message) {
      restore();
      if (typeof onResult === "function") {
        onResult("gagal", message);
      }
    }

    btn.addEventListener("click", function () {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan QRIS...';

      fetch(endpoints.snapToken, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN": csrfToken(),
        },
        body: JSON.stringify(buildPayload(order)),
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data.token) {
            throw new Error(result.data.message || "Gagal membuat transaksi pembayaran.");
          }

          if (typeof window.snap === "undefined") {
            throw new Error("Snap.js belum termuat. Periksa Client Key Midtrans.");
          }

          window.snap.pay(result.data.token, {
            onSuccess: function () {
              verify(order.id).then(function (data) {
                var status = data && data.paymentStatus ? data.paymentStatus : "berhasil";
                if (typeof onResult === "function") onResult(status);
              });
            },
            onPending: function () {
              if (typeof onResult === "function") onResult("menunggu");
            },
            onError: function () {
              fail("Pembayaran gagal diproses Midtrans.");
            },
            onClose: function () {
              restore();
            },
          });
        })
        .catch(function (e) {
          fail(e.message || "Terjadi kesalahan saat menghubungi Midtrans.");
        });
    });
  }

  window.AcicuMidtrans = { init: init };
})();
