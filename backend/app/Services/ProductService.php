<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private readonly ImageService $imageService) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  UploadedFile[]  $images
     */
    public function create(User $user, array $data, array $images): Product
    {
        $isDraft = (bool) ($data['is_draft'] ?? false);

        return DB::transaction(function () use ($user, $data, $images, $isDraft) {
            $product = Product::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'brand' => $data['brand'] ?? null,
                'description' => $data['description'],
                'price' => $data['price'],
                'quantity' => $data['quantity'],
                'condition' => $data['condition'],
                'city_id' => $data['city_id'],
                'neighborhood_id' => $data['neighborhood_id'] ?? null,
                'delivery_available' => $data['delivery_available'] ?? false,
                'status' => $isDraft ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE,
                'views_count' => 0,
                'contacts_count' => 0,
                'published_at' => $isDraft ? null : now(),
                'expires_at' => $isDraft ? null : now()->addDays(90),
            ]);

            $this->attachImages($product, $images);

            return $product->load(['images', 'category', 'city']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  UploadedFile[]  $newImages
     */
    public function update(Product $product, array $data, array $newImages = []): Product
    {
        return DB::transaction(function () use ($product, $data, $newImages) {
            if (isset($data['title']) && $data['title'] !== $product->title) {
                $data['slug'] = $this->uniqueSlug($data['title'], $product->id);
            }

            if (isset($data['status'])) {
                $this->transitionStatus($product, $data['status']);
                unset($data['status']);
            }

            $product->update($data);

            if (! empty($newImages)) {
                $this->attachImages($product, $newImages);
            }

            return $product->fresh(['images', 'category', 'city']);
        });
    }

    /**
     * N'autorise que les transitions de statut sûres pour le propriétaire de l'annonce.
     * Les statuts de modération (BLOQUEE, REFUSEE, SIGNALEE, EN_ATTENTE) sont gérés
     * séparément par le back-office (voir Phase 5).
     */
    private function transitionStatus(Product $product, string $newStatus): void
    {
        $allowedFrom = [
            Product::STATUS_ACTIVE => [Product::STATUS_SOLD, Product::STATUS_ARCHIVED],
            Product::STATUS_DRAFT => [Product::STATUS_ACTIVE],
            Product::STATUS_ARCHIVED => [Product::STATUS_ACTIVE],
        ];

        if (! in_array($newStatus, $allowedFrom[$product->status] ?? [], true)) {
            return;
        }

        $product->status = $newStatus;

        if ($newStatus === Product::STATUS_ACTIVE && $product->published_at === null) {
            $product->published_at = now();
            $product->expires_at = now()->addDays(90);
        }
    }

    /**
     * @param  UploadedFile[]  $images
     */
    private function attachImages(Product $product, array $images): void
    {
        $position = $product->images()->max('position') ?? -1;
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($images as $image) {
            $position++;
            $path = $this->imageService->storeCompressed($image, 'products/'.$product->id);

            $product->images()->create([
                'path' => $path,
                'position' => $position,
                'is_primary' => ! $hasPrimary,
            ]);

            $hasPrimary = true;
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
