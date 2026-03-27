<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
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
     * Returns a paginated stock report for all products including total stock in, stock out, current stock, and status.
     */
    public function stock(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search'      => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'status'      => 'nullable|string|in:low,empty',
            'per_page'    => 'nullable|integer|min:1|max:100',
            'page'        => 'nullable|integer|min:1',
        ]);

        $products = $this->reportService->stockReport($filters);

        return $this->success(StockReportResource::collection($products)->toResponse($request)->getData(true));
    }
}
