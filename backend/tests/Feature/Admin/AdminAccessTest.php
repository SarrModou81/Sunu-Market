<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_client_cannot_access_admin_routes(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $this->authenticateAs($client);

        $this->getJson('/api/admin/stats')->assertStatus(403);
        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_moderator_can_access_admin_dashboard_but_not_change_roles(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $target = User::factory()->create();
        $this->authenticateAs($moderator);

        $this->getJson('/api/admin/stats')->assertOk();
        $this->getJson('/api/admin/users')->assertOk();

        $response = $this->patchJson("/api/admin/users/{$target->id}/role", ['role' => 'admin']);
        $response->assertStatus(403);
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create();
        $this->authenticateAs($admin);

        $response = $this->patchJson("/api/admin/users/{$target->id}/role", ['role' => 'moderator']);

        $response->assertOk();
        $this->assertSame('moderator', $target->fresh()->role);
        $this->assertDatabaseHas('admin_logs', ['admin_id' => $admin->id, 'action' => 'user.role.update']);
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/admin/stats')->assertStatus(401);
    }
}
