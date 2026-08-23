<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'subcategory_id', 'title', 'slug', 'brand', 'description',
        'price', 'quantity', 'condition', 'city_id', 'neighborhood_id', 'delivery_available',
        'status', 'rejection_reason', 'published_at', 'expires_at', 'boosted_until',
        'views_count', 'contacts_count',
    ];

    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'BROUILLON';

    public const STATUS_PENDING = 'EN_ATTENTE';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SOLD = 'VENDUE';

    public const STATUS_ARCHIVED = 'ARCHIVEE';

    public const STATUS_REPORTED = 'SIGNALEE';

    public const STATUS_BLOCKED = 'BLOQUEE';

    public const STATUS_REFUSED = 'REFUSEE';

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'delivery_available' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'boosted_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBoosted(Builder $query): Builder
    {
        return $query->whereNotNull('boosted_until')->where('boosted_until', '>', now());
    }

    public function isBoosted(): bool
    {
        return $this->boosted_until !== null && $this->boosted_until->isFuture();
    }
}
