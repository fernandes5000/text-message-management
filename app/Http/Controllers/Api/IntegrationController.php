<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IntegrationResource;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IntegrationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $integrations = Integration::where('organization_id', $request->user()->active_organization_id)
            ->orderBy('type')
            ->get();

        return IntegrationResource::collection($integrations);
    }

    public function connect(Request $request, Integration $integration): IntegrationResource
    {
        $this->authorizeOrg($request, $integration);

        $integration->update(['status' => 'connected']);

        return new IntegrationResource($integration);
    }

    public function disconnect(Request $request, Integration $integration): IntegrationResource
    {
        $this->authorizeOrg($request, $integration);

        $integration->update(['status' => 'disconnected']);

        return new IntegrationResource($integration);
    }

    private function authorizeOrg(Request $request, Integration $integration): void
    {
        if ($integration->organization_id !== $request->user()->active_organization_id) {
            abort(403);
        }
    }
}
