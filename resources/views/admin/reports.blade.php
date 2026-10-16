@extends('admin.layout')

@section('title', 'Laporan Penjualan')

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
      <span class="admin-eyebrow">
        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
        Modul Pembukuan &amp; Keuangan &middot; Sinkronisasi Real-Time
      </span>
      <h1>Laporan Penjualan &amp; Rekapitulasi Pre-Order</h1>
      <p class="admin-page-sub">
        Rekap transaksi pre-order dari Cloud Firestore. Hanya pesanan berstatus
        <strong>Dibayar</strong> yang dihitung sebagai pendapatan.
      </p>
    </div>
  </div>

  @if ($error)
    <div class="admin-toolbar">
      <p class="admin-page-sub" style="color: #c0392b;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $error }}
      </p>
    </div>
  @endif

  {{-- ===== FILTER ===== --}}
  <form method="GET" action="{{ route('admin.reports') }}" class="admin-toolbar">
    <label class="admin-field select-field">
      <i class="fa-regular fa-calendar"></i>
      <span style="font-size: 0.85rem; color: #6b6b7b;">Dari Tanggal:</span>
      <input type="date" name="from" id="reportFrom" value="{{ $from }}" aria-label="Dari tanggal" />
    </label>

    <label class="admin-field select-field">
      <i class="fa-regular fa-calendar"></i>
      <span style="font-size: 0.85rem; color: #6b6b7b;">Sampai Tanggal:</span>
      <input type="date" name="to" id="reportTo" value="{{ $to }}" aria-label="Sampai tanggal" />
    </label>

    <button type="submit" class="admin-btn pink" id="reportApply">
      <i class="fa-solid fa-filter"></i> Terapkan Filter
    </button>

    <a href="{{ route('admin.reports') }}" class="admin-icon-btn" title="Reset filter">
      <i class="fa-solid fa-rotate-left"></i>
    </a>

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
  </form>

  {{-- ===== KARTU REKAP ===== --}}
  <div class="admin-stats">
    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Pesanan Terkonfirmasi</span>
        <span class="admin-stat-icon pink"><i class="fa-regular fa-clipboard"></i></span>
      </div>
      <div class="admin-stat-value">{{ $stats['confirmed'] }} <small>Pesanan</small></div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Porsi Terjual</span>
        <span class="admin-stat-icon pink"><i class="fa-solid fa-bowl-food"></i></span>
      </div>
      <div class="admin-stat-value">{{ $stats['porsi'] }} <small>Porsi</small></div>
    </div>

    <div class="admin-stat">
      <div class="admin-stat-top">
        <span>Total Pendapatan Bersih</span>
        <span class="admin-stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></span>
      </div>
      <div class="admin-stat-value" style="font-size: 1.5rem;">
        Rp{{ number_format($stats['revenue'], 0, ',', '.') }}
      </div>
    </div>
  </div>

  {{-- ===== TABEL TRANSAKSI ===== --}}
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h2>Data Transaksi</h2>
        <p>
          @if ($from || $to)
            Periode: {{ $from ?: 'awal' }} &ndash; {{ $to ?: 'sekarang' }}
          @else
            Seluruh transaksi (maks. 500 terbaru)
          @endif
        </p>
      </div>
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
          @forelse ($orders as $order)
            @php
              $orderId = $order['orderId'] ?? ($order['_id'] ?? '—');
              $status = $order['paymentStatus'] ?? 'menunggu';
              [$badgeClass, $badgeLabel] = $statusMap[$status] ?? ['grey', ucfirst((string) $status)];
              $createdAt = (string) ($order['createdAt'] ?? '');
              try {
                  $dt = $createdAt !== '' ? \Illuminate\Support\Carbon::parse($createdAt) : null;
              } catch (\Throwable) {
                  $dt = null;
              }
            @endphp
            <tr data-row>
              <td><strong>{{ $orderId }}</strong></td>
              <td>{{ $dt ? $dt->locale('id')->translatedFormat('d M Y H:i') : '—' }}</td>
              <td>{{ $order['recipient']['nama'] ?? '—' }}</td>
              <td>{{ \App\Services\OrderStats::itemsLabel($order) ?: '—' }}</td>
              <td>{{ $order['paymentMethod'] ?? 'QRIS' }}</td>
              <td>Rp{{ number_format((int) ($order['total'] ?? 0), 0, ',', '.') }}</td>
              <td><span class="admin-badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
            </tr>
          @empty
            <tr data-empty>
              <td colspan="7" class="adm-empty-cell">
                <div class="admin-empty">
                  <i class="fa-solid fa-chart-column"></i>
                  <strong>Belum ada transaksi</strong>
                  <span data-empty-text data-default="Belum ada transaksi pada rentang tanggal ini.">
                    Belum ada transaksi pada rentang tanggal ini.
                  </span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="admin-pagination">
      <span>Menampilkan {{ count($orders) }} dari {{ count($orders) }} pesanan</span>
      <div class="admin-pages">
        <button type="button" disabled><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="active" disabled>1</button>
        <button type="button" disabled><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>
  </div>

  <p class="admin-page-sub">
    <i class="fa-solid fa-circle-info"></i>
    Tombol <strong>Export Excel</strong> menghasilkan berkas <strong>CSV</strong>
    asli (dapat dibuka di Excel) sesuai baris yang tampil, tanpa menambah
    dependensi.
  </p>
@endsection
