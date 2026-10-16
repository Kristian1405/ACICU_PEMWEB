<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;

/**
 * OrderController (Admin)
 * ------------------------------------------------------------------
 * Menampilkan daftar pesanan pre-order yang tersimpan di Cloud
 * Firestore (koleksi "orders"). Pembacaan dilakukan dari sisi server
 * memakai Service Account sehingga tidak memerlukan login Firebase.
 *
 * Bila Firebase belum dikonfigurasi, halaman tetap tampil dengan empty
 * state + pesan jujur (tidak ada data palsu).
 */
class OrderController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(): View
    {
        $orders = [];
        $error = null;

        try {
            if ($issue = $this->firestore->configurationIssue()) {
                $error = 'Panel admin belum bisa membaca Firestore. '.$issue
                    .' Unduh Service Account dari Firebase Console (Project settings → Service accounts → Generate new private key), simpan sebagai storage/app/firebase/service-account.json, lalu jalankan: php artisan acicu:firebase-check';
            } else {
                $orders = $this->firestore->all(
                    (string) config('firebase.orders_collection'),
                    limit: 100,
                    orderBy: 'createdAt',
                    direction: 'DESCENDING',
                );
            }
        } catch (\Throwable $e) {
            $error = 'Gagal memuat data pesanan dari Firestore: '.$e->getMessage();
        }

        return view('admin.orders', [
            'orders' => $orders,
            'error' => $error,
        ]);
    }
}
