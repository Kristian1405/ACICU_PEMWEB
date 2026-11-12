<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FirestoreService;
use App\Services\MidtransService;
use App\Services\OwnerNotifier;
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
        private readonly OwnerNotifier $notifier,
    ) {}

    /**
     * POST /acicu/pembayaran/snap
     * Buat Snap token (QRIS). Harga & total DIHITUNG ULANG di server dari
     * tabel products; nilai harga/total dari browser tidak dipercaya.
     */
    public function snapToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]{1,64}$/'],
        ]);
        $orderId = $validated['order_id'];

        if (! $this->firestore->isConfigured()) {
            return response()->json(['message' => 'Penyimpanan pesanan (Firebase) belum dikonfigurasi di server.'], 503);
        }

        try {
            $order = $this->firestore->get((string) config('firebase.orders_collection'), $orderId);
        } catch (\Throwable $e) {
            Log::error('Firestore baca pesanan gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membaca pesanan. Coba lagi.'], 502);
        }

        if (! $order) {
            return response()->json(['message' => 'Pesanan belum tersimpan. Kembali ke formulir dan ulangi pemesanan.'], 404);
        }

        if (($order['paymentStatus'] ?? 'menunggu') === 'berhasil') {
            return response()->json(['message' => 'Pesanan ini sudah dibayar.'], 409);
        }

        try {
            [$lines, $total] = $this->priceOrder($order);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Pakai ulang token yang masih baru agar tombol bayar bisa dibuka lagi.
        $existing = $order['payment']['snapToken'] ?? null;
        $issuedAt = isset($order['payment']['updatedAt']) ? strtotime((string) $order['payment']['updatedAt']) : 0;
        if ($existing && (int) ($order['payment']['amount'] ?? 0) === $total && $issuedAt > time() - 3600) {
            return response()->json(['token' => $existing, 'redirect_url' => null]);
        }

        $recipient = (array) ($order['recipient'] ?? []);

        try {
            $result = $this->midtrans->createSnapToken([
                'order_id' => $orderId,
                'gross_amount' => $total,
                'items' => $this->mapItemDetails($lines),
                'customer' => $this->mapCustomerDetails([
                    'first_name' => $recipient['nama'] ?? null,
                    'phone' => $recipient['whatsapp'] ?? null,
                    'address' => $recipient['alamat'] ?? null,
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans snapToken gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi atau buat pesanan baru.',
            ], 502);
        }

        $isNew = empty($existing);

        $this->persistPaymentMeta($orderId, [
            'items' => array_map(static fn (array $l): array => [
                'id' => $l['id'], 'name' => $l['name'], 'variant' => $l['variant'],
                'price' => $l['price'], 'qty' => $l['quantity'],
            ], $lines),
            'subtotal' => $total,
            'ongkir' => 0,
            'total' => $total,
            'paymentStatus' => 'menunggu',
            'payment' => [
                'provider' => 'midtrans',
                'method' => 'qris',
                'snapToken' => $result['token'],
                'amount' => $total,
                'updatedAt' => now()->toIso8601String(),
            ],
        ]);

        if ($isNew) {
            $this->notifier->notify(sprintf(
                'Pesanan baru %s dari %s - Rp%s (menunggu pembayaran)',
                $orderId,
                $recipient['nama'] ?? '-',
                number_format($total, 0, ',', '.')
            ));
        }

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

        if ($orderId !== '') {
            $this->applyTransaction($orderId, $payload);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * GET /acicu/status/{order}
     * (Opsional) Baca status pesanan langsung dari Firestore.
     */
    public function status(string $order): JsonResponse
    {
        if (! preg_match('/^[A-Za-z0-9._-]{1,64}$/', $order) || ! $this->firestore->isConfigured()) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        $data = $this->firestore->get(config('firebase.orders_collection'), $order);

        if (! $data) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json([
            'order_id' => $order,
            'paymentStatus' => $data['paymentStatus'] ?? 'menunggu',
            'fulfillment' => $data['fulfillment'] ?? 'baru',
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
            'order_id' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]{1,64}$/'],
        ]);

        $orderId = $validated['order_id'];

        try {
            $transaction = $this->midtrans->getTransactionStatus($orderId);
        } catch (\Throwable $e) {
            Log::error('Midtrans verify gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memverifikasi status pembayaran.'], 502);
        }

        $status = $this->applyTransaction($orderId, $transaction);

        return response()->json([
            'order_id' => $orderId,
            'paymentStatus' => $status ?? 'menunggu',
        ]);
    }

    /* ==============================================================
     |  Helpers
     * ============================================================== */

    /**
     * Hitung ulang item & total dari tabel products (sumber harga resmi).
     *
     * @param  array<string, mixed>  $order
     * @return array{0: array<int, array{id: string, name: string, variant: string, price: int, quantity: int}>, 1: int}
     */
    private function priceOrder(array $order): array
    {
        $items = (array) ($order['items'] ?? []);

        if ($items === [] || count($items) > 30) {
            throw new \InvalidArgumentException('Daftar pesanan tidak valid.');
        }

        $lines = [];
        $total = 0;

        foreach ($items as $item) {
            $item = (array) $item;
            $qty = (int) ($item['qty'] ?? 0);

            if ($qty < 1 || $qty > 99) {
                throw new \InvalidArgumentException('Jumlah item tidak valid.');
            }

            $product = Product::active()->where('slug', (string) ($item['id'] ?? ''))->first();

            if (! $product) {
                throw new \InvalidArgumentException('Ada produk yang sudah tidak tersedia. Silakan pesan ulang.');
            }

            $lines[] = [
                'id' => (string) $product->slug,
                'name' => (string) $product->name,
                'variant' => (string) ($item['variant'] ?? ''),
                'price' => (int) $product->price,
                'quantity' => $qty,
            ];
            $total += (int) $product->price * $qty;
        }

        if ($total < 1) {
            throw new \InvalidArgumentException('Total pesanan tidak valid.');
        }

        return [$lines, $total];
    }

    /**
     * Terapkan hasil transaksi Midtrans (webhook / verify) ke pesanan.
     * Aman terhadap: nominal tidak cocok, notifikasi terlambat, dan duplikat.
     *
     * @param  array<string, mixed>  $txn
     * @return string|null status pesanan setelah diproses (null = tidak ditemukan)
     */
    private function applyTransaction(string $orderId, array $txn): ?string
    {
        if (! $this->firestore->isConfigured()) {
            return null;
        }

        try {
            $order = $this->firestore->get((string) config('firebase.orders_collection'), $orderId);
        } catch (\Throwable $e) {
            Log::error('Firestore baca pesanan gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $order) {
            Log::warning('Transaksi untuk pesanan yang tidak dikenal', ['order_id' => $orderId]);

            return null;
        }

        $current = (string) ($order['paymentStatus'] ?? 'menunggu');

        if ((int) round((float) ($txn['gross_amount'] ?? 0)) !== (int) ($order['total'] ?? -1)) {
            Log::warning('Nominal transaksi tidak cocok dengan pesanan', [
                'order_id' => $orderId,
                'midtrans' => $txn['gross_amount'] ?? null,
                'order' => $order['total'] ?? null,
            ]);

            return $current;
        }

        $new = $this->mapTransactionStatus(
            (string) ($txn['transaction_status'] ?? ''),
            (string) ($txn['fraud_status'] ?? '')
        );

        // Jangan menurunkan status: "berhasil" tidak boleh kembali ke menunggu/gagal,
        // dan pesanan yang sudah refund tetap refund.
        if (($current === 'berhasil' && in_array($new, ['menunggu', 'gagal'], true))
            || ($current === 'refund' && $new !== 'refund')) {
            return $current;
        }

        $this->persistPaymentMeta($orderId, [
            'paymentStatus' => $new,
            'payment' => [
                'provider' => 'midtrans',
                'method' => $txn['payment_type'] ?? 'qris',
                'snapToken' => $order['payment']['snapToken'] ?? null,
                'amount' => $order['payment']['amount'] ?? (int) ($order['total'] ?? 0),
                'transactionId' => $txn['transaction_id'] ?? null,
                'transactionStatus' => $txn['transaction_status'] ?? null,
                'fraudStatus' => $txn['fraud_status'] ?? null,
                'grossAmount' => $txn['gross_amount'] ?? null,
                'paidAt' => $new === 'berhasil'
                    ? ($order['payment']['paidAt'] ?? now()->toIso8601String())
                    : ($order['payment']['paidAt'] ?? null),
                'updatedAt' => now()->toIso8601String(),
            ],
        ]);

        if ($new === 'berhasil' && $current !== 'berhasil') {
            $this->notifier->notify(sprintf(
                'Pembayaran masuk: %s - %s - Rp%s',
                $orderId,
                ($order['recipient']['nama'] ?? '-'),
                number_format((int) ($order['total'] ?? 0), 0, ',', '.')
            ));
        }

        return $new;
    }

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
        $serverKey = (string) config('midtrans.signature_key');

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
