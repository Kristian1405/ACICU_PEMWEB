<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pre-Order - ACICU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Bagel+Fat+One&family=Poppins:wght@400;500;600;700&family=Baloo+2:wght@400;500;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/acicu-checkout.css') }}" />
  </head>
  <body>
    <header class="navbar">
      <div class="nav-left">
        <a href="/acicu" style="display:flex;align-items:center;gap:20px;text-decoration:none;">
          <img src="{{ asset('src-acicu/logo.jpg') }}" alt="Logo" class="logo" />
          <div class="logo-text">
            <h1>ACICU</h1>
            <p>Aneka Aci Bungcu</p>
          </div>
        </a>
      </div>
      <nav class="nav-menu" id="navMenu">
        <a href="/acicu" class="nav-link">Home</a>
        <a href="/acicu#menu" class="nav-link">Menu</a>
      </nav>
      <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
      </div>
    </header>

    <main class="checkout-page">
      <div class="checkout-container">
        <h1 class="checkout-title">Formulir Pre-Order</h1>

        <div class="checkout-grid">
          <section class="form-section">
            <h2><i class="fa-solid fa-user"></i> Data Penerima & Kontak</h2>
            <form id="preorderForm" novalidate>
              <div class="form-group">
                <label for="nama">Nama Penerima *</label>
                <input type="text" id="nama" required placeholder="Nama lengkap" />
                <span class="error-msg" id="errNama"></span>
              </div>
              <div class="form-group">
                <label for="whatsapp">Nomor WhatsApp *</label>
                <input type="tel" id="whatsapp" required placeholder="08xxxxxxxxxx" />
                <span class="error-msg" id="errWa"></span>
              </div>
              <div class="form-group">
                <label for="alamat">Alamat Pengantaran *</label>
                <textarea id="alamat" required rows="3" placeholder="Alamat lengkap"></textarea>
                <span class="error-msg" id="errAlamat"></span>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="tanggal">Tanggal Pengantaran *</label>
                  <input type="date" id="tanggal" required />
                  <span class="error-msg" id="errTanggal"></span>
                </div>
                <div class="form-group">
                  <label for="slot">Slot Pengantaran *</label>
                  <select id="slot" required>
                    <option value="">Pilih Slot</option>
                    <option value="11:00">11:00 WIB</option>
                    <option value="15:00">15:00 WIB</option>
                  </select>
                  <span class="error-msg" id="errSlot"></span>
                </div>
              </div>
              <div class="form-group">
                <label for="catatan">Catatan (opsional)</label>
                <textarea id="catatan" rows="2" placeholder="Catatan tambahan"></textarea>
              </div>
            </form>
          </section>

          <section class="summary-section">
            <h2><i class="fa-solid fa-receipt"></i> Ringkasan Pesanan</h2>
            <div id="orderSummary" class="order-summary"></div>
            <div class="summary-totals">
              <div class="summary-row">
                <span>Subtotal</span>
                <span id="summarySubtotal">Rp0</span>
              </div>
              <div class="summary-row">
                <span>Ongkos Kirim</span>
                <span id="summaryOngkir">Gratis</span>
              </div>
              <div class="summary-row total">
                <span>Total</span>
                <span id="summaryTotal">Rp0</span>
              </div>
            </div>
            <button type="button" class="btn-submit" id="btnSubmitOrder">
              Lanjut ke Pembayaran
            </button>
            <a href="/acicu#menu" class="btn-back">
              <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu
            </a>
          </section>
        </div>
      </div>
    </main>

    <script src="{{ asset('js/script.js') }}"></script>
    <script src="{{ asset('js/acicu-checkout.js') }}"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        if (typeof AcicuCheckout !== "undefined") {
          AcicuCheckout.initPreorder();
        }
      });
    </script>
  </body>
</html>
