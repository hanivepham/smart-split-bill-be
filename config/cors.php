<?php

// config/cors.php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini dibaca oleh middleware HandleCors yang sudah otomatis
    | terpasang secara global di Laravel 11. Kamu tidak perlu mendaftarkan
    | middleware apapun — cukup atur file ini.
    |
    */

    // Path endpoint mana yang dikenakan aturan CORS ini.
    // 'api/*' = semua endpoint yang dimulai dengan /api/
    'paths' => ['api/*'],

    // HTTP Method yang diizinkan untuk dilakukan cross-origin.
    // ['*'] = izinkan semua (GET, POST, PUT, DELETE, OPTIONS, dsb.)
    'allowed_methods' => ['*'],

    // Origin (gabungan protokol + domain + port) yang diizinkan.
    // Sesuaikan dengan URL Frontend kamu saat development.
    'allowed_origins' => [
        'http://localhost:5173',     // Vite default port
        'http://127.0.0.1:5173',    // Alternatif localhost via IP
    ],

    // Pola regex untuk allowed_origins (tidak dipakai, biarkan kosong)
    'allowed_origins_patterns' => [],

    // Header yang boleh dikirim oleh browser dalam request.
    // ['*'] = izinkan semua header (termasuk Content-Type, Authorization, dsb.)
    'allowed_headers' => ['*'],

    // Header dari response server yang boleh dibaca oleh JavaScript Frontend.
    'exposed_headers' => [],

    // Berapa lama browser boleh meng-cache hasil preflight OPTIONS request (detik).
    // 0 = tidak di-cache (bagus untuk development agar perubahan langsung berlaku)
    'max_age' => 0,

    // Atur ke true jika perlu mengirim cookies/session lintas origin.
    // Untuk REST API stateless seperti ini: false.
    'supports_credentials' => false,

];