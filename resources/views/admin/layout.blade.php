<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Admin') - ACICU Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Baloo+2:wght@400;500;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />

    {{-- Stylesheet khusus admin, terpisah dari style.css user/pembeli --}}
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}" />
  </head>
  <body class="admin-body">
    <div class="admin-shell">
      {{-- ========================= SIDEBAR ========================= --}}
      <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
          <img src="{{ asset('src-acicu/logo.jpg') }}" alt="Logo ACICU" />
          <div class="admin-brand-text">
            <strong>ACICU</strong>
            <span>ADMIN PANEL</span>
          </div>
        </div>

        <nav class="admin-nav">
          <a
            href="{{ route('admin.dashboard') }}"
            class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
          >
            <i class="fa-solid fa-table-cells-large"></i> Dashboard
          </a>
          <a
            href="{{ route('admin.orders') }}"
            class="admin-nav-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}"
          >
            <i class="fa-regular fa-clipboard"></i> Data Pesanan
          </a>
          <a
            href="{{ route('admin.products') }}"
            class="admin-nav-link {{ request()->routeIs('admin.products') ? 'active' : '' }}"
          >
            <i class="fa-solid fa-utensils"></i> Data Produk
          </a>
          <a
            href="{{ route('admin.reports') }}"
            class="admin-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}"
          >
            <i class="fa-solid fa-chart-column"></i> Laporan Penjualan
          </a>
        </nav>

        <div class="admin-sidebar-footer">
          <div class="admin-profile">
            <span class="admin-avatar"><i class="fa-regular fa-user"></i></span>
            <div>
              <strong>{{ auth()->user()?->name ?? 'Admin ACICU' }}</strong>
              <span>{{ auth()->user()?->email ?? 'Pengelola' }}</span>
            </div>
          </div>
          <form
            method="POST"
            action="{{ route('admin.logout') }}"
            class="admin-logout-form"
          >
            @csrf
            <button type="submit" class="admin-logout" id="adminLogout">
              <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
            </button>
          </form>
        </div>
      </aside>

      <div class="admin-overlay" id="adminOverlay"></div>

      {{-- ========================= MAIN ========================= --}}
      <div class="admin-main">
        <header class="admin-header">
          <button class="admin-menu-toggle" id="adminMenuToggle" aria-label="Buka menu">
            <i class="fa-solid fa-bars"></i>
          </button>
          <span class="admin-header-logo">ACICU</span>
          <div class="admin-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="search"
              placeholder="Cari pesanan, produk, atau laporan..."
              aria-label="Pencarian"
              data-admin-global-search
            />
          </div>
        </header>

        <main class="admin-content">
          @yield('content')
        </main>
      </div>
    </div>

    <div class="admin-toast" id="adminToast" role="status" aria-live="polite"></div>

    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
  </body>
</html>
