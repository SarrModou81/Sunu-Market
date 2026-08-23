<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoostPlanResource;
use App\Models\BoostPlan;
use Illuminate\Http\JsonResponse;

class BoostPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = BoostPlan::where('is_active', true)->orderBy('duration_hours')->get();

        return response()->json(['data' => BoostPlanResource::collection($plans)]);
    }
}
