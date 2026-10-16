<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Simulasi Pembayaran - ACICU</title>
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
    <meta name="csrf-token" content="{{ csrf_token() }}" />
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
      @php($midtransReady = filled(config('midtrans.client_key')))
      <div class="checkout-container payment-container">
        <div class="sim-banner">
          @if ($midtransReady)
            <i class="fa-solid fa-qrcode"></i>
            PEMBAYARAN QRIS MIDTRANS &mdash; {{ config('midtrans.is_production') ? 'MODE PRODUKSI' : 'MODE SANDBOX' }}
          @else
            <i class="fa-solid fa-flask"></i>
            SIMULASI PEMBAYARAN &mdash; BELUM TERHUBUNG KE PAYMENT GATEWAY
          @endif
        </div>

        <h1 class="checkout-title">Pembayaran Pesanan</h1>

        <div class="payment-grid">
          <section class="payment-info-section">
            <div class="order-ref-card">
              <span class="order-ref-label">ID Pesanan (Simulasi)</span>
              <span class="order-ref-id" id="payOrderId">-</span>
            </div>

            <h2><i class="fa-solid fa-receipt"></i> Ringkasan</h2>
            <div id="payOrderSummary" class="order-summary"></div>
            <div class="summary-totals">
              <div class="summary-row total">
                <span>Total Pembayaran</span>
                <span id="payTotal">Rp0</span>
              </div>
            </div>

            <div class="recipient-info" id="payRecipient"></div>
          </section>

          <section class="payment-action-section">
            @if ($midtransReady)
              <h2><i class="fa-solid fa-qrcode"></i> Metode Pembayaran: QRIS</h2>
              <div class="payment-methods">
                <label class="payment-method selected">
                  <input type="radio" name="payMethod" value="qris" checked />
                  <i class="fa-solid fa-qrcode"></i>
                  <span>QRIS (semua bank &amp; e-wallet)</span>
                </label>
              </div>

              <p class="sim-note">
                <i class="fa-solid fa-circle-info"></i>
                QR code dibuat otomatis oleh Midtrans. Scan dengan aplikasi
                bank / e-wallet apa pun yang mendukung QRIS.
              </p>

              <div class="payment-status" id="paymentStatus">
                <i class="fa-solid fa-clock"></i>
                <span>Menunggu Pembayaran</span>
              </div>

              <div class="payment-actions" id="paymentActions">
                <button class="btn-pay-success" id="btnPayQris">
                  <i class="fa-solid fa-qrcode"></i> Bayar via QRIS
                </button>
              </div>
            @else
              <h2><i class="fa-solid fa-credit-card"></i> Metode Pembayaran (Simulasi)</h2>
              <div class="payment-methods">
                <label class="payment-method selected">
                  <input type="radio" name="payMethod" value="transfer" checked />
                  <i class="fa-solid fa-building-columns"></i>
                  <span>Transfer Bank (Simulasi)</span>
                </label>
                <label class="payment-method">
                  <input type="radio" name="payMethod" value="ewallet" />
                  <i class="fa-solid fa-wallet"></i>
                  <span>E-Wallet (Simulasi)</span>
                </label>
              </div>

              <p class="sim-note">
                <i class="fa-solid fa-circle-info"></i>
                Ini simulasi frontend. Tidak ada transaksi keuangan nyata yang terjadi.
                Tombol di bawah hanya mengubah status simulasi untuk demonstrasi.
              </p>

              <div class="payment-status" id="paymentStatus">
                <i class="fa-solid fa-clock"></i>
                <span>Menunggu Pembayaran</span>
              </div>

              <div class="payment-actions" id="paymentActions">
                <button class="btn-pay-success" id="btnPaySuccess">
                  <i class="fa-solid fa-check"></i> Simulasi: Bayar Berhasil
                </button>
                <button class="btn-pay-fail" id="btnPayFail">
                  <i class="fa-solid fa-xmark"></i> Simulasi: Gagal / Batal
                </button>
              </div>
            @endif

            <div class="payment-result" id="paymentResult" style="display:none;"></div>

            <a href="/acicu/pre-order" class="btn-back">
              <i class="fa-solid fa-arrow-left"></i> Kembali ke Formulir
            </a>
          </section>
        </div>
      </div>
    </main>

    @include('partials.acicu-config')
    <script src="{{ asset('js/script.js') }}"></script>
    {{-- Firebase SDK (compat) --}}
    <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore-compat.js"></script>
    <script src="{{ asset('js/acicu-firebase.js') }}"></script>
    @if ($midtransReady)
      {{-- Snap.js: menampilkan QRIS dari token yang dibuat server --}}
      <script
        src="{{ config('midtrans.snap_script_url') }}"
        data-client-key="{{ config('midtrans.client_key') }}"
      ></script>
      <script src="{{ asset('js/acicu-midtrans.js') }}"></script>
    @endif
    <script src="{{ asset('js/acicu-checkout.js') }}"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        if (typeof AcicuCheckout !== "undefined") {
          AcicuCheckout.initPayment();
        }
      });
    </script>
  </body>
</html>
