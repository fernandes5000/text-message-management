<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Http\Resources\SubscriberListResource;
use App\Models\SubscriberList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $lists = SubscriberList::where('organization_id', $org->id)
            ->withCount('subscribers')
            ->orderBy('name')
            ->get();

        return SubscriberListResource::collection($lists)->response();
    }

    public function store(StoreListRequest $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $list = SubscriberList::create(array_merge(
            $request->validated(),
            ['organization_id' => $org->id]
        ));

        $list->loadCount('subscribers');

        return (new SubscriberListResource($list))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): SubscriberListResource
    {
        $org = $request->user()->activeOrganization();
        $list = SubscriberList::where('organization_id', $org->id)
            ->withCount('subscribers')
            ->findOrFail($id);

        return new SubscriberListResource($list);
    }

    public function update(UpdateListRequest $request, int $id): SubscriberListResource
    {
        $org = $request->user()->activeOrganization();
        $list = SubscriberList::where('organization_id', $org->id)->findOrFail($id);

        $list->update($request->validated());
        $list->loadCount('subscribers');

        return new SubscriberListResource($list);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $org = $request->user()->activeOrganization();
        $list = SubscriberList::where('organization_id', $org->id)->findOrFail($id);

        $list->delete();

        return response()->json(null, 204);
    }
}
