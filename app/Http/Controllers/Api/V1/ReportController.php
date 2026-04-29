<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\StockReportCollection;
use App\Http\Resources\StockReportResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReportController extends ApiController
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * Stock report.
     *
     * Returns a paginated stock report for all products including total stock in, stock out, adjustment, current stock, and status.
     *
     * @queryParam search string Search by product name or SKU. Example: Sabun
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam type string Filter by product type (simple, variant, bundle). Example: simple
     * @queryParam status string Filter by stock status (low, empty, over). Example: low
     * @queryParam start_date date Filter stock movements from this date. Example: 2026-01-01
     * @queryParam end_date date Filter stock movements until this date. Example: 2026-04-30
     * @queryParam include_variants boolean Include variant products in the report. Example: true
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     */
    public function stock(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'           => 'nullable|string',
            'category_id'      => 'nullable|integer|exists:categories,id',
            'type'             => 'nullable|string|in:simple,variant,bundle',
            'status'           => 'nullable|string|in:low,empty,over',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'include_variants' => 'nullable|boolean',
            'per_page'         => 'nullable|integer|min:1|max:100',
            'page'             => 'nullable|integer|min:1',
        ]);

        $products = $this->reportService->stockReport($filters);

        return $this->success((new StockReportCollection($products))->toArray($request));
    }
}
