<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_refuse_product_with_reason_and_notify_seller(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id, 'status' => Product::STATUS_ACTIVE]);

        $this->authenticateAs($moderator);
        $response = $this->postJson("/api/admin/products/{$product->id}/refuse", ['reason' => 'Photos non conformes']);

        $response->assertOk();
        $this->assertSame(Product::STATUS_REFUSED, $product->fresh()->status);
        $this->assertSame('Photos non conformes', $product->fresh()->rejection_reason);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $seller->id]);
        $this->assertDatabaseHas('admin_logs', ['action' => 'product.refuse']);
    }

    public function test_refusing_product_requires_a_reason(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $product = Product::factory()->create();
        $this->authenticateAs($moderator);

        $this->postJson("/api/admin/products/{$product->id}/refuse", [])->assertStatus(422);
    }

    public function test_moderator_can_approve_reported_product(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $product = Product::factory()->create(['status' => Product::STATUS_REPORTED]);
        $this->authenticateAs($moderator);

        $response = $this->postJson("/api/admin/products/{$product->id}/approve");

        $response->assertOk();
        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
    }

    public function test_client_cannot_moderate_products(): void
    {
        $client = User::factory()->create();
        $product = Product::factory()->create();
        $this->authenticateAs($client);

        $this->postJson("/api/admin/products/{$product->id}/approve")->assertStatus(403);
    }
}
