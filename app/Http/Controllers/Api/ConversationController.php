<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StartConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Product;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversationService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where('buyer_id', $user->id)->orWhere('seller_id', $user->id)
            ->with(['product', 'buyer.profile', 'seller.profile'])
            ->orderByDesc('last_message_at')
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        $conversations->getCollection()->transform(function (Conversation $conversation) use ($user) {
            $conversation->unread_count = $this->conversationService->unreadCountFor($user, $conversation);
            $lastMessage = $conversation->messages()->latest('id')->first();
            $conversation->setRelation('messages', $lastMessage ? collect([$lastMessage]) : collect());

            return $conversation;
        });

        return response()->json([
            'data' => ConversationResource::collection($conversations->items()),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'last_page' => $conversations->lastPage(),
            ],
        ]);
    }

    public function store(StartConversationRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));

        if ($product->user_id === $request->user()->id) {
            throw ValidationException::withMessages(['product_id' => 'Vous ne pouvez pas démarrer une conversation avec vous-même.']);
        }

        $conversation = $this->conversationService->startConversation($request->user(), $product, $request->string('message'));

        return response()->json(['data' => new ConversationResource($conversation)], 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->conversationService->markRead($request->user(), $conversation);

        $messages = $conversation->messages()->orderBy('created_at')->paginate(50);

        return response()->json([
            'data' => new ConversationResource($conversation->load(['product', 'buyer.profile', 'seller.profile'])),
            'messages' => MessageResource::collection($messages->items()),
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $message = $this->conversationService->sendMessage(
            $request->user(),
            $conversation,
            $request->input('body'),
            $request->file('attachment'),
        );

        return response()->json(['data' => new MessageResource($message)], 201);
    }

    public function toggleBlock(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation = $this->conversationService->toggleBlock($request->user(), $conversation);

        return response()->json(['data' => new ConversationResource($conversation)]);
    }
}
