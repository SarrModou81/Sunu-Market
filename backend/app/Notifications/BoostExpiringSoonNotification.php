<?php

namespace App\Notifications;

use App\Models\Boost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BoostExpiringSoonNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Boost $boost) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'boost_expiring_soon',
            'boost_id' => $this->boost->id,
            'product_id' => $this->boost->product_id,
            'ends_at' => $this->boost->ends_at,
        ];
    }
}
