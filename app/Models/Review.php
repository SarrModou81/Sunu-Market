<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['author_id', 'seller_id', 'product_id', 'rating', 'comment', 'status'];

    public const STATUS_VISIBLE = 'visible';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_FLAGGED = 'flagged';

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
