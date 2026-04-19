<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()->organizations()->get();

        return response()->json([
            'organizations' => OrganizationResource::collection($organizations),
        ]);
    }

    public function switch(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        if (! $user->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $user->update(['active_organization_id' => $organization->id]);

        return response()->json([
            'user' => new UserResource($user->fresh()),
            'organization' => new OrganizationResource($organization),
            'organizations' => OrganizationResource::collection($user->organizations()->get()),
        ]);
    }
}
