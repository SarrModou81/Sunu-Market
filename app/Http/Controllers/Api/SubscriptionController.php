<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubscriptionPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function store(CreateSubscriptionPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->createSubscriptionPayment(
            $request->user(),
            $request->string('provider'),
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
