<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_pro_subscription_checkout(): void
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create();
        $this->authenticateAs($user);

        $response = $this->postJson('/api/subscriptions/pro', ['provider' => 'orange_money']);

        $response->assertCreated();
        $response->assertJsonPath('data.amount', 2000);
        $this->assertDatabaseHas('subscriptions', ['user_id' => $user->id, 'status' => Subscription::STATUS_PENDING]);
    }

    public function test_completing_payment_upgrades_seller_to_pro(): void
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create();
        $this->authenticateAs($user);

        $checkout = $this->postJson('/api/subscriptions/pro', ['provider' => 'orange_money']);
        $checkoutUrl = $checkout->json('data.checkout_url');
        $path = parse_url($checkoutUrl, PHP_URL_PATH).'?'.parse_url($checkoutUrl, PHP_URL_QUERY);

        $this->getJson($path)->assertOk();

        $sellerProfile = $user->sellerProfile->fresh();
        $this->assertSame('pro', $sellerProfile->seller_type);
        $this->assertTrue($sellerProfile->isPro());
        $this->assertNull($sellerProfile->activeProductsLimit());

        $subscription = Subscription::first();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);

        $payment = Payment::first();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
    }

    public function test_guest_cannot_subscribe(): void
    {
        $this->postJson('/api/subscriptions/pro', ['provider' => 'wave'])->assertStatus(401);
    }
}
