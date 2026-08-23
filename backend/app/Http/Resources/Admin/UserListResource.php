<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'full_name' => $this->profile?->full_name,
            'seller_type' => $this->sellerProfile?->seller_type,
            'products_count' => $this->products_count ?? $this->products()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
