<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductSearchService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductSearchService $searchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'q', 'category_id', 'subcategory_id', 'condition', 'city_id', 'neighborhood_id',
            'delivery_available', 'min_price', 'max_price', 'sort',
        ]);

        $perPage = min((int) $request->integer('per_page', 20), 50);
        $page = max((int) $request->integer('page', 1), 1);

        $results = $this->searchService->search($filters, $perPage, $page);

        return response()->json([
            'data' => ProductListResource::collection($results->items()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $products = $request->user()->products()
            ->with(['images', 'category', 'city'])
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        return response()->json([
            'data' => ProductListResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $viewer = $request->user();
        $isOwnerOrModerator = $viewer && ($viewer->id === $product->user_id || $viewer->isModerator());

        if ($product->status !== Product::STATUS_ACTIVE && ! $isOwnerOrModerator) {
            abort(404);
        }

        $product->load(['images', 'category', 'subcategory', 'city', 'neighborhood', 'user.profile.city', 'user.sellerProfile']);

        if ($viewer) {
            $product->load(['favorites' => fn ($q) => $q->where('user_id', $viewer->id)]);
        }

        if (! $isOwnerOrModerator) {
            $product->increment('views_count');
            $product->user->sellerProfile()->increment('views_count');
        }

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->user(),
            $request->safe()->except(['images']),
            $request->file('images', []),
        );

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update(
            $product,
            $request->safe()->except(['new_images']),
            $request->file('new_images', []),
        );

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Annonce supprimée.']);
    }
}
