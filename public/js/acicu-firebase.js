/* =========================
   FILE: acicu-firebase.js
   Inisialisasi Firebase (klien) + penyimpanan pesanan ke Cloud Firestore.

   Peran: halaman pre-order memanggil AcicuFirestore.saveOrder(order) untuk
   membuat dokumen di koleksi "orders". Pembacaan/validasi status dilakukan
   dari sisi server (Service Account) sehingga TIDAK bocor ke klien.

   Bila konfigurasi Firebase belum lengkap, modul ini non-aktif dan
   halaman tetap berjalan seperti semula (localStorage).
   ========================= */

(function () {
  "use strict";

  var cfg = (window.ACICU_CONFIG && window.ACICU_CONFIG.firebase) || null;
  var COLLECTION = "orders";
  var db = null;

  if (cfg && cfg.apiKey && cfg.projectId && window.firebase && window.firebase.firestore) {
    try {
      if (!window.firebase.apps.length) {
        window.firebase.initializeApp(cfg);
      }
      db = window.firebase.firestore();
    } catch (e) {
      console.warn("[ACICU] Firebase gagal diinisialisasi:", e);
      db = null;
    }
  }

  function str(v) {
    return v == null ? "" : String(v);
  }

  function num(v) {
    return Number(v) || 0;
  }

  /* Bentuk dokumen Firestore (hindari nilai undefined agar tidak error). */
  function toDocument(order) {
    var recipient = order.recipient || {};
    var delivery = order.delivery || {};

    return {
      orderId: str(order.id),
      items: (order.items || []).map(function (i) {
        return {
          id: str(i.id),
          name: str(i.name),
          variant: str(i.variant),
          price: num(i.price),
          qty: num(i.qty),
        };
      }),
      subtotal: num(order.subtotal),
      ongkir: num(order.ongkir),
      total: num(order.total),
      recipient: {
        nama: str(recipient.nama),
        whatsapp: str(recipient.whatsapp),
        alamat: str(recipient.alamat),
        catatan: str(recipient.catatan),
      },
      delivery: {
        tanggal: str(delivery.tanggal),
        slot: str(delivery.slot),
      },
      kitchenSchedule: str(order.kitchenSchedule),
      paymentStatus: str(order.paymentStatus || "menunggu"),
      source: "web",
      createdAt: order.orderedAt || new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };
  }

  /**
   * Simpan pesanan ke Firestore. Mengembalikan Promise<boolean>.
   */
  function saveOrder(order) {
    if (!db || !order || !order.id) {
      return Promise.resolve(false);
    }

    return db
      .collection(COLLECTION)
      .doc(order.id)
      .set(toDocument(order), { merge: true })
      .then(function () {
        return true;
      })
      .catch(function (e) {
        console.warn("[ACICU] Gagal menyimpan pesanan ke Firestore:", e);
        return false;
      });
  }

  /**
   * Ambil satu pesanan (dibatasi oleh Firestore Security Rules).
   */
  function getOrder(id) {
    if (!db || !id) return Promise.resolve(null);
    return db
      .collection(COLLECTION)
      .doc(id)
      .get()
      .then(function (doc) {
        return doc.exists ? doc.data() : null;
      })
      .catch(function () {
        return null;
      });
  }

  window.AcicuFirestore = {
    enabled: !!db,
    saveOrder: saveOrder,
    getOrder: getOrder,
  };
})();
