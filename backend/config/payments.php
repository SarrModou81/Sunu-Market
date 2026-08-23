<?php

return [
    // Pilote utilisé quand aucun fournisseur n'est explicitement demandé, ou en local/tests
    // sans identifiants réels configurés : "fake" simule un paiement réussi instantanément.
    'default_driver' => env('PAYMENTS_DRIVER', 'fake'),

    'currency' => 'XOF',

    'wave' => [
        'base_url' => env('WAVE_API_BASE_URL', 'https://api.wave.com'),
        'api_key' => env('WAVE_API_KEY'),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
    ],

    'orange_money' => [
        'base_url' => env('ORANGE_MONEY_API_BASE_URL'),
        'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        'webhook_secret' => env('ORANGE_MONEY_WEBHOOK_SECRET'),
    ],

    'boost_prices' => [
        '72h' => (int) env('BOOST_PRICE_72H', 500),
        '7d' => (int) env('BOOST_PRICE_7D', 1000),
        '30d' => (int) env('BOOST_PRICE_30D', 3000),
    ],

    'seller_pro_monthly_price' => (int) env('SELLER_PRO_MONTHLY_PRICE', 2000),

    // URLs de redirection après paiement côté app mobile (deep link) — l'activation
    // réelle ne se fait jamais ici mais uniquement via la confirmation serveur du webhook.
    'success_url' => env('PAYMENT_SUCCESS_URL', 'sunumarket://payments/success'),
    'error_url' => env('PAYMENT_ERROR_URL', 'sunumarket://payments/error'),
];
