<?php

namespace Database\Factories;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerProfile>
 */
class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'seller_type' => SellerProfile::TYPE_STANDARD,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'seller_type' => SellerProfile::TYPE_PRO,
            'pro_started_at' => now(),
            'pro_expires_at' => now()->addMonth(),
        ]);
    }
}
