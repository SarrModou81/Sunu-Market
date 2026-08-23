<?php

namespace Tests\Feature\Payments;

use App\Models\Boost;
use App\Models\BoostPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoostPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_initiate_boost_checkout(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create();
        $this->authenticateAs($seller);

        $response = $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', Payment::STATUS_PENDING);
        $response->assertJsonPath('data.amount', 500);
        $this->assertNotNull($response->json('data.checkout_url'));
        $this->assertDatabaseHas('boosts', ['product_id' => $product->id, 'status' => Boost::STATUS_PENDING]);
    }

    public function test_non_owner_cannot_boost_product(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create();
        $stranger = User::factory()->create();
        $this->authenticateAs($stranger);

        $response = $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);

        $response->assertStatus(403);
    }

    public function test_completing_fake_payment_activates_boost_and_boosts_product(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create(['duration_hours' => 72]);
        $this->authenticateAs($seller);

        $checkout = $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);
        $checkoutUrl = $checkout->json('data.checkout_url');

        // Le lien simulé renvoyé par le fournisseur "fake" pointe directement vers l'API.
        $path = parse_url($checkoutUrl, PHP_URL_PATH).'?'.parse_url($checkoutUrl, PHP_URL_QUERY);
        $complete = $this->getJson($path);
        $complete->assertOk();

        $boost = Boost::where('product_id', $product->id)->first();
        $this->assertSame(Boost::STATUS_ACTIVE, $boost->status);
        $this->assertNotNull($boost->ends_at);

        $product->refresh();
        $this->assertNotNull($product->boosted_until);
        $this->assertTrue($product->isBoosted());

        $payment = Payment::first();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_completing_fake_payment_twice_does_not_double_activate(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create();
        $this->authenticateAs($seller);

        $checkout = $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);
        $checkoutUrl = $checkout->json('data.checkout_url');
        $path = parse_url($checkoutUrl, PHP_URL_PATH).'?'.parse_url($checkoutUrl, PHP_URL_QUERY);

        $this->getJson($path)->assertOk();
        $firstEndsAt = Boost::first()->ends_at;

        $this->getJson($path)->assertOk();
        $secondEndsAt = Boost::first()->fresh()->ends_at;

        $this->assertSame(1, Payment::count());
        $this->assertTrue($firstEndsAt->equalTo($secondEndsAt));
    }
}
