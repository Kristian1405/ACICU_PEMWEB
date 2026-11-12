@extends('admin.layout')

@section('title', 'Data Pesanan')

@php
  $statusMap = [
      'berhasil' => ['green', 'Dibayar'],
      'menunggu' => ['yellow', 'Menunggu Bayar'],
      'gagal' => ['grey', 'Gagal'],
      'refund' => ['blue', 'Refund'],
  ];
  $total = count($orders);
@endphp

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Kelola Data Pesanan Pre-Order</h1>
      <p class="admin-page-sub">
        Data pesanan dibaca dari Cloud Firestore (project: {{ config('firebase.project_id') ?: '—' }}).
      </p>
    </div>
    <span class="admin-chip">
      <i class="fa-regular fa-clipboard"></i>
      Total Antrean: {{ $total }} Pesanan
    </span>
  </div>

  @if (session('status'))
    <div class="admin-toolbar"><p class="admin-page-sub" style="color:#1e8449;"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</p></div>
  @endif
  @if (session('error'))
    <div class="admin-toolbar"><p class="admin-page-sub" style="color:#c0392b;"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</p></div>
  @endif

  @if ($error)
    <div class="admin-toolbar">
      <p class="admin-page-sub" style="color:#c0392b;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $error }}
      </p>
    </div>
  @endif

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
        <option>Menunggu Bayar</option>
        <option>Dibayar</option>
        <option>Gagal</option>
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
          @forelse ($orders as $index => $order)
            @php
              $recipient = $order['recipient'] ?? [];
              $delivery = $order['delivery'] ?? [];
              $items = $order['items'] ?? [];
              $orderId = $order['orderId'] ?? ($order['_id'] ?? '—');
              $status = $order['paymentStatus'] ?? 'menunggu';
              [$badgeClass, $badgeLabel] = $statusMap[$status] ?? ['grey', ucfirst($status)];

              $rincian = collect($items)
                  ->map(function ($item) {
                      $name = $item['name'] ?? '-';
                      if (! empty($item['variant'])) {
                          $name .= ' ('.$item['variant'].')';
                      }
                      return ($item['qty'] ?? 0).'× '.$name;
                  })
                  ->implode(', ');
            @endphp
            <tr data-row>
              <td>{{ $index + 1 }}</td>
              <td><strong>{{ $orderId }}</strong></td>
              <td>{{ $recipient['nama'] ?? '—' }}</td>
              <td>{{ $recipient['whatsapp'] ?? '—' }}</td>
              <td>{{ $rincian !== '' ? $rincian : '—' }}</td>
              <td>Rp{{ number_format((int) ($order['total'] ?? 0), 0, ',', '.') }}</td>
              <td>
                {{ $delivery['tanggal'] ?? '—' }}
                @if (! empty($delivery['slot']))
                  &middot; {{ $delivery['slot'] }} WIB
                @endif
              </td>
              <td><span class="admin-badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
              <td>
                @php
                  $wa = preg_replace('/\D+/', '', (string) ($recipient['whatsapp'] ?? ''));
                  if (str_starts_with($wa, '0')) { $wa = '62'.substr($wa, 1); }
                  $fulfillment = $order['fulfillment'] ?? 'baru';
                @endphp
                <form method="POST" action="{{ route('admin.orders.status', ['order' => $orderId]) }}" style="display:flex;gap:6px;align-items:center;">
                  @csrf
                  <select name="fulfillment" aria-label="Tahap pesanan {{ $orderId }}" onchange="this.form.submit()">
                    @foreach (['baru' => 'Baru', 'diproses' => 'Diproses', 'dikirim' => 'Dikirim', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                      <option value="{{ $value }}" @selected($fulfillment === $value)>{{ $label }}</option>
                    @endforeach
                  </select>
                  @if ($wa !== '')
                    <a class="admin-icon-btn" title="Chat pelanggan via WhatsApp" target="_blank" rel="noopener"
                       href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Halo kak '.($recipient['nama'] ?? '').', pesanan ACICU '.$orderId.' ') }}">
                      <i class="fa-brands fa-whatsapp"></i>
                    </a>
                  @endif
                </form>
              </td>
            </tr>
          @empty
            <tr data-empty>
              <td colspan="9" class="adm-empty-cell">
                <div class="admin-empty">
                  <i class="fa-regular fa-clipboard"></i>
                  <strong>Belum ada pesanan</strong>
                  <span data-empty-text data-default="{{ $error ? 'Firebase belum dikonfigurasi sehingga data pesanan belum dapat dimuat.' : 'Belum ada pesanan yang tersimpan di Cloud Firestore.' }}">
                    {{ $error ? 'Firebase belum dikonfigurasi sehingga data pesanan belum dapat dimuat.' : 'Belum ada pesanan yang tersimpan di Cloud Firestore.' }}
                  </span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>
        @if ($total > 0)
          Menampilkan 1&ndash;{{ $total }} dari {{ $total }} pesanan
        @else
          Menampilkan 0&ndash;0 dari 0 pesanan
        @endif
      </span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>

  <p class="admin-page-sub">
    <i class="fa-solid fa-circle-info"></i>
    Status pembayaran diperbarui otomatis oleh Midtrans. Tahap pesanan (Baru/Diproses/Dikirim/Selesai) diubah dari kolom Aksi.
  </p>
@endsection
