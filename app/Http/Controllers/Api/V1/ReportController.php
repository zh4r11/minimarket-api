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
     *
     * @queryParam search string Search by product name or SKU. Example: Indomie
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam status string Filter by stock status (low, empty). Example: low
     * @queryParam per_page integer Number of items per page (max 100). Defaults to 15. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function stock(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $products = Product::query()
            ->with(['category', 'unit'])
            ->select([
                'products.*',
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type IN ('in', 'initial')) as stock_in"),
                DB::raw("(SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type = 'out') as stock_out"),
            ])
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$request->search}%")
                ->orWhere('products.sku', 'like', "%{$request->search}%")))
            ->when($request->category_id, fn ($q) => $q->where('products.category_id', $request->category_id))
            ->when($request->status === 'low', fn ($q) => $q->whereRaw('products.stock <= products.min_stock AND products.stock > 0'))
            ->when($request->status === 'empty', fn ($q) => $q->where('products.stock', 0))
            ->paginate($perPage);

        return $this->success(StockReportResource::collection($products)->toResponse($request)->getData(true));
    }
}
