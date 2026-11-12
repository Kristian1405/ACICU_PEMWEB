<?php

return [

    /*
    | Nomor WhatsApp pemilik (format internasional tanpa "+", mis. 6282286512274).
    | Dipakai untuk tombol "Chat Penjual" di halaman status pesanan pelanggan.
    | TIDAK ada pengiriman WhatsApp otomatis (hanya tautan klik-untuk-chat).
    */
    'owner_whatsapp' => env('ACICU_OWNER_WHATSAPP', '6282286512274'),

    /*
    | Notifikasi Telegram ke pemilik (opsional). Kosongkan bila tidak dipakai.
    | Buat bot lewat @BotFather, lalu isi token dan chat id.
    */
    'telegram_bot_token' => env('ACICU_TELEGRAM_BOT_TOKEN'),
    'telegram_chat_id' => env('ACICU_TELEGRAM_CHAT_ID'),


    /*
    | OPSIONAL. Bila diisi, hanya email di daftar ini yang boleh membuka /admin
    | (pisahkan koma). Kosong = semua akun yang login boleh (perilaku lama).
    */
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ACICU_ADMIN_EMAILS', ''))
    ))),
];
