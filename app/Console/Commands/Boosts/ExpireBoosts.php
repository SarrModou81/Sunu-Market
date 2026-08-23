<?php

namespace App\Console\Commands\Boosts;

use App\Models\Boost;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('boosts:expire')]
#[Description("Expire les boosts arrivés à échéance et retire l'effet de mise en avant des annonces concernées.")]
class ExpireBoosts extends Command
{
    public function handle(): int
    {
        $expired = Boost::query()
            ->where('status', Boost::STATUS_ACTIVE)
            ->where('ends_at', '<=', now())
            ->with('product')
            ->get();

        foreach ($expired as $boost) {
            $boost->forceFill(['status' => Boost::STATUS_EXPIRED])->save();

            $product = $boost->product;
            $stillBoosted = $product->boosts()
                ->where('status', Boost::STATUS_ACTIVE)
                ->where('ends_at', '>', now())
                ->exists();

            if (! $stillBoosted) {
                $product->forceFill(['boosted_until' => null])->save();
            }
        }

        $this->info("{$expired->count()} boost(s) expiré(s).");

        return self::SUCCESS;
    }
}
