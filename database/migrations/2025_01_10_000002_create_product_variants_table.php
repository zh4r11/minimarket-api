<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('sku');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
