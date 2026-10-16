@extends('admin.layout')

@section('title', 'Data Pesanan')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Kelola Data Pesanan Pre-Order</h1>
      <p class="admin-page-sub">
        Pantau verifikasi transfer pelanggan dan atur alur masuk dapur.
      </p>
    </div>
    <span class="admin-chip">
      <i class="fa-regular fa-clipboard"></i>
      Total Antrean: &mdash; Pesanan
    </span>
  </div>

  {{-- ===== TOOLBAR ===== --}}
  <div class="admin-toolbar">
    <label class="admin-field">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input
        type="search"
        placeholder="Cari nama pelanggan atau nomor PO (misal: PO-0824)..."
        data-table-search="ordersTable"
      />
    </label>

    <label class="admin-field select-field">
      <i class="fa-regular fa-calendar"></i>
      <input type="date" aria-label="Filter tanggal" />
    </label>

    <label class="admin-field select-field">
      <i class="fa-solid fa-filter"></i>
      <select aria-label="Filter status">
        <option>Semua Status</option>
        <option>Menunggu Konfirmasi</option>
        <option>Proses Dapur</option>
        <option>Selesai</option>
      </select>
    </label>

    <button
      type="button"
      class="admin-icon-btn pink"
      title="Segarkan data"
      data-admin-refresh
    >
      <i class="fa-solid fa-rotate-right"></i>
    </button>
  </div>

  {{-- ===== TABEL ===== --}}
  <div class="admin-card">
    <div class="admin-table-wrap">
      <table class="admin-table" id="ordersTable">
        <thead>
          <tr>
            <th>No</th>
            <th>Nomor PO</th>
            <th>Nama Pelanggan</th>
            <th>WhatsApp</th>
            <th>Rincian Produk</th>
            <th>Total Harga</th>
            <th>Jadwal PO</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr data-empty>
            <td colspan="9" class="adm-empty-cell">
              <div class="admin-empty">
                <i class="fa-regular fa-clipboard"></i>
                <strong>Belum ada pesanan</strong>
                <span data-empty-text data-default="Belum ada data pesanan pada server. Data pesanan user saat ini masih tersimpan di browser (simulasi) dan belum terhubung ke backend admin.">
                  Belum ada data pesanan pada server. Data pesanan user saat ini
                  masih tersimpan di browser (simulasi) dan belum terhubung ke
                  backend admin.
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan 0&ndash;0 dari 0 pesanan</span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>

  <p class="admin-page-sub">
    <i class="fa-solid fa-circle-info"></i>
    Tombol <strong>Validasi Bukti</strong>, <strong>Detail</strong>, dan
    <strong>Selesai</strong> akan muncul per baris setelah data pesanan dan
    modul pembayaran tersedia di backend.
  </p>
@endsection
