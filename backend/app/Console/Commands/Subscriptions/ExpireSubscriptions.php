<?php

namespace App\Console\Commands\Subscriptions;

use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:expire')]
#[Description('Expire les abonnements Vendeur Pro arrivés à échéance et repasse le vendeur en compte Standard.')]
class ExpireSubscriptions extends Command
{
    public function handle(): int
    {
        $expired = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', now())
            ->with('user.sellerProfile')
            ->get();

        foreach ($expired as $subscription) {
            $subscription->forceFill(['status' => Subscription::STATUS_EXPIRED])->save();

            $sellerProfile = $subscription->user->sellerProfile;
            if ($sellerProfile && $sellerProfile->pro_expires_at?->lessThanOrEqualTo(now())) {
                $sellerProfile->update(['seller_type' => 'standard']);
            }
        }

        $this->info("{$expired->count()} abonnement(s) Pro expiré(s).");

        return self::SUCCESS;
    }
}
