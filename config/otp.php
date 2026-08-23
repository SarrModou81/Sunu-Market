<?php

return [
    // Nombre de chiffres du code OTP.
    'length' => (int) env('OTP_LENGTH', 6),

    // Durée de validité d'un code, en minutes.
    'expiration_minutes' => (int) env('OTP_EXPIRATION_MINUTES', 5),

    // Nombre maximal de tentatives de vérification avant invalidation du code.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    // Délai minimal entre deux envois pour un même couple (téléphone, motif).
    'resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),

    // Pilote d'envoi : "log" (développement, écrit le code dans les logs) ou "sms" (fournisseur réel).
    'driver' => env('OTP_DRIVER', 'log'),

    'sms' => [
        'base_url' => env('SMS_PROVIDER_BASE_URL'),
        'api_key' => env('SMS_PROVIDER_API_KEY'),
        'sender_id' => env('SMS_PROVIDER_SENDER_ID', 'SunuMarket'),
    ],
];
