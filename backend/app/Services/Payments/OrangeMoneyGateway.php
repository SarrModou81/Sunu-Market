<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Intégration Orange Money Web Payment (OAuth2 client_credentials + API de paiement).
 * À valider/ajuster avec la documentation Orange Developer et un compte marchand réel
 * avant mise en production — non testée contre l'environnement Orange réel ici (aucun
 * identifiant marchand disponible dans cet environnement de développement).
 */
class OrangeMoneyGateway implements PaymentGateway
{
    public function initiateCheckout(Payment $payment): CheckoutResult
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->baseUrl(config('payments.orange_money.base_url'))
            ->timeout(15)
            ->post('/omcoreapis/1.0.2/mp/pay', [
                'merchant_key' => config('payments.orange_money.merchant_key'),
                'currency' => $payment->currency,
                'order_id' => $payment->reference,
                'amount' => $payment->amount,
                'return_url' => config('payments.success_url'),
                'cancel_url' => config('payments.error_url'),
                'notif_url' => route('webhooks.orange-money'),
                'lang' => 'fr',
            ]);

        if ($response->failed()) {
            Log::error('Orange Money checkout initiation failed', ['response' => $response->body()]);
            throw new RuntimeException('Impossible de créer la session de paiement Orange Money.');
        }

        $data = $response->json();

        return new CheckoutResult(
            providerReference: $data['pay_token'] ?? $payment->reference,
            checkoutUrl: $data['payment_url'] ?? null,
            raw: $data,
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('payments.orange_money.webhook_secret');
        $signature = $request->header('X-Orange-Signature');

        if (blank($secret) || blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->json()->all();

        return new WebhookEvent(
            providerReference: $payload['order_id'] ?? $payload['pay_token'] ?? '',
            succeeded: in_array($payload['status'] ?? null, ['SUCCESS', 'SUCCESSFUL'], true),
            raw: $payload,
        );
    }

    public function checkStatus(Payment $payment): bool
    {
        $reference = $payment->transactions()->latest()->first()?->provider_reference;

        if (! $reference) {
            return false;
        }

        $response = Http::withToken($this->accessToken())
            ->baseUrl(config('payments.orange_money.base_url'))
            ->timeout(10)
            ->post('/omcoreapis/1.0.2/mp/paymentstatus', [
                'order_id' => $payment->reference,
                'pay_token' => $reference,
            ]);

        return $response->successful() && in_array($response->json('status'), ['SUCCESS', 'SUCCESSFUL'], true);
    }

    private function accessToken(): string
    {
        return Cache::remember('orange_money_access_token', 3000, function () {
            $response = Http::asForm()
                ->withBasicAuth(config('payments.orange_money.client_id'), config('payments.orange_money.client_secret'))
                ->baseUrl(config('payments.orange_money.base_url'))
                ->timeout(10)
                ->post('/oauth/v3/token', ['grant_type' => 'client_credentials']);

            if ($response->failed()) {
                throw new RuntimeException("Impossible d'obtenir un jeton Orange Money.");
            }

            return $response->json('access_token');
        });
    }
}
