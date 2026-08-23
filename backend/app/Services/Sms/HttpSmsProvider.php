<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP générique pour un fournisseur SMS tiers (à adapter selon le
 * fournisseur retenu pour la production : Orange, Twilio, etc.). La clé API
 * ne doit jamais être exposée côté mobile ; seul ce service backend l'utilise.
 */
class HttpSmsProvider implements SmsProvider
{
    public function send(string $phone, string $message): bool
    {
        $baseUrl = config('otp.sms.base_url');
        $apiKey = config('otp.sms.api_key');

        if (blank($baseUrl) || blank($apiKey)) {
            Log::warning('SMS provider non configuré (SMS_PROVIDER_BASE_URL / SMS_PROVIDER_API_KEY manquants).');

            return false;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post($baseUrl, [
                    'to' => $phone,
                    'sender' => config('otp.sms.sender_id'),
                    'message' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Échec envoi SMS: '.$e->getMessage());

            return false;
        }
    }
}
