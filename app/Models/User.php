<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    protected $fillable = ['phone', 'password', 'role', 'status', 'phone_verified_at', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_CLIENT = 'client';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ADMIN = 'admin';

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function purchasesConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function salesConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [self::ROLE_MODERATOR, self::ROLE_ADMIN], true);
    }

    public function isSellerPro(): bool
    {
        return $this->sellerProfile?->seller_type === 'pro'
            && $this->sellerProfile?->pro_expires_at?->isFuture();
    }
}
