<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver WhatsApp
    |--------------------------------------------------------------------------
    |
    | `log`     — OTP ditulis ke log (default di luar produksi, tanpa biaya).
    | `email`   — OTP dikirim lewat email (development).
    | `kirimwa` — Provider berbayar Kirim WA (produksi).
    | `baileys` — Gateway WhatsApp Web lokal (Baileys, Node.js sidecar).
    |             Jalankan: `cd seekitar-server/whatsapp-gateway && npm start`
    |
    */

    'driver' => env('WHATSAPP_DRIVER', env('OTP_DELIVERY', 'log')),

    'baileys' => [
        // URL service Node di `seekitar-server/whatsapp-gateway`.
        'url'     => env('BAILEYS_URL', 'http://127.0.0.1:3001'),

        // Token yang sama dengan BAILEYS_TOKEN di service Node. Kosong = tanpa
        // autentikasi (jangan dipakai bila service diekspos ke jaringan).
        'token'   => env('BAILEYS_TOKEN', ''),

        'timeout' => (int) env('BAILEYS_TIMEOUT', 10),
    ],

];
