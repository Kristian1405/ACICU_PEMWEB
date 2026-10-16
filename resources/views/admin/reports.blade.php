@extends('admin.layout')

@section('title', 'Laporan Penjualan')

@section('content')
  <div class="admin-page-head">
    <div>
      <span class="admin-eyebrow">
        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
        Modul Pembukuan &amp; Keuangan &middot; Sinkronisasi Real-Time
      </span>
      <h1>Laporan Penjualan &amp; Rekapitulasi Pre-Order</h1>
      <p class="admin-page-sub">
        Unduh rekapan transaksi pre-order untuk pembukuan harian dan mingguan ACICU.
      </p>
    </div>
  </div>

  {{-- ===== FILTER ===== --}}
  <div class="admin-toolbar">
    <label class="admin-field select-field">
      <i class="fa-regular fa-calendar"></i>
      <span style="font-size: 0.85rem; color: #6b6b7b;">Dari Tanggal:</span>
      <input type="date" id="reportFrom" aria-label="Dari tanggal" />
    </label>

    <label class="admin-field select-field">
      <i class="fa-regular fa-calendar"></i>
      <span style="font-size: 0.85rem; color: #6b6b7b;">Sampai Tanggal:</span>
      <input type="date" id="reportTo" aria-label="Sampai tanggal" />
    </label>

    <button type="button" class="admin-btn pink" id="reportApply">
      <i class="fa-solid fa-filter"></i> Terapkan Filter
    </button>

    <button type="button" class="admin-icon-btn" id="reportReset" title="Reset filter">
      <i class="fa-solid fa-rotate-left"></i>
    </button>

    <button
      type="button"
      class="admin-btn green"
      data-export-csv="reportTable"
      data-export-filename="laporan-penjualan-acicu.csv"
      style="margin-left: auto;"
    >
      <i class="fa-solid fa-file-excel"></i> Export Excel (.xlsx)
      <i class="fa-solid fa-download"></i>
    </button>
  </div>

  {{-- ===== KARTU REKAP ===== --}}
  <div class="admin-stats">
    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pesanan Terkonfirmasi</span>
        <span class="admin-stat-icon pink"><i class="fa-regular fa-clipboard"></i></span>
      </div>
      <div class="admin-stat-value">&mdash; <small>Pesanan</small></div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Porsi Terjual</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-bowl-food"></i></span>
      </div>
      <div class="admin-stat-value">&mdash; <small>Porsi</small></div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pendapatan Bersih</span>
        <span class="admin-stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></span>
      </div>
      <div class="admin-stat-value">&mdash;</div>
    </div>
  </div>

  {{-- ===== TABEL TRANSAKSI ===== --}}
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h2>Data Transaksi Terkini Siap Ekspor</h2>
        <p>Hanya pesanan terkonfirmasi yang dihitung sebagai pendapatan.</p>
      </div>
      <label class="admin-field select-field" style="flex: 0 0 auto;">
        <span style="font-size: 0.82rem; color: #6b6b7b; white-space: nowrap;">Baris per halaman:</span>
        <select aria-label="Baris per halaman" style="width: auto;">
          <option>25</option>
          <option>50</option>
          <option>100</option>
        </select>
      </label>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" id="reportTable">
        <thead>
          <tr>
            <th>ID Transaksi</th>
            <th>Tanggal &amp; Jam</th>
            <th>Nama Pelanggan</th>
            <th>Rincian Menu</th>
            <th>Metode</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr data-empty>
            <td colspan="7" class="adm-empty-cell">
              <div class="admin-empty">
                <i class="fa-solid fa-chart-column"></i>
                <strong>Belum ada transaksi</strong>
                <span data-empty-text data-default="Belum ada transaksi terkonfirmasi pada server. Pesanan user masih berupa simulasi di browser sehingga belum dapat direkap sebagai pendapatan.">
                  Belum ada transaksi terkonfirmasi pada server. Pesanan user
                  masih berupa simulasi di browser sehingga belum dapat direkap
                  sebagai pendapatan.
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan data 0&ndash;0 dari 0 pesanan terkonfirmasi</span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>

  <p class="admin-page-sub">
    <i class="fa-solid fa-circle-info"></i>
    Tombol <strong>Export Excel</strong> saat ini menghasilkan berkas
    <strong>CSV</strong> asli (dapat dibuka di Excel) tanpa menambah dependensi.
    Ekspor <strong>.xlsx</strong> sesungguhnya memerlukan paket tambahan dan
    menunggu persetujuan Anda.
  </p>
@endsection
