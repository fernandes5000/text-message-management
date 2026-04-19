<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKeywordRequest;
use App\Http\Requests\UpdateKeywordRequest;
use App\Http\Resources\KeywordResource;
use App\Models\Keyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KeywordController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $request->user()->active_organization_id;

        $query = Keyword::with('lists')
            ->where('organization_id', $orgId)
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where('name', 'like', $search);
        }

        return KeywordResource::collection($query->paginate(20));
    }

    public function store(StoreKeywordRequest $request): KeywordResource
    {
        $data = $request->validated();
        $listIds = $data['list_ids'] ?? [];
        unset($data['list_ids']);

        $data['organization_id'] = $request->user()->active_organization_id;
        $data['name'] = strtoupper($data['name']);

        $keyword = Keyword::create($data);

        if ($listIds) {
            $keyword->lists()->sync($listIds);
        }

        return new KeywordResource($keyword->load('lists'));
    }

    public function show(Keyword $keyword): KeywordResource
    {
        $this->authorizeOrg($keyword);
        return new KeywordResource($keyword->load('lists'));
    }

    public function update(UpdateKeywordRequest $request, Keyword $keyword): KeywordResource
    {
        $this->authorizeOrg($keyword);

        $data = $request->validated();
        $listIds = $data['list_ids'] ?? null;
        unset($data['list_ids']);

        if (isset($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }

        $keyword->update($data);

        if ($listIds !== null) {
            $keyword->lists()->sync($listIds);
        }

        return new KeywordResource($keyword->fresh()->load('lists'));
    }

    public function destroy(Keyword $keyword): JsonResponse
    {
        $this->authorizeOrg($keyword);
        $keyword->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    private function authorizeOrg(Keyword $keyword): void
    {
        abort_if(
            $keyword->organization_id !== request()->user()->active_organization_id,
            403
        );
    }
}
