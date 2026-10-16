@extends('admin.layout')

@section('title', 'Data Produk')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>
        Katalog &amp; Manajemen Menu Aci
        <span class="admin-badge pink" style="vertical-align: middle; margin-left: 8px;">
          <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> {{ $products->count() }} Produk
        </span>
      </h1>
      <p class="admin-page-sub">
        Produk di sini adalah sumber data yang sama dengan katalog pada halaman
        pembeli. Perubahan (tambah/edit/hapus) langsung tercermin di homepage.
      </p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="admin-btn pink">
      <i class="fa-solid fa-plus"></i> Tambah Produk
    </a>
  </div>

  @if (session('status'))
    <div class="admin-toolbar">
      <p class="admin-page-sub" style="color: var(--adm-green);">
        <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
      </p>
    </div>
  @endif

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
          @forelse ($products as $index => $product)
            <tr data-row data-category="{{ $product->category }}">
              <td>{{ $index + 1 }}</td>
              <td>
                @if ($product->imageUrl() !== '')
                  <img
                    src="{{ $product->imageUrl() }}"
                    alt="{{ $product->name }}"
                    style="width: 46px; height: 46px; object-fit: cover; border-radius: 10px;"
                  />
                @else
                  &mdash;
                @endif
              </td>
              <td>
                <strong>{{ $product->name }}</strong>
                <br />
                <small style="color: var(--adm-muted);">{{ $product->title }}</small>
              </td>
              <td>{{ $product->category ?: '—' }}</td>
              <td>Rp{{ number_format($product->price, 0, ',', '.') }}</td>
              <td>{{ $product->stock > 0 ? $product->stock.' porsi' : 'Tanpa batas' }}</td>
              <td>
                <span class="admin-badge {{ $product->is_active ? 'green' : 'grey' }}">
                  {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <a
                  href="{{ route('admin.products.edit', $product) }}"
                  class="admin-icon-btn"
                  title="Edit produk"
                >
                  <i class="fa-regular fa-pen-to-square"></i>
                </a>
                <form
                  method="POST"
                  action="{{ route('admin.products.destroy', $product) }}"
                  style="display: inline;"
                  onsubmit="return confirm('Hapus produk {{ $product->name }}? Tindakan ini tidak dapat dibatalkan.');"
                >
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="admin-icon-btn" title="Hapus produk">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr data-empty>
              <td colspan="8" class="adm-empty-cell">
                <div class="admin-empty">
                  <i class="fa-solid fa-utensils"></i>
                  <strong>Katalog masih kosong</strong>
                  <span data-empty-text data-default="Belum ada produk. Klik “Tambah Produk” untuk menambahkan menu.">
                    Belum ada produk. Klik “Tambah Produk” untuk menambahkan menu.
                  </span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan {{ $products->count() }} dari {{ $products->count() }} produk</span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
@endsection
