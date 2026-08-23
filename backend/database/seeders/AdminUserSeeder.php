<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée un premier compte administrateur pour le développement local.
     * Le téléphone/mot de passe se configurent via ADMIN_PHONE / ADMIN_PASSWORD
     * pour éviter de coder des identifiants en dur.
     */
    public function run(): void
    {
        $phone = env('ADMIN_PHONE', '+221770000000');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        $admin = User::firstOrCreate(
            ['phone' => $phone],
            [
                'password' => $password,
                'phone_verified_at' => now(),
                'role' => User::ROLE_ADMIN,
                'status' => 'active',
            ]
        );

        if (! $admin->profile) {
            $admin->profile()->create([
                'first_name' => 'Admin',
                'last_name' => 'SunuMarket',
            ]);
        }
    }
}
