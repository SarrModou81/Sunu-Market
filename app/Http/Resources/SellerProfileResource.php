<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'shop_name' => $this->shop_name,
            'description' => $this->description,
            'seller_type' => $this->seller_type,
            'is_pro' => $this->isPro(),
            'pro_expires_at' => $this->pro_expires_at,
            'badge_verified' => $this->badge_verified,
            'rating_average' => (float) $this->rating_average,
            'rating_count' => $this->rating_count,
            'active_products_limit' => $this->activeProductsLimit(),
        ];
    }
}
