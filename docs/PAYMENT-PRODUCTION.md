# Migrasi Midtrans: Sandbox → Produksi

Dokumen ini mencatat perubahan dan sisa langkah saat payment gateway QRIS
dipindahkan dari **sandbox** ke **produksi**. Tanggal migrasi: 2026-11-04.

> Rahasia (Client/Server Key) **tidak** ditulis di dokumen ini dan tidak
> pernah masuk Git. Semua kredensial hanya ada di `.env`.

---

## 1. Apa yang berubah

Kode aplikasi **tidak** punya URL sandbox yang di-hardcode. Semua endpoint
ditentukan `MIDTRANS_IS_PRODUCTION` di `config/midtrans.php`, jadi migrasi
cukup lewat `.env`:

| Pengaturan | Sandbox (sebelum) | Produksi (sekarang) |
|---|---|---|
| `.env` | `MIDTRANS_IS_PRODUCTION=false` | `MIDTRANS_IS_PRODUCTION=true` |
| Client Key | key sandbox | key dashboard produksi |
| Server Key | key sandbox | key dashboard produksi |
| Snap API | `app.sandbox.midtrans.com` | `app.midtrans.com` |
| Snap.js (browser) | `app.sandbox.midtrans.com/snap/snap.js` | `app.midtrans.com/snap/snap.js` |
| Status API | `api.sandbox.midtrans.com/v2` | `api.midtrans.com/v2` |
| Simulator QRIS | dipakai | **tidak berlaku** (QRIS asli) |

Verifikasi status di UI (`resources/views/acicu-payment.blade.php`) otomatis
menampilkan banner **MODE PRODUKSI** karena membaca `config('midtrans.is_production')`.

### File yang terlibat

| File | Perubahan |
|---|---|
| `.env` | `MIDTRANS_IS_PRODUCTION=true` + Client/Server Key produksi. **Tidak di-commit** (`.gitignore`). |
| `.env.example` | Komentar produksi + penjelasan `MIDTRANS_IS_PRODUCTION`. |
| `config/midtrans.php` | Tidak diubah — endpoint dibuat otomatis dari flag produksi. |
| `app/Services/MidtransService.php` | Tidak diubah — memakai `config('midtrans.snap_url')` / `api_base_url`. |
| `app/Http/Controllers/PaymentController.php` | Tidak diubah — signature webhook tetap SHA-512 + Server Key. |
| `resources/views/acicu-payment.blade.php` | Tidak diubah — banner mode mengikuti config. |
| `docs/*`, `README.md` | Diperbarui: langkah produksi, bukan sandbox. |

---

## 2. Checklist go-live (wajib)

### 2.1 Dashboard Midtrans produksi

<https://dashboard.midtrans.com> — **bukan** `dashboard.sandbox.midtrans.com`.

- [ ] **Settings → Access Keys**: pastikan Client Key dan Server Key yang
      dipakai adalah milik akun produksi.
- [ ] **Settings → Payment Channels / Snap Preferences**: kanal **QRIS**
      aktif (`other_qris` di Midtrans). Di sandbox QRIS selalu tersedia; di
      produksi harus diaktifkan untuk merchant.
- [ ] **Settings → Configuration → Payment Notification URL**:
      `https://domain-anda/midtrans/notification`
      (wajib domain + port 80/443, tanpa IP dan tanpa `:8000`).
- [ ] **Merchant ID** (Settings → Access Keys) → isi `MIDTRANS_MERCHANT_ID`
      bila ingin dipakai untuk laporan/rekonsiliasi. Snap QRIS tidak
      mewajibkannya.
- [ ] Akun bisnis sudah **aktif/terverifikasi** (jika belum, transaksi
      produksi akan ditolak Midtrans).

### 2.2 `.env` server produksi

`APP_URL` menentukan URL `finish`/`unfinish` Snap, jadi harus mengarah ke
domain publik, bukan localhost:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda

MIDTRANS_IS_PRODUCTION=true
MIDTRANS_CLIENT_KEY=<key produksi>
MIDTRANS_SERVER_KEY=<key produksi>          # RAHASIA
MIDTRANS_ENABLED_PAYMENTS=other_qris
MIDTRANS_FINISH_URL="${APP_URL}/acicu/status"
MIDTRANS_UNFINISH_URL="${APP_URL}/acicu/pembayaran"
```

> Di mesin lokal `APP_URL=http://localhost:8000` boleh dibiarkan **hanya untuk
> pengembangan**. Dengan `MIDTRANS_IS_PRODUCTION=true`, pembayaran yang terjadi
> adalah transaksi **asli**, tetapi redirect `finish` akan menuju localhost.

Domain yang dipakai project ini: **`acicu.me`** (A record ke IP VPS, HTTPS via
Caddy). Konfigurasi reverse proxy ada di `deploy/Caddyfile`; langkah lengkap di
[DEPLOY-VPS.md](DEPLOY-VPS.md) bagian 10.

### 2.3 Terapkan konfigurasi

```bash
php artisan config:clear      # lokal / non-Docker
# atau di VPS (Docker):
docker compose up -d          # start.sh menghapus bootstrap/cache/*.php
docker compose exec app php artisan config:clear
```

Tanpa langkah ini, `config:cache` lama masih memakai mode sandbox.

---

## 3. Verifikasi kredensial (tanpa membuat transaksi)

Server Key produksi bisa diuji ke API produksi. Transaksi tidak dibuat;
respons yang diharapkan adalah `status_code: 404 Transaction doesn't exist`
untuk order id acak. Jika key salah, Midtrans membalas `401`.

```bash
curl -s -u "$(grep '^MIDTRANS_SERVER_KEY=' .env | cut -d= -f2-):" \
  "https://api.midtrans.com/v2/PROBE-ACICU-000/status"
```

PowerShell:

```powershell
$sk = ((Get-Content .env | Where-Object { $_ -match '^MIDTRANS_SERVER_KEY=' }) -replace '^MIDTRANS_SERVER_KEY=','').Trim()
$b  = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${sk}:"))
Invoke-WebRequest -Uri 'https://api.midtrans.com/v2/PROBE-ACICU-000/status' `
  -Headers @{ Authorization = "Basic $b" } -UseBasicParsing
```

Hasil pemeriksaan saat migrasi ini dilakukan: **Server Key diterima API
produksi** (HTTP 200, `Transaction doesn't exist`). Client Key tidak bisa
diuji dari server; pastikan nilainya diambil dari dashboard produksi yang sama.

### Cek di aplikasi

- [ ] `/acicu/pembayaran` menampilkan banner **PEMBAYARAN QRIS MIDTRANS — MODE PRODUKSI**.
- [ ] Klik **Bayar via QRIS** → popup Snap produksi muncul (bukan sandbox).
- [ ] Scan dengan aplikasi e-wallet/m-banking sungguhan, nominal kecil.
- [ ] `paymentStatus` di Firestore berubah `berhasil` (webhook atau fallback verify).
- [ ] `storage/logs/laravel.log` tidak berisi `Invalid signature` / `401`.

> Uji dengan nominal terkecil yang diizinkan, lalu refund lewat dashboard
> Midtrans bila perlu. Jangan memakai simulator sandbox — simulator tidak
> terhubung ke transaksi produksi.

---

## 4. Rollback ke sandbox

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_CLIENT_KEY=<key sandbox>
MIDTRANS_SERVER_KEY=<key sandbox>
```

lalu `php artisan config:clear` (atau restart container). Kode tidak perlu
diubah. Data transaksi sandbox dan produksi terpisah di dashboard masing-masing.

---

## 5. Risiko yang perlu diketahui sebelum menerima uang asli

**Nominal pembayaran masih berasal dari browser.**
`PaymentController@snapToken()` memakai `gross_amount` dan `items` yang
dikirim `public/js/acicu-midtrans.js` tanpa mencocokkannya ke total pesanan di
Firestore. Siapa pun bisa memanggil endpoint itu dengan nominal lebih kecil
untuk `order_id` yang valid. Di sandbox ini tidak berbahaya; di produksi ini
berarti kerugian nyata.

Perbaikan yang disarankan (belum diterapkan karena mengubah alur pembayaran):
ambil dokumen `orders/{order_id}` di server, pakai `total` dan `items` dari
dokumen tersebut, dan tolak request bila nominal klien tidak sama. Endpoint
`/acicu/pembayaran/verify` sendiri aman karena status selalu ditanyakan ke
Midtrans, bukan dari input klien.

Risiko lain: webhook hanya berjalan bila `APP_URL` memakai domain HTTPS publik
dan URL-nya terdaftar di dashboard. Bila tidak, status tetap tersinkron lewat
fallback `verify` saat browser memanggilnya setelah pembayaran.
