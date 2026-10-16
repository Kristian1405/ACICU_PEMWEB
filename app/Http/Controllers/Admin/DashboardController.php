<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\OrderStats;
use Illuminate\Contracts\View\View;

/**
 * DashboardController (Admin)
 * ------------------------------------------------------------------
 * Menampilkan statistik nyata dari koleksi "orders" di Cloud Firestore
 * (total pesanan, pesanan hari ini, produk terlaris, pendapatan, tren 7
 * hari, dan aktivitas terbaru). Bila Firebase belum dikonfigurasi,
 * halaman tetap tampil dengan empty state + pesan jujur (tanpa data palsu).
 */
class DashboardController extends Controller
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
                    limit: 500,
                    orderBy: 'createdAt',
                    direction: 'DESCENDING',
                );
            }
        } catch (\Throwable $e) {
            $error = 'Gagal memuat data pesanan dari Firestore: '.$e->getMessage();
        }

        return view('admin.dashboard', [
            'stats' => OrderStats::summarize($orders),
            'error' => $error,
        ]);
    }
}
