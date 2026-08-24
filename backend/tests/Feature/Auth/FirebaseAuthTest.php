<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirebaseAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_phone_number_registers_a_new_account_when_profile_is_provided(): void
    {
        $response = $this->postJson('/api/auth/firebase', [
            'id_token' => '771234567',
            'first_name' => 'Awa',
            'last_name' => 'Diop',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['user' => ['id', 'phone', 'profile'], 'token', 'is_new']);
        $response->assertJsonPath('is_new', true);

        $this->assertDatabaseHas('users', ['phone' => '+221771234567']);
        $this->assertDatabaseHas('profiles', ['first_name' => 'Awa', 'last_name' => 'Diop']);
        $this->assertDatabaseHas('seller_profiles', []);
    }

    public function test_new_phone_number_without_profile_fields_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/firebase', ['id_token' => '771234567']);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['phone' => '+221771234567']);
    }

    public function test_existing_phone_number_logs_in_without_profile_fields(): void
    {
        $user = User::factory()->create(['phone' => '+221771234567']);

        $response = $this->postJson('/api/auth/firebase', ['id_token' => '771234567']);

        $response->assertOk();
        $response->assertJsonPath('is_new', false);
        $response->assertJsonPath('user.id', $user->id);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/firebase', ['id_token' => 'not-a-phone-number']);

        $response->assertStatus(422);
    }

    public function test_suspended_account_cannot_authenticate(): void
    {
        User::factory()->create(['phone' => '+221771234567', 'status' => 'suspended']);

        $response = $this->postJson('/api/auth/firebase', ['id_token' => '771234567']);

        $response->assertStatus(401);
    }
}
