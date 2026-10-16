<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase / Cloud Firestore
    |--------------------------------------------------------------------------
    |
    | Ada dua jalur akses yang dipakai aplikasi ACICU:
    |
    | 1. SERVER (bagian ini) -> memakai Service Account sehingga server dapat
    |    membaca/menulis Firestore untuk webhook pembayaran & panel admin.
    |    Service Account melewati (bypass) Firestore Security Rules.
    |
    | 2. KLIEN (web config di bawah) -> dipakai browser untuk membuat pesanan
    |    (create) dari halaman pre-order, sesuai Firestore Security Rules.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    | Path absolut/relatif ke file Service Account JSON. Disarankan:
    | storage/app/firebase/service-account.json
    | Bila diisi relatif, akan dihitung dari base_path().
    */
    'credentials' => env('FIREBASE_CREDENTIALS'),

    /*
    | Koleksi Firestore yang dipakai aplikasi.
    */
    'orders_collection' => env('FIREBASE_ORDERS_COLLECTION', 'orders'),
    'products_collection' => env('FIREBASE_PRODUCTS_COLLECTION', 'products'),

    /*
    |--------------------------------------------------------------------------
    | Web config (KLEN) - Firebase Console > Project settings > General
    |--------------------------------------------------------------------------
    */
    'web' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
    ],

];
