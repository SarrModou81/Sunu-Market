<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isBuyer = $request->user()?->id === $this->buyer_id;
        $otherUser = $isBuyer ? $this->seller : $this->buyer;

        return [
            'id' => $this->id,
            'product' => new ProductListResource($this->whenLoaded('product')),
            'other_user' => new SellerSummaryResource($otherUser),
            'is_blocked' => $this->isBlockedFor($request->user()),
            'last_message' => $this->whenLoaded('messages', fn () => $this->messages->last() ? new MessageResource($this->messages->last()) : null),
            // Calculé par le contrôleur (nombre de messages non lus par l'utilisateur courant).
            'unread_count' => $this->unread_count ?? 0,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
        ];
    }
}
