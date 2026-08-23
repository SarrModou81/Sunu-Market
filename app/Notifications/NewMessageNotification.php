<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Message $message) {}

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
            'type' => 'new_message',
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'preview' => str($this->message->body ?? 'Photo')->limit(120)->toString(),
        ];
    }
}
