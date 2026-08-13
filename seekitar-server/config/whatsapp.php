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

        // ── Jalur cepat (socket) ────────────────────────────────────────────
        // Bila diisi, OTP TIDAK lewat HTTP melainkan di-publish ke channel
        // Redis yang disubscribe gateway Node (koneksi socket persisten —
        // sangat cepat, tanpa handshake per pesan). Kosongkan untuk memakai
        // HTTP biasa. Contoh: redis://127.0.0.1:6379
        'redis_url' => env('BAILEYS_REDIS_URL', ''),

        // Nama channel Redis (harus sama dengan WA_CHANNEL_SEND di gateway).
        'channel_send' => env('BAILEYS_CHANNEL_SEND', 'seekitar:wa:send'),

        // List Redis tempat gateway menulis hasil kirim (WA_RESULT_LIST).
        'result_list' => env('BAILEYS_RESULT_LIST', 'seekitar:wa:result:list'),

        // Detik menunggu konfirmasi kirim dari gateway sebelum fallback HTTP.
        'ack_timeout' => (int) env('BAILEYS_ACK_TIMEOUT', 5),
    ],

];
