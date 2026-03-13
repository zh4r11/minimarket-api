<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_photos', function (Blueprint $table): void {
            $table->boolean('is_main')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('product_photos', function (Blueprint $table): void {
            $table->dropColumn('is_main');
        });
    }
};
