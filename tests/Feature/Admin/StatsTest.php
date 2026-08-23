<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_stats_reflect_current_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Product::factory()->count(3)->create(['status' => Product::STATUS_ACTIVE]);
        Product::factory()->create(['status' => Product::STATUS_PENDING]);
        Payment::factory()->count(2)->create(['status' => Payment::STATUS_PAID, 'amount' => 500, 'payable_type' => 'boost']);

        $this->authenticateAs($admin);
        $response = $this->getJson('/api/admin/stats');

        $response->assertOk();
        $response->assertJsonPath('data.products.active', 3);
        $response->assertJsonPath('data.products.pending_moderation', 1);
        $response->assertJsonPath('data.revenue.total', 1000);
    }
}
