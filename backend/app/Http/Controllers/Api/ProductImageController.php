<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function destroy(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('manageImages', $product);

        if ($image->product_id !== $product->id) {
            abort(404);
        }

        $this->imageService->delete($image->path, $image->disk);
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $product->images()->orderBy('position')->first()?->update(['is_primary' => true]);
        }

        return response()->json(['message' => 'Photo supprimée.']);
    }

    public function setPrimary(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('manageImages', $product);

        if ($image->product_id !== $product->id) {
            abort(404);
        }

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json(['message' => 'Photo principale mise à jour.']);
    }
}
