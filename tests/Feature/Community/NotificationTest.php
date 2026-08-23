<?php

namespace Tests\Feature\Community;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_notification_when_product_is_favorited(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $buyerToken = $buyer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/favorites', ['product_id' => $product->id]);

        // Le guard "sanctum" met en cache l'utilisateur résolu pour la durée du test.
        $this->app['auth']->forgetGuards();

        $sellerToken = $seller->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', "Bearer {$sellerToken}")->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonPath('data.0.data.type', 'product_favorited');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $buyerToken = $buyer->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson('/api/favorites', ['product_id' => $product->id]);

        $this->app['auth']->forgetGuards();

        $sellerToken = $seller->createToken('test')->plainTextToken;
        $notificationId = $seller->notifications()->first()->id;

        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson("/api/notifications/{$notificationId}/read")
            ->assertOk();

        $this->assertSame(0, $seller->unreadNotifications()->count());
    }
}
