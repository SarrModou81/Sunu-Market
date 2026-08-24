<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Intégration PayTech (paytech.sn) — agrégateur sénégalais Wave / Orange Money /
 * Free Money / carte bancaire derrière une seule API.
 *
 * La création de session de paiement (endpoint, en-têtes API_KEY/API_SECRET,
 * champs du formulaire, format de réponse avec `token`/`redirect_url`) est
 * conforme au SDK officiel PHP de PayTech (github.com/PapiHack/paytech-php-client).
 *
 * En revanche la vérification de signature IPN ci-dessous (comparaison de
 * `api_key_sha256`/`api_secret_sha256` avec le hash SHA-256 de vos propres
 * identifiants) reproduit le mécanisme documenté par PayTech, mais n'a pas pu
 * être vérifiée contre un webhook réel dans cet environnement de
 * développement (aucun compte marchand disponible ici). À valider avec un
 * paiement test réel avant mise en production — si les noms de champs
 * diffèrent de ce qui est reçu, ajuster `parseWebhook()`/`verifyWebhookSignature()`
 * en conséquence.
 */
class PaytechGateway implements PaymentGateway
{
    public function initiateCheckout(Payment $payment): CheckoutResult
    {
        $response = Http::asForm()
            ->withHeaders([
                'API_KEY' => config('payments.paytech.api_key'),
                'API_SECRET' => config('payments.paytech.api_secret'),
            ])
            ->timeout(15)
            ->post(config('payments.paytech.base_url').'/api/payment/request-payment', [
                'item_name' => 'SunuMarket',
                'item_price' => (string) $payment->amount,
                'currency' => $payment->currency,
                'ref_command' => $payment->reference,
                'command_name' => "Paiement SunuMarket #{$payment->reference}",
                'env' => config('payments.paytech.env'),
                'ipn_url' => config('payments.paytech.ipn_url'),
                'success_url' => config('payments.success_url'),
                'cancel_url' => config('payments.error_url'),
                'custom_field' => json_encode(['payment_id' => $payment->id]),
            ]);

        $data = $response->json();

        if ($response->failed() || empty($data['token'])) {
            Log::error('PayTech checkout initiation failed', ['response' => $data]);
            throw new RuntimeException('Impossible de créer la session de paiement PayTech.');
        }

        return new CheckoutResult(
            providerReference: $payment->reference,
            checkoutUrl: $data['redirect_url'] ?? config('payments.paytech.base_url').'/payment/checkout/'.$data['token'],
            raw: $data,
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $apiKey = config('payments.paytech.api_key');
        $apiSecret = config('payments.paytech.api_secret');

        $sentKeyHash = $request->input('api_key_sha256');
        $sentSecretHash = $request->input('api_secret_sha256');

        if (blank($apiKey) || blank($apiSecret) || blank($sentKeyHash) || blank($sentSecretHash)) {
            return false;
        }

        return hash_equals(hash('sha256', $apiKey), $sentKeyHash)
            && hash_equals(hash('sha256', $apiSecret), $sentSecretHash);
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();

        return new WebhookEvent(
            providerReference: $payload['ref_command'] ?? '',
            succeeded: ($payload['type_event'] ?? null) === 'sale_complete',
            raw: $payload,
        );
    }

    public function checkStatus(Payment $payment): bool
    {
        // PayTech ne documente pas publiquement d'endpoint de consultation de statut
        // à l'unité ; le webhook IPN reste la source de vérité pour cette intégration.
        return $payment->isPaid();
    }
}
