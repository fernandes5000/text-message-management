<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Jobs\SendMessageJob;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $request->user()->active_organization_id;

        $query = Message::with('lists')
            ->where('organization_id', $orgId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                  ->orWhere('body', 'like', $search);
            });
        }

        return MessageResource::collection($query->paginate(20));
    }

    public function store(StoreMessageRequest $request): MessageResource
    {
        $data = $request->validated();
        $listIds = $data['list_ids'] ?? [];
        unset($data['list_ids']);

        $data['organization_id'] = $request->user()->active_organization_id;
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft';

        $message = Message::create($data);

        if ($listIds) {
            $message->lists()->sync($listIds);
        }

        if ($request->input('send_type') === 'now') {
            $this->dispatchSend($message);
        } elseif ($request->input('send_type') === 'scheduled') {
            $message->update(['status' => 'scheduled']);
            SendMessageJob::dispatch($message)->delay($message->scheduled_at);
        }

        return new MessageResource($message->load('lists'));
    }

    public function show(Message $message): MessageResource
    {
        $this->authorizeOrg($message);
        return new MessageResource($message->load('lists', 'creator'));
    }

    public function update(UpdateMessageRequest $request, Message $message): MessageResource
    {
        $this->authorizeOrg($message);

        $data = $request->validated();
        $listIds = $data['list_ids'] ?? null;
        unset($data['list_ids']);

        $message->update($data);

        if ($listIds !== null) {
            $message->lists()->sync($listIds);
        }

        return new MessageResource($message->load('lists'));
    }

    public function destroy(Message $message): JsonResponse
    {
        $this->authorizeOrg($message);
        $message->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    public function send(Message $message): MessageResource
    {
        $this->authorizeOrg($message);
        $this->dispatchSend($message);
        return new MessageResource($message->fresh()->load('lists'));
    }

    private function dispatchSend(Message $message): void
    {
        $message->update(['status' => 'sending']);
        SendMessageJob::dispatch($message);
    }

    private function authorizeOrg(Message $message): void
    {
        abort_if(
            $message->organization_id !== request()->user()->active_organization_id,
            403
        );
    }
}
