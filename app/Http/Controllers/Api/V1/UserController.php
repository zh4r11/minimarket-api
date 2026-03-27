<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * List all users.
     *
     * Returns a paginated list of users with their assigned roles.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ]);

        $users = $this->userService->list($filters);

        return $this->success(UserResource::collection($users)->toResponse($request)->getData(true));
    }

    /**
     * Show a user.
     *
     * Returns a single user with their assigned roles.
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->show($user);

        return $this->success(new UserResource($user));
    }

    /**
     * List all available roles.
     *
     * Returns all roles defined in the system.
     */
    public function roles(): JsonResponse
    {
        $roles = $this->userService->roles();

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
        $user = $this->userService->assignRole($user, $request->validated('role'));

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
        $result = $this->userService->removeRole($user, $role);

        if (! $result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success(new UserResource($user), $result['message']);
    }
}
