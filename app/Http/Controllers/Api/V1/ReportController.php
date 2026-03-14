<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\StockReportResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportController extends ApiController
{
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

        $perPage = min($filters['per_page'] ?? 15, 100);
        $search  = $filters['search'] ?? null;
        $status  = $filters['status'] ?? null;

        $products = Product::query()
            ->with(['category', 'unit'])
            ->select([
                'products.*',
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type IN ('in', 'initial')) as stock_in"),
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type = 'out') as stock_out"),
            ])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('products.category_id', $filters['category_id']))
            ->when($status === 'low', fn ($q) => $q->whereRaw('products.stock <= products.min_stock AND products.stock > 0'))
            ->when($status === 'empty', fn ($q) => $q->where('products.stock', 0))
            ->paginate($perPage);

        return $this->success(StockReportResource::collection($products)->toResponse($request)->getData(true));
    }
}
