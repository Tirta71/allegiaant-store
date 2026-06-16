<?php

return [
    'qris' => [
        'merchant_name' => env('PAYMENT_QRIS_MERCHANT_NAME', 'ALLEGIANT PET SHOP'),
        'static_payload' => env('PAYMENT_QRIS_STATIC_PAYLOAD'),
        'static_image_url' => env('PAYMENT_QRIS_STATIC_IMAGE_URL'),
    ],
];
