<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'provider' => $this->provider,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payable_type' => $this->payable_type,
            'checkout_url' => $this->meta['checkout_url'] ?? null,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
