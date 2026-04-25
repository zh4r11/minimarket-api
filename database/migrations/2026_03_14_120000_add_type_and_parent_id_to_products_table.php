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
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->enum('type', ['single', 'parent', 'variant', 'bundle'])->default('single')->after('parent_id');

            $table->foreign('parent_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index('parent_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['parent_id', 'type']);
        });
    }
};
