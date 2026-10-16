@extends('admin.layout')

@section('title', 'Dashboard & Analytics')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Halo, Owner ACICU!</h1>
      <p class="admin-page-sub">
        <i class="fa-regular fa-calendar"></i>
        {{ now()->locale('id')->translatedFormat('l, j F Y') }}
        &nbsp;&middot;&nbsp; Dapur Pusat ACICU
      </p>
    </div>
    <span class="admin-chip"><i class="fa-solid fa-fire"></i> Batch Aktif</span>
  </div>

  {{-- ===== KARTU STATISTIK ===== --}}
  <div class="admin-stats">
    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pesanan</span>
        <span class="admin-stat-icon pink"><i class="fa-regular fa-clipboard"></i></span>
      </div>
      <div class="admin-stat-value">&mdash; <small>PO</small></div>
      <div class="admin-stat-note">Belum ada data pesanan</div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Pesanan Hari Ini</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-bowl-food"></i></span>
      </div>
      <div class="admin-stat-value">&mdash; <small>PO</small></div>
      <div class="admin-stat-note">Belum ada data pesanan</div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Produk Terlaris</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-fire"></i></span>
      </div>
      <div class="admin-stat-value">&mdash;</div>
      <div class="admin-stat-note">Belum ada data penjualan</div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pendapatan</span>
        <span class="admin-stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></span>
      </div>
      <div class="admin-stat-value">&mdash;</div>
      <div class="admin-stat-note">Belum ada pendapatan terverifikasi</div>
    </div>
  </div>

  {{-- ===== GRAFIK TREN ===== --}}
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h2>Tren Pesanan (7 Hari Terakhir)</h2>
        <p>Performa pergerakan volume porsi &amp; nominal harian</p>
      </div>
      <div class="admin-toggle" data-segmented>
        <button type="button" class="active">Porsi Terjual</button>
        <button type="button">Nominal (Rp)</button>
      </div>
    </div>

    <div class="admin-empty">
      <i class="fa-solid fa-chart-line"></i>
      <strong>Grafik belum tersedia</strong>
      <span>
        Belum ada data pesanan pada backend, sehingga tren 7 hari terakhir
        belum dapat dihitung. Grafik akan tampil otomatis setelah modul
        pesanan terhubung.
      </span>
    </div>
  </div>

  {{-- ===== AKTIVITAS TERBARU ===== --}}
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h2>Aktivitas Pesanan Terbaru</h2>
        <p>Riwayat pesanan yang masuk ke dapur</p>
      </div>
      <a
        href="{{ route('admin.orders') }}"
        class="admin-link"
        style="align-self: center;"
      >
        Lihat Semua Pesanan <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>

    <div class="admin-empty small">
      <i class="fa-regular fa-clock"></i>
      <strong>Belum ada aktivitas</strong>
      <span>Pesanan terbaru akan muncul di sini setelah data pesanan tersedia.</span>
    </div>
  </div>
@endsection
