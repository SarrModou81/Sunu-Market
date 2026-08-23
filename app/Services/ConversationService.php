<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(private readonly ImageService $imageService) {}

    public function startConversation(User $buyer, Product $product, string $body): Conversation
    {
        return DB::transaction(function () use ($buyer, $product, $body) {
            $conversation = Conversation::firstOrCreate([
                'product_id' => $product->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $product->user_id,
            ]);

            $this->sendMessage($buyer, $conversation, $body);

            return $conversation->fresh(['product', 'buyer', 'seller']);
        });
    }

    public function sendMessage(User $sender, Conversation $conversation, ?string $body, ?UploadedFile $attachment = null): Message
    {
        $attachmentPath = $attachment
            ? $this->imageService->storeCompressed($attachment, 'messages/'.$conversation->id)
            : null;

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
            'attachment_path' => $attachmentPath,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        $recipient = $sender->id === $conversation->buyer_id ? $conversation->seller : $conversation->buyer;
        $recipient->notify(new NewMessageNotification($message));

        return $message;
    }

    public function markRead(User $user, Conversation $conversation): void
    {
        $field = $user->id === $conversation->buyer_id ? 'buyer_last_read_at' : 'seller_last_read_at';
        $conversation->forceFill([$field => now()])->save();
    }

    public function toggleBlock(User $user, Conversation $conversation): Conversation
    {
        $field = $user->id === $conversation->buyer_id ? 'blocked_by_buyer' : 'blocked_by_seller';
        $conversation->forceFill([$field => ! $conversation->{$field}])->save();

        return $conversation;
    }

    public function unreadCountFor(User $user, Conversation $conversation): int
    {
        $lastReadAt = $user->id === $conversation->buyer_id
            ? $conversation->buyer_last_read_at
            : $conversation->seller_last_read_at;

        return $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();
    }
}
