<?php

namespace App\Console\Commands\Boosts;

use App\Models\Boost;
use App\Notifications\BoostExpiringSoonNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('boosts:notify-expiring')]
#[Description('Notifie les vendeurs dont un Boost actif se termine dans moins de 2 heures.')]
class NotifyExpiringBoosts extends Command
{
    private const WARNING_WINDOW_HOURS = 2;

    public function handle(): int
    {
        $boosts = Boost::query()
            ->where('status', Boost::STATUS_ACTIVE)
            ->whereNull('expiring_notified_at')
            ->where('ends_at', '<=', now()->addHours(self::WARNING_WINDOW_HOURS))
            ->where('ends_at', '>', now())
            ->get();

        foreach ($boosts as $boost) {
            $boost->user->notify(new BoostExpiringSoonNotification($boost));
            $boost->forceFill(['expiring_notified_at' => now()])->save();
        }

        $this->info("{$boosts->count()} vendeur(s) notifié(s) de la fin prochaine de leur Boost.");

        return self::SUCCESS;
    }
}
