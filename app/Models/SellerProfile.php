<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'shop_name', 'description', 'seller_type', 'pro_started_at', 'pro_expires_at',
    'badge_verified', 'auto_reply_enabled', 'auto_reply_message',
    'views_count', 'contacts_count', 'rating_average', 'rating_count',
])]
class SellerProfile extends Model
{
    public const TYPE_STANDARD = 'standard';

    public const TYPE_PRO = 'pro';

    public const MAX_ACTIVE_PRODUCTS_STANDARD = 10;

    protected function casts(): array
    {
        return [
            'pro_started_at' => 'datetime',
            'pro_expires_at' => 'datetime',
            'badge_verified' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'rating_average' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPro(): bool
    {
        return $this->seller_type === self::TYPE_PRO
            && $this->pro_expires_at !== null
            && $this->pro_expires_at->isFuture();
    }

    public function activeProductsLimit(): ?int
    {
        return $this->isPro() ? null : self::MAX_ACTIVE_PRODUCTS_STANDARD;
    }
}
