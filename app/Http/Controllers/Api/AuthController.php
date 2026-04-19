<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();
        $organization = $user->activeOrganization();
        $organizations = $user->organizations()->get();

        return response()->json([
            'user' => new UserResource($user),
            'organization' => $organization ? new OrganizationResource($organization) : null,
            'organizations' => OrganizationResource::collection($organizations),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $organization = $user->activeOrganization();
        $organizations = $user->organizations()->get();

        return response()->json([
            'user' => new UserResource($user),
            'organization' => $organization ? new OrganizationResource($organization) : null,
            'organizations' => OrganizationResource::collection($organizations),
        ]);
    }
}
