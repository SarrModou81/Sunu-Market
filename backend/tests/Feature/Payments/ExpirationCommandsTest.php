<?php

namespace Tests\Feature\Payments;

use App\Models\Boost;
use App\Models\BoostPlan;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BoostExpiringSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpirationCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_boosts_command_expires_past_boosts_and_clears_product(): void
    {
        $product = Product::factory()->create(['boosted_until' => now()->subHour()]);
        $plan = BoostPlan::factory()->create();
        Boost::create([
            'product_id' => $product->id,
            'user_id' => $product->user_id,
            'boost_plan_id' => $plan->id,
            'status' => Boost::STATUS_ACTIVE,
            'starts_at' => now()->subDays(4),
            'ends_at' => now()->subHour(),
        ]);

        $this->artisan('boosts:expire')->assertExitCode(0);

        $this->assertSame(Boost::STATUS_EXPIRED, Boost::first()->status);
        $this->assertNull($product->fresh()->boosted_until);
    }

    public function test_expire_boosts_keeps_product_boosted_if_another_active_boost_remains(): void
    {
        $product = Product::factory()->create();
        $plan = BoostPlan::factory()->create();

        Boost::create([
            'product_id' => $product->id, 'user_id' => $product->user_id, 'boost_plan_id' => $plan->id,
            'status' => Boost::STATUS_ACTIVE, 'starts_at' => now()->subDays(4), 'ends_at' => now()->subHour(),
        ]);
        Boost::create([
            'product_id' => $product->id, 'user_id' => $product->user_id, 'boost_plan_id' => $plan->id,
            'status' => Boost::STATUS_ACTIVE, 'starts_at' => now(), 'ends_at' => now()->addDay(),
        ]);
        $product->forceFill(['boosted_until' => now()->addDay()])->save();

        $this->artisan('boosts:expire');

        $this->assertNotNull($product->fresh()->boosted_until);
    }

    public function test_notify_expiring_boosts_only_notifies_once(): void
    {
        Notification::fake();
        $product = Product::factory()->create();
        $plan = BoostPlan::factory()->create();
        $boost = Boost::create([
            'product_id' => $product->id, 'user_id' => $product->user_id, 'boost_plan_id' => $plan->id,
            'status' => Boost::STATUS_ACTIVE, 'starts_at' => now()->subDay(), 'ends_at' => now()->addHour(),
        ]);

        $this->artisan('boosts:notify-expiring');
        $this->artisan('boosts:notify-expiring');

        Notification::assertSentToTimes($boost->user, BoostExpiringSoonNotification::class, 1);
    }

    public function test_expire_subscriptions_reverts_seller_to_standard(): void
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create([
            'seller_type' => SellerProfile::TYPE_PRO,
            'pro_started_at' => now()->subMonth(),
            'pro_expires_at' => now()->subHour(),
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_code' => Subscription::PLAN_SELLER_PRO_MONTHLY,
            'price' => 2000,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subHour(),
        ]);

        $this->artisan('subscriptions:expire')->assertExitCode(0);

        $this->assertSame(Subscription::STATUS_EXPIRED, Subscription::first()->status);
        $this->assertSame('standard', $user->sellerProfile->fresh()->seller_type);
    }
}
