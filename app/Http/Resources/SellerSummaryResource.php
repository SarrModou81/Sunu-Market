<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Informations publiques d'un vendeur, telles qu'affichées sur une annonce
 * (section 8 du cahier des charges : "Informations vendeur").
 *
 * @mixin User
 */
class SellerSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->profile?->full_name,
            'avatar_url' => $this->profile ? (new ProfileResource($this->profile))->toArray($request)['avatar_url'] : null,
            'city' => $this->profile?->city ? new CityResource($this->profile->city) : null,
            'is_pro' => $this->sellerProfile?->isPro() ?? false,
            'badge_verified' => $this->sellerProfile?->badge_verified ?? false,
            'rating_average' => (float) ($this->sellerProfile?->rating_average ?? 0),
            'rating_count' => $this->sellerProfile?->rating_count ?? 0,
            'member_since' => $this->created_at,
        ];
    }
}
