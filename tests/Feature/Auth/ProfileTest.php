<?php

namespace Tests\Feature\Auth;

use App\Models\City;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->profile()->create(['first_name' => 'Awa', 'last_name' => 'Diop']);
        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");

        return $user;
    }

    public function test_user_can_update_profile(): void
    {
        $this->actingAsUser();
        $city = City::factory()->create();

        $response = $this->putJson('/api/profile', [
            'first_name' => 'Fatou',
            'city_id' => $city->id,
            'bio' => 'Vendeuse passionnée',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('profiles', ['first_name' => 'Fatou', 'bio' => 'Vendeuse passionnée']);
    }

    public function test_user_can_change_phone_with_valid_otp(): void
    {
        $this->actingAsUser(['phone' => '+221771234567']);

        OtpCode::create([
            'phone' => '+221781234567',
            'code_hash' => Hash::make('999888'),
            'purpose' => 'change_phone',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/profile/phone/change', [
            'new_phone' => '781234567',
            'code' => '999888',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['phone' => '+221781234567']);
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = $this->actingAsUser(['password' => 'Password123']);

        $response = $this->deleteJson('/api/account', ['password' => 'Password123']);

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_account_deletion_fails_with_wrong_password(): void
    {
        $this->actingAsUser(['password' => 'Password123']);

        $response = $this->deleteJson('/api/account', ['password' => 'wrong']);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->putJson('/api/profile', [])->assertStatus(401);
        $this->deleteJson('/api/account', [])->assertStatus(401);
    }
}
