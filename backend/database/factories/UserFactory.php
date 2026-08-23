<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => PhoneNumber::normalize(fake()->randomElement(['70', '76', '77', '78']).fake()->numerify('#######')),
            'phone_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'role' => User::ROLE_CLIENT,
            'status' => 'active',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
