<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'role' => $this->role,
            'status' => $this->status,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'seller_profile' => new SellerProfileResource($this->whenLoaded('sellerProfile')),
            'created_at' => $this->created_at,
        ];
    }
}
