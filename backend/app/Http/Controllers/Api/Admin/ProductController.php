<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefuseProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Notifications\ProductModeratedNotification;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly AdminLogService $adminLog) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['images', 'category', 'city', 'user.profile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function approve(Request $request, Product $product): JsonResponse
    {
        $product->forceFill([
            'status' => Product::STATUS_ACTIVE,
            'rejection_reason' => null,
            'published_at' => $product->published_at ?? now(),
            'expires_at' => $product->expires_at ?? now()->addDays(90),
        ])->save();

        $this->adminLog->log($request->user(), 'product.approve', $product, null, $request->ip());
        $product->user->notify(new ProductModeratedNotification($product, 'approved'));

        return response()->json(['data' => new ProductResource($product->fresh(['images', 'category', 'city']))]);
    }

    public function refuse(RefuseProductRequest $request, Product $product): JsonResponse
    {
        $product->forceFill([
            'status' => Product::STATUS_REFUSED,
            'rejection_reason' => $request->string('reason'),
        ])->save();

        $this->adminLog->log($request->user(), 'product.refuse', $product, $request->string('reason'), $request->ip());
        $product->user->notify(new ProductModeratedNotification($product, 'refused'));

        return response()->json(['data' => new ProductResource($product->fresh(['images', 'category', 'city']))]);
    }

    public function block(RefuseProductRequest $request, Product $product): JsonResponse
    {
        $product->forceFill([
            'status' => Product::STATUS_BLOCKED,
            'rejection_reason' => $request->string('reason'),
        ])->save();

        $this->adminLog->log($request->user(), 'product.block', $product, $request->string('reason'), $request->ip());
        $product->user->notify(new ProductModeratedNotification($product, 'blocked'));

        return response()->json(['data' => new ProductResource($product->fresh(['images', 'category', 'city']))]);
    }
}
