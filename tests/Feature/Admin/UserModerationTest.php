<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_suspend_user_and_revoke_tokens(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $target = User::factory()->create();
        $targetToken = $target->createToken('mobile')->plainTextToken;

        $this->authenticateAs($moderator);
        $response = $this->patchJson("/api/admin/users/{$target->id}/status", [
            'status' => 'suspended',
            'reason' => 'Comportement suspect',
        ]);

        $response->assertOk();
        $this->assertSame('suspended', $target->fresh()->status);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('admin_logs', ['action' => 'user.status.update']);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $target = User::factory()->create(['phone' => '+221771234567', 'password' => 'Password123']);

        $this->authenticateAs($moderator);
        $this->patchJson("/api/admin/users/{$target->id}/status", ['status' => 'banned'])->assertOk();

        $login = $this->postJson('/api/auth/login', ['phone' => '771234567', 'password' => 'Password123']);
        $login->assertStatus(401);
    }

    public function test_moderator_can_verify_seller_badge(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $seller = User::factory()->create();
        $seller->sellerProfile()->create();

        $this->authenticateAs($moderator);
        $response = $this->patchJson("/api/admin/users/{$seller->id}/seller-verification", ['badge_verified' => true]);

        $response->assertOk();
        $this->assertTrue((bool) $seller->sellerProfile->fresh()->badge_verified);
    }
}
