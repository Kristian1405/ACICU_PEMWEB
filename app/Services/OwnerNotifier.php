<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OwnerNotifier
 * ------------------------------------------------------------------
 * Memberi tahu pemilik saat ada pesanan baru / pembayaran masuk.
 * Selalu menulis log; mengirim Telegram bila token & chat id diisi.
 * Panel admin juga memantau pesanan baru (badge + suara) lewat polling.
 * Kegagalan notifikasi tidak pernah menggagalkan alur pembayaran.
 */
class OwnerNotifier
{
    public function notify(string $message): void
    {
        Log::info('Notifikasi pemilik: '.$message);

        $token = (string) config('acicu.telegram_bot_token');
        $chat = (string) config('acicu.telegram_chat_id');

        if ($token === '' || $chat === '') {
            return;
        }

        try {
            Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chat,
                'text' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram gagal terkirim', ['error' => $e->getMessage()]);
        }
    }
}
