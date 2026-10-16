<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController
 * ------------------------------------------------------------------
 * Menjembatani halaman pembayaran ACICU dengan Midtrans Snap (QRIS)
 * dan menyinkronkan status pembayaran ke Cloud Firestore.
 *
 * Alur:
 *  1. snapToken()   : browser minta token -> dikembalikan ke Snap.js.
 *  2. notification(): Midtrans memanggil webhook ini saat status berubah.
 *  3. status()      : (opsional) baca status pesanan dari Firestore.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly FirestoreService $firestore,
    ) {}

    /**
     * POST /acicu/pembayaran/snap
     * Buat Snap token (QRIS) untuk sebuah pesanan.
     */
    public function snapToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:64'],
            'gross_amount' => ['required', 'integer', 'min:1'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['required_with:items', 'string', 'max:100'],
            'items.*.price' => ['required_with:items', 'integer', 'min:0'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.variant' => ['nullable', 'string', 'max:100'],
            'customer' => ['nullable', 'array'],
            'customer.first_name' => ['nullable', 'string', 'max:100'],
            'customer.phone' => ['nullable', 'string', 'max:25'],
            'customer.address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->midtrans->createSnapToken([
                'order_id' => $validated['order_id'],
                'gross_amount' => $validated['gross_amount'],
                'items' => $this->mapItemDetails($validated['items'] ?? []),
                'customer' => $this->mapCustomerDetails($validated['customer'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans snapToken gagal', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal membuat transaksi pembayaran. Periksa kredensial Midtrans.',
            ], 502);
        }

        // Sinkronisasi ke Firestore (best-effort, tidak menggagalkan pembayaran).
        $this->persistPaymentMeta($validated['order_id'], [
            'paymentStatus' => 'menunggu',
            'payment' => [
                'provider' => 'midtrans',
                'method' => 'qris',
                'snapToken' => $result['token'],
                'updatedAt' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'token' => $result['token'],
            'redirect_url' => $result['redirect_url'],
        ]);
    }

    /**
     * POST /midtrans/notification
     * Webhook dari Midtrans. Wajib dapat diakses publik (pakai tunnel saat lokal).
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->isValidSignature($payload)) {
            Log::warning('Midtrans notification: signature tidak valid', $payload);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $status = $this->mapTransactionStatus(
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? '')
        );

        if ($orderId !== '') {
            $this->persistPaymentMeta($orderId, [
                'paymentStatus' => $status,
                'payment' => [
                    'provider' => 'midtrans',
                    'method' => $payload['payment_type'] ?? null,
                    'transactionId' => $payload['transaction_id'] ?? null,
                    'transactionStatus' => $payload['transaction_status'] ?? null,
                    'fraudStatus' => $payload['fraud_status'] ?? null,
                    'grossAmount' => $payload['gross_amount'] ?? null,
                    'paidAt' => in_array($status, ['berhasil'], true) ? now()->toIso8601String() : null,
                    'updatedAt' => now()->toIso8601String(),
                ],
            ]);
        }

        Log::info('Midtrans notification diterima', [
            'order_id' => $orderId,
            'status' => $status,
        ]);

        return response()->json(['message' => 'OK']);
    }

    /**
     * GET /acicu/status/{order}
     * (Opsional) Baca status pesanan langsung dari Firestore.
     */
    public function status(string $order): JsonResponse
    {
        $data = $this->firestore->get(config('firebase.orders_collection'), $order);

        if (! $data) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json([
            'order_id' => $order,
            'paymentStatus' => $data['paymentStatus'] ?? 'menunggu',
        ]);
    }

    /**
     * POST /acicu/pembayaran/verify
     * Verifikasi status langsung ke Midtrans, dipicu browser setelah pembayaran.
     * Berguna sebagai fallback saat webhook belum dapat menjangkau server
     * (mis. server masih diakses lewat IP:port tanpa domain/HTTPS).
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:64'],
        ]);

        try {
            $transaction = $this->midtrans->getTransactionStatus($validated['order_id']);
        } catch (\Throwable $e) {
            Log::error('Midtrans verify gagal', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal memverifikasi status pembayaran.'], 502);
        }

        $paymentStatus = $this->mapTransactionStatus(
            (string) ($transaction['transaction_status'] ?? ''),
            (string) ($transaction['fraud_status'] ?? '')
        );

        $this->persistPaymentMeta($validated['order_id'], [
            'paymentStatus' => $paymentStatus,
            'payment' => [
                'provider' => 'midtrans',
                'method' => $transaction['payment_type'] ?? 'qris',
                'transactionId' => $transaction['transaction_id'] ?? null,
                'transactionStatus' => $transaction['transaction_status'] ?? null,
                'fraudStatus' => $transaction['fraud_status'] ?? null,
                'grossAmount' => $transaction['gross_amount'] ?? null,
                'paidAt' => $paymentStatus === 'berhasil' ? now()->toIso8601String() : null,
                'updatedAt' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'order_id' => $validated['order_id'],
            'paymentStatus' => $paymentStatus,
        ]);
    }

    /* ==============================================================
     |  Helpers
     * ============================================================== */

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItemDetails(array $items): array
    {
        return array_map(static function (array $item): array {
            $name = (string) $item['name'];

            if (! empty($item['variant'])) {
                $name .= ' ('.$item['variant'].')';
            }

            return [
                'id' => (string) ($item['id'] ?? $item['name']),
                'name' => mb_substr($name, 0, 50),
                'price' => (int) $item['price'],
                'quantity' => (int) $item['quantity'],
            ];
        }, $items);
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function mapCustomerDetails(array $customer): array
    {
        $details = array_filter([
            'first_name' => $customer['first_name'] ?? null,
            'phone' => $customer['phone'] ?? null,
        ], static fn ($value) => filled($value));

        if (! empty($customer['address'])) {
            $details['billing_address'] = ['address' => $customer['address']];
            $details['shipping_address'] = ['address' => $customer['address']];
        }

        return $details;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidSignature(array $payload): bool
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '' || empty($payload['signature_key'])) {
            return false;
        }

        $expected = hash('sha512', ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$serverKey);

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    private function mapTransactionStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'challenge' ? 'menunggu' : 'berhasil',
            'settlement' => 'berhasil',
            'pending' => 'menunggu',
            'deny', 'cancel', 'expire', 'failure' => 'gagal',
            'refund', 'partial_refund' => 'refund',
            default => 'menunggu',
        };
    }

    /**
     * Tulis ke Firestore tanpa menggagalkan alur utama bila Firebase belum siap.
     *
     * @param  array<string, mixed>  $data
     */
    private function persistPaymentMeta(string $orderId, array $data): void
    {
        if (! $this->firestore->isConfigured()) {
            return;
        }

        try {
            $this->firestore->update(
                (string) config('firebase.orders_collection'),
                $orderId,
                $data
            );
        } catch (\Throwable $e) {
            Log::error('Firestore sync gagal', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
