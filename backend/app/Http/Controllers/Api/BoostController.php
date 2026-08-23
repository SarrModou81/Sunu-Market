<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBoostPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\BoostPlan;
use App\Models\Product;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class BoostController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function store(CreateBoostPaymentRequest $request, Product $product): JsonResponse
    {
        $plan = BoostPlan::findOrFail($request->integer('boost_plan_id'));

        $payment = $this->paymentService->createBoostPayment(
            $request->user(),
            $product,
            $plan,
            $request->string('provider'),
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
