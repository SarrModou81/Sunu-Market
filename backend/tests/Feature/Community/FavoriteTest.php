<?php

namespace Tests\Feature\Community;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_and_remove_favorite(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $token = $buyer->createToken('test')->plainTextToken;

        $add = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/favorites', ['product_id' => $product->id]);
        $add->assertCreated();
        $this->assertDatabaseHas('favorites', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $seller->id]);

        $list = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/favorites');
        $list->assertOk();
        $list->assertJsonCount(1, 'data');

        $remove = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/favorites/{$product->id}");
        $remove->assertOk();
        $this->assertDatabaseMissing('favorites', ['user_id' => $buyer->id, 'product_id' => $product->id]);
    }

    public function test_adding_same_favorite_twice_is_idempotent(): void
    {
        $product = Product::factory()->create();
        $buyer = User::factory()->create();
        $token = $buyer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/favorites', ['product_id' => $product->id]);
        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/favorites', ['product_id' => $product->id]);

        $this->assertSame(1, Favorite::count());
    }

    public function test_guest_cannot_add_favorite(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/favorites', ['product_id' => $product->id])->assertStatus(401);
    }
}
