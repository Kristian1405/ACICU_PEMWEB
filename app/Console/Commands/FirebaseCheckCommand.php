<?php

namespace App\Console\Commands;

use App\Services\FirestoreService;
use Illuminate\Console\Command;

/**
 * Memeriksa apakah panel admin sudah bisa membaca Cloud Firestore.
 *
 * Contoh:
 *   php artisan acicu:firebase-check
 *   docker compose exec app php artisan acicu:firebase-check
 *
 * Perintah ini memakai jalur yang sama dengan panel admin (Service
 * Account), sehingga hasilnya mencerminkan kondisi sebenarnya.
 */
class FirebaseCheckCommand extends Command
{
    protected $signature = 'acicu:firebase-check';

    protected $description = 'Cek konfigurasi & koneksi Firebase Firestore untuk panel admin';

    public function handle(FirestoreService $firestore): int
    {
        $this->line('Project   : '.(config('firebase.project_id') ?: '(kosong)'));
        $this->line('Kredensial: '.((string) config('firebase.credentials') ?: '(kosong)'));
        $this->newLine();

        if ($issue = $firestore->configurationIssue()) {
            $this->error('BELUM SIAP: '.$issue);
            $this->newLine();
            $this->warn('Langkah perbaikan:');
            $this->line('  1. Buka Firebase Console > Project settings > Service accounts.');
            $this->line('     https://console.firebase.google.com/project/'.(config('firebase.project_id') ?: 'PROJECT').'/settings/serviceaccounts/adminsdk');
            $this->line('  2. Klik "Generate new private key" -> unduh file JSON.');
            $this->line('  3. Simpan file itu sebagai: storage/app/firebase/service-account.json');
            $this->line('  4. Pastikan .env berisi FIREBASE_PROJECT_ID dan FIREBASE_CREDENTIALS.');
            $this->line('  5. Jalankan ulang perintah ini.');

            return self::FAILURE;
        }

        $this->info('Konfigurasi terbaca. Menguji koneksi ke Firestore...');

        try {
            $orders = $firestore->all(
                (string) config('firebase.orders_collection'),
                limit: 5,
                orderBy: 'createdAt',
                direction: 'DESCENDING',
            );
        } catch (\Throwable $e) {
            $this->error('GAGAL terhubung: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('BERHASIL terhubung ke Firestore.');
        $this->line('Contoh jumlah dokumen pesanan yang terbaca: '.count($orders));

        if ($orders === []) {
            $this->warn('Belum ada pesanan di koleksi "orders" (bukan error).');
        }

        return self::SUCCESS;
    }
}
