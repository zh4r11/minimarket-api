<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReportService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function stockReport(array $filters): LengthAwarePaginator
    {
        $perPage = min($filters['per_page'] ?? 15, 100);

        return $this->productRepository->stockReport($filters, $perPage);
    }
}
