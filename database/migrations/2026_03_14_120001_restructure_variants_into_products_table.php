<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove variant_id from stock_movements and purchase_items (added in previous session)
        if (Schema::hasColumn('stock_movements', 'variant_id')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->dropForeign(['variant_id']);
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            });
        }

        if (Schema::hasColumn('purchase_items', 'variant_id')) {
            Schema::table('purchase_items', function (Blueprint $table): void {
                $table->dropForeign(['variant_id']);
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            });
        }

        // 2. Add product_id column to product_variant_values (nullable for migration)
        if (Schema::hasTable('product_variant_values') && ! Schema::hasColumn('product_variant_values', 'product_id')) {
            Schema::table('product_variant_values', function (Blueprint $table): void {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
            });
        }

        // 3. Migrate each product_variant into the products table and build mapping
        //    Only if product_variants table still exists
        $variantMapping = []; // old product_variant.id => new product.id (on products table)

        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->orderBy('id')->each(function (stdClass $variant) use (&$variantMapping): void {
                // Skip if already migrated (sku already exists in products as variant)
                $existing = DB::table('products')
                    ->where('sku', $variant->sku)
                    ->where('type', 'variant')
                    ->value('id');

                if ($existing !== null) {
                    $variantMapping[$variant->id] = $existing;
                    return;
                }

                $parent = DB::table('products')->find($variant->product_id);

                $newProductId = DB::table('products')->insertGetId([
                    'parent_id'   => $variant->product_id,
                    'type'        => 'variant',
                    'category_id' => $parent?->category_id,
                    'brand_id'    => $parent?->brand_id ?? null,
                    'unit_id'     => $parent?->unit_id,
                    'sku'         => $variant->sku,
                    'name'        => $parent?->name ?? $variant->sku,
                    'description' => null,
                    'buy_price'   => $variant->buy_price,
                    'sell_price'  => $variant->sell_price,
                    'stock'       => $variant->stock,
                    'min_stock'   => $variant->min_stock,
                    'is_active'   => $variant->is_active,
                    'created_at'  => $variant->created_at,
                    'updated_at'  => $variant->updated_at,
                ]);

                $variantMapping[$variant->id] = $newProductId;

                // Remap pivot rows to new product_id
                if (Schema::hasColumn('product_variant_values', 'product_id')) {
                    DB::table('product_variant_values')
                        ->where('variant_id', $variant->id)
                        ->update(['product_id' => $newProductId]);
                }
            });

            // 4. Update bundle_items.product_variant_id to point to new product IDs
            foreach ($variantMapping as $oldVariantId => $newProductId) {
                DB::table('bundle_items')
                    ->where('product_variant_id', $oldVariantId)
                    ->update(['product_variant_id' => $newProductId]);
            }
        }

        // 5. Mark parent products as type='parent'
        $parentIds = DB::table('products')
            ->where('type', 'variant')
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique();

        if ($parentIds->isNotEmpty()) {
            DB::table('products')
                ->whereIn('id', $parentIds)
                ->where('type', 'single')
                ->update(['type' => 'parent']);
        }

        // 6. Restructure product_variant_values: drop old FK/unique/column, add new FK
        if (Schema::hasTable('product_variant_values') && Schema::hasColumn('product_variant_values', 'variant_id')) {
            Schema::table('product_variant_values', function (Blueprint $table): void {
                $table->dropForeign(['variant_id']);
                $table->dropUnique('product_variant_values_variant_id_attribute_value_id_unique');
                $table->dropColumn('variant_id');
            });
        }

        if (Schema::hasTable('product_variant_values') && Schema::hasColumn('product_variant_values', 'product_id')) {
            $pvvFks     = collect(Schema::getForeignKeys('product_variant_values'))->pluck('name');
            $pvvIndexes = collect(Schema::getIndexes('product_variant_values'))->pluck('name');

            $hasFkOnProductId     = $pvvFks->contains(fn ($n) => str_contains($n, 'product_id'));
            $hasUniqueOnProductId = $pvvIndexes->contains(fn ($n) => str_contains($n, 'product_id'));

            if (! $hasFkOnProductId || ! $hasUniqueOnProductId) {
                Schema::table('product_variant_values', function (Blueprint $table) use ($hasFkOnProductId, $hasUniqueOnProductId): void {
                    $table->unsignedBigInteger('product_id')->nullable(false)->change();

                    if (! $hasFkOnProductId) {
                        $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                    }

                    if (! $hasUniqueOnProductId) {
                        $table->unique(['product_id', 'attribute_value_id']);
                    }
                });
            }
        }

        // 7. Drop bundle_items FK to product_variants before dropping that table
        if (Schema::hasTable('product_variants')) {
            $bundleFks = collect(Schema::getForeignKeys('bundle_items'))->pluck('name');

            if ($bundleFks->contains(fn ($n) => str_contains($n, 'product_variant_id'))) {
                Schema::table('bundle_items', function (Blueprint $table): void {
                    $table->dropForeign(['product_variant_id']);
                });
            }

            $bundleIndexes = collect(Schema::getIndexes('bundle_items'))->pluck('name');
            if ($bundleIndexes->contains(fn ($n) => str_contains($n, 'product_variant_id'))) {
                Schema::table('bundle_items', function (Blueprint $table): void {
                    $table->dropIndex(['product_variant_id']);
                });
            }

            // 8. Drop the now-unused product_variants table
            Schema::dropIfExists('product_variants');
        }

        // 9. Re-add bundle_items.product_variant_id FK pointing to products (if not already done)
        $bundleFksAfter = collect(Schema::getForeignKeys('bundle_items'))->pluck('name');
        if (! $bundleFksAfter->contains(fn ($n) => str_contains($n, 'product_variant_id'))) {
            Schema::table('bundle_items', function (Blueprint $table): void {
                $table->foreign('product_variant_id')->references('id')->on('products')->nullOnDelete();
                $table->index('product_variant_id');
            });
        }
    }

    public function down(): void
    {
        // Recreate product_variants table structure (data is lost)
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
        });

        // Restore product_variant_values pivot
        Schema::table('product_variant_values', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropUnique(['product_id', 'attribute_value_id']);
            $table->dropColumn('product_id');

            $table->unsignedBigInteger('variant_id')->nullable()->after('id');
            $table->foreign('variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->unique(['variant_id', 'attribute_value_id']);
        });

        // Restore variant_id to stock_movements and purchase_items
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            $table->index('variant_id');
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
            $table->index('variant_id');
        });

        // Remove variant-related modifications from products
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['parent_id', 'type']);
        });
    }
};
