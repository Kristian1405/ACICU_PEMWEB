@extends('admin.layout')

@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('content')
  <div class="admin-page-head">
    <div>
      <span class="admin-eyebrow">
        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
        Katalog Produk
      </span>
      <h1>{{ $product->exists ? 'Edit Produk' : 'Tambah Produk Baru' }}</h1>
      <p class="admin-page-sub">
        Produk ini dipakai bersama oleh homepage pembeli &amp; panel admin.
      </p>
    </div>
    <a href="{{ route('admin.products') }}" class="admin-btn ghost">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  @if ($errors->any())
    <div class="admin-toolbar">
      <p class="admin-page-sub" style="color: #c0392b;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
      </p>
    </div>
  @endif

  <form
    method="POST"
    action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
    enctype="multipart/form-data"
    class="admin-card admin-product-form"
  >
    @csrf
    @if ($product->exists)
      @method('PUT')
    @endif

    <div class="admin-form-grid">
      <div class="admin-form-field">
        <label for="title">Nama Singkat (judul slider)</label>
        <input
          type="text"
          id="title"
          name="title"
          value="{{ old('title', $product->title) }}"
          placeholder="mis. CIWENG"
          required
        />
      </div>

      <div class="admin-form-field">
        <label for="name">Nama Jual</label>
        <input
          type="text"
          id="name"
          name="name"
          value="{{ old('name', $product->name) }}"
          placeholder="mis. Ciweng Original Gurih"
          required
        />
      </div>

      <div class="admin-form-field">
        <label for="category">Kategori</label>
        <input
          type="text"
          id="category"
          name="category"
          list="categoryOptions"
          value="{{ old('category', $product->category ?? 'aci kering') }}"
          placeholder="mis. aci kering"
        />
        <datalist id="categoryOptions">
          <option value="aci kering"></option>
          <option value="aci kuah"></option>
        </datalist>
      </div>

      <div class="admin-form-field">
        <label for="price">Harga (Rp)</label>
        <input
          type="number"
          id="price"
          name="price"
          min="0"
          step="500"
          value="{{ old('price', $product->price ?? 10000) }}"
          required
        />
      </div>

      <div class="admin-form-field">
        <label for="stock">Stok / Kuota Harian <small>(0 = tanpa batas)</small></label>
        <input
          type="number"
          id="stock"
          name="stock"
          min="0"
          value="{{ old('stock', $product->stock ?? 0) }}"
        />
      </div>

      <div class="admin-form-field">
        <label for="sort_order">Urutan Tampil</label>
        <input
          type="number"
          id="sort_order"
          name="sort_order"
          min="0"
          value="{{ old('sort_order', $product->sort_order ?? 0) }}"
        />
      </div>

      <div class="admin-form-field full">
        <label for="description">Deskripsi</label>
        <textarea
          id="description"
          name="description"
          rows="3"
          placeholder="Deskripsi singkat produk..."
        >{{ old('description', $product->description) }}</textarea>
      </div>

      <div class="admin-form-field">
        <label for="image">Gambar Produk</label>
        <input type="file" id="image" name="image" accept="image/*" />
        @if ($product->exists && $product->imageUrl() !== '')
          <img
            src="{{ $product->imageUrl() }}"
            alt="{{ $product->name }}"
            style="margin-top: 8px; width: 84px; height: 84px; object-fit: cover; border-radius: 12px;"
          />
          <small style="color: var(--adm-muted);">Biarkan kosong untuk mempertahankan gambar ini.</small>
        @endif
      </div>

      <div class="admin-form-field admin-form-check">
        <input
          type="checkbox"
          id="is_active"
          name="is_active"
          value="1"
          {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
        />
        <label for="is_active" style="font-weight: 600;">Tampilkan produk (aktif)</label>
      </div>
    </div>

    <div class="admin-form-actions">
      <button type="submit" class="admin-btn pink">
        <i class="fa-solid fa-floppy-disk"></i> Simpan
      </button>
      <a href="{{ route('admin.products') }}" class="admin-btn ghost">Batal</a>
    </div>
  </form>
@endsection
