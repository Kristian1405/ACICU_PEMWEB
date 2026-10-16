# Runbook Deploy ke VPS (Docker)

Panduan langkah demi langkah mem-publish project ACICU (beserta integrasi
Firestore + QRIS Midtrans) ke VPS. Asumsi VPS: Linux, sudah terpasang
**Docker** + **Docker Compose**, dan repo `ACICU_PEMWEB` sudah pernah di-clone.

> Detail konfigurasi Firebase/Midtrans ada di `docs/FIREBASE-MIDTRANS.md`.

---

## 0. Ringkasan urutan

1. Siapkan `.env` di VPS (dari `.env.example`).
2. Taruh **Service Account** Firebase.
3. Publish **Firestore Rules**.
4. (Opsional) Set **Notification URL** Midtrans.
5. `git pull` → `docker compose up -d --build`.
6. Verifikasi.

---

## 1. Siapkan `.env` di VPS

`docker-compose.yml` membaca file `.env` di root project (lewat `env_file`).
File ini **tidak ikut Git**, jadi harus dibuat manual.

```bash
cd /path/ke/ACICU_PEMWEB
cp .env.example .env
```

Edit `.env`, minimal isi/ubah:

```dotenv
APP_NAME=ACICU
APP_ENV=production
APP_DEBUG=false
APP_KEY=                       # lihat cara generate di bawah
APP_URL=http://70.153.142.126:8000

# Midtrans
MIDTRANS_CLIENT_KEY=Mid-client-IByVHjrZL9eCFq56
MIDTRANS_SERVER_KEY=Mid-server-xxxxxxxxxxxxxxxx      # rotasi dulu, lihat catatan
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_ENABLED_PAYMENTS=qris

# Firebase
FIREBASE_PROJECT_ID=acicu-pemweb
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_API_KEY=AIzaSyCIysc_sRLUsOznJ09os7q82O8G6QhVqVk
FIREBASE_AUTH_DOMAIN=acicu-pemweb.firebaseapp.com
FIREBASE_STORAGE_BUCKET=acicu-pemweb.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=932172319741
FIREBASE_APP_ID=1:932172319741:web:17a1a71f7fe430f9bae794
```

**Generate `APP_KEY`** (di VPS atau di komputer lokal, asal project sama):

```bash
php artisan key:generate --show
# contoh keluaran: base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=
```

Salin hasilnya ke `APP_KEY=` di `.env`. Dengan begitu `APP_KEY` tetap stabil
(tidak berubah tiap container restart).

> Ganti `APP_URL` bila nanti sudah memakai domain (mis. `https://acicu.example.com`).

---

## 2. Service Account Firebase

1. Firebase Console → Project settings → **Service accounts** →
   **Generate new private key** → simpan JSON.
2. Di VPS, taruh file tersebut (folder ini sudah di-bind-mount ke container):

```bash
cp /path/unduhan/service-account.json storage/app/firebase/service-account.json
chmod 644 storage/app/firebase/service-account.json
```

3. Verifikasi (harus muncul `BERHASIL terhubung ke Firestore`):

```bash
docker compose exec app php artisan acicu:firebase-check
```

---

## 3. Firestore Rules

Console → Firestore Database → tab **Rules** → tempel isi
`firebase/firestore.rules` → **Publish**.

Atau lewat CLI: `firebase deploy --only firestore:rules`.

---

## 4. (Opsional) Notification URL Midtrans

Webhook Midtrans **butuh domain + port standar (80/443), bukan IP:8000**.
Selama belum ada domain, biarkan kosong — verifikasi pembayaran tetap berjalan
otomatis lewat endpoint `/acicu/pembayaran/verify` (dipicu browser).

Bila sudah punya domain + HTTPS:

Dashboard Midtrans → Settings → Configuration → **Payment Notification URL**:

```
https://domain-anda/midtrans/notification
```

---

## 5. Deploy

```bash
git pull
docker compose up -d --build
```

Cek status:

```bash
docker compose ps
docker compose logs -f app
```

`docker/start.sh` otomatis: membuat `.env` container, membuat SQLite,
menjalankan migrasi, dan `storage:link`. Cache config dibersihkan tiap boot.

Kalau perlu membersihkan cache manual:

```bash
docker compose exec app php artisan config:clear
```

### Membuat akun admin

Panel admin (`/admin/*`) dilindungi login. Buat akunnya:

```bash
docker compose exec app php artisan acicu:admin admin@acicu.test --name="Admin ACICU"
```

Perintah akan meminta kata sandi secara interaktif (tidak tersimpan di
riwayat shell). Untuk otomatis, tambahkan `--password="kata-sandi-kuat"`.

- Login di `http://<APP_URL>/admin/login` — bisa memakai **email** atau **nama** admin.
- Menjalankan perintah yang sama dengan email yang sudah ada akan **menyetel ulang** kata sandinya.

---

## 6. Verifikasi

- [ ] `http://<APP_URL>/` → halaman ACICU tampil.
- [ ] `/acicu/pre-order` → isi pesanan → muncul di Firestore (koleksi `orders`).
- [ ] `/acicu/pembayaran` → tombol **Bayar via QRIS** muncul.
- [ ] Scan QRIS di https://simulator.sandbox.midtrans.com/ → sukses.
- [ ] `/acicu/status` menampilkan **Dibayar**.
- [ ] `/admin/dashboard` → diarahkan ke `/admin/login`; setelah login berhasil masuk dashboard.
- [ ] `/admin/pesanan` menampilkan pesanan + status (bukan "Firebase belum dikonfigurasi").

---

## 7. Update berikutnya (redeploy)

```bash
git pull
docker compose up -d --build
```

Perubahan `.env` cukup diikuti:

```bash
docker compose up -d            # me-restart container dengan env baru
```

---

## 8. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| `env file .env not found` | Belum `cp .env.example .env`. |
| Tidak bisa login admin | Akun belum dibuat. Jalankan `docker compose exec app php artisan acicu:admin email@kamu --name="Admin"`. |
| Lupa kata sandi admin | Jalankan ulang `acicu:admin` dengan email yang sama untuk menyetel ulang. |
| Panel admin bilang "belum bisa membaca Firestore" | `FIREBASE_*` / Service Account kurang. Jalankan `docker compose exec app php artisan acicu:firebase-check` — pesannya menyebut penyebab persisnya (project ID kosong, file hilang, dll). |
| Error 403/PERMISSION_DENIED Firestore | Service Account salah/tidak terbaca. Cek `storage/app/firebase/service-account.json` + `chmod 644`. |
| Tombol QRIS tidak muncul | `MIDTRANS_CLIENT_KEY` kosong → halaman jatuh ke mode simulasi. |
| `502 Gagal membuat transaksi` | `MIDTRANS_SERVER_KEY` salah. Lihat `docker compose logs app`. |
| Sesi login sering logout | `APP_KEY` berubah tiap restart → set `APP_KEY` tetap di `.env`. |
| Webhook tidak masuk | URL harus domain + port 80/443, bukan IP:8000. Pakai fallback verify atau domain. |

---

## 9. Keamanan

- **Jangan commit** `.env` dan `service-account.json` (sudah di-ignore).
- **Rotasi Server Key Midtrans** yang pernah dibagikan lewat chat.
- Batasi **API key Firebase** (HTTP referrers) + aktifkan **App Check**.
- Aktifkan **HTTPS** (mis. Caddy/Nginx + Let's Encrypt) untuk produksi.
