<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\NewConversationMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $request->user()->active_organization_id;

        $query = Conversation::with('subscriber')
            ->where('organization_id', $orgId)
            ->orderByDesc('last_message_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('unread') && $request->boolean('unread')) {
            $query->where('unread', true);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->whereHas('subscriber', function ($q) use ($search): void {
                $q->where('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        return ConversationResource::collection($query->paginate(30));
    }

    public function show(Conversation $conversation): ConversationResource
    {
        $this->authorizeOrg($conversation);

        $conversation->load(['subscriber', 'messages']);
        $conversation->update(['unread' => false]);

        return new ConversationResource($conversation);
    }

    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeOrg($conversation);

        $request->validate(['body' => ['required', 'string', 'max:1600']]);

        $msg = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'body'            => $request->body,
            'direction'       => 'outbound',
            'user_id'         => $request->user()->id,
            'sent_at'         => now(),
        ]);

        $conversation->update([
            'last_message'    => $msg->body,
            'last_message_at' => $msg->sent_at,
            'status'          => 'open',
        ]);

        $msg->load('conversation.organization');
        broadcast(new NewConversationMessage($msg))->toOthers();

        return response()->json([
            'id'              => $msg->id,
            'conversation_id' => $msg->conversation_id,
            'body'            => $msg->body,
            'direction'       => $msg->direction,
            'user_id'         => $msg->user_id,
            'sent_at'         => $msg->sent_at->toISOString(),
        ], 201);
    }

    public function markDone(Conversation $conversation): ConversationResource
    {
        $this->authorizeOrg($conversation);
        $conversation->update(['status' => 'done', 'unread' => false]);
        return new ConversationResource($conversation->load('subscriber'));
    }

    public function markUnread(Conversation $conversation): ConversationResource
    {
        $this->authorizeOrg($conversation);
        $conversation->update(['unread' => true]);
        return new ConversationResource($conversation->load('subscriber'));
    }

    private function authorizeOrg(Conversation $conversation): void
    {
        abort_if(
            $conversation->organization_id !== request()->user()->active_organization_id,
            403
        );
    }
}
