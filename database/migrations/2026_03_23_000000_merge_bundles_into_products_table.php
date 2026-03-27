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
        $isMysql = DB::getDriverName() === 'mysql';

        // 1. Add 'bundle' to products.type enum (MySQL only; SQLite stores it as text)
        if ($isMysql) {
            DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('single', 'parent', 'variant', 'bundle') NOT NULL DEFAULT 'single'");
        }

        // 2. Add 'sale' and 'purchase' to stock_movements.type enum (MySQL only)
        if ($isMysql) {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'initial', 'sale', 'purchase') NOT NULL");
        }

        // 2. Migrate existing bundles -> products with type='bundle', build ID mapping
        $bundleIdToProductId = [];

        DB::table('bundles')->orderBy('id')->each(function (stdClass $bundle) use (&$bundleIdToProductId): void {
            $newProductId = DB::table('products')->insertGetId([
                'type'        => 'bundle',
                'parent_id'   => null,
                'sku'         => $bundle->sku,
                'name'        => $bundle->name,
                'description' => $bundle->description,
                'buy_price'   => 0,
                'sell_price'  => $bundle->sell_price,
                'stock'       => 0,
                'min_stock'   => 0,
                'is_active'   => $bundle->is_active,
                'created_at'  => $bundle->created_at,
                'updated_at'  => $bundle->updated_at,
            ]);

            $bundleIdToProductId[$bundle->id] = $newProductId;
        });

        // 3. Update bundle_items.bundle_id to point to new product IDs
        foreach ($bundleIdToProductId as $oldBundleId => $newProductId) {
            DB::table('bundle_items')
                ->where('bundle_id', $oldBundleId)
                ->update(['bundle_id' => $newProductId]);
        }

        // 4. Merge bundle_items.product_variant_id -> product_id
        //    (product_variant_id already remapped to products.id in previous migration)
        DB::statement('UPDATE bundle_items SET product_id = product_variant_id WHERE product_id IS NULL AND product_variant_id IS NOT NULL');

        // 5. Fix bundle_items FKs and drop product_variant_id column
        // For SQLite (test environment): disable legacy_alter_table check to allow dropping
        // a column that's still referenced in a FK definition.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA legacy_alter_table = 1');
        }

        Schema::table('bundle_items', function (Blueprint $table): void {
            $existingFks     = collect(Schema::getForeignKeys('bundle_items'));
            $existingIndexes = collect(Schema::getIndexes('bundle_items'));

            $hasPvFk     = $existingFks->contains(fn ($fk) => in_array('product_variant_id', (array) ($fk['local_columns'] ?? $fk['columns'] ?? [])));
            $hasBundleFk = $existingFks->contains(fn ($fk) => in_array('bundle_id', (array) ($fk['local_columns'] ?? $fk['columns'] ?? [])));
            $hasPvIndex  = $existingIndexes->contains(fn ($idx) => in_array('product_variant_id', (array) ($idx['columns'] ?? [])));

            if ($hasPvFk) {
                $table->dropForeign(['product_variant_id']);
            }

            if ($hasPvIndex) {
                $table->dropIndex(['product_variant_id']);
            }

            if ($hasBundleFk) {
                $table->dropForeign(['bundle_id']);
            }

            $table->dropColumn('product_variant_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA legacy_alter_table = 0');
        }

        // 6. Add new FK: bundle_items.bundle_id -> products.id
        Schema::table('bundle_items', function (Blueprint $table): void {
            $table->foreign('bundle_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // 7. Drop the now-replaced bundles table
        Schema::dropIfExists('bundles');
    }

    public function down(): void
    {
        // Recreate bundles table
        Schema::create('bundles', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('sku');
            $table->index('name');
        });

        // Re-add product_variant_id to bundle_items
        Schema::table('bundle_items', function (Blueprint $table): void {
            $table->dropForeign(['bundle_id']);
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            $table->foreign('bundle_id')->references('id')->on('bundles')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('products')->nullOnDelete();
            $table->index('product_variant_id');
        });

        // Restore products.type enum without 'bundle' (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('single', 'parent', 'variant') NOT NULL DEFAULT 'single'");

            // Restore stock_movements.type enum without 'sale'/'purchase'
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'initial') NOT NULL");
        }
    }
};
