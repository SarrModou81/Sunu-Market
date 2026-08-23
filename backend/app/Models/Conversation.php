<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'product_id', 'buyer_id', 'seller_id', 'buyer_last_read_at', 'seller_last_read_at',
        'blocked_by_buyer', 'blocked_by_seller', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'buyer_last_read_at' => 'datetime',
            'seller_last_read_at' => 'datetime',
            'blocked_by_buyer' => 'boolean',
            'blocked_by_seller' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isBlockedFor(User $user): bool
    {
        return ($user->id === $this->buyer_id && $this->blocked_by_seller)
            || ($user->id === $this->seller_id && $this->blocked_by_buyer);
    }
}
