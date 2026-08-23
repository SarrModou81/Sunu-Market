<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductModeratedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product, private readonly string $decision) {}

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
            'type' => 'product_'.$this->decision,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'reason' => $this->product->rejection_reason,
        ];
    }
}
