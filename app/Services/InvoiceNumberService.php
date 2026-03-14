<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InvoiceNumberService
{
    /**
     * Generate a unique purchase invoice number.
     * Format: PO-YYYYMMDD-XXXX (e.g. PO-20260314-0001)
     */
    public function generatePurchaseNumber(): string
    {
        return $this->generate('PO', 'purchases');
    }

    /**
     * Generate a unique sale invoice number.
     * Format: INV-YYYYMMDD-XXXX (e.g. INV-20260314-0001)
     */
    public function generateSaleNumber(): string
    {
        return $this->generate('INV', 'sales');
    }

    private function generate(string $prefix, string $table): string
    {
        $date = Carbon::today()->format('Ymd');
        $like = "{$prefix}-{$date}-%";

        $last = DB::table($table)
            ->where('invoice_number', 'like', $like)
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last !== null ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
