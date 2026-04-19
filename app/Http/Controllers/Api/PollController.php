<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePollRequest;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PollController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $request->user()->active_organization_id;

        $polls = Poll::where('organization_id', $orgId)
            ->withCount('responses')
            ->with('responses')
            ->latest()
            ->paginate(20);

        return PollResource::collection($polls);
    }

    public function store(StorePollRequest $request): JsonResponse
    {
        $poll = Poll::create([
            'organization_id' => $request->user()->active_organization_id,
            'question'        => $request->validated('question'),
            'options'         => $request->validated('options'),
        ]);

        $poll->load('responses');

        return response()->json(new PollResource($poll), 201);
    }

    public function show(Request $request, Poll $poll): PollResource
    {
        $this->authorizeOrg($request, $poll);

        $poll->load('responses.subscriber');

        return new PollResource($poll);
    }

    public function destroy(Request $request, Poll $poll): JsonResponse
    {
        $this->authorizeOrg($request, $poll);

        $poll->delete();

        return response()->json(null, 204);
    }

    private function authorizeOrg(Request $request, Poll $poll): void
    {
        if ($poll->organization_id !== $request->user()->active_organization_id) {
            abort(403);
        }
    }
}
