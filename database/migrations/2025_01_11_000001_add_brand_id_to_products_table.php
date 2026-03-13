<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->nullOnDelete();
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['brand_id']);
            $table->dropIndex(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
