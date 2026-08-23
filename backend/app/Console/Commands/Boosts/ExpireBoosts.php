<?php

namespace App\Console\Commands\Boosts;

use App\Models\Boost;
use Illuminate\Console\Command;

class ExpireBoosts extends Command
{
    protected $signature = 'boosts:expire';

    protected $description = "Expire les boosts arrivés à échéance et retire l'effet de mise en avant des annonces concernées.";

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
