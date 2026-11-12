# Catatan Perbaikan (patch keamanan + notifikasi)

## Yang diubah
1. **Harga dihitung di server.** `POST /acicu/pembayaran/snap` hanya menerima `order_id`. Server membaca pesanan dari Firestore, menghitung ulang harga dari tabel `products`, menulis total yang benar ke Firestore, lalu membuat QRIS. Harga dari browser diabaikan.
2. **Status pembayaran tidak bisa dipalsukan.** `firestore.rules` hanya mengizinkan klien membuat pesanan baru berstatus `menunggu` (tanpa data pembayaran). Status "berhasil" hanya ditulis server dari Midtrans. `onSuccess` kini bernilai `menunggu` bila server belum mengonfirmasi.
3. **Webhook lebih aman.** Nominal harus sama dengan pesanan, status tidak turun (berhasil -> menunggu/gagal diabaikan), duplikat tidak mengirim notifikasi ganda.
4. **(Opsional)** `ACICU_ADMIN_EMAILS` membatasi `/admin` ke email tertentu. Dikosongkan = perilaku lama. Catatan: selama pendaftaran `/register` terbuka, siapa pun yang mendaftar bisa membuka `/admin`; sebaiknya isi variabel ini.
5. **Route:** `contents` (rusak, view tidak ada) dibuang. `order_id` divalidasi (`A-Z a-z 0-9 . _ -`) dan di-encode di URL Midtrans.
6. **Admin pesanan:** ubah tahap pesanan (Baru/Diproses/Dikirim/Selesai/Dibatalkan) dan tombol chat WhatsApp pelanggan.
7. **Notifikasi:** badge/toast + bunyi + judul tab di panel admin (cek tiap 45 detik), Telegram opsional, dan tombol "Chat Penjual" di halaman status pelanggan. Tidak ada kirim WhatsApp otomatis.

## Langkah di server (urut)
1. Timpa file proyek dengan isi zip (jangan timpa `.env`, `storage/`, `vendor/`).
2. Tambahkan ke `.env`:
   ```
   ACICU_OWNER_WHATSAPP=6282286512274
   # opsional
   # ACICU_ADMIN_EMAILS=admin@acicu.test
   ACICU_TELEGRAM_BOT_TOKEN=
   ACICU_TELEGRAM_CHAT_ID=
   ```
3. `php artisan config:clear && php artisan migrate --force`
4. Akun admin yang sudah ada tetap dipakai (tidak perlu dibuat ulang).
5. **Publikasikan ulang Firestore rules:** `firebase deploy --only firestore:rules` (atau tempel isi `firebase/firestore.rules` di Firebase Console > Firestore > Rules > Publish). Tanpa ini celah lama tetap terbuka.
6. `php artisan acicu:firebase-check` harus lolos.
7. Docker: `docker compose up -d --build`.

## Yang harus disiapkan pemilik akun (temanmu)
| Kebutuhan | Di mana | Catatan |
|---|---|---|
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` | `.env` server | Jangan dikirim lewat chat. Sandbox dulu (`MIDTRANS_IS_PRODUCTION=false`). |
| Webhook URL di Midtrans | Dashboard Midtrans > Settings > Payment > Notification URL | Isi `https://acicu.me/midtrans/notification` (harus HTTPS dan publik). |
| `storage/app/firebase/service-account.json` | Server | Dari Firebase Console > Service accounts. Jangan di-commit. |
| `FIREBASE_*` | `.env` server | Sama seperti sebelumnya. |
| Bot Telegram (opsional) | @BotFather | Isi token dan chat id di `.env`. |

## Uji sebelum produksi
- `php artisan test` harus hijau.
- Buat pesanan di `/acicu/pre-order`, bayar QRIS sandbox, cek status jadi "Dibayar" di `/admin/pesanan`.
- Coba ubah harga lewat DevTools: total di Midtrans tetap sesuai harga produk di database.
- (Bila `ACICU_ADMIN_EMAILS` diisi) akun biasa dari `/register` membuka `/admin/dashboard`: harus 403.
