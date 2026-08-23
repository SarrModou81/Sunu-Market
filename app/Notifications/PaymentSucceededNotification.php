<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

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
            'type' => 'payment_succeeded',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'payable_type' => $this->payment->payable_type,
        ];
    }
}
