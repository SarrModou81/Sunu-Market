<?php

namespace Database\Seeders;

use App\Models\BoostPlan;
use Illuminate\Database\Seeder;

class BoostPlanSeeder extends Seeder
{
    /**
     * Offres de Boost définies au cahier des charges (section 11).
     */
    public function run(): void
    {
        $plans = [
            ['code' => BoostPlan::CODE_72H, 'name' => 'Boost 72 h', 'price' => 500, 'duration_hours' => 72],
            ['code' => BoostPlan::CODE_7D, 'name' => 'Boost 7 jours', 'price' => 1000, 'duration_hours' => 7 * 24],
            ['code' => BoostPlan::CODE_30D, 'name' => 'Boost Premium 30 jours', 'price' => 3000, 'duration_hours' => 30 * 24],
        ];

        foreach ($plans as $plan) {
            BoostPlan::updateOrCreate(['code' => $plan['code']], [...$plan, 'is_active' => true]);
        }
    }
}
