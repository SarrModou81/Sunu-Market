<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        $cities = City::query()
            ->where('is_active', true)
            ->with('neighborhoods')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => CityResource::collection($cities)]);
    }
}
