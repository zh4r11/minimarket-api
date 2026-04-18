<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->userRepository->paginate($filters, $perPage);
    }

    public function show(User $user): User
    {
        $user->load('roles');

        return $user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->select(['id', 'name'])->get();
    }

    public function assignRole(User $user, string $role): User
    {
        $user->assignRole($role);
        $user->load('roles');

        return $user;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function removeRole(User $user, string $role): array
    {
        if (! $user->hasRole($role)) {
            return ['success' => false, 'message' => 'User does not have this role.'];
        }

        $user->removeRole($role);
        $user->load('roles');

        return ['success' => true, 'message' => 'Role removed successfully.'];
    }
}
