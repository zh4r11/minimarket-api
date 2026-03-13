<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

final class UserController extends ApiController
{
    /**
     * List all users.
     *
     * Returns a paginated list of users with their assigned roles.
     *
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $users = User::query()
            ->with('roles')
            ->paginate($perPage);

        return $this->success(UserResource::collection($users)->toResponse($request)->getData(true));
    }

    /**
     * Show a user.
     *
     * Returns a single user with their assigned roles.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        return $this->success(new UserResource($user));
    }

    /**
     * List all available roles.
     *
     * Returns all roles defined in the system.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::query()->select(['id', 'name'])->get();

        return $this->success($roles);
    }

    /**
     * Assign a role to a user.
     *
     * Assigns the specified role to the given user.
     *
     * @bodyParam role string required The role name to assign. Example: admin
     */
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $user->assignRole($request->validated('role'));
        $user->load('roles');

        return $this->success(new UserResource($user), 'Role assigned successfully.');
    }

    /**
     * Remove a role from a user.
     *
     * Removes the specified role from the given user.
     *
     * @urlParam role string required The role name to remove. Example: staff
     */
    public function removeRole(User $user, string $role): JsonResponse
    {
        if (! $user->hasRole($role)) {
            return $this->error('User does not have this role.', 422);
        }

        $user->removeRole($role);
        $user->load('roles');

        return $this->success(new UserResource($user), 'Role removed successfully.');
    }
}
