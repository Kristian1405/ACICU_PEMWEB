<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * POST /admin/pesanan/{order}/status
     * Ubah tahap pengerjaan pesanan (terpisah dari status pembayaran).
     */
    public function updateStatus(Request $request, string $order): RedirectResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9._-]{1,64}$/', $order) === 1, 404);

        $data = $request->validate([
            'fulfillment' => ['required', Rule::in(self::FULFILLMENT)],
        ]);

        $collection = (string) config('firebase.orders_collection');

        try {
            abort_if($this->firestore->get($collection, $order) === null, 404);

            $this->firestore->update($collection, $order, [
                'fulfillment' => $data['fulfillment'],
                'updatedAt' => now()->toIso8601String(),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengubah status: '.$e->getMessage());
        }

        return back()->with('status', "Status pesanan {$order} diperbarui.");
    }

    /**
     * GET /admin/pesanan/ringkasan
     * Ringkasan 10 pesanan terbaru untuk notifikasi (badge + suara) di panel.
     */
    public function summary(): JsonResponse
    {
        try {
            $orders = $this->firestore->isConfigured()
                ? $this->firestore->all((string) config('firebase.orders_collection'), limit: 10, orderBy: 'createdAt', direction: 'DESCENDING')
                : [];
        } catch (\Throwable $e) {
            return response()->json(['events' => [], 'error' => true]);
        }

        $events = array_map(static fn (array $o): array => [
            'key' => ($o['orderId'] ?? '').':'.($o['paymentStatus'] ?? 'menunggu'),
            'id' => $o['orderId'] ?? '',
            'nama' => $o['recipient']['nama'] ?? '-',
            'total' => (int) ($o['total'] ?? 0),
            'status' => $o['paymentStatus'] ?? 'menunggu',
        ], $orders);

        return response()->json(['events' => $events]);
    }

    public const FULFILLMENT = ['baru', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
}
