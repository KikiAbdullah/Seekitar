<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Credentials tidak boleh dipadukan dengan wildcard origin. Bila tidak
    // ada origin frontend yang dikonfigurasi, CORS ditolak seluruhnya.
    'allowed_origins' => array_values(array_filter([env('FRONTEND_URL')])) ,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
