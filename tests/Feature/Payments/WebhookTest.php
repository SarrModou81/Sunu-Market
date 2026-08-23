<?php

namespace Tests\Feature\Payments;

use App\Models\Boost;
use App\Models\BoostPlan;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_without_valid_signature_is_rejected(): void
    {
        $response = $this->postJson('/api/webhooks/wave', ['reference' => 'x', 'status' => 'success']);

        $response->assertStatus(401);
    }

    public function test_valid_webhook_activates_matching_payment(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create();
        $this->authenticateAs($seller);

        $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);

        $reference = PaymentTransaction::first()->provider_reference;

        $response = $this->withHeader('X-Fake-Signature', 'valid')
            ->postJson('/api/webhooks/wave', ['reference' => $reference, 'status' => 'success']);

        $response->assertOk();
        $this->assertSame(Payment::STATUS_PAID, Payment::first()->status);
        $this->assertSame(Boost::STATUS_ACTIVE, Boost::first()->status);
    }

    public function test_webhook_for_unknown_reference_is_ignored_without_error(): void
    {
        $response = $this->withHeader('X-Fake-Signature', 'valid')
            ->postJson('/api/webhooks/wave', ['reference' => 'inconnu', 'status' => 'success']);

        $response->assertOk();
    }

    public function test_failed_webhook_marks_payment_and_boost_as_failed(): void
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $plan = BoostPlan::factory()->create();
        $this->authenticateAs($seller);

        $this->postJson("/api/products/{$product->id}/boost", [
            'boost_plan_id' => $plan->id,
            'provider' => 'wave',
        ]);
        $reference = PaymentTransaction::first()->provider_reference;

        $this->withHeader('X-Fake-Signature', 'valid')
            ->postJson('/api/webhooks/wave', ['reference' => $reference, 'status' => 'failed'])
            ->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, Payment::first()->status);
        $this->assertSame(Boost::STATUS_CANCELLED, Boost::first()->status);
    }
}
