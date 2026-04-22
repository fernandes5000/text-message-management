<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSubscribersRequest;
use App\Http\Requests\StoreSubscriberRequest;
use App\Http\Requests\UpdateSubscriberRequest;
use App\Http\Resources\SubscriberResource;
use App\Models\Subscriber;
use App\Services\SubscriberImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriberController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $org = $request->user()->activeOrganization();

        $query = Subscriber::with('lists')
            ->where('organization_id', $org->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%' . $request->search . '%';
                $q->where(fn ($q) => $q
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest();

        return SubscriberResource::collection($query->paginate(25));
    }

    public function store(StoreSubscriberRequest $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $subscriber = Subscriber::create(array_merge(
            $request->validated(),
            ['organization_id' => $org->id]
        ));

        return (new SubscriberResource($subscriber))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): SubscriberResource
    {
        $org = $request->user()->activeOrganization();
        $subscriber = Subscriber::where('organization_id', $org->id)->findOrFail($id);

        return new SubscriberResource($subscriber);
    }

    public function update(UpdateSubscriberRequest $request, int $id): SubscriberResource
    {
        $org = $request->user()->activeOrganization();
        $subscriber = Subscriber::where('organization_id', $org->id)->findOrFail($id);

        $subscriber->update($request->validated());

        return new SubscriberResource($subscriber);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $org = $request->user()->activeOrganization();
        $subscriber = Subscriber::where('organization_id', $org->id)->findOrFail($id);

        $subscriber->delete();

        return response()->json(null, 204);
    }

    public function import(ImportSubscribersRequest $request, SubscriberImportService $service): JsonResponse
    {
        $org = $request->user()->activeOrganization();
        $stats = $service->import($request->file('file'), $org);

        return response()->json($stats);
    }
}
