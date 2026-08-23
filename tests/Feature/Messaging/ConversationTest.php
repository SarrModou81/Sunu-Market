<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_buyer_can_start_conversation_with_seller(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($buyer))
            ->postJson('/api/conversations', [
                'product_id' => $product->id,
                'message' => 'Bonjour, le produit est-il toujours disponible ?',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('conversations', ['buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $this->assertDatabaseHas('messages', ['body' => 'Bonjour, le produit est-il toujours disponible ?']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $seller->id]);
    }

    public function test_seller_cannot_start_conversation_with_self(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($seller))
            ->postJson('/api/conversations', ['product_id' => $product->id, 'message' => 'Test']);

        $response->assertStatus(422);
    }

    public function test_starting_conversation_twice_reuses_existing_thread(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $token = $this->tokenFor($buyer);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/conversations', ['product_id' => $product->id, 'message' => 'Premier message']);
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/conversations', ['product_id' => $product->id, 'message' => 'Deuxième message']);

        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Message::count());
    }

    public function test_only_participants_can_view_conversation(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($stranger))
            ->getJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(403);
    }

    public function test_blocking_prevents_further_messages_from_blocked_side(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $sellerToken = $this->tokenFor($seller);
        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson("/api/conversations/{$conversation->id}/block")
            ->assertOk();

        // Le guard "sanctum" met en cache l'utilisateur résolu pour la durée du test ;
        // on le réinitialise pour que la requête suivante s'authentifie bien comme l'acheteur.
        $this->app['auth']->forgetGuards();

        $buyerToken = $this->tokenFor($buyer);
        $response = $this->withHeader('Authorization', "Bearer {$buyerToken}")
            ->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Toujours là ?']);

        $response->assertStatus(403);
    }

    public function test_viewing_conversation_marks_messages_as_read(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $conversation = Conversation::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $conversation->messages()->create(['sender_id' => $buyer->id, 'body' => 'Salut']);

        $sellerToken = $this->tokenFor($seller);
        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->getJson("/api/conversations/{$conversation->id}")
            ->assertOk();

        $this->assertNotNull($conversation->fresh()->seller_last_read_at);
    }
}
