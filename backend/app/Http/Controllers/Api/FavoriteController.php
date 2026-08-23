<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Models\Product;
use App\Notifications\ProductFavoritedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['images', 'city', 'category'])
            ->orderByDesc('id')
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

    public function store(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'integer', Rule::exists('products', 'id')]]);

        $user = $request->user();
        $product = Product::findOrFail($request->integer('product_id'));

        $favorite = $user->favorites()->firstOrCreate(['product_id' => $product->id]);

        if ($favorite->wasRecentlyCreated && $product->user_id !== $user->id) {
            $product->user->notify(new ProductFavoritedNotification($product));
        }

        return response()->json(['message' => 'Ajouté aux favoris.'], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->favorites()->where('product_id', $product->id)->delete();

        return response()->json(['message' => 'Retiré des favoris.']);
    }
}
