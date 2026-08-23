<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(User $seller): JsonResponse
    {
        $reviews = $seller->reviewsReceived()
            ->where('status', 'visible')
            ->with('author.profile')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function store(StoreReviewRequest $request, User $seller): JsonResponse
    {
        $review = $this->reviewService->create($request->user(), $seller, $request->validated());

        return response()->json(['data' => new ReviewResource($review->load('author.profile'))], 201);
    }
}
