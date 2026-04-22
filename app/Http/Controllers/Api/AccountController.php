<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
            'user'          => new UserResource($user->fresh()),
            'organization'  => new OrganizationResource($organization),
            'organizations' => OrganizationResource::collection($user->organizations()->get()),
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        return response()->json(new OrganizationResource($org));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'default_number' => ['nullable', 'string', 'max:20'],
        ]);

        $org->update($data);

        return response()->json(new OrganizationResource($org->fresh()));
    }

    public function members(Request $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $members = $org->users()->get()->map(fn (User $u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->pivot->role,
        ]);

        return response()->json($members);
    }

    public function inviteMember(Request $request): JsonResponse
    {
        $org = $request->user()->activeOrganization();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'  => ['required', Rule::in(['admin', 'member'])],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make('password'),
        ]);

        $org->users()->attach($user->id, ['role' => $data['role']]);
        $user->update(['active_organization_id' => $org->id]);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $data['role'],
        ], 201);
    }

    public function removeMember(Request $request, User $user): JsonResponse
    {
        $org      = $request->user()->activeOrganization();
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            abort(422, 'You cannot remove yourself from the organization.');
        }

        if (! $org->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'Member not found in this organization.');
        }

        $org->users()->detach($user->id);

        return response()->json(null, 204);
    }
}
