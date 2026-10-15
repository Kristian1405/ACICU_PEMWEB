# ACICU - Frontend Pre-Order & Payment Simulation

Proyek frontend untuk sistem pre-order makanan ACICU dengan simulasi payment gateway.

## Deskripsi

Aplikasi web untuk pemesanan makanan dengan alur:
- Menu → Keranjang → Formulir Pre-Order → Simulasi Pembayaran → Status Pesanan

**Catatan:** Payment gateway masih berupa simulasi frontend. Integrasi backend dan transaksi nyata akan dikerjakan kemudian.

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
- Simulasi pembayaran (belum terhubung gateway nyata)
- Tracking status pesanan
- Timestamp WIB (Asia/Jakarta)
- Jadwal persiapan & pengantaran
- Responsif desktop/mobile

## Dokumentasi

- [Implementation.md](Implementation.md) - Spesifikasi implementasi lengkap
- [references/](references/) - Referensi desain visual

## Status

Frontend demonstrasi. Backend integration pending.

## License

MIT
