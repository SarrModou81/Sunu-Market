<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp_for_registration(): void
    {
        $response = $this->postJson('/api/auth/otp/request', [
            'phone' => '771234567',
            'purpose' => 'register',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('otp_codes', [
            'phone' => '+221771234567',
            'purpose' => 'register',
        ]);
    }

    public function test_user_can_register_with_valid_otp(): void
    {
        $otp = OtpCode::create([
            'phone' => '+221771234567',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'register',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'phone' => '771234567',
            'code' => '123456',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Awa',
            'last_name' => 'Diop',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['user' => ['id', 'phone', 'profile'], 'token']);

        $this->assertDatabaseHas('users', ['phone' => '+221771234567']);
        $this->assertDatabaseHas('profiles', ['first_name' => 'Awa', 'last_name' => 'Diop']);

        $this->assertNotNull($otp->fresh()->consumed_at);
    }

    public function test_registration_fails_with_invalid_otp(): void
    {
        OtpCode::create([
            'phone' => '+221771234567',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'register',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'phone' => '771234567',
            'code' => '000000',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Awa',
            'last_name' => 'Diop',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['phone' => '+221771234567']);
    }

    public function test_registration_fails_when_phone_already_registered(): void
    {
        User::factory()->create(['phone' => '+221771234567']);

        $response = $this->postJson('/api/auth/otp/request', [
            'phone' => '771234567',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_expired_otp(): void
    {
        OtpCode::create([
            'phone' => '+221771234567',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'register',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'phone' => '771234567',
            'code' => '123456',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Awa',
            'last_name' => 'Diop',
        ]);

        $response->assertStatus(422);
    }
}
