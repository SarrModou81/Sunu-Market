<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation complète d'une annonce (page produit, section 8 du cahier des charges).
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
            'delivery_available' => $this->delivery_available,
            'status' => $this->status,
            'rejection_reason' => $this->when($request->user()?->id === $this->user_id, $this->rejection_reason),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'city' => new CityResource($this->whenLoaded('city')),
            'neighborhood' => new NeighborhoodResource($this->whenLoaded('neighborhood')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'seller' => new SellerSummaryResource($this->whenLoaded('user')),
            'is_boosted' => $this->isBoosted(),
            // Le contrôleur ne charge la relation "favorites" que filtrée sur l'utilisateur courant.
            'is_favorited' => $this->when(
                $request->user() !== null && $this->relationLoaded('favorites'),
                fn () => $this->favorites->isNotEmpty()
            ),
            'views_count' => $this->views_count,
            'contacts_count' => $this->contacts_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
