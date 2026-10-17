# Integrasi Firebase (Firestore) + QRIS Midtrans — ACICU

Dokumen ini menjelaskan **konfigurasi** dan **cara kerja kode** integrasi
Cloud Firestore dan pembayaran QRIS otomatis Midtrans (Snap) pada project
ACICU. Tampilan/desain yang sudah ada **tidak diubah** — hanya ditambah
konfigurasi, service, kontroler, dan dua modul JavaScript baru.

---

## 1. Ringkasan arsitektur

```
┌─────────────────────────── BROWSER (klien) ───────────────────────────┐
│  Pre-Order  ──► simpan pesanan ke Firestore  (Firebase Web SDK)       │
│                          │ koleksi: orders/{orderId}                  │
│                          ▼                                             │
│  Pembayaran ──► POST /acicu/pembayaran/snap  ──► Snap token            │
│                 snap.pay(token) ──► Midtrans menampilkan QRIS          │
└───────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌──────────────────────────── SERVER (Laravel) ─────────────────────────┐
│  PaymentController@snapToken  ── MidtransService ──► Midtrans Snap API │
│  PaymentController@notification ◄── webhook Midtrans (status bayar)    │
│        └── FirestoreService (Service Account) ──► update orders/{id}   │
│  Admin\OrderController@index ── FirestoreService ──► baca semua order  │
└───────────────────────────────────────────────────────────────────────┘
```

- **Klien** hanya membuat pesanan (`create`) ke Firestore via Web SDK.
- **Server** memakai Service Account (Firestore REST API) untuk webhook &
  panel admin. Akses server melewati Security Rules (seperti Admin SDK).
- **Midtrans Snap** dipakai untuk QRIS otomatis. Server Key hanya ada di
  server; browser hanya memegang Client Key (publik).

---

## 2. Variabel lingkungan (`.env`)

Tambahkan blok berikut (sudah ditambahkan ke `.env` dan `.env.example`):

```dotenv
# Midtrans (QRIS via Snap)
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=Mid-client-xxxxxxxxxxxxxxxx
MIDTRANS_SERVER_KEY=Mid-server-xxxxxxxxxxxxxxxx      # RAHASIA
MIDTRANS_IS_PRODUCTION=false                          # false = sandbox
MIDTRANS_ENABLED_PAYMENTS=other_qris                  # kanal QRIS generik Midtrans
MIDTRANS_FINISH_URL="${APP_URL}/acicu/status"
MIDTRANS_UNFINISH_URL="${APP_URL}/acicu/pembayaran"

# Firebase / Firestore
FIREBASE_PROJECT_ID=acicu-pemweb
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_API_KEY=
FIREBASE_AUTH_DOMAIN=acicu-pemweb.firebaseapp.com
FIREBASE_STORAGE_BUCKET=acicu-pemweb.appspot.com
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_APP_ID=
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

> **Penting:** `MIDTRANS_SERVER_KEY` dan file Service Account bersifat rahasia.
> `.env` dan `storage/app/firebase/*.json` sudah dikecualikan dari Git
> (lihat `.gitignore`). **Server Key yang pernah dibagikan lewat chat sebaiknya
> di-rotate** di dashboard Midtrans.

---

## 3. Langkah konfigurasi Firebase

Console: <https://console.firebase.google.com/project/acicu-pemweb>

### 3.1 Web Config (untuk klien)

1. **Project settings (⚙) → General → Your apps → Web app**.
2. Salin objek `firebaseConfig`, lalu isi `.env`:
   `FIREBASE_API_KEY`, `FIREBASE_AUTH_DOMAIN`, `FIREBASE_PROJECT_ID`,
   `FIREBASE_STORAGE_BUCKET`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_APP_ID`.

Nilai ini disuntik ke browser lewat `resources/views/partials/acicu-config.blade.php`
(menjadi `window.ACICU_CONFIG.firebase`). Web config **bukan rahasia**, tetapi
sebaiknya dibatasi dengan **Firebase App Check** dan *API key restrictions*.

### 3.2 Service Account (untuk server)

1. **Project settings → Service accounts → Generate new private key**.
2. Simpan file JSON sebagai `storage/app/firebase/service-account.json`.
3. Pastikan `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`.

### 3.3 Security Rules

Konten ada di `firebase/firestore.rules`:

- `orders/{orderId}`: `create` boleh dari klien (dengan validasi),
  `read/update/delete` **ditolak** dari klien.
- Server (Service Account) tetap bisa baca/update (bypass rules).

Terapkan lewat salah satu cara:

```bash
# Cara A: Firebase CLI
npm i -g firebase-tools
firebase login
firebase deploy --only firestore:rules
```

Cara B: Console → **Firestore Database → Rules** → tempel isi file → Publish.

### 3.4 Model data

Koleksi **`orders`**, dokumen id = nomor PO (`PO-1234`):

```jsonc
{
  "orderId": "PO-1234",
  "items": [{ "id": "aci-1", "name": "Aci Bungcu", "variant": "Original", "price": 10000, "qty": 2 }],
  "subtotal": 20000,
  "ongkir": 0,
  "total": 20000,
  "recipient": { "nama": "...", "whatsapp": "08...", "alamat": "...", "catatan": "" },
  "delivery": { "tanggal": "2026-10-20", "slot": "11:00" },
  "kitchenSchedule": "09:00",
  "paymentStatus": "menunggu",        // menunggu | berhasil | gagal | refund
  "payment": { "provider": "midtrans", "method": "qris", "transactionId": "..." },
  "source": "web",
  "createdAt": "2026-10-16T09:00:00Z"
}
```

---

## 4. Langkah konfigurasi Midtrans

Dashboard Sandbox: <https://dashboard.sandbox.midtrans.com>

### 4.1 Access Keys

**Settings → Access Keys / Credentials** → salin:

- **Client Key** → `MIDTRANS_CLIENT_KEY` (dipakai Snap.js di browser).
- **Server Key** → `MIDTRANS_SERVER_KEY` (dipakai server, rahasia).

### 4.2 Payment Notification URL (webhook)

**Settings → Configuration → Payment Notification URL**, isi:

```
https://<domain-anda>/midtrans/notification
```

> **Batasan penting (dari dokumentasi Midtrans):**
> - **Dilarang memakai format IP address** (mis. `http://70.153.142.126/...`).
> - **Dilarang memakai port tidak standar** (mis. `:8000`). Pakai port **80/443**.
> - Sebaiknya pakai **HTTPS** dengan sertifikat valid, dan **tanpa redirect**.

Artinya URL VPS saat ini (`http://70.153.142.126:8000/midtrans/notification`)
**tidak bisa** dipakai sebagai Notification URL. Pilihan:

1. **Domain + HTTPS (produksi).** Arahkan domain ke VPS, pasang reverse proxy
   (Nginx/Caddy) dengan Let's Encrypt di port 443 → diteruskan ke container.
   Lalu set `https://domain-anda/midtrans/notification`.
2. **Tunnel untuk uji sandbox.** Contoh:
   ```bash
   cloudflared tunnel --url http://localhost:8000
   # atau
   ngrok http 8000
   ```
   Pakai URL HTTPS dari tunnel (port 443) sebagai Notification URL.

Sementara webhook belum tersedia, pembayaran tetap terverifikasi otomatis lewat
endpoint **`POST /acicu/pembayaran/verify`** yang dipicu browser setelah
`snap.pay()` sukses (server menanyakan status ke Midtrans lalu menulis ke
Firestore). Lihat bagian 5 & 6.

Signature webhook diverifikasi di server (SHA-512 dari
`order_id + status_code + gross_amount + ServerKey`).

### 4.3 Testing QRIS di sandbox

1. Selesaikan pre-order → halaman pembayaran → **Bayar via QRIS**.
2. Snap menampilkan QR. Simulasikan pembayaran di **QRIS Simulator**:
   <https://simulator.sandbox.midtrans.com/>.
3. Webhook akan menandai pesanan `paymentStatus: berhasil` di Firestore.

---

## 5. Struktur file & penjelasan kode

### Konfigurasi

| File | Fungsi |
|---|---|
| `config/midtrans.php` | Kredensial & endpoint Midtrans (sandbox/produksi otomatis). |
| `config/firebase.php` | Project ID, path Service Account, web config, nama koleksi. |

### Service (logika inti)

| File | Fungsi |
|---|---|
| `app/Services/MidtransService.php` | `createSnapToken()` membuat transaksi Snap via HTTP Basic (Server Key). |
| `app/Services/FirestoreService.php` | Akses Firestore REST v1: `set()`, `update()`, `get()`, `all()`. Token OAuth2 dari Service Account (JWT RS256) dan di-cache 50 menit. Tanpa paket pihak ketiga. |

### Kontroler & rute

| File / Rute | Fungsi |
|---|---|
| `POST /acicu/pembayaran/snap` → `PaymentController@snapToken` | Validasi pesanan, buat Snap token, sinkronkan meta ke Firestore. |
| `POST /acicu/pembayaran/verify` → `PaymentController@verify` | Fallback: cek status ke Midtrans lalu update Firestore (dipicu browser setelah bayar). |
| `POST /midtrans/notification` → `PaymentController@notification` | Webhook Midtrans; verifikasi signature; update status di Firestore. Dikecualikan dari CSRF (`bootstrap/app.php`). |
| `GET /acicu/status/{order}` → `PaymentController@status` | (Opsional) cek status pesanan dari Firestore. |
| `GET /admin/pesanan` → `Admin\OrderController@index` | Baca daftar pesanan dari Firestore untuk panel admin. |

### Tampilan (perubahan minimal, desain tetap)

| File | Perubahan |
|---|---|
| `resources/views/partials/acicu-config.blade.php` | **Baru.** Menyuntik `window.ACICU_CONFIG` (Firebase + Midtrans client key + endpoint). |
| `resources/views/acicu-preorder.blade.php` | Tambah meta CSRF + Firebase SDK + `acicu-firebase.js`. |
| `resources/views/acicu-payment.blade.php` | Banner & tombol **kondisional**: bila Client Key ada → tampil **QRIS Midtrans**; bila tidak → tetap mode simulasi seperti sebelumnya. |
| `resources/views/acicu-status.blade.php` | Tambah partial config (untuk sinkron status dari server). |
| `resources/views/admin/orders.blade.php` | Tabel diisi dari Firestore memakai markup & kelas CSS yang sudah ada; empty state tetap. |

### JavaScript

| File | Fungsi |
|---|---|
| `public/js/acicu-firebase.js` | **Baru.** Inisialisasi Firebase + `AcicuFirestore.saveOrder(order)`. Non-aktif otomatis bila config kosong. |
| `public/js/acicu-midtrans.js` | **Baru.** Tombol `#btnPayQris` → minta token → `snap.pay()` → tampilkan QRIS. |
| `public/js/acicu-checkout.js` | Hook kecil: simpan ke Firestore setelah pre-order, teruskan hasil Snap ke `finish()`, dan sinkron status dari server di halaman status. |

### Lainnya

| File | Fungsi |
|---|---|
| `firebase/firestore.rules` | Security Rules (klien hanya boleh `create`). |
| `firebase/firestore.indexes.json`, `firebase.json`, `.firebaserc` | Konfigurasi Firebase CLI. |

---

## 6. Alur data end-to-end

1. **Pre-order** (`initPreorder`) → pesanan disimpan ke `localStorage` **dan**
   Firestore `orders/{PO-xxxx}` (`AcicuFirestore.saveOrder`).
2. **Pembayaran** → klik *Bayar via QRIS* → `POST /acicu/pembayaran/snap` →
   server membuat Snap token (`MidtransService`) → `snap.pay(token)` → QRIS.
3. **Pelanggan membayar** → Midtrans mengirim webhook ke
   `/midtrans/notification` → `PaymentController@notification` memverifikasi
   signature lalu `FirestoreService::update()` menulis
   `paymentStatus` dan detail `payment`.
   *Fallback:* bila webhook tak terjangkau, browser memanggil
   `POST /acicu/pembayaran/verify` setelah `snap.pay()` sukses; server
   menanyakan status ke Midtrans (`MidtransService::getTransactionStatus`)
   lalu menulis hasilnya ke Firestore. Hasil sama, tanpa perlu webhook publik.
4. **Halaman status** membaca `localStorage`, lalu menyinkronkan status terbaru
   dari `GET /acicu/status/{order}` (bila berbeda → refresh badge).
5. **Admin** membuka `/admin/pesanan` → `FirestoreService::all('orders')`
   menampilkan daftar pesanan + status pembayaran.

---

## 7. Pengujian cepat (opsional)

```bash
php artisan config:clear

# Cara cepat: cek konfigurasi + koneksi sekaligus (disarankan)
php artisan acicu:firebase-check

# Manual (setara dengan yang dipakai panel admin)
php artisan tinker
>>> app(App\Services\FirestoreService::class)->configurationIssue();  // null bila siap
>>> app(App\Services\FirestoreService::class)->all('orders');         // array
```

> Catatan: `createSnapToken()` dan webhook tidak dapat diuji tanpa kredensial
> Midtrans yang valid, dan webhook memerlukan URL publik (mis. ngrok).

---

## 8. Keamanan

- **Server Key** hanya di `.env` (server). Jangan pernah dikirim ke browser.
- **Service Account** disimpan di `storage/app/firebase/` (di-*gitignore*).
- **Firestore Rules** melarang klien membaca/mengubah data pesanan.
- Untuk produksi, aktifkan **Firebase App Check** dan batasi **API key**
  (HTTP referrer) di Google Cloud Console.
- QRIS Midtrans di mode sandbox; ubah `MIDTRANS_IS_PRODUCTION=true` (dan pakai
  kredensial produksi) saat rilis.

---

## 9. Troubleshooting

| Gejala | Kemungkinan & solusi |
|---|---|
| Popup QRIS tidak muncul | Client Key salah; cek `data-client-key` pada tag Snap.js dan `MIDTRANS_CLIENT_KEY`. |
| `502 Gagal membuat transaksi` | Server Key salah / `MIDTRANS_ENABLED_PAYMENTS` tidak valid. Cek `storage/logs/laravel.log`. |
| Webhook selalu **403 Invalid signature** | `MIDTRANS_SERVER_KEY` berbeda dari yang dipakai Midtrans. Samakan. |
| Firestore error 403/PERMISSION_DENIED | Service Account tidak punya akses / path salah. Periksa `FIREBASE_CREDENTIALS`. |
| Pesanan tersimpan tapi status tetap *menunggu* | Webhook belum diterima. Pastikan URL publik & terdaftar di dashboard Midtrans. |
| Data admin kosong | `FIREBASE_*` belum lengkap → kartu pesan di `/admin/pesanan` akan menjelaskan. |

---

## 10. Yang sengaja TIDAK dirombak

- Struktur halaman, CSS, dan alur simulasi tetap dipertahankan. Integrasi aktif
  hanya bila Client Key Midtrans terisi; jika belum, halaman berjalan seperti
  semula (mode simulasi).
- Tidak ada paket composer/npm baru yang ditambahkan, sehingga versi Symfony
  dan dependensi lain pada project tetap utuh.

---

## 11. Deploy ke VPS (Docker)

Project ini berjalan via Docker Compose (`docker-compose.yml`, port host
`8000`). Deploy perubahan integrasi:

```bash
# 1) Di komputer pengembang: commit & push
git add .
git commit -m "Konfigurasi Firebase + QRIS Midtrans"
git push origin main

# 2) Di VPS
git pull
docker compose up -d --build
docker compose exec app php artisan config:clear
```

### 11.1 Set `APP_URL` di `.env` VPS

`APP_URL` dipakai untuk **redirect finish** Snap dan tautan status. Karena
`docker-compose.yml` kini memakai `${APP_URL:-...}`, cukup set di `.env` VPS:

```dotenv
APP_URL=http://70.153.142.126:8000
```

Lalu `docker compose up -d` agar diterapkan. Redirect browser bekerja dengan
IP:port; hanya **webhook** yang memerlukan domain + port standar (lihat 4.2).

### 11.2 Web Config & Service Account Firebase

- **Web Config** (`FIREBASE_API_KEY`, dll) → tulis di `.env` VPS. Nilai ini
  diteruskan ke container lewat `env_file`.
- **Service Account** → karena `storage` di-mount sebagai volume, salin file ke
  dalam container:

  ```bash
  docker compose cp storage/app/firebase/service-account.json app:/var/www/html/storage/app/firebase/service-account.json
  # atau buat bind mount (lihat catatan di docker-compose.yml)
  ```

  Pastikan `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`.

### 11.3 Catatan `docker/start.sh`

Skrip start menjalankan `php artisan key:generate --force` **setiap** container
dinyalakan. Akibatnya `APP_KEY` berganti tiap restart (sesi login ikut
ter-reset). Untuk produksi sebaiknya dihapus dan set `APP_KEY` tetap di `.env`.
Ini tidak memengaruhi integrasi Firebase/Midtrans, tetapi perlu diketahui.

### 11.4 Urutan verifikasi di VPS

1. `http://70.153.142.126:8000/acicu/pre-order` → isi pesanan.
2. Cek dokumen muncul di Firestore console (koleksi `orders`).
3. `/acicu/pembayaran` → **Bayar via QRIS** → scan QRIS di
   <https://simulator.sandbox.midtrans.com/>.
4. `/acicu/status` dan `/admin/pesanan` menampilkan status **Dibayar**.
