<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payable_type' => 'boost',
            'payable_id' => 1,
            'provider' => Payment::PROVIDER_WAVE,
            'amount' => 500,
            'currency' => 'XOF',
            'reference' => 'SM-'.Str::upper(Str::random(10)),
            'status' => Payment::STATUS_PENDING,
        ];
    }
}
