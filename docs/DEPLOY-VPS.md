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
4. `git pull` → `docker compose up -d --build`.
5. Pasang **Caddy** untuk HTTPS (bagian 10).
6. Set **Notification URL** Midtrans (wajib untuk produksi).
7. Verifikasi.

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
APP_URL=https://domain-anda

# Midtrans (key dari dashboard PRODUKSI, bukan sandbox)
MIDTRANS_CLIENT_KEY=Mid-client-xxxxxxxxxxxxxxxx
MIDTRANS_SERVER_KEY=Mid-server-xxxxxxxxxxxxxxxx      # RAHASIA
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_ENABLED_PAYMENTS=other_qris

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

> `APP_URL` **wajib domain HTTPS publik** saat `MIDTRANS_IS_PRODUCTION=true`
> (mis. `https://acicu.example.com`). Nilai ini dipakai untuk redirect
> `finish`/`unfinish` Snap. Detail: [PAYMENT-PRODUCTION.md](PAYMENT-PRODUCTION.md).

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

## 4. Notification URL Midtrans (wajib untuk produksi)

Webhook Midtrans **butuh domain + port standar (80/443), bukan IP:8000**.
Tanpa webhook, status pembayaran hanya tersinkron lewat fallback
`/acicu/pembayaran/verify` saat browser memanggilnya — tidak ideal untuk
transaksi asli karena status bisa tertinggal bila pelanggan menutup browser.

Dashboard **produksi** (<https://dashboard.midtrans.com>) → Settings →
Configuration → **Payment Notification URL**:

```
https://domain-anda/midtrans/notification
```

Domain harus sudah punya HTTPS yang valid — pasang Caddy dulu (bagian 10).
Contoh untuk project ini: `https://acicu.me/midtrans/notification`.

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
- [ ] Banner halaman pembayaran menunjukkan **MODE PRODUKSI**.
- [ ] Scan QRIS dengan aplikasi pembayaran sungguhan (nominal kecil) → sukses.
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
- Jangan mencampur key sandbox dengan `MIDTRANS_IS_PRODUCTION=true`; key
  sandbox ditolak API produksi (`401`).
- Batasi **API key Firebase** (HTTP referrers) + aktifkan **App Check**.
- Aktifkan **HTTPS** (mis. Caddy/Nginx + Let's Encrypt) untuk produksi.

---

## 10. HTTPS dengan Caddy (domain `acicu.me`)

Caddy berjalan di host dan meneruskan request ke aplikasi Docker di
`127.0.0.1:8000`. Sertifikat Let's Encrypt diurus otomatis.

Prasyarat:

- A record `@` → IP publik VPS.
- Port **80** dan **443** terbuka di NSG Azure dan firewall host
  (`sudo ufw allow 80,443/tcp` bila ufw aktif).

Pasang Caddy:

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```

Pasang konfigurasi dari repo:

```bash
sudo cp deploy/Caddyfile /etc/caddy/Caddyfile
sudo systemctl reload caddy
sudo journalctl -u caddy -n 50 --no-pager | grep -i -E "certificate|error"
```

Isi `deploy/Caddyfile`:

```
acicu.me {
	reverse_proxy 127.0.0.1:8000
}
```

Verifikasi:

```bash
curl -I https://acicu.me
curl -I https://acicu.me/midtrans/notification
```

Setelah HTTPS terbukti jalan, tutup akses langsung ke port 8000 (NSG rule
`website`) agar aplikasi hanya bisa diakses lewat 443.
