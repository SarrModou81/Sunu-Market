<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_password(): void
    {
        User::factory()->create(['phone' => '+221771234567', 'password' => 'Password123']);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '771234567',
            'password' => 'Password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['phone' => '+221771234567', 'password' => 'Password123']);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '771234567',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->create([
            'phone' => '+221771234567',
            'password' => 'Password123',
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '771234567',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_login_with_otp(): void
    {
        User::factory()->create(['phone' => '+221771234567']);
        OtpCode::create([
            'phone' => '+221771234567',
            'code_hash' => Hash::make('654321'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/otp/login', [
            'phone' => '771234567',
            'code' => '654321',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $me = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/auth/me');
        $me->assertOk();

        $logout = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/auth/logout');
        $logout->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Le guard "sanctum" met en cache l'utilisateur résolu pour la durée du test ;
        // on le réinitialise pour vérifier une résolution fraîche depuis la base.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        User::factory()->create(['phone' => '+221771234567', 'password' => 'OldPassword1']);
        OtpCode::create([
            'phone' => '+221771234567',
            'code_hash' => Hash::make('111222'),
            'purpose' => 'reset_password',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/password/reset', [
            'phone' => '771234567',
            'code' => '111222',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ]);

        $response->assertOk();

        $this->postJson('/api/auth/login', [
            'phone' => '771234567',
            'password' => 'NewPassword1',
        ])->assertOk();
    }
}
