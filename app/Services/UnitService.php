<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UnitService
{
    public function __construct(
        private readonly UnitRepositoryInterface $unitRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $this->unitRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Unit
    {
        /** @var Unit */
        return $this->unitRepository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit
    {
        /** @var Unit */
        return $this->unitRepository->update($unit, $data);
    }

    public function delete(Unit $unit): void
    {
        $this->unitRepository->delete($unit);
    }
}
