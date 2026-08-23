<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 1000000),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(1000, 500000),
            'quantity' => 1,
            'condition' => fake()->randomElement(['neuf', 'tres_bon', 'bon', 'moyen']),
            'city_id' => City::factory(),
            'delivery_available' => fake()->boolean(),
            'status' => Product::STATUS_ACTIVE,
            'published_at' => now(),
            'expires_at' => now()->addDays(90),
        ];
    }

    public function boosted(): static
    {
        return $this->state(fn (array $attributes) => [
            'boosted_until' => now()->addDays(3),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_DRAFT,
            'published_at' => null,
            'expires_at' => null,
        ]);
    }
}
