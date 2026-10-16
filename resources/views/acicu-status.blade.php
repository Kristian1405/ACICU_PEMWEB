<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Status Pesanan - ACICU</title>
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
      <div class="checkout-container status-container">
        <div class="sim-banner">
          <i class="fa-solid fa-flask"></i>
          DATA SIMULASI &mdash; Tersimpan di browser, bukan server
        </div>

        <h1 class="checkout-title">Status Pesanan</h1>

        <div class="status-grid">
          <section class="status-main">
            <div class="order-ref-card">
              <span class="order-ref-label">ID Pesanan (Simulasi)</span>
              <span class="order-ref-id" id="statusOrderId">-</span>
            </div>

            <div class="status-badge-container" id="statusBadgeContainer"></div>

            <div class="status-timeline" id="statusTimeline"></div>
          </section>

          <section class="status-detail">
            <h2><i class="fa-solid fa-receipt"></i> Detail Pesanan</h2>
            <div id="statusOrderItems" class="order-summary"></div>
            <div class="summary-totals">
              <div class="summary-row total">
                <span>Total</span>
                <span id="statusTotal">Rp0</span>
              </div>
            </div>

            <h2 style="margin-top:24px;"><i class="fa-solid fa-user"></i> Penerima</h2>
            <div id="statusRecipient" class="recipient-info"></div>

            <div class="status-actions">
              <a href="/acicu" class="btn-submit" style="text-align:center;text-decoration:none;">
                Kembali ke Beranda
              </a>
              <button class="btn-back" id="btnNewOrder" style="border:none;cursor:pointer;">
                <i class="fa-solid fa-plus"></i> Buat Pesanan Baru
              </button>
            </div>
          </section>
        </div>
      </div>
    </main>

    @include('partials.acicu-config')
    <script src="{{ asset('js/script.js') }}"></script>
    <script src="{{ asset('js/acicu-checkout.js') }}"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        if (typeof AcicuCheckout !== "undefined") {
          AcicuCheckout.initStatus();
        }
      });
    </script>
  </body>
</html>
