<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fournisseur de développement/tests : simule une session de paiement qui
 * "réussit" immédiatement, sans appel réseau. Permet de valider tout le flux
 * (checkout -> webhook -> activation) sans identifiants Wave/Orange Money réels.
 * Ne doit jamais être utilisé en production (voir PaymentGatewayFactory).
 */
class FakeGateway implements PaymentGateway
{
    public function initiateCheckout(Payment $payment): CheckoutResult
    {
        $reference = 'fake_'.Str::uuid()->toString();

        return new CheckoutResult(
            providerReference: $reference,
            checkoutUrl: url("/api/payments/{$payment->id}/fake-complete?ref={$reference}"),
            raw: ['simulated' => true],
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $request->header('X-Fake-Signature') === 'valid';
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        return new WebhookEvent(
            providerReference: (string) $request->input('reference'),
            succeeded: $request->input('status') === 'success',
            raw: $request->all(),
        );
    }

    public function checkStatus(Payment $payment): bool
    {
        return $payment->isPaid();
    }
}
