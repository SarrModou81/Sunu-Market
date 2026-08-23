<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation allégée d'une annonce pour les listes (accueil, recherche, favoris).
 *
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'price' => $this->price,
            'condition' => $this->condition,
            'city' => $this->whenLoaded('city', fn () => new CityResource($this->city)),
            'cover_image_url' => $primaryImage?->url,
            'is_boosted' => $this->isBoosted(),
            'delivery_available' => $this->delivery_available,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'created_at' => $this->created_at,
        ];
    }
}
