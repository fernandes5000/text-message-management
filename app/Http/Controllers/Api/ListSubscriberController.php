<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListSubscriberController extends Controller
{
    public function store(Request $request, int $listId): JsonResponse
    {
        $request->validate(['subscriber_id' => ['required', 'integer', 'exists:subscribers,id']]);

        $org = $request->user()->activeOrganization();
        $list = SubscriberList::where('organization_id', $org->id)->findOrFail($listId);

        $list->subscribers()->syncWithoutDetaching([$request->subscriber_id]);

        return response()->json(null, 201);
    }

    public function destroy(Request $request, int $listId, int $subscriberId): JsonResponse
    {
        $org = $request->user()->activeOrganization();
        $list = SubscriberList::where('organization_id', $org->id)->findOrFail($listId);

        $list->subscribers()->detach($subscriberId);

        return response()->json(null, 204);
    }
}
