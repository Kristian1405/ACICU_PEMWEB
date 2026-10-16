<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\OrderStats;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * ReportController (Admin)
 * ------------------------------------------------------------------
 * Rekap transaksi dari koleksi "orders" (Cloud Firestore) dengan filter
 * rentang tanggal. Hanya pesanan berstatus "berhasil" yang dihitung
 * sebagai pendapatan; pesanan lain tetap tampil pada tabel sebagai info.
 */
class ReportController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(Request $request): View
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
            $error = 'Gagal memuat data transaksi dari Firestore: '.$e->getMessage();
        }

        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));

        $filtered = array_values(array_filter($orders, function (array $order) use ($from, $to): bool {
            $date = OrderStats::dateOf($order);

            if ($from && $date < $from) {
                return false;
            }

            return ! ($to && $date > $to);
        }));

        return view('admin.reports', [
            'orders' => $filtered,
            'stats' => OrderStats::summarize($filtered),
            'from' => $from,
            'to' => $to,
            'error' => $error,
        ]);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
