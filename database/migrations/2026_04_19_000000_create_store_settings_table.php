<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('store_name');
            $table->string('store_tagline')->nullable();
            $table->text('store_address')->nullable();
            $table->string('store_city')->nullable();
            $table->string('store_province')->nullable();
            $table->string('store_postal_code', 20)->nullable();
            $table->string('store_phone', 30)->nullable();
            $table->string('store_email')->nullable();
            $table->string('store_logo')->nullable();
            $table->string('currency_code', 10)->default('IDR');
            $table->string('currency_symbol', 10)->default('Rp');
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->string('receipt_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
