@extends('admin.layout')

@section('title', 'Data Produk')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>
        Katalog &amp; Manajemen Menu Aci
        <span class="admin-badge pink" style="vertical-align: middle; margin-left: 8px;">
          <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> 0 Produk Aktif
        </span>
      </h1>
      <p class="admin-page-sub">
        Atur varian aci kenyal gurih, kuota batch harian, serta harga promo
        langsung ke katalog e-commerce.
      </p>
    </div>
    <button
      type="button"
      class="admin-btn pink"
      data-admin-disabled="Tambah produk belum tersedia — katalog produk belum terhubung ke backend."
    >
      <i class="fa-solid fa-plus"></i> Tambah Produk
    </button>
  </div>

  {{-- ===== TOOLBAR ===== --}}
  <div class="admin-toolbar">
    <label class="admin-field">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input
        type="search"
        placeholder="Cari menu aci..."
        data-table-search="productsTable"
      />
    </label>

    <div class="admin-pills" data-filter-group="productsTable">
      <button type="button" class="admin-pill active" data-filter-value="all">Semua</button>
      <button type="button" class="admin-pill" data-filter-value="aci kering">Aci Kering</button>
      <button type="button" class="admin-pill" data-filter-value="aci kuah">Aci Kuah</button>
    </div>
  </div>

  {{-- ===== TABEL ===== --}}
  <div class="admin-card">
    <div class="admin-table-wrap">
      <table class="admin-table" id="productsTable">
        <thead>
          <tr>
            <th>No</th>
            <th>Gambar</th>
            <th>Nama Produk</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Stok / Kuota Harian</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr data-empty>
            <td colspan="8" class="adm-empty-cell">
              <div class="admin-empty">
                <i class="fa-solid fa-utensils"></i>
                <strong>Belum ada produk pada katalog admin</strong>
                <span data-empty-text data-default="Produk saat ini masih di-hardcode pada halaman user/pembeli dan belum tersimpan di database, sehingga katalog admin masih kosong.">
                  Produk saat ini masih di-hardcode pada halaman user/pembeli dan
                  belum tersimpan di database, sehingga katalog admin masih kosong.
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan 0&ndash;0 dari 0 produk</span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>

  <p class="admin-page-sub">
    <i class="fa-solid fa-circle-info"></i>
    Operasi <strong>Edit</strong> dan <strong>Hapus</strong> memerlukan tabel
    produk pada database. Saat ini tabel tersebut belum tersedia, sehingga
    aksi CRUD belum diaktifkan agar tidak merusak relasi data yang ada.
  </p>
@endsection
