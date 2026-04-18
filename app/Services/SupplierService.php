<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->supplierRepository->paginate($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        /** @var Supplier */
        return $this->supplierRepository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        /** @var Supplier */
        return $this->supplierRepository->update($supplier, $data);
    }

    public function delete(Supplier $supplier): void
    {
        $this->supplierRepository->delete($supplier);
    }
}
