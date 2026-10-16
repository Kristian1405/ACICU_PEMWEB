@extends('admin.layout')

@section('title', 'Dashboard & Analytics')

@section('content')
  @php
    $statusMap = [
        'berhasil' => ['green', 'Dibayar'],
        'menunggu' => ['yellow', 'Menunggu Bayar'],
        'gagal' => ['grey', 'Gagal'],
        'refund' => ['blue', 'Refund'],
    ];
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>Halo, {{ auth()->user()?->name ?? 'Admin ACICU' }}!</h1>
      <p class="admin-page-sub">
        <i class="fa-regular fa-calendar"></i>
        {{ now()->locale('id')->translatedFormat('l, j F Y') }}
        &nbsp;&middot;&nbsp; Dapur Pusat ACICU
      </p>
    </div>
    <span class="admin-chip"><i class="fa-solid fa-fire"></i> Batch Aktif</span>
  </div>

  @if ($error)
    <div class="admin-toolbar">
      <p class="admin-page-sub" style="color: #c0392b;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $error }}
      </p>
    </div>
  @endif

  {{-- ===== KARTU STATISTIK ===== --}}
  <div class="admin-stats">
    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pesanan</span>
        <span class="admin-stat-icon pink"><i class="fa-regular fa-clipboard"></i></span>
      </div>
      <div class="admin-stat-value">{{ $stats['total'] }} <small>PO</small></div>
      <div class="admin-stat-note">{{ $stats['confirmed'] }} pesanan terkonfirmasi</div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Pesanan Hari Ini</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-bowl-food"></i></span>
      </div>
      <div class="admin-stat-value">{{ $stats['today'] }} <small>PO</small></div>
      <div class="admin-stat-note">{{ $stats['porsi'] }} total porsi tercatat</div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Produk Terlaris</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-fire"></i></span>
      </div>
      <div class="admin-stat-value" style="font-size: 1.4rem;">
        {{ $stats['bestSeller'] ?? '—' }}
      </div>
      <div class="admin-stat-note">
        {{ $stats['bestSeller'] ? $stats['bestSellerQty'].' porsi terjual' : 'Belum ada data penjualan' }}
      </div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pendapatan</span>
        <span class="admin-stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></span>
      </div>
      <div class="admin-stat-value" style="font-size: 1.5rem;">
        Rp{{ number_format($stats['revenue'], 0, ',', '.') }}
      </div>
      <div class="admin-stat-note">Dari pesanan berstatus dibayar</div>
    </div>
  </div>

  {{-- ===== GRAFIK TREN ===== --}}
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h2>Tren Pesanan (7 Hari Terakhir)</h2>
        <p>Performa pergerakan volume porsi &amp; nominal harian</p>
      </div>
      <div class="admin-toggle" data-segmented data-trend-toggle>
        <button type="button" class="active" data-trend-mode="porsi">Porsi Terjual</button>
        <button type="button" data-trend-mode="nominal">Nominal (Rp)</button>
      </div>
    </div>

    @if ($stats['total'] > 0)
      <div class="admin-trend" data-trend='@json($stats['trend'])'></div>
      <p class="admin-page-sub" style="margin-top: 10px;">
        <i class="fa-solid fa-rotate"></i>
        Data disegarkan otomatis setiap 30 detik.
      </p>
    @else
      <div class="admin-empty">
        <i class="fa-solid fa-chart-line"></i>
        <strong>Grafik belum tersedia</strong>
        <span>Belum ada data pesanan, sehingga tren 7 hari terakhir belum dapat dihitung.</span>
      </div>
    @endif
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

    @if (count($stats['recent']) > 0)
      <div class="admin-activity-list">
        @foreach ($stats['recent'] as $order)
          @php
            [$badgeClass, $badgeLabel] = $statusMap[$order['paymentStatus'] ?? 'menunggu'] ?? ['grey', 'Lainnya'];
          @endphp
          <div class="admin-activity">
            <span class="admin-avatar"><i class="fa-regular fa-clipboard"></i></span>
            <div class="admin-activity-body">
              <strong>{{ $order['recipient']['nama'] ?? 'Tanpa nama' }}</strong>
              <span>{{ \App\Services\OrderStats::itemsLabel($order) ?: '—' }}</span>
            </div>
            <span class="admin-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            <span class="admin-activity-total">
              Rp{{ number_format((int) ($order['total'] ?? 0), 0, ',', '.') }}
            </span>
          </div>
        @endforeach
      </div>
    @else
      <div class="admin-empty small">
        <i class="fa-regular fa-clock"></i>
        <strong>Belum ada aktivitas</strong>
        <span>Pesanan terbaru akan muncul di sini setelah data pesanan tersedia.</span>
      </div>
    @endif
  </div>
@endsection
