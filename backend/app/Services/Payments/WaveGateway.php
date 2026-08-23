<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Intégration Wave (Checkout API). À valider/ajuster avec la documentation
 * officielle et un compte marchand réel avant mise en production — cette classe
 * suit la structure documentée publiquement (session de paiement + webhook signé)
 * mais n'a pas pu être testée contre l'environnement Wave réel dans cet
 * environnement de développement (aucun identifiant marchand disponible ici).
 */
class WaveGateway implements PaymentGateway
{
    public function initiateCheckout(Payment $payment): CheckoutResult
    {
        $response = Http::withToken(config('payments.wave.api_key'))
            ->baseUrl(config('payments.wave.base_url'))
            ->timeout(15)
            ->post('/v1/checkout/sessions', [
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'client_reference' => $payment->reference,
                'success_url' => config('payments.success_url'),
                'error_url' => config('payments.error_url'),
            ]);

        if ($response->failed()) {
            Log::error('Wave checkout initiation failed', ['response' => $response->body()]);
            throw new RuntimeException('Impossible de créer la session de paiement Wave.');
        }

        $data = $response->json();

        return new CheckoutResult(
            providerReference: $data['id'],
            checkoutUrl: $data['wave_launch_url'] ?? null,
            raw: $data,
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('payments.wave.webhook_secret');
        $signatureHeader = $request->header('Wave-Signature');

        if (blank($secret) || blank($signatureHeader)) {
            return false;
        }

        // Format documenté : "t=<timestamp>,v1=<hmac_sha256(t.payload)>".
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$key] = $value;
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'].'.'.$request->getContent(), $secret);

        return hash_equals($expected, $parts['v1']);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->json()->all();

        return new WebhookEvent(
            providerReference: $payload['data']['id'] ?? $payload['id'] ?? '',
            succeeded: ($payload['type'] ?? null) === 'checkout.session.completed'
                && ($payload['data']['payment_status'] ?? null) === 'succeeded',
            raw: $payload,
        );
    }

    public function checkStatus(Payment $payment): bool
    {
        $reference = $payment->transactions()->latest()->first()?->provider_reference;

        if (! $reference) {
            return false;
        }

        try {
            $response = Http::withToken(config('payments.wave.api_key'))
                ->baseUrl(config('payments.wave.base_url'))
                ->timeout(10)
                ->get("/v1/checkout/sessions/{$reference}");
        } catch (ConnectionException $e) {
            Log::warning('Wave status check failed: '.$e->getMessage());

            return false;
        }

        return $response->successful() && ($response->json('payment_status') === 'succeeded');
    }
}
