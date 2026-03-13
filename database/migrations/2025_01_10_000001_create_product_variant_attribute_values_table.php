<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_id')->constrained('product_variant_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
            $table->index('attribute_id');
            $table->unique(['attribute_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};
