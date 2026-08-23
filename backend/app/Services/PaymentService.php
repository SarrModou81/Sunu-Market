<?php

namespace App\Services;

use App\Models\Boost;
use App\Models\BoostPlan;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BoostActivatedNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Payments\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private readonly PaymentGatewayFactory $gatewayFactory) {}

    public function createBoostPayment(User $user, Product $product, BoostPlan $plan, string $provider): Payment
    {
        return DB::transaction(function () use ($user, $product, $plan, $provider) {
            $boost = Boost::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'boost_plan_id' => $plan->id,
                'status' => Boost::STATUS_PENDING,
            ]);

            $payment = $this->createPayment($user, $boost, $plan->price, $provider);
            $this->initiateCheckout($payment, $provider);

            return $payment->fresh(['payable']);
        });
    }

    public function createSubscriptionPayment(User $user, string $provider): Payment
    {
        return DB::transaction(function () use ($user, $provider) {
            $price = (int) config('payments.seller_pro_monthly_price');

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_code' => Subscription::PLAN_SELLER_PRO_MONTHLY,
                'price' => $price,
                'status' => Subscription::STATUS_PENDING,
            ]);

            $payment = $this->createPayment($user, $subscription, $price, $provider);
            $this->initiateCheckout($payment, $provider);

            return $payment->fresh(['payable']);
        });
    }

    private function createPayment(User $user, Boost|Subscription $payable, int $amount, string $provider): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->id,
            'provider' => $provider,
            'amount' => $amount,
            'currency' => config('payments.currency'),
            'reference' => 'SM-'.Str::upper(Str::random(12)),
            'payer_phone' => $user->phone,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    private function initiateCheckout(Payment $payment, string $provider): void
    {
        $gateway = $this->gatewayFactory->make($provider);
        $result = $gateway->initiateCheckout($payment);

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => PaymentTransaction::TYPE_CHECKOUT_INITIATED,
            'provider_reference' => $result->providerReference,
            'status' => 'initiated',
            'raw_payload' => $result->raw,
            'occurred_at' => now(),
        ]);

        $payment->forceFill(['meta' => ['checkout_url' => $result->checkoutUrl]])->save();
    }

    /**
     * Traite un webhook déjà authentifié (signature vérifiée par l'appelant) pour le
     * fournisseur donné. Idempotent : un webhook rejoué pour un paiement déjà confirmé
     * n'active rien une seconde fois.
     */
    public function handleVerifiedWebhook(string $provider, WebhookEvent $event): void
    {
        $payment = $this->findPaymentByProviderReference($event->providerReference);

        if (! $payment) {
            Log::warning("Webhook {$provider} reçu pour une référence inconnue: {$event->providerReference}");

            return;
        }

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => PaymentTransaction::TYPE_WEBHOOK,
            'provider_reference' => $event->providerReference,
            'status' => $event->succeeded ? 'succeeded' : 'failed',
            'raw_payload' => $event->raw,
            'signature_valid' => 'true',
            'occurred_at' => now(),
        ]);

        // Idempotence : un paiement déjà confirmé ne doit jamais être ré-activé.
        if ($payment->status !== Payment::STATUS_PENDING) {
            return;
        }

        if ($event->succeeded) {
            $this->markPaid($payment);
        } else {
            $this->markFailed($payment);
        }
    }

    private function markPaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->forceFill(['status' => Payment::STATUS_PAID, 'paid_at' => now()])->save();

            $payable = $payment->payable;

            if ($payable instanceof Boost) {
                $this->activateBoost($payable);
            } elseif ($payable instanceof Subscription) {
                $this->activateSubscription($payable);
            }

            $payment->user->notify(new PaymentSucceededNotification($payment));
        });
    }

    private function activateBoost(Boost $boost): void
    {
        $plan = $boost->boostPlan;
        $endsAt = now()->addHours($plan->duration_hours);

        $boost->forceFill([
            'status' => Boost::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $endsAt,
        ])->save();

        $boost->product->forceFill(['boosted_until' => $endsAt])->save();

        $boost->user->notify(new BoostActivatedNotification($boost));
    }

    private function activateSubscription(Subscription $subscription): void
    {
        $endsAt = now()->addMonth();

        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $endsAt,
        ])->save();

        $subscription->user->sellerProfile()->update([
            'seller_type' => 'pro',
            'pro_started_at' => now(),
            'pro_expires_at' => $endsAt,
        ]);
    }

    private function markFailed(Payment $payment): void
    {
        $payment->forceFill(['status' => Payment::STATUS_FAILED])->save();

        $payable = $payment->payable;
        if ($payable instanceof Boost) {
            $payable->forceFill(['status' => Boost::STATUS_CANCELLED])->save();
        } elseif ($payable instanceof Subscription) {
            $payable->forceFill(['status' => Subscription::STATUS_CANCELLED])->save();
        }
    }

    private function findPaymentByProviderReference(string $providerReference): ?Payment
    {
        if (blank($providerReference)) {
            return null;
        }

        $transaction = PaymentTransaction::where('provider_reference', $providerReference)
            ->latest('id')
            ->first();

        return $transaction?->payment;
    }
}
