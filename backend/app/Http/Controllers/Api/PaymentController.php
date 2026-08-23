<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payments\WebhookEvent;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $request->user()->payments()
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        return response()->json([
            'data' => PaymentResource::collection($payments->items()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json(['data' => new PaymentResource($payment)]);
    }

    /**
     * Simule la confirmation d'un paiement par le fournisseur "fake" (développement/tests
     * uniquement). Reproduit ce qu'un vrai webhook déclencherait, sans jamais être
     * disponible en production (voir routes/api.php et PaymentGatewayFactory).
     */
    public function fakeComplete(Request $request, Payment $payment): JsonResponse
    {
        if (app()->environment('production') || config('payments.default_driver') !== 'fake') {
            abort(404);
        }

        $this->paymentService->handleVerifiedWebhook('fake', new WebhookEvent(
            providerReference: $request->query('ref', ''),
            succeeded: true,
            raw: ['simulated' => true],
        ));

        return response()->json(['message' => 'Paiement simulé confirmé.']);
    }
}
