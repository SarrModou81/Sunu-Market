<?php

namespace Database\Factories;

use App\Models\BoostPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoostPlan>
 */
class BoostPlanFactory extends Factory
{
    protected $model = BoostPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => BoostPlan::CODE_72H,
            'name' => 'Boost 72 h',
            'price' => 500,
            'duration_hours' => 72,
            'is_active' => true,
        ];
    }
}
