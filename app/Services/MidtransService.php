<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * MidtransService
 * ------------------------------------------------------------------
 * Pembungkus tipis untuk Midtrans Snap API. Tidak memakai SDK pihak
 * ketiga agar tidak menambah dependensi / mengubah versi paket yang
 * sudah ada di project. Autentikasi Snap memakai HTTP Basic dengan
 * Server Key sebagai username dan password kosong.
 *
 * Alur QRIS otomatis:
 *  1. Server memanggil createSnapToken()  -> dapat Snap token.
 *  2. Browser menjalankan snap.pay(token) -> Midtrans menampilkan QRIS.
 *  3. Pelanggan membayar -> Midtrans mengirim notifikasi ke webhook
 *     (lihat PaymentController::notification()).
 */
class MidtransService
{
    public function isConfigured(): bool
    {
        return filled(config('midtrans.server_key'));
    }

    /**
     * Buat transaksi Snap dan kembalikan token + redirect URL.
     *
     * @param  array{
     *     order_id: string,
     *     gross_amount: int,
     *     items?: array<int, array<string, mixed>>,
     *     customer?: array<string, mixed>,
     * }  $order
     * @return array{token: string, redirect_url: ?string}
     */
    public function createSnapToken(array $order): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi pada file .env.');
        }

        $payload = array_filter([
            'transaction_details' => [
                'order_id' => (string) $order['order_id'],
                'gross_amount' => (int) $order['gross_amount'],
            ],
            'item_details' => $order['items'] ?? null,
            'customer_details' => $order['customer'] ?? null,
            'enabled_payments' => config('midtrans.enabled_payments') ?: null,
            'callbacks' => array_filter([
                'finish' => config('midtrans.finish_url'),
                'unfinish' => config('midtrans.unfinish_url'),
            ]),
        ], static fn ($value) => $value !== null && $value !== []);

        $response = $this->request()->post(config('midtrans.snap_url'), $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal membuat transaksi Midtrans: '.$response->status().' '.$response->body()
            );
        }

        return [
            'token' => (string) $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
        ];
    }

    /**
     * Ambil status transaksi langsung dari Midtrans API (untuk rekonsiliasi
     * bila webhook tidak sempat diterima).
     *
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $orderId): array
    {
        $response = $this->request()->get(config('midtrans.api_base_url').'/'.$orderId.'/status');

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil status Midtrans: '.$response->status().' '.$response->body()
            );
        }

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) config('midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
