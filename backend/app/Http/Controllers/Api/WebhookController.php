<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentService $paymentService,
    ) {}

    public function wave(Request $request): JsonResponse
    {
        return $this->handle(Payment::PROVIDER_WAVE, $request);
    }

    public function orangeMoney(Request $request): JsonResponse
    {
        return $this->handle(Payment::PROVIDER_ORANGE_MONEY, $request);
    }

    public function paytech(Request $request): JsonResponse
    {
        return $this->handle(Payment::PROVIDER_PAYTECH, $request);
    }

    private function handle(string $provider, Request $request): JsonResponse
    {
        $gateway = $this->gatewayFactory->make($provider);

        // Ne jamais traiter un webhook dont la signature n'est pas authentifiée :
        // c'est la seule preuve que la requête vient réellement du fournisseur de paiement.
        if (! $gateway->verifyWebhookSignature($request)) {
            Log::warning("Webhook {$provider} rejeté : signature invalide ou absente.");

            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        $event = $gateway->parseWebhook($request);
        $this->paymentService->handleVerifiedWebhook($provider, $event);

        return response()->json(['message' => 'ok']);
    }
}
