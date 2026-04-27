<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundle_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('products')->nullOnDelete();
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('bundle_items', function (Blueprint $table): void {
            $table->dropForeign(['variant_id']);
            $table->dropIndex(['variant_id']);
            $table->dropColumn('variant_id');
        });
    }
};
