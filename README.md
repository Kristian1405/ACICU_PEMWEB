# ACICU - Frontend Pre-Order & Payment Simulation

Proyek frontend untuk sistem pre-order makanan ACICU dengan pembayaran
**QRIS otomatis via Midtrans Snap**, pesanan tersimpan di **Cloud Firestore**,
dan panel admin yang membaca pesanan dari Firestore.

## Deskripsi

Aplikasi web untuk pemesanan makanan dengan alur:
- Menu → Keranjang → Formulir Pre-Order → Pembayaran QRIS (Midtrans) → Status Pesanan

**Catatan:** Integrasi pembayaran memakai **Midtrans Snap (mode sandbox)** dan
penyimpanan pesanan memakai **Firebase Cloud Firestore**. Bila kredensial belum
diisi, halaman otomatis kembali ke mode simulasi.

## Tech Stack

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** React 19 + Inertia.js
- **Styling:** Tailwind CSS 4
- **Build Tool:** Vite 8
- **Auth:** Laravel Fortify + Passkeys

## Cara Menjalankan

### Prasyarat

- PHP 8.3+
- Composer
- Node.js 18+
- npm/pnpm

### Setup Awal

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Jalankan migrasi database
php artisan migrate

# Build assets
npm run build
```

### Development

```bash
# Jalankan server development
composer dev

# atau terpisah
php artisan serve    # Backend (port 8000)
npm run dev          # Frontend hot-reload
```

### Commands Lainnya

```bash
npm run build        # Build production
npm run check        # Lint & format check
npm run check:fix    # Fix lint issues
npm run types:check  # TypeScript type check

composer test        # Jalankan semua test
composer lint        # Format PHP code
```

## Struktur Proyek

```
app/
├── Http/Controllers/    # Controllers
├── Models/              # Eloquent models
└── Actions/             # Fortify actions

resources/
├── js/                  # React components & pages
└── views/               # Blade templates

routes/
└── web.php              # Route definitions

references/              # Referensi desain UI
```

## Fitur

- Menu produk dengan variasi
- Keranjang belanja (localStorage persistence)
- Formulir pre-order dengan validasi
- Pembayaran **QRIS otomatis via Midtrans Snap**
- Pesanan tersimpan di **Cloud Firestore** (klien) + dibaca server (admin)
- Verifikasi status otomatis (webhook Midtrans + fallback verify)
- Tracking status pesanan
- Timestamp WIB (Asia/Jakarta)
- Jadwal persiapan & pengantaran
- Responsif desktop/mobile

## Dokumentasi

- [docs/FIREBASE-MIDTRANS.md](docs/FIREBASE-MIDTRANS.md) - Konfigurasi Firebase + QRIS Midtrans & penjelasan kode
- [docs/DEPLOY-VPS.md](docs/DEPLOY-VPS.md) - Runbook deploy ke VPS (Docker)
- [Implementation.md](Implementation.md) - Spesifikasi implementasi lengkap
- [references/](references/) - Referensi desain visual

## Status

Integrasi Firebase Firestore + Midtrans QRIS (sandbox) tersedia. Untuk produksi:
aktifkan HTTPS/domain, App Check, dan batasi API key.

## License

MIT
