<?php

return [
    'qris' => [
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'ALLEGIANT PET SHOP'),
        'static_payload' => env('PAYMENT_QRIS_STATIC_PAYLOAD'),
        'static_image_url' => env('PAYMENT_QRIS_STATIC_IMAGE_URL'),
    ],
    'pakasir' => [
        'base_url' => rtrim(env('PAKASIR_BASE_URL', 'https://app.pakasir.com'), '/'),
        'project_slug' => env('PAKASIR_PROJECT_SLUG'),
        'api_key' => env('PAKASIR_API_KEY'),
        'qris_only' => env('PAKASIR_QRIS_ONLY', true),
        'frontend_url' => env(
            'PAKASIR_FRONTEND_URL',
            explode(',', env('FRONTEND_URLS', 'http://localhost:5173'))[0] ?? 'http://localhost:5173'
        ),
    ],
];
