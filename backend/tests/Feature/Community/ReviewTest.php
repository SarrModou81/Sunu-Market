<?php

namespace Tests\Feature\Community;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_who_contacted_seller_can_leave_a_review(): void
    {
        $seller = User::factory()->create();
        $seller->sellerProfile()->create();
        $buyer = User::factory()->create();
        Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $token = $buyer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/sellers/{$seller->id}/reviews", ['rating' => 5, 'comment' => 'Très fiable']);

        $response->assertCreated();
        $this->assertDatabaseHas('reviews', ['author_id' => $buyer->id, 'seller_id' => $seller->id, 'rating' => 5]);
        $this->assertEquals(5.0, (float) $seller->sellerProfile->fresh()->rating_average);
        $this->assertSame(1, $seller->sellerProfile->fresh()->rating_count);
    }

    public function test_review_without_prior_contact_is_rejected(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $token = $buyer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/sellers/{$seller->id}/reviews", ['rating' => 4]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_review_self(): void
    {
        $seller = User::factory()->create();
        $token = $seller->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/sellers/{$seller->id}/reviews", ['rating' => 5]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_review_same_seller_twice(): void
    {
        $seller = User::factory()->create();
        $seller->sellerProfile()->create();
        $buyer = User::factory()->create();
        Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $token = $buyer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/sellers/{$seller->id}/reviews", ['rating' => 5])
            ->assertCreated();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/sellers/{$seller->id}/reviews", ['rating' => 1]);

        $response->assertStatus(422);
    }
}
