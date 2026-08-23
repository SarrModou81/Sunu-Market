<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Principales villes du Sénégal (chef-lieux de région).
     */
    public function run(): void
    {
        $cities = [
            ['name' => 'Dakar', 'region' => 'Dakar'],
            ['name' => 'Pikine', 'region' => 'Dakar'],
            ['name' => 'Guédiawaye', 'region' => 'Dakar'],
            ['name' => 'Rufisque', 'region' => 'Dakar'],
            ['name' => 'Thiès', 'region' => 'Thiès'],
            ['name' => 'Mbour', 'region' => 'Thiès'],
            ['name' => 'Diourbel', 'region' => 'Diourbel'],
            ['name' => 'Touba', 'region' => 'Diourbel'],
            ['name' => 'Saint-Louis', 'region' => 'Saint-Louis'],
            ['name' => 'Louga', 'region' => 'Louga'],
            ['name' => 'Fatick', 'region' => 'Fatick'],
            ['name' => 'Kaolack', 'region' => 'Kaolack'],
            ['name' => 'Kaffrine', 'region' => 'Kaffrine'],
            ['name' => 'Tambacounda', 'region' => 'Tambacounda'],
            ['name' => 'Kédougou', 'region' => 'Kédougou'],
            ['name' => 'Kolda', 'region' => 'Kolda'],
            ['name' => 'Sédhiou', 'region' => 'Sédhiou'],
            ['name' => 'Ziguinchor', 'region' => 'Ziguinchor'],
            ['name' => 'Matam', 'region' => 'Matam'],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(['name' => $city['name']], $city);
        }
    }
}
