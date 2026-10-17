<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Konfigurasi kredensial Midtrans untuk pembayaran QRIS otomatis melalui
    | Snap. Nilai diambil dari file .env sehingga kunci rahasia tidak pernah
    | ikut ter-commit ke repositori.
    |
    | Dashboard Sandbox : https://dashboard.sandbox.midtrans.com
    | Dashboard Produksi: https://dashboard.midtrans.com
    |
    */

    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    'server_key' => env('MIDTRANS_SERVER_KEY'),

    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    /*
    | Metode pembayaran yang ditampilkan pada Snap. Default hanya QRIS
    | agar fokus pada QRIS otomatis. Bisa diisi beberapa, pisahkan koma.
    | Contoh: "other_qris,gopay,shopeepay"
    */
    'enabled_payments' => array_values(array_filter(array_map(
        static function (string $value): string {
            $value = trim($value);

            // Midtrans menamai kanal QRIS generik sebagai "other_qris",
            // bukan "qris". "qris" tetap diterima agar .env lama tidak rusak.
            return strtolower($value) === 'qris' ? 'other_qris' : $value;
        },
        explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', 'other_qris'))
    ))),

    'finish_url' => env('MIDTRANS_FINISH_URL'),

    'unfinish_url' => env('MIDTRANS_UNFINISH_URL'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint (otomatis mengikuti mode sandbox / produksi)
    |--------------------------------------------------------------------------
    */

    'snap_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions',

    'snap_script_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js',

    'api_base_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://api.midtrans.com/v2'
        : 'https://api.sandbox.midtrans.com/v2',

];
